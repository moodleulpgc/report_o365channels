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
 * Script for synchronizing o365 Channels and groups membership in a course.
 *
 * @package     report_o365channels
 * @category    admin
 * @copyright   2021 Enrique Castro @ULPGC
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use \local_o365teams\coursegroups\utils;
 
require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir.'/grouplib.php');

$courseid = required_param('id', PARAM_INT);       // course id
$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context = context_course::instance($course->id);
$groupingid = optional_param('grouping', 0, PARAM_INT);

require_login($course);
require_capability('report/o365channels:view', $context);


//setting page url
$params = ['id' => $courseid];
if($groupingid) {
    $params['grouping'] = $groupingid;
}
$baseurl = new moodle_url('/report/o365channels/index.php', $params);
$returnurl = new moodle_url('/group/index.php', $params);

$PAGE->set_url($baseurl);
$PAGE->set_context($context);
//setting page layout to report
$PAGE->set_pagelayout('report');
//checking if user is capable of viewing this report in $coursecontext
require_capability('report/o365channels:view', $context);
//strings
$strgroupreport = get_string('pluginname' , 'report_o365channels');

//setting page title and page heading
$PAGE->set_title($course->shortname .': '. $strgroupreport);
$PAGE->set_heading($course->fullname);

$output = $PAGE->get_renderer('report_o365channels');

// Actions 
$action = optional_param('action', '', PARAM_ALPHANUMEXT);
$stype = optional_param('s', 0, PARAM_INT);
$item = optional_param('g', 0, PARAM_INT);

$canmanage = has_all_capabilities(['report/o365channels:manage', 'local/o365:teamowner'], $context);

$coursegroupobj = utils::get_o365_object($courseid);
$courseteamsobj = utils::get_teams_object($courseid);
                        
// check o365 API & configuration
$apiconfigured = (\local_o365\utils::is_configured() === true);
[$teamsenabled, $channelsenabled, $usergroupsenabled] = utils::enabled_mode(true); 
                 
$teamschannels = null;
if($graphclient = utils::get_graphclient() AND $apiconfigured) {
    $teamschannels = new \local_o365teams\coursegroups\teamschannels($graphclient, $DB, (bool)$CFG->debugdisplay);                 
}

// Sanitation, team
$deleted = [];
if(!empty($courseteamsobj)) {
    if(!$teamo365obj = $teamschannels->get_team($courseteamsobj->objectid)) {
        $deleted[] = $courseteamsobj->id;
    }
}

