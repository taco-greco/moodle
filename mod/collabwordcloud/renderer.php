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
 * Wordcloud module renderer.
 *
 * @package     mod_collabwordcloud
 * @copyright   2023 DNE - Ministere de l'Education Nationale
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__.'/wordsubmit_form.php');

/**
 * Class mod_collabwordcloud_renderer.
 *
 * @package     mod_collabwordcloud
 * @copyright   2021 Reseau-Canope
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_collabwordcloud_renderer extends plugin_renderer_base {

    public function display_wordcloud_cloud() {
        return '<div class="wordcloudcontainer"><div class="wc_empty" style="display:none">'.get_string('empty_wordcloud','mod_collabwordcloud').'</div></div>';
    }
    
    public function display_wordcloud_editor($wordcloud, $hide=false) {
        $endstart_date = '';
        if ($wordcloud->has_ended() && $wordcloud->activity->timeend > 0) {
            $endstart_date = get_string('student_cant_submit_since','mod_collabwordcloud', strftime(get_string('strftimedaydatetime','core_langconfig'), $wordcloud->activity->timeend));
        } else if ($wordcloud->is_started() && $wordcloud->activity->timestart > 0) {
            $endstart_date = get_string('student_can_submit_upto','mod_collabwordcloud', strftime(get_string('strftimedaydatetime','core_langconfig'), $wordcloud->activity->timestart));
        } else if ($wordcloud->activity->timestart > 0) {
            $endstart_date = get_string('student_can_submit_from','mod_collabwordcloud', strftime(get_string('strftimedaydatetime','core_langconfig'), $wordcloud->activity->timestart));
        }
        
        return '
<div class="wc_tools"'.($hide?' style="display:none"':'').'>
    <div class="wc_editor_view">
        <div id="wc_exports"><div><button class="wc_exportpng">'.get_string('exporttoimage','mod_collabwordcloud').'</button></div><div><button class="wc_exportdata">'.get_string('exportdata','mod_collabwordcloud').'</button></div></div>
        <div class="wc_participation"><span class="wc_participation_count">'.get_string('nosubmition','mod_collabwordcloud').'</span><br/><span class="wc_endstart_date">'.$endstart_date.'</span></div>
        <div>
            <div class="wc_addword_header">'.get_string('addword','mod_collabwordcloud').'</div>
            <div class="row wc_formrow">
                <div class="col-md-3">'.get_string('word','mod_collabwordcloud').'</div>
                <div class="col-md-3"><input maxlength="'.get_config('collabwordcloud', 'wordmaxlenght').'" type="text" class="form-control" name="wcaddword"/></div>
                <div class="col-md-6"></div>
            </div>
            <div class="row wc_formrow">
                <div class="col-md-3"></div>
                <div class="col-md-9"><button class="wc_addword">'.get_string('add','mod_collabwordcloud').'</button></div>
            </div> 
        </div>
    </div>
</div>
<div class="wc_editor_word">
    <div class="wc_editword_header">'.get_string('updateaword','mod_collabwordcloud').'</div>
    <div class="row wc_formrow">
        <div class="col-md-3">'.get_string('word','mod_collabwordcloud').'</div>
        <div class="col-md-9">
            <input type="text" name="wceditword" class="form-control"/>
            <span id="updateword_fusion_warn">Ce mot existe par ailleurs. Les propositions seront fusionnées. Le poids prévu est indiqué ci-dessous.</span>
        </div>
        <div class="col-md-6"></div>
    </div>
    <div class="row wc_formrow wc_weight">
        <div class="col-md-3">'.get_string('wordweight','mod_collabwordcloud').'</div>
        <div class="col-md-9">
            <span>0</span>
        </div>
    </div>
    <div class="row wc_formrow">
        <div class="col-md-3"></div>
        <div class="row col-md-9">
            <div><button class="wc_updateword">'.get_string('updateword','mod_collabwordcloud').'</button></div>
            <div><button class="wc_removeword">'.get_string('removeword','mod_collabwordcloud').'</button></div>
            <div><button class="wc_closeedit">'.get_string('canceledit','mod_collabwordcloud').'</button></div>
        </div>    
    </div>
    <div class="row wc_formrow wc_users">
        <div class="col-md-3">'.get_string('wordusers','mod_collabwordcloud').'</div>
        <div class="col-md-9">
            <ul></ul>
        </div>
    </div>
</div>';
    }

    public function display_wordcloud_submit_form($wordcloud, $g = 0) {
        $url = new moodle_url('/mod/collabwordcloud/view.php',array('id' => $wordcloud->cm->id, 'g' => $g));
        $form = new mod_collabwordcloud_wordsubmit_form($url,
            array('wordsallowed' => $wordcloud->activity->wordsallowed, 'wordsrequired' => $wordcloud->activity->wordsrequired, 'group' => $g), 'post');
        
        return $form->render();
    }

    public function display_wordcloud_submit($hide, $content = '') {
        return '<div id="wcform"'.($hide ? ' style="display:none"' : '').'>'.$content.'</div>';
    }

    public function display_wordcloud_groupselector($wordcloud, $g) {
        $user_groups = $wordcloud->get_user_groups();
        
        if (count($user_groups) == 0 ){return '';}
        
        $notdisplayed = count($user_groups) == 1 ? 'hide' : '';
        $output = '<div class="wc_groups '.$notdisplayed.'">'.get_string('group','mod_collabwordcloud').' <select class="wc_groupselector">';

        foreach($user_groups AS $group) {
            $output .= '<option value="'.$group->id.'"'.($group->id == $g ? ' selected="selected"' : '').'>'.$group->name.($group->member ? ' (membre)' : '').'</option>';
        }
        
        $output .= '</select></div>';
        
        return $output;
    }

    /**
     * Render a course index summary.
     *
     * @param wordcloud_course_index_summary $indexsummary
     * @return string
     */
    public function render_mod_collabwordcloud_course_index_summary(\mod_collabwordcloud\mod_collabwordcloud_course_index_summary $indexsummary) {
        $strplural = get_string('modulenameplural', 'collabwordcloud');
        $strsectionname  = $indexsummary->courseformatname;

        $table = new html_table();
        if ($indexsummary->usesections) {
            $table->head  = array ($strsectionname, $strplural);
            $table->align = array ('left', 'left');
        } else {
            $table->head  = array ($strplural);
            $table->align = array ('left');
        }
        $table->data = array();

        $currentsection = '';
        foreach ($indexsummary->wordclouds as $info) {
            $params = array('id' => $info['cmid']);
            $link = html_writer::link(new moodle_url('/mod/collabwordcloud/view.php', $params), $info['cmname']);

            $printsection = '';
            if ($indexsummary->usesections) {
                if ($info['sectionname'] !== $currentsection) {
                    if ($info['sectionname']) {
                        $printsection = $info['sectionname'];
                    }
                    if ($currentsection !== '') {
                        $table->data[] = 'hr';
                    }
                    $currentsection = $info['sectionname'];
                }
            }

            if ($indexsummary->usesections) {
                $row = array($printsection, $link);
            } else {
                $row = array($link);
            }
            $table->data[] = $row;
        }

        return html_writer::table($table);
    }

}

