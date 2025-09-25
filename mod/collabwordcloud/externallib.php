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
 * Wordcloud webservices
 *
 * @package    mod_collabwordcloud
 * @copyright  2023 DNE - Ministere de l'Education Nationale
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot.'/mod/collabwordcloud/collabwordcloud.php');


class mod_collabwordcloud_external extends \core_external\external_api {
    
    private static $context;
    private static $cm;
    private static $wc;
    private static $wordcloud;
    
    
    private static function precheck($cmid){
        global $DB, $PAGE;
        if (isset($cmid)) {
            self::$cm = get_coursemodule_from_id('collabwordcloud', $cmid);
            self::$wc = $DB->get_record('collabwordcloud', array('id' => self::$cm->instance));
            if (self::$wc === false) {
                return array('error'=>true,'msg'=>get_string('activitynotfound','collabwordcloud'));
            }
            self::$wordcloud = new collabwordcloud(self::$wc->id);
            self::$context = context_module::instance(self::$cm->id);
            $PAGE->set_context(self::$context);
        } else {
            return array('error'=>true,'msg'=>get_string('missingparams','collabwordcloud'));
        }
        
        if (((self::$wc->timestart > 0 && time() < self::$wc->timestart) || (self::$wc->timeend > 0 & time() > self::$wc->timeend)) && !self::$wordcloud->is_editor()) {
            return array('error'=>true,'msg'=>get_string('activitynotopenned','collabwordcloud'));
        }
        return null;
    }
    
    public static function getdata_parameters() {
        return new \core_external\external_function_parameters(
            array(
                'cmid'  => new \core_external\external_value(PARAM_INT),
                'groupid' => new \core_external\external_value(PARAM_INT),
                'lastupdate' => new \core_external\external_value(PARAM_INT)
            )
        );
    }
    
    public static function getdata_returns() {
        return new \core_external\external_single_structure(
            array(
                'error' => new \core_external\external_value(PARAM_BOOL),
                'msg' => new \core_external\external_value(PARAM_TEXT, '', VALUE_OPTIONAL),
                'mod' => new \core_external\external_value(PARAM_ALPHA, '', VALUE_OPTIONAL),
                'form' => new \core_external\external_value(PARAM_RAW, '', VALUE_OPTIONAL),
                'noupdate' => new \core_external\external_value(PARAM_BOOL, '', VALUE_OPTIONAL),
                'editor' => new \core_external\external_value(PARAM_BOOL, '', VALUE_OPTIONAL),
                'timemodified' => new \core_external\external_value(PARAM_INT, '', VALUE_OPTIONAL),
                'subs' => new \core_external\external_value(PARAM_INT, '', VALUE_OPTIONAL),
                'words' => new \core_external\external_multiple_structure(
                    new \core_external\external_single_structure(
                        array(
                            'text' => new \core_external\external_value(PARAM_TEXT, '', VALUE_OPTIONAL),
                            'count' => new \core_external\external_value(PARAM_INT, '', VALUE_OPTIONAL),
                            'max' => new \core_external\external_value(PARAM_INT, '', VALUE_OPTIONAL),
                            'size' => new \core_external\external_value(PARAM_INT, '', VALUE_OPTIONAL),
                        )
                    ), '', VALUE_OPTIONAL)
            )
        );
    }
    
    public static function getdata($cmid, $groupid, $lastupdate)
    {
        global $PAGE, $USER;
        
        $check = self::precheck($cmid);
        if ($check !== null){
            return $check;
        }
        
        if (!has_capability('mod/collabwordcloud:submitword', self::$context)) {
            return array('error'=>true,'msg'=>get_string('accessdenied','collabwordcloud'));
        }
        if ($lastupdate > 0 && $lastupdate == self::$wc->timemodified) {
            return array('error'=>false,'mod'=>'c','noupdate'=>true);
        }
        
        // Editors
        $member = false;
        $groups = self::$wordcloud->get_user_groups();
        foreach($groups AS $group) {
            if ($group->id == $groupid) {
                $member = $group->member;
            }
        }
        // Student
        $readonly = true;
        if (!self::$wordcloud->is_editor()) {
            if (self::$wordcloud->cm->groupmode == 0) {
                $readonly = false;
            } else if ($member) {
                $readonly = false;
            }
        }
        
        if (count(self::$wordcloud->get_user_words($USER->id, $groupid)) > 0 || self::$wordcloud->is_editor() || $readonly) {
            $words = self::$wordcloud->get_cloud_words($groupid);
            $users = self::$wordcloud->get_cloud_users($groupid);
            $accessallgroups = has_capability('moodle/site:accessallgroups', self::$context);
            return array(
                'error' => false,
                'mod' => 'c',
                'noupdate' => false,
                'editor' => (self::$wordcloud->is_editor() && ($member || $accessallgroups)?true:false),
                'timemodified' => self::$wc->timemodified,
                'subs' => count($users),
                'words' => $words
            );
        } else {
            $renderer = $PAGE->get_renderer('mod_collabwordcloud');
            $form = $renderer->display_wordcloud_submit_form(self::$wordcloud, $groupid);
            return array('error'=>false,'mod'=>'f','form'=>$form);
        }
    }
    
