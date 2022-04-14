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
 * Plugin renderer class.
 *
 * @package    report_o365channels
 * @copyright  2021 Enrique Castro @ ULPGC
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_o365channels\output;

use single_select;
use core_collator;
use html_table;

defined('MOODLE_INTERNAL') || die();

/**
 * Plugin renderer class.
 *
 * @package    report_o365channels
 * @copyright  2021 Enrique Castro @ULPGC
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends \plugin_renderer_base {

    public function print_grouping_select($groupings, $groupingid) {
        foreach ($groupings as $gid => $grouping) {
            $groupings[$gid]->formattedname = format_string($grouping->name, true, ['context' => $this->page->context]);
        }
        core_collator::asort_objects_by_property($groupings, 'formattedname');
        // Add 'no groupings' / 'no groups' selectors.
        $groupings[-1] = (object)[
            'id' => -1,
            'formattedname' => get_string('nogrouping', 'group'),
        ];
        $options = [];
        $options[0] = get_string('all');
        foreach ($groupings as $grouping) {
            $options[$grouping->id] = strip_tags($grouping->formattedname);
        }
        $select = new single_select($this->page->url, 'grouping', $options, $groupingid, []);
        $select->label = get_string('nogrouping', 'group');
        $select->formid = 'selectgrouping';
        return  $this->render($select);
    }

    public function print_general_update_buttons($add = false) {
        $url = clone $this->page->url;
        
        $message = '';
        if($add) {
            // add teams button
            $url->param('action', 'addteam');
            $button = $this->single_button($url, get_string('addteam', 'report_o365channels'));
            $message = get_string('nocourseteam', 'report_o365channels');
        } else {
            $url->param('action', 'syncteam');
            $button = $this->single_button($url, get_string('syncteam', 'report_o365channels'));

            $url->param('action', 'syncall');    
            $button .= $this->single_button($url, get_string('syncall', 'report_o365channels'),
                                                'post', ['class' => ' pull-right ']);
        }
        return $this->container($message . $button, 'manageteam');
    }

    public function print_channels_table($groups, $hascourseteam = false, $canmanage = false) {
        
        $baseurl =  clone $this->page->url;
        $strupdate       = get_string('resynch', 'report_o365channels');
        $stradd          = get_string('synch', 'report_o365channels');
        $strdelete       = get_string('unlink', 'report_o365channels');
        $strdelgroup     = get_string('unlinkgroup', 'report_o365channels');
        $strmail         = get_string('synchgroup', 'report_o365channels');
    
        $table = new html_table();
        $table->head  = [get_string('grouping', 'group'), 
                        get_string('groupname', 'group'), 
                        get_string('usercount', 'group'),
                        get_string('usergroup', 'report_o365channels'),
                        get_string('channel', 'report_o365channels'),
                        ];
        $table->size  = ['20', '20%', '10%', '20%', '20%'];
        $table->align = ['left', 'left', 'center', 'center'];
        if($canmanage) {
            $table->head[] = get_string('action');
            $table->size[] = '10%';
            $table->align[] = 'center';
        }
        
        $table->width = '95%';
        $table->data  = [];

        $baseurl->param('sesskey', sesskey());
        foreach($groups as $group) {
            $baseurl->remove_params('action');
            $row = [];
            $row[] = format_string($group->groupingname);
            $row[] = $group->name;
            $row[] = $group->numusers;

            $baseurl->param('g', $group->id);            
            // mail usergroups
            $usergrouplinks = '';
            if($group->usergroupobjectid) {
                $usergrouplinks = $group->mailnickname;
                $baseurl->param('action', 'del');
                $baseurl->param('s', 1);
                $icon = new \pix_icon('t/delete', $strdelgroup, 'core', ['class'=>'iconsmall', 'title'=>$strdelete]);
                $confirmaction = new \confirm_action(get_string('confirmunlinkgroup', 'report_o365channels', $group->name));
                $usergrouplinks .= ' '. $this->action_icon($baseurl, $icon, $confirmaction);
                if(!empty($group->links)) { 
                    foreach($group->links as $key => $url) {
                        $group->links[$key] = \html_writer::link($url, get_string('link_'.$key, 'report_o365channels'));
                    }
                    $usergrouplinks .= ' '. \html_writer::span(implode(', ', $group->links), 'usergrouplinks');
                }
        
            }
            $row[] = $usergrouplinks;
            
            // Team channels
            $link = '';
            $attributes = ['target' => '_blank', 'rel' => 'noopener noreferrer'];
            if($group->metadata) {
                $data = get_object_vars(json_decode($group->metadata));
                $link = $data['webUrl'];
                //https://teams.microsoft.com/l/channel/19%3a0cc3880fddda47888fa3a92d278c3380%40thread.tacv2/Teor%25C3%25ADa%252002%2520(est%25C3%25A1ndard)?groupId=45489011-99ac-4ce4-98e6-384e79ef0f65&tenantId=b2bb731c-460d-420f-a475-3ed615a82987        
            } elseif($group->objectid) {
                $link = new moodle_url('https://teams.microsoft.com/l/channel/'.urlencode($group->objectid), 
                                        ['tenantId' => urlencode($group->tenant)]);
            }
            $row[] = ($link) ? \html_writer::link($link, format_string($group->o365name), $attributes) : '';
            
            $action = '';
            $buttons = [];
            if($canmanage) {
                $baseurl->remove_params('s');
                if($hascourseteam) {
                    if($group->objectid || $group->usergroupobjectid) {
                        // there is one, delete, resynch
                        $baseurl->param('action', 'update');
                        $icon = new \pix_icon('i/cohort', $strupdate, 'core', ['class'=>'iconsmall', 'title'=>$strupdate]);
                        $buttons[] = $this->action_icon($baseurl, $icon);
                        
                        $baseurl->param('action', 'del');
                        $baseurl->param('s', 0);
                        $icon = new \pix_icon('t/delete', $strdelete, 'core', ['class'=>'iconsmall', 'title'=>$strdelete]);
                        $confirmaction = new \confirm_action(get_string('confirmunlink', 'report_o365channels', $group->name));
                        $buttons[] = $this->action_icon($baseurl, $icon, $confirmaction);
                    }  
                    if(!$group->objectid){
                        // add icon
                        $baseurl->param('action', 'add');
                        $baseurl->param('s', 0);
                        $icon = new \pix_icon('i/addblock', $stradd, 'core', ['class'=>'iconsmall', 'title'=>$stradd]);
                        $buttons[] = $this->action_icon($baseurl, $icon);
                    }
                }
                if(!$group->usergroupobjectid){
                    // add icon
                    $baseurl->param('action', 'add');
                    $baseurl->param('s', 1);
                    $icon = new \pix_icon('t/email', $stradd, 'core', ['class'=>'iconsmall', 'title'=>$strmail]);
                    $buttons[] = $this->action_icon($baseurl, $icon);
                }
                if($buttons) {
                    $action .= implode('&nbsp;&nbsp;', $buttons);  
                }
                $row[] = $action;
            }
            
            $table->data[] = $row;
        }

        return \html_writer::table($table);    
    
    
    }
    
}