if($canmanage && $action && ($courseteamsobj || ($action === 'addteam'))) {
    confirm_sesskey();
    
    $itemobj = null;
    if($item) {
        $subtype = ['teamchannel', 'usergroup'];
        $params = ['type' => 'group', 'subtype' => $subtype[$stype], 'moodleid' => $item];
        $itemobj = $DB->get_record('local_o365_objects', $params);
    }
    
    
    $updated = [];
    $success = false;
    $message = '?';
    if($action == 'add') {
        if($item) {
            if(empty($itemobj)) {
                // Add ONLY if not existing yet
                if($stype == 0) { // means channels
                    if($channelobj = $teamschannels->add_channel_for_group($item)) {
                        \core\notification::add(get_string('channeladded', 'report_o365channels'), 
                                        \core\output\notification::NOTIFY_SUCCESS);    
                        // Trigger an channel created event.
                        report_o365channels_get_updated_and_event($channelobj, 'channel_created');
                        
                        $retrycounter = 0;
                        while ($retrycounter <= API_CALL_RETRY_LIMIT) {
                            if ($retrycounter) {
                                sleep(10);
                            }
                            try {
                                $done = $teamschannels->resync_channel_membership($courseid, $item, $courseteamsobj->objectid, $channelobj->objectid);
                                if(!empty($done)) {
                                    $updated[] = $done;
                                    report_o365channels_get_updated_and_event($channelobj, 'channel_synced');
                                }
                                break;
                            } catch (\Exception $e) {
                                $retrycounter++;
                            }
                        }                        
                    }
                } elseif($stype > 1) { // means usergroup
                    if($groupobj = $teamschannels->create_usergroup($item)) {
                        report_o365channels_get_updated_and_event($groupobj, 'channel_created');                                        
                        \core\notification::add(get_string('usergroupadded', 'report_o365channels'), 
                                        \core\output\notification::NOTIFY_SUCCESS);    
                        $retrycounter = 0;
                        while ($retrycounter <= API_CALL_RETRY_LIMIT) {
                            if ($retrycounter) {
                                sleep(10);
                            }
                            try {
                                [$toadd, $toremove] = $teamschannels->resync_group_owners_and_members($course->id, $groupobj->objectid);
                                if($done = report_o365channels_get_updated_and_event($groupobj, 'channel_synced', $toadd, $toremove)) {
                                    $updated[] = $done;
                                }
                                break;
                            } catch (\Exception $e) {
                                $retrycounter++;
                            }
                        }                        
                    }                
                }
            } else {
                $message = get_string('error_noaddexisting', 'report_o365channels', $itemobj->o365name);
            }
        } else {
            $message = get_string('error_noitem', 'report_o365channels');
        }
    
    } elseif($action == 'del') {    
        if($item) {
            if(!empty($itemobj)) {        
                if($stype == 0) { // means channels
                    if($success = $teamschannels->remove_group_channel($courseid, $item, $courseteamsobj->objectid)) {
                        report_o365channels_get_updated_and_event($itemobj, 'channel_deleted');
                        \core\notification::add(get_string('channeldeleted', 'report_o365channels'), 
                                        \core\output\notification::NOTIFY_SUCCESS);    
                    }
                    
                } elseif($stype > 1) { // means usergroup
                    if($success = $teamschannels->delete_usergroup($item)) {
                        report_o365channels_get_updated_and_event($itemobj, 'channel_deleted');
                        \core\notification::add(get_string('usergroupdeleted', 'report_o365channels'), 
                                        \core\output\notification::NOTIFY_SUCCESS);    
                    }   
                }
            } else {
                $message = get_string('error_delnonexisting', 'report_o365channels');
            }        
        } else {
            $message = get_string('error_noitem', 'report_o365channels');
        }
        
    } elseif($action == 'addteam') {
        if($teamschannels->create_group_for_course($course)) {
            $coursegroupobj = utils::get_o365_object($courseid);
            $courseteamsobj = utils::get_teams_object($courseid);
            if(!empty($courseteamsobj) && $courseteamsobj) {
                utils::set_course_sync_enabled($course->id, true);
                report_o365channels_get_updated_and_event($courseteamsobj, 'team_created');
            }
        } else {
            $message = get_string('error_noteam', 'report_o365channels');
        }
    } elseif($action == 'syncteam') {
        [$toadd, $toremov] = $teamschannels->resync_group_owners_and_members($courseid, $coursegroupobj->objectid);
        if($done = report_o365channels_get_updated_and_event($coursegroupobj, 'team_synced', $toadd, $toremove)) {
            $updated[] = $done;
        }
        
    } elseif(($action == 'syncall') ||  (($action == 'update') && $item)) {
        $params = ['courseid' => $courseid];
        $groupwhere = '';
        if(($action != 'syncall') && ($action == 'update') && $item) {
            $groupwhere = 'AND g.id = :groupid ';
            $params['groupid'] = $item;
        } 
    
        $sql = "SELECT oc.*, g.id AS gid
                  FROM {groups} g 
                  JOIN {local_o365_objects} oc ON g.id = oc.moodleid AND oc.subtype = :subtype AND oc.type = 'group' 
                  WHERE g.courseid = :courseid  $groupwhere ";
        $params['subtype'] = 'teamchannel';
        $groups = $DB->get_records_sql($sql, $params); 
        foreach($groups as $group) {
                $done = $teamschannels->resync_channel_membership($courseid, $group->gid, $courseteamsobj->objectid, $group->objectid);
                if(!empty($done)) {
                    $updated[] = $done;
                    report_o365channels_get_updated_and_event($group, 'channel_synced');
                }
        }
    
        $params['subtype'] = 'usergroup';
        $groups = $DB->get_records_sql($sql, $params); 
        foreach($groups as $group) {
            [$toadd, $toremove] = $teamschannels->resync_group_owners_and_members($courseid, $groupobj->objectid);
            if($done = report_o365channels_get_updated_and_event($groupobj, 'channel_synced', $toadd, $toremove)) {
                $updated[] = $done;
            }
        }
    }

    if($updated) {
        $success = true;
        if(!is_array($updated)) {
            $updated = [$updated];
        } 
        foreach($updated as $update) {
            \core\notification::add(get_string('membersupdated', 'report_o365channels', $update), 
                        \core\output\notification::NOTIFY_INFO);    
        }
    }
    
    if(!$success) {
        \core\notification::add(get_string('notdone', 'report_o365channels', $message), 
                        \core\output\notification::NOTIFY_ERROR);    
    }
}