    public static function getwordinfo_parameters() {
        return new \core_external\external_function_parameters(
            array(
                'cmid'  => new \core_external\external_value(PARAM_INT),
                'groupid' => new \core_external\external_value(PARAM_INT),
                'word' => new \core_external\external_value(PARAM_TEXT)
            )
        );
    }
    
    public static function getwordinfo_returns() {
        return new \core_external\external_single_structure(
            array(
                'error' => new \core_external\external_value(PARAM_BOOL),
                'msg' => new \core_external\external_value(PARAM_TEXT, '', VALUE_OPTIONAL),
                'word' => new \core_external\external_value(PARAM_TEXT, '', VALUE_OPTIONAL),
                'weight' => new \core_external\external_value(PARAM_INT, '', VALUE_OPTIONAL),
                'users' => new \core_external\external_multiple_structure(
                    new \core_external\external_value(PARAM_RAW, '', VALUE_OPTIONAL)
                    , '', VALUE_OPTIONAL)
            )
        );
    }
    
    public static function getwordinfo($cmid, $groupid, $word) {
        $check = self::precheck($cmid);
        if ($check !== null){
            return $check;
        }
        
        if (!has_capability('mod/collabwordcloud:manageword', self::$context)) {
            return array('error'=>true,'msg'=>get_string('accessdenied','collabwordcloud'));
        }
        $infos = self::$wordcloud->get_cloud_word_info($word, $groupid);
        
        if ($infos===false) {
            return array('error'=>true,'msg'=>get_string('wordnotfound','collabwordcloud'));
        }
        
        return array(
            'error' => false,
            'word' => $word,
            'weight' => $infos->weight,
            'users' => $infos->usershtml
        );
    }
    
    public static function addword_parameters() {
        return new \core_external\external_function_parameters(
            array(
                'cmid'  => new \core_external\external_value(PARAM_INT),
                'groupid' => new \core_external\external_value(PARAM_INT),
                'word' => new \core_external\external_value(PARAM_TEXT)
            )
        );
    }
    
    public static function addword_returns() {
        return new \core_external\external_single_structure(
            array(
                'error' => new \core_external\external_value(PARAM_BOOL),
                'msg' => new \core_external\external_value(PARAM_TEXT)
            )
        );
    }
    
    public static function addword($cmid, $groupid, $word) {
        global $USER;
        $check = self::precheck($cmid);
        if ($check !== null){
            return $check;
        }
        
        if (!has_capability('mod/collabwordcloud:manageword', self::$context)) {
            return array('error'=>true,'msg'=>get_string('accessdenied','collabwordcloud'));
        }
        
        $result = self::$wordcloud->add_word($USER->id, $word, $groupid);
        
        $msg = '';
        $error = true;
        if ($result===true) {
            $msg = get_string('wordadded','collabwordcloud');
            $error = false;
        } else if($result==collabwordcloud::ERROR_WORD_ALREADY_EXIST) {
            $msg = get_string('wordalreadyexist','collabwordcloud');
        } else if($result==collabwordcloud::ERROR_WORD_TOO_LONG) {
            $msg = get_string('wordistoolong','collabwordcloud');
        } else {
            $msg = get_string('wordisnotvalid','collabwordcloud');
        }
        
        return array('error'=>$error,'msg'=>$msg);
    }
    
    public static function updateword_parameters() {
        return new \core_external\external_function_parameters(
            array(
                'cmid'  => new \core_external\external_value(PARAM_INT),
                'groupid' => new \core_external\external_value(PARAM_INT),
                'word' => new \core_external\external_value(PARAM_TEXT),
                'newword' => new \core_external\external_value(PARAM_TEXT)
            )
        );
    }
    
    public static function updateword_returns() {
        return new \core_external\external_single_structure(
            array(
                'error' => new \core_external\external_value(PARAM_BOOL),
                'msg' => new \core_external\external_value(PARAM_TEXT)
            )
        );
    }
    
    public static function updateword($cmid, $groupid, $word, $newword) {
        $check = self::precheck($cmid);
        if ($check !== null){
            return $check;
        }
        
        if (!has_capability('mod/collabwordcloud:manageword', self::$context)) {
            return array('error'=>true,'msg'=>get_string('accessdenied','collabwordcloud'));
        }
        
        $result = self::$wordcloud->rename_word($word, $newword, $groupid);
        
        $msg = '';
        $error = true;
        if ($result === true) {
            $msg = get_string('wordupdated','collabwordcloud');
            $error = false;
        } else if ($result == collabwordcloud::ERROR_NEW_WORD_IS_THE_SAME) {
            $msg = get_string('newwordisthesame','collabwordcloud');
        } else if ($result == collabwordcloud::ERROR_NO_WORD_FOUND) {
            $msg = get_string('oldwordnotfound','collabwordcloud');
        } else {
            $msg = get_string('wordisnotvalid','collabwordcloud');
        }
        
        return array('error'=>$error,'msg'=>$msg);
    }
    
