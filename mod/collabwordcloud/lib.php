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
 * Library of functions and constants for wordcloud module.
 *
 * @package     mod_collabwordcloud
 * @copyright   2023 DNE - Ministere de l'Education Nationale
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function collabwordcloud_supports($feature) {
    switch($feature) {
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return false;
        case FEATURE_COMPLETION_HAS_RULES:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return false;
        case FEATURE_GRADE_OUTCOMES:
            return false;
        case FEATURE_GROUPINGS:
            return true;
        case FEATURE_GROUPS:
            return true;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        default:
            return null;
    }
}

function collabwordcloud_get_instance($wordcloudid) {
    global $DB;
    return $DB->get_record('collabwordcloud', array('id' => $wordcloudid));
}

function collabwordcloud_add_instance($wordcloud) {
    global $DB, $CFG;

    $cmid = $wordcloud->coursemodule;
    $draftitemid = $wordcloud->instructionseditor['itemid'];
    
    $wc = new stdClass();
    $wc->course = $wordcloud->course;
    $wc->name = $wordcloud->name;
    $wc->intro = $wordcloud->intro;
    $wc->introformat = $wordcloud->introformat;
    $wc->instructions = $wordcloud->instructionseditor['text'];
    $wc->wordsallowed = $wordcloud->wordsallowed;
    $wc->wordsrequired = $wordcloud->wordsrequired;
    $wc->completionwords = (isset($wordcloud->completionwords)?1:0);
    $wc->timestart = $wordcloud->timestart;
    $wc->timeend = $wordcloud->timeend;
    $wc->timecreate = time();
    $wc->timemodified = time();
    
    $wcid = $DB->insert_record('collabwordcloud', $wc, true);
    $context = context_module::instance($cmid);

    if ($draftitemid) {
        $options = array('subdirs' => false, 'maxbytes' => $CFG->maxbytes, 'maxfiles' => -1, 'changeformat' => 1, 'context' => $context, 'noclean' => 1, 'trusttext' => 0);
        $newwc = new stdClass();
        $newwc->instructions = file_save_draft_area_files($draftitemid, $context->id, 'mod_collabwordcloud', 'instructions', 0, $options, $wc->instructions);
        $newwc->id = $wcid;
        $DB->update_record('collabwordcloud', $newwc);
    }
    return $wcid;
}

/**
 * Given an object containing all the necessary data, (defined by the form in mod.html) this function
 * will update an existing instance with new data.
 *
 * @param $wordcloud
 * @return bool
 * @throws dml_exception
 */
function collabwordcloud_update_instance($wordcloud) {
    global $DB, $CFG;
    
    $wc = $DB->get_record('collabwordcloud', array('id' => $wordcloud->instance), '*', MUST_EXIST);
    $cmid = $wordcloud->coursemodule;
    $draftitemid = $wordcloud->instructionseditor['itemid'];
    
    // Updating all mod values
    $wc->name = $wordcloud->name;
    $wc->intro = $wordcloud->intro;
    $wc->introformat = $wordcloud->introformat;
    $wc->instructions = $wordcloud->instructionseditor['text'];
    $wc->wordsallowed = $wordcloud->wordsallowed;
    $wc->wordsrequired = $wordcloud->wordsrequired;
    $wc->completionwords = (isset($wordcloud->completionwords)?1:0);
    $wc->timestart = $wordcloud->timestart;
    $wc->timeend = $wordcloud->timeend;
    $wc->timemodified = time();

    $DB->update_record("collabwordcloud", $wc);

    $context = context_module::instance($cmid);
    if ($draftitemid) {
        $options =  array('subdirs' => false, 'maxbytes' => $CFG->maxbytes, 'maxfiles' => -1, 'changeformat' => 1, 'context' => $context, 'noclean' => 1, 'trusttext' => 0);
        
        $wc->instructions = file_save_draft_area_files($draftitemid, $context->id, 'mod_collabwordcloud', 'instructions', 0, $options, $wc->instructions);
        $DB->update_record('collabwordcloud', $wc);
    }

    return true;
}

