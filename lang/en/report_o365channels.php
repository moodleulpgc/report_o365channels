<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Plugin strings are defined here.
 *
 * @package     report_o365channels
 * @category    string
 * @copyright   2021 Enrique Castro @ULPGC
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['addteam'] = 'Add Team';
$string['addgroup'] = 'Add Group';
$string['channel'] = 'Teams Channel';
$string['channeladded'] = 'New channel created';
$string['channeldeleted'] = 'Channel has been deleted';
$string['confirmunlink'] = 'You have asked to unlink and delete the Teams channel associated with moodle group named "{$a}". <br />
Do you want to proceed?';
$string['confirmunlinkgroup'] = 'You have asked to unlink and delete the Outlook/Sharepoint group associated with moodle group named "{$a}". <br />
Do you want to proceed?';
$string['error_delnonexisting'] = 'There\'s no associated o365 object to delete.';
$string['error_noaddexisting'] = 'There\'s a current object for group {$a}.';
$string['error_noitem'] = 'No group specified.';
$string['eventchannelcreated'] = 'Teams channel created';
$string['eventchanneldeleted'] = 'Teams channel deleted';
$string['eventchannelsynced'] = 'Updated members in channel';
$string['eventteamcreated'] = 'Teams group created';
$string['eventteamsynced'] = 'Synced users in Teams';
$string['link_calendar'] = 'Calendar'; 
$string['link_conversations'] = 'Outlook'; 
$string['link_notebook'] = 'Notebook'; 
$string['link_onedrive'] = 'OneDrive'; 
$string['membersupdated'] = 'Updated members: added {$a->added}/{$a->toadd} members and removed {$a->removed}/{$a->toremove} users.';
$string['nocourseteam'] = 'There is no Team linked to this course.';
$string['notavailable'] = 'o365 connection is not configured or Teams/Groups features disabled';
$string['notdone'] = 'Group operation has failed, nothing done. Reason: {$a}.';
$string['noteam'] = 'Team operation has failed, no team created.';
$string['o365channels:manage'] = 'Update and synch o365 channels from groups';
$string['o365channels:view'] = 'View o365 Channel - Group synch page';
$string['pluginname'] = 'o365 Channels synch';
$string['referencesdeleted'] = 'Removed {$a} references to o365 resources that no longer exist in o365.';
$string['resynch'] = 'Update channel members from moodle group';
$string['syncall'] = 'Update all channels';
$string['syncallcourses'] = 'Update all courses';
$string['syncallcourses_desc'] = 'If checked, then ALL courses with an o365 object will be included in user synchronization not just those updated from last run.';
$string['synch'] = 'Create Teams channel from moodle group';
$string['synchgroup'] = 'Create Outlook group from moodle group';
$string['syncteam'] = 'Update Team';
$string['task_teamschannelsynch'] = 'Synchronize users in Teams & Channels';
$string['teamschannelsynch'] = 'Synchronize Teams & Channels users with moodle';
$string['unlink'] = 'Remove Channel in Teams';
$string['unlinkgroup'] = 'Remove Group in Outlook';
$string['usergroup'] = 'Outlook group';
$string['usergroupadded'] = 'New Outlook group added';
$string['usergroupdeleted'] = 'Outlook group has been deleted';