    public static function simupdateword_parameters() {
        return new \core_external\external_function_parameters(
            array(
                'cmid'  => new \core_external\external_value(PARAM_INT),
                'groupid' => new \core_external\external_value(PARAM_INT),
                'word' => new \core_external\external_value(PARAM_TEXT),
                'newword' => new \core_external\external_value(PARAM_TEXT)
            )
        );
    }
    
    public static function simupdateword_returns() {
        return new \core_external\external_single_structure(
            array(
                'error' => new \core_external\external_value(PARAM_BOOL),
                'msg' => new \core_external\external_value(PARAM_TEXT, '', VALUE_OPTIONAL),
                'subs' => new \core_external\external_value(PARAM_INT, '', VALUE_OPTIONAL),
                'fusion' => new \core_external\external_value(PARAM_BOOL, '', VALUE_OPTIONAL)
            )
        );
    }
    
    public static function simupdateword($cmid, $groupid, $word, $newword) {
        $check = self::precheck($cmid);
        if ($check !== null){
            return $check;
        }
        
        if (!has_capability('mod/collabwordcloud:manageword', self::$context)) {
            return array('error'=>true,'msg'=>get_string('accessdenied','collabwordcloud'));
        }
        
        $result = self::$wordcloud->simulate_rename_word($word, $newword, $groupid);
        
        if(!is_array($result)) {
            if ($result == collabwordcloud::ERROR_NO_WORD_FOUND) {
                $msg = get_string('oldwordnotfound','collabwordcloud');
                return array('error'=>true,'msg'=>$msg);
            }
        }
         
        list($fusion,$newweight) = $result;

        return array(
            'error'=>false,
            'subs'=>$newweight,
            'fusion'=>$fusion
        );
    }
    
    public static function removeword_parameters() {
        return new \core_external\external_function_parameters(
            array(
                'cmid'  => new \core_external\external_value(PARAM_INT),
                'groupid' => new \core_external\external_value(PARAM_INT),
                'word' => new \core_external\external_value(PARAM_TEXT)
            )
        );
    }
    
    public static function removeword_returns() {
        return new \core_external\external_single_structure(
            array(
                'error' => new \core_external\external_value(PARAM_BOOL),
                'msg' => new \core_external\external_value(PARAM_TEXT)
            )
        );
    }
    
    public static function removeword($cmid, $groupid, $word) {
        $check = self::precheck($cmid);
        if ($check !== null){
            return $check;
        }
        
        
        if (!has_capability('mod/collabwordcloud:manageword', self::$context)) {
            return array('error'=>true,'msg'=>get_string('accessdenied','collabwordcloud'));
        }
        
        $nbwords = self::$wordcloud->remove_word($word, $groupid);
        
        $msg = '';
        if ($nbwords > 1) {
            $msg = get_string('nwordsdeleted','collabwordcloud', $nbwords);
        } else if ($nbwords == 1) {
            $msg = get_string('oneworddeleted','collabwordcloud');
        } else {
            $msg = get_string('noworddeleted','collabwordcloud');
        }
        
        return array('error'=>false,'msg'=>$msg);
    }
    
    public static function exportdata_parameters() {
        return new \core_external\external_function_parameters(
            array(
                'cmid'  => new \core_external\external_value(PARAM_INT),
                'groupid' => new \core_external\external_value(PARAM_INT)
            )
        );
    }
    
    public static function exportdata_returns() {
        return new \core_external\external_single_structure(
            array(
                'error' => new \core_external\external_value(PARAM_BOOL),
                'msg' => new \core_external\external_value(PARAM_TEXT, '', VALUE_OPTIONAL),
                'data' => new \core_external\external_value(PARAM_TEXT, '', VALUE_OPTIONAL)
            )
        );
    }
    
    public static function exportdata($cmid, $groupid) {
        $check = self::precheck($cmid);
        if ($check !== null){
            return $check;
        }
        
        if (!has_capability('mod/collabwordcloud:manageword', self::$context)) {
            return array('error'=>true,'msg'=>get_string('accessdenied','collabwordcloud'));
        }
        
        $delimiter = get_string('listsep','core_langconfig');
        
        $users_words = self::$wordcloud->get_cloud_users_words($groupid);
        
        $filename = make_request_directory().'/export.csv';
        $tfile = fopen($filename,'w+');
        
        fputs($tfile, (chr(0xEF) . chr(0xBB) . chr(0xBF)));
        
        $headers = array(get_string('csv_word','collabwordcloud'), get_string('csv_user','collabwordcloud'), get_string('csv_date','collabwordcloud'));
        fputcsv($tfile, $headers, $delimiter);
        
        foreach ($users_words as $word) {
            fputcsv($tfile, array($word->word, $word->user, date("d/m/Y H:i:s", $word->timecreated)), $delimiter);
        }
        
        fclose($tfile);
        $csvfile = base64_encode(file_get_contents($filename));
        
        unlink($filename);
        return array('error'=>false,'data'=>$csvfile);
    }
    
    
}


