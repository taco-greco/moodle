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
 * The main mod_collabwordcloud configuration form.
 *
 * @package     mod_collabwordcloud
 * @category    form
 * @copyright   2023 DNE - Ministere de l'Education Nationale
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once ($CFG->dirroot.'/course/moodleform_mod.php');

/**
 * Module instance settings form.
 *
 * @package     mod_collabwordcloud
 * @category    form
 * @copyright   2021 Reseau-Canope
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_collabwordcloud_mod_form extends moodleform_mod {

    /**
     * Defines forms elements.
     */
    function definition() {
        global $PAGE;

        $PAGE->force_settings_menu();

        $mform = $this->_form;
        $mform->addElement('header', 'general', get_string('general', 'form'));
        
        $mform->addElement('text', 'name', get_string('name', 'collabwordcloud'), array('size' => '64'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        
        $this->standard_intro_elements(get_string('description'));
        
        $mform->addElement('editor', 'instructionseditor', get_string('instructions', 'collabwordcloud'), array('rows' => 10),
            array('maxfiles' => EDITOR_UNLIMITED_FILES, 'noclean' => true, 'context' => $this->context, 'subdirs' => true)
        );

        $maxword_options = array();
        for ($i = 1; $i <= get_config('collabwordcloud', 'maxwordsallowed'); $i++) {
            $maxword_options[$i] = $i;
        }
        
        $mform->addElement('select', 'wordsallowed', get_string('maxwordsallowed', 'collabwordcloud'), $maxword_options);
        $mform->setType('wordsallowed', PARAM_INT);
        $mform->addRule('wordsallowed', null, 'required', null, 'client');
        $mform->setDefault('wordsallowed', 5);
        
        $mform->addElement('static', 'submitions_wont_be_altered', '', '<span>'.get_string('submitions_wont_be_altered', 'collabwordcloud').'</span>');
        
        $mform->addElement('select', 'wordsrequired', get_string('wordrequired', 'collabwordcloud'), $maxword_options);
        $mform->setType('wordsrequired', PARAM_INT);
        $mform->addRule('wordsrequired', null, 'required', null, 'client');
        $mform->setDefault('wordsrequired', 1);
        
        $mform->addElement('header', 'availabilityhdr', get_string('availability'));
        $mform->addElement('date_time_selector', 'timestart', get_string('allowsubmitionfrom', 'collabwordcloud'), ['optional' => true]);
        $mform->addElement('date_time_selector', 'timeend', get_string('allowsubmitionupto', 'collabwordcloud'), ['optional' => true]);
        
        $this->standard_coursemodule_elements();

        $this->add_action_buttons(true, false, null);
        
    }

    /**
     * Set up the instruction and completion checkboxes which aren't part of standard data.
     *
     * @param array $default_values
     */
    function data_preprocessing(&$default_values) {
        if ($this->current->instance) {
            $draftitemid = file_get_submitted_draft_itemid('instructions');
            $default_values['instructionseditor']['text'] = file_prepare_draft_area($draftitemid, $this->context->id,
                                'mod_collabwordcloud', 'instructions', 0,
                                array('maxfiles' => EDITOR_UNLIMITED_FILES, 'noclean'=>true, 'context'=>$this->context),
                                $default_values['instructions']);
            $default_values['instructionseditor']['format'] = 1;
            $default_values['instructionseditor']['itemid'] = $draftitemid;
        } else {
            $draftitemid = file_get_submitted_draft_itemid('instructions');
            file_prepare_draft_area($draftitemid, null, 'mod_collabwordcloud', 'instructions', 0);    // no context yet, itemid not used
            $data['instructionseditor'] = array('text' => '', 'format' => editors_get_preferred_format(), 'itemid' => $draftitemid);
        }

        // We also make the default value (if you turn on the checkbox) for those
        // numbers to be 1, this will not apply unless checkbox is ticked.
        $default_values['completionwords']= (!empty($default_values['completionwords']) && $default_values['completionwords']!==0)? 1 : 0;
    }
    
    /**
     * Perform minimal validation on the settings form.
     *
     * @param array $data
     * @param array $files
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        
        if (!isset($data['wordsallowed'])) {
            $errors['wordsallowed'] = get_string('maxwordsallowed_undefined', 'collabwordcloud');
        } else if ($data['wordsallowed'] > get_config('collabwordcloud', 'maxwordsallowed')) {
            $errors['wordsallowed'] = get_string('maxwordsallowed_tomany', 'collabwordcloud');
        }
        
        if (!isset($data['wordsrequired'])) {
            $errors['wordsrequired'] = get_string('maxwordrequired_undefined', 'collabwordcloud');
        } else if ($data['wordsrequired'] > get_config('collabwordcloud', 'maxwordsallowed')) {
            $errors['wordsrequired'] = get_string('maxwordrequired_tomany', 'collabwordcloud');
        }
        
        if (isset($data['wordsrequired']) && isset($data['wordsallowed'])) {
            if ($data['wordsrequired'] > $data['wordsallowed']){
                $errors['wordsrequired'] = get_string('maxwordrequired_bigger_than_allowed', 'collabwordcloud');
            }
        }
        
        if (!empty($data['timestart']) && !empty($data['timeend'])) {
            if ($data['timeend'] < $data['timestart'] ) {
                $errors['timeend'] = get_string('timeend_before_start', 'assign');
            }
        }
        
        return $errors;
    }
    
    /**
     * Add elements for setting the custom completion rules.
     *
     * @category completion
     * @return array List of added element names, or names of wrapping group elements.
     */
    public function add_completion_rules() {
        $mform = $this->_form;
        
        $group = array(
            $mform->createElement('checkbox', 'completionwords', '', get_string('completionwords', 'collabwordcloud')),
        );
        
        $mform->addGroup($group, 'completionwordsgroup', get_string('completionwordsgroup','collabwordcloud'), array(' '), false);
        
        $mform->disabledIf('completionwords', 'completionunlocked', 'eq', 0);
        
        return array('completionwordsgroup');
    }

    /**
     * Return submitted data if properly submitted or returns NULL if validation fails or
     * if there is no submitted data.
     *
     * Do not override this method, override data_postprocessing() instead.
     *
     * @return object submitted data; NULL if not valid or not submitted or cancelled
     */
    function get_data() {
        $data = parent::get_data();
        if (!$data) {
            return $data;
        } 
        if (!empty($data->completionunlocked)) {
            // Turn off completion settings if the checkboxes aren't ticked
            $autocompletion = !empty($data->completion) && $data->completion==COMPLETION_TRACKING_AUTOMATIC;
            if (empty($data->completionwords) || !$autocompletion) {
               $data->completionwords = 0;
            }
        }
        return $data;
    }

    /**
     * Called during validation. Indicates if a module-specific completion rule is selected.
     *
     * @param array $data
     * @return bool
     */
    function completion_rule_enabled($data) {
        return !empty($data['completionwords']);
    }

}