if($apiconfigured && ($teamsenabled || $usergroupsenable)) {
    // obtain course groups
    $params = ['courseid' => $courseid];
    $groupingwhere = '';
    if ($groupingid) {
        if ($groupingid < 0) { // No grouping filter.
            $groupingwhere = "AND gg.groupingid IS NULL";
        } else {
            $groupingwhere = "AND gg.groupingid = :groupingid";
            $params['groupingid'] = $groupingid;
        }
    }

    $userwhere = '';
    if(!has_capability('moodle/site:accessallgroups', $context)) {
        $userwhere = ' AND EXISTS (SELECT 1 FROM {groups_members} m WHERE m.groupid = g.id AND m.userid = :userid) ';
        $params['userid'] = $USER->id;
    }

    
    $sql = "SELECT g.*, gg.groupingid, gi.name AS groupingname, COUNT(gm.userid) AS numusers, 
                        oc.id AS ocid, oc.objectid, oc.metadata, oc.o365name, oc.tenant, 
                        og.id AS ogid, og.objectid AS usergroupobjectid, og.o365name AS mailnickname
             FROM {groups} g
        LEFT JOIN {local_o365_objects} oc ON g.id = oc.moodleid AND oc.subtype = 'teamchannel' AND oc.type = 'group' 
        LEFT JOIN {local_o365_objects} og ON g.id = og.moodleid AND og.subtype = 'usergroup' AND og.type = 'group' 
        LEFT JOIN {groupings_groups} gg ON g.id = gg.groupid
        LEFT JOIN {groupings} gi ON gi.id = gg.groupingid
        LEFT JOIN {groups_members} gm ON g.id = gm.groupid
            WHERE g.courseid = :courseid $groupingwhere $userwhere
        GROUP BY g.id  
        ORDER BY gi.name, g.name";

    $groups = $DB->get_records_sql($sql, $params);

    if($groups) {
        // process groups with API for usergroups
        if(!empty($teamschannels))  {
            foreach($groups as $gid => $group) {
                if(isset($group->objectid) && $group->objectid) {
                    if(!$success = $teamschannels->get_channel($courseteamsobj->objectid, $group->objectid, false)) {
                        $deleted[] = $group->ocid;
                    }
                }
            
                if(isset($group->usergroupobjectid) && $group->usergroupobjectid) {            
                    if(!$success = $teamschannels->get_usergroup($group->usergroupobjectid)) {
                        $deleted[] = $group->ogid;
                    }
                }
            }             
        }
    }
    
    if($deleted) {
        if(in_array($courseteamsobj->id, $deleted)) {
            $DB->delete_records('local_o365_teams_cache', ['objectid' => $courseteamsobj->objectid]);
        }
        if($DB->delete_records_list('local_o365_objects', 'id', $deleted)) {
            \core\notification::warning(get_string('referencesdeleted', 'report_o365channels', count($deleted)));            
        }
        redirect($baseurl);
    }
}

// Start output 
//Displaying header and heading
echo $output->header();
echo $output->heading($strgroupreport);

$strgrouping         = get_string('grouping', 'group');
$strnotingrouping    = get_string('notingrouping', 'group');
$strnogrouping       = get_string('nogrouping', 'group');

if(!$apiconfigured || !($teamsenabled || $usergroupsenable)) {
    echo $output->notification(get_string('notavailable', 'report_o365channels'), 'notifyproblem');
} else {
    // Get all groupings and present selection menu.
    $groupings = $DB->get_records('groupings', ['courseid'=>$courseid], 'name');
    echo $output->print_grouping_select($groupings, $groupingid);
    
    // print Team / Channels / Groups updating buttons
    echo $output->print_general_update_buttons((empty($courseteamsobj) && $teamsenabled)) ;
    
    if($groups) {
        // process groups with API for usergroups
        if(!empty($teamschannels))  {
            foreach($groups as $gid => $group) {
                if($group->links = $teamschannels->get_usergroup_urls($group)) {
                    $groups[$gid] = clone $group;
                }
            }             
        }
        echo $output->print_channels_table($groups, $courseteamsobj, $canmanage);
    } else {
        echo $output->heading(get_string('nothingtodisplay'));
    }

}
echo $output->container_start('buttons');
echo $output->single_button($returnurl, get_string('backtogroups', 'group'));
echo $output->container_end();

//making log entry
//add_to_log($course->id, 'course', 'report edit groups', "report/o365channels/index.php?id=$course->id", $course->id);
// Trigger a report viewed event.
$event = \report_o365channels\event\report_viewed::create(array('context' => $context));
$event->trigger();

//display page footer
echo $output->footer();

////////////////////////////////////////////////////////////////////////////
function report_o365channels_get_updated_and_event(stdclass $o365obj, string $eventname, $toadd = null, $toremove = null) {
    global $PAGE;
    $context = $PAGE->context;
    $done = null;
                                
    if(!empty($toadd) || !empty($toremove)) {
        $done = new \stdClass();
        $done->toadd = count($toadd);
        $done->added = $done->toadd;
        $done->toremove = count($toremove);
        $done->removed = $done->toremove;                                   
    }
    
    $other = ['o365objectid' => $o365obj->objectid];
    if($o365obj->subtype == 'usergroup') {
        $other['subtype'] = 'usergroup';
    }
    $eventmethod = "\\report_o365channels\event\{$eventname}::create";
    $event = $eventmethod(['context' => $context,
                            'objectid' => $o365obj->id,
                            'other' => $other,
                        ]);
    $event->trigger();                                
    
    return $done;
}