/**
 * Given an ID of an instance of this module, this function will permanently delete the instance and any data that depends on it.
 *
 * @param $id
 * @return bool
 * @throws dml_exception
 */
function collabwordcloud_delete_instance($id) {
    global $DB;

    if (! $wordcloud = $DB->get_record('collabwordcloud', array('id' => $id))) {
        return false;
    }

    $result = true;

    if ($events = $DB->get_records('event', array("modulename" => 'collabwordcloud', "instance" => $wordcloud->id))) {
        foreach ($events as $event) {
            $event = calendar_event::load($event);
            $event->delete();
        }
    }

    if (! $DB->delete_records('collabwordcloud', array('id' => $wordcloud->id))) {
        $result = false;
    }

    if ($DB->count_records('collabwordcloud_words', array('wcid' => $wordcloud->id)) > 0) {
        $result = $result && $DB->delete_records('collabwordcloud_words', array('wcid' => $wordcloud->id));
    }

    return $result;
}

/**
 * Print a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * $course and $mod are unused, but API requires them. Suppress PHPMD warning.
 *
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
function collabwordcloud_user_complete($course, $user, $mod, $wordcloud) {
    global $CFG;
    require_once($CFG->dirroot . '/mod/collabwordcloud/locallib.php');

    if ($responses = collabwordcloud_get_user_responses($wordcloud->id, $user->id, false)) {
        foreach ($responses as $response) {
            if ($response->complete == 'y') {
                echo get_string('submitted', 'collabwordcloud').' '.userdate($response->submitted).'<br />';
            } else {
                echo get_string('attemptstillinprogress', 'collabwordcloud').' '.userdate($response->submitted).'<br />';
            }
        }
    } else {
        print_string('noresponses', 'collabwordcloud');
    }

    return true;
}

/**
 * Obtains the automatic completion state for this forum based on any conditions
 * in wordcloud settings.
 *
 * @global object
 * @global object
 * @param object $course Course
 * @param object $cm Course-module
 * @param int $userid User ID
 * @param bool $type Type of comparison (or/and; can be used as return value if no conditions)
 * @return bool True if completed, false if not. (If no conditions, then return
 *   value depends on comparison type)
 */
function collabwordcloud_get_completion_state($course, $cm, $userid, $type) {
    global $DB;
    
    // Get wordcloud details
    if (!($wordcloud=$DB->get_record('collabwordcloud',array('id' => $cm->instance)))) {
        throw new Exception("Can't find wordcloud {$cm->instance}");
    }
    
    $result = $type; // Default return value
    
    $wordcountparams = array('userid' => $userid, 'wordcloudid' => $wordcloud->id);
    $wordcountsql =
"SELECT COUNT(ww.id)
FROM  {collabwordcloud_words} ww
WHERE ww.userid=:userid AND ww.wcid=:wordcloudid";
    
    if ($wordcloud->completionwords) {
        $value = $wordcloud->wordsrequired <= $DB->get_field_sql($wordcountsql, $wordcountparams);
        if ($type == COMPLETION_AND) {
            $result = $result && $value;
        } else {
            $result = $result || $value;
        }
    }
    
    return $result;
}

/**
 * Serves the page files.
 *
 * @package  mod_page
 * @category files
 * @param stdClass $course course object
 * @param stdClass $cm course module object
 * @param stdClass $context context object
 * @param string $filearea file area
 * @param array $args extra arguments
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool false if file not found, does not return if found - just send the file
 */
function collabwordcloud_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
    global $CFG;
    require_once($CFG->libdir.'/resourcelib.php');

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    require_course_login($course, true, $cm);

    if ($filearea !== 'instructions') {
        // intro is handled automatically in pluginfile.php
        return false;
    }

    $fs = get_file_storage();
    $relativepath = implode('/', $args);
    $fullpath = "/$context->id/mod_collabwordcloud/$filearea/0/$relativepath";
    $file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory();

    // finally send the file
    send_stored_file($file, null, 0, $forcedownload, $options);

}

