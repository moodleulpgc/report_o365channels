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
 * Library code for the synchronize o365 channels and groups report
 *
 * @package     report_o365channels
 * @category    admin
 * @copyright   2021 Enrique Castro @ULPGC
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * This function extends the navigation with the report items
 *
 * @param navigation_node $navigation The navigation node to extend
 * @param stdClass $course The course to object for the report
 * @param stdClass $context The context of the course
 */
function report_o365channels_extend_navigation_course($navigation, $course, $context) {
    global $PAGE;
    
    // Only add this settings item on non-site course pages.
    if (!$PAGE->course || $PAGE->course->id == SITEID) {
        return null;
    }
    
    if (has_capability('report/o365channels:view', $context)) {
        $url = new moodle_url('/report/o365channels/index.php', array('id'=>$course->id));
        $pluginname = get_string('pluginname', 'report_o365channels');
        $navigation->add(get_string( 'pluginname', 'report_o365channels'),
             $url, navigation_node::TYPE_SETTING,
                null, 'report_o365channels', new pix_icon('i/report', ''));
    }
}


/**
* Return a list of page types
* @param string $pagetype current page type
* @param stdClass $parentcontext Block's parent context
* @param stdClass $currentcontext Current context of block
* @return array
*/
function report_o365channels_page_type_list($pagetype, $parentcontext, $currentcontext) {
    return array(
        '*'                       => get_string('page-x', 'pagetype'),
        'report-*'                => get_string('page-report-x', 'pagetype'),
        'report-o365channels-index' => get_string('page-report-o365channels-index',  'report_o365channels'),
    );
}
