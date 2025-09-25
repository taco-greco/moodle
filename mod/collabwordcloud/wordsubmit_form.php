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
 * Defines the form that allow student to submit as many words as required.
 *
 * @package     mod_collabwordcloud
 * @category    form
 * @copyright   2023 DNE - Ministere de l'Education Nationale
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->libdir.'/formslib.php');

/**
 * A form that allow student to submit as many words as required.
 *
 * @package     mod_collabwordcloud
 * @category    form
 * @copyright   2021 Reseau-Canope
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_collabwordcloud_wordsubmit_form extends moodleform {

    /**
     * Defines forms elements.
     */
    protected function definition() {
        $mform = $this->_form;

        $wordsallowed = $this->_customdata['wordsallowed'];
        $wordsrequired = $this->_customdata['wordsrequired'];
        $wordmaxlenght = get_config('collabwordcloud', 'wordmaxlenght');
        
        for ($i = 1; $i <= $wordsallowed; $i++) {
            $mform->addElement('text', 'word_'.$i, get_string('word_nb', 'collabwordcloud').' '.$i, array('maxlength'=>$wordmaxlenght,'size'=>$wordmaxlenght+1));
            $mform->setType('word_'.$i, PARAM_NOTAGS);
            if ($wordsrequired >= $i) {
                $mform->addRule('word_'.$i, get_string('missingword', 'collabwordcloud'), 'required', null, 'server');
            }
        }

        $this->add_action_buttons(false, get_string('send', 'collabwordcloud'));
        $mform->setDisableShortforms();
        $mform->disable_form_change_checker();
    }

    /**
     * Perform minimal validation on the form.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        
        $words = array();
        for ($i=1; $i <= $this->_customdata['wordsallowed']; $i++) {
            $word = str_replace('"','', trim($data['word_'.$i]));
            if ($word == '') {
                continue;
            } else if (in_array($word,$words)) {
                $errors['word_'.$i] = get_string('word_already_used', 'collabwordcloud');
            } else {
                $words[] = $word;
            }
        }

        return $errors;
    }
}