function collabwordcloud_process_submit($wordcloud, $g = 0) {
    global $USER, $PAGE;
    
    require_once(__DIR__.'/wordsubmit_form.php');
    
    $form = new mod_collabwordcloud_wordsubmit_form($PAGE->url,
        array('wordsallowed' => $wordcloud->activity->wordsallowed, 'wordsrequired' => $wordcloud->activity->wordsrequired, 'group'=>$g), 'post');
    
    if (!$form->is_submitted()) {
        return false;
    }
    
    if ($data = $form->get_data()) {
        $words = array();
        for ($i = 1; $i <= $wordcloud->activity->wordsallowed; $i++) {
            if (isset($data->{'word_'.$i})) {
                $word = trim($data->{'word_'.$i});
                if ($word != '') {
                    $words[$i] = $word;
                }
            }
        }
        
        if (count($words) >= $wordcloud->activity->wordsrequired) {
            foreach ($words AS $word) {
                $wordcloud->add_word($USER->id, $word,$g);
            }
            // Update completion state
            $completion=new completion_info($wordcloud->course);
            $modinfo = get_fast_modinfo($wordcloud->course);
            $cm = $modinfo->instances['collabwordcloud'][$wordcloud->activity->id];
            if ($completion->is_enabled($cm) && $wordcloud->activity->completionwords) {
                $completion->update_state($cm,COMPLETION_COMPLETE);
            }
            redirect($PAGE->url);
        } else {
            echo 'Error: Missing words';
        }
        return $form->render();
    } else if (!$form->is_validated()) {
        return $form->render();
    }
    
    return false;
}

////////////////////////////////////////////////////////////////////////////////
// Course reset API                                                           //
////////////////////////////////////////////////////////////////////////////////

/**
 * Extends the course reset form with wordcloud specific settings.
 *
 * @param MoodleQuickForm $mform
 */
function collabwordcloud_reset_course_form_definition($mform) {
    
    $mform->addElement('header', 'collabwordcloudheader', get_string('modulenameplural', 'mod_collabwordcloud'));
    
    $mform->addElement('advcheckbox', 'reset_collabwordcloud_submissions', get_string('resetsubmissions', 'mod_collabwordcloud'));
    $mform->addHelpButton('reset_collabwordcloud_submissions', 'resetsubmissions', 'mod_collabwordcloud');
}

/**
 * Provides default values for the collabwordcloud settings in the course reset form.
 *
 * @param stdClass $course The course to be reset.
 */
function collabwordcloud_reset_course_form_defaults(stdClass $course) {
    
    $defaults = array(
        'reset_collabwordcloud_submissions' => 1
    );
    
    return $defaults;
}

/**
 * Performs the reset of all wordcloud instances in the course.
 *
 * @param stdClass $data The actual course reset settings.
 * @return array List of results, each being array[(string)component, (string)item, (string)error]
 */
function collabwordcloud_reset_userdata(stdClass $data) {
    global $CFG, $DB;
    
    require_once($CFG->dirroot.'/mod/collabwordcloud/collabwordcloud.php');
    
    $status = array();
    
    if (empty($data->reset_collabwordcloud_submissions)) {
        return $status;
    }
    
    $wordclouds = $DB->get_records('collabwordcloud', array('course' => $data->courseid));
    
    if (empty($wordclouds)) {
        return $status;
    }
    $course = $DB->get_record('course', array('id' => $data->courseid), '*', MUST_EXIST);
    
    foreach ($wordclouds as $wordcloud) {
        $wc = new collabwordcloud($wordcloud->id);
        $wc->reset_cloud();
        
        $status[] = array(
            'component' => get_string('modulenameplural', 'collabwordcloud'),
            'item' => get_string('resetsubmissions', 'mod_collabwordcloud').' ('.$wordcloud->id.')',
            'error' => false,
        );
    }
    
    return $status;
}
