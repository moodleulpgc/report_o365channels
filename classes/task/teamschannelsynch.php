<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package report_o365channels
 * @author Enrique castro @ULPGC
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright (C) 2021 onwards Enrique Castro 
 */

namespace report_o365channels\task;

/**
 * Create any needed groups in Microsoft 365.
 */
class teamschannelsynch extends \core\task\scheduled_task {
    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('task_teamschannelsynch', 'report_o365channels');
    }

    /**
     * Do the job.
     */
    public function execute() {
        global $DB;

        if (\local_o365\utils::is_configured() !== true) {
            return false;
        }

        if (\local_o365\feature\usergroups\utils::is_enabled() !== true) {
            mtrace('Groups not enabled, skipping...');
            return true;
        }

        $httpclient = new \local_o365\httpclient();
        $clientdata = \local_o365\oauth2\clientdata::instance_from_oidc();

        $unifiedresource = \local_o365\rest\unified::get_tokenresource();
        $unifiedtoken = \local_o365\utils::get_app_or_system_token($unifiedresource, $clientdata, $httpclient);
        if (empty($unifiedtoken)) {
            mtrace('Could not get graph API token.');
            return true;
        }
        $graphclient = new \local_o365\rest\unified($unifiedtoken, $httpclient);

        $coursegroups = new \local_o365\feature\usergroups\coursegroups($graphclient, $DB, true);
        
        $lastrun = $this->get_last_run_time();  
        if($syncall = get_config('report_o365channels', 'syncall')) {
            $lastrun = 0;
        }
        $changed = '';
       
        // Synchonize course teams 
        list($insql, $params) = $DB->get_in_or_equal(['course','courseteam']);

        if($lastrun) {
            mtrace("... Syncing courses updated from $lastrun");
            $changed = " AND ( 
                            EXISTS (SELECT 1 
                            FROM {logstore_standard_log} l1 
                            WHERE l1.courseid = c.id AND l1.target = 'user_enrolment' AND l1.timecreated >= ?
                            )
                            OR EXISTS (SELECT 1 
                            FROM {logstore_standard_log} l2 
                            WHERE l2.courseid = c.id AND l2.target = 'group_member' AND l2.timecreated >= ?
                            ) 
                            )";
            $params[] = $lastrun;
            $params[] = $lastrun;
        }
        
        $sql = "SELECT o.id, o.objectid, o.moodleid AS courseid
                  FROM {local_o365_objects} o 
                  JOIN {course} c ON c.id = o.moodleid
                 WHERE o.type = 'group' AND o.subtype $insql $changed
                 GROUP BY o.objectid, o.moodleid";

        $courses = $DB->get_recordset_sql($sql, $params);
        if($courses->valid()) {
            foreach($courses as $course) {
                try {
                    $url = $graphclient->get_group_urls($course->objectid);
                    $coursegroups->resync_group_membership($course->courseid, $course->objectid);
                    
                    // Teams / Group users updated, now do channels if any
                    // Synchonize teams channels         
                    $sql = "SELECT o.id, o.objectid AS channelobjectid, o.moodleid AS groupid, c.id AS courseid, t.objectid AS teamobjectid
                            FROM {local_o365_objects} o 
                            JOIN {groups} g ON g.id = o.moodleid AND o.type = 'group' AND o.subtype = 'teamchannel'
                            JOIN {course} c ON c.id = g.courseid
                            JOIN {local_o365_objects} t ON t.moodleid = c.id AND t.type = 'group' AND t.subtype = 'courseteam'
                            WHERE c.id = ? AND o.type = 'group' AND o.subtype = 'teamchannel' 
                                AND EXISTS (SELECT 1 
                                FROM {logstore_standard_log} l 
                                WHERE l.courseid = c.id AND l.target = 'group_member' AND l.objectid = g.id AND l.timecreated >= ?) 
                            ";
                    $channels = $DB->get_recordset_sql($sql, [$course->courseid, $lastrun]);
                    if($channels->valid()) {
                        foreach($channels as $coursechanel) {
                            $channel = $graphclient->get_channel($coursechanel->teamobjectid, $coursechanel->channelobjectid); 
                            if(!empty($channel['id'])) {
                                $coursegroups->resync_channel_membership($coursechanel->courseid, $coursechanel->groupid, $coursechanel->channelobjectid, $course->teamobjectid); 
                            } else {
                                // Do nothing.
                                mtrace("    ... Teams channel '{$coursechanel->channelobjectid}' doesn't exist for course #" . $coursechanel->courseid);
                            }
                        }
                        $channels->close();
                    }
                } catch (\Exception $e) {
                    // Do nothing.
                    mtrace("    ... Group/Teams doesn't exist.  " . $e->getMessage());
                }                
            }
            $courses->close();
        } else {
            mtrace("    NO courses updated from last run.");
        }
        
        /*
        // Synchonize teams channels         
        $sql = "SELECT o.id, o.objectid AS channelobjectid, o.moodleid AS groupid, c.id AS courseid, t.objectid AS teamobjectid
                  FROM {local_o365_objects} o 
                  JOIN {groups} g ON g.id = o.moodleid AND o.type = 'group' AND o.subtype = 'teamchannel'
                  JOIN {course} c ON c.id = g.courseid
                  JOIN {local_o365_objects} t ON t.moodleid = c.id AND t.type = 'group' AND t.subtype = 'courseteam'
                 WHERE o.type = 'group' AND o.subtype = 'teamchannel' ";
        $courses = $DB->get_recordset_sql($sql, $params);
        if($courses->valid()) {
            foreach($courses as $course) {
                $channel = $graphclient->get_channel($course->teamobjectid, $course->channelobjectid); 
                if(!empty($channel['id'])) {
                    $coursegroups->resync_channel_membership($course->courseid, $course->groupid, $course->channelobjectid, $course->teamobjectid); 
                } else {
                    // Do nothing.
                    mtrace("    ... Teams channel '{$course->channelobjectid}' doesn't exist for course #" . $course->courseid);
                }
            }
            $courses->close();
        }
        */
        
        $changed = '';
        $params = [];
        if($lastrun) {
            $changed = "AND EXISTS (SELECT 1 
                        FROM {logstore_standard_log} l 
                        WHERE l.courseid = c.id AND l.target = 'group_member' AND l.objectid = g.id AND l.timecreated >= ?) ";
            $params = [$lastrun];
        }
        // Synchonize usergroups
        $sql = "SELECT o.id, o.objectid, o.moodleid AS groupid, c.id AS courseid
                  FROM {local_o365_objects} o 
                  JOIN {groups} g ON g.id = o.moodleid AND o.type = 'group' AND o.subtype = 'usergroup'
                  JOIN {course} c ON c.id = g.courseid
                 WHERE o.type = 'group' AND o.subtype = 'usergroup' $changed  
                 ";
        $courses = $DB->get_recordset_sql($sql, $params);
        if($courses->valid()) {
            foreach($courses as $course) {
                $coursegroups->resync_group_membership($course->courseid, $course->objectid); 
            }
            $courses->close();
        } else {
            mtrace("    NO groups with usergoups updated from last run.");
        }
    }
}
