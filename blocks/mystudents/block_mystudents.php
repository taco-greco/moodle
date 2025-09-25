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
 * Block displaying all the students I have in my courses.
 *
 * This block can be used to display easily the list of all student of my courses and their affiliation.
 *
 * @package    block_mystudents
 * @copyright  2018 Namur University
 * @author     Laurence Dumortier <laurence.dumortier@unamur.be>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Block displaying all the students I have in my courses.
 * @copyright  2018 Namur University
 * @author     Laurence Dumortier <laurence.dumortier@unamur.be>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_mystudents extends block_base {
    /**
     * block initializations
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_mystudents');
    }

    /**
     * block contents
     * @return object
     */
    public function get_content() {
        global $CFG, $OUTPUT;

        if (empty($this->instance)) {
            $this->content = '';
            return $this->content;
        }

        if (!isloggedin() || isguestuser()) {
            return '';      // Never useful unless you are logged in as real users.
        }

        $this->content = new stdClass();
        $this->content->items = [];
        $this->content->icons = [];
        $this->content->footer = '';

        $icon = $OUTPUT->pix_icon('i/users', '');
        $this->content->text = '<a title="'.get_string('listofallpeople').'" href="'.
                $CFG->wwwroot.'/blocks/mystudents/view.php">'.$icon.get_string('mystudents', 'block_mystudents').'</a>';
        return $this->content;
    }

    /**
     * allow the block to have a configuration page
     *
     * @return boolean
     */
    public function has_config() {
        return false;
    }

    /**
     * allow more than one instance of the block on a page
     *
     * @return boolean
     */
    public function instance_allow_multiple() {
        return false;
    }

    /**
     * allow instances to have their own configuration
     *
     * @return boolean
     */
    public function instance_allow_config() {
        return false;
    }

    /**
     * instance specialisations (must have instance allow config true)
     *
     */
    public function specialization() {
    }

    /**
     * locations where block can be displayed
     *
     * @return array
     */
    public function applicable_formats() {
        return ['all' => false, 'my' => true];
    }

    /**
     * post install configurations
     *
     */
    public function after_install() {
    }

    /**
     * post delete configurations
     *
     */
    public function before_delete() {
    }

}
