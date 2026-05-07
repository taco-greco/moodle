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
 * A form to edit reactions and difficulty tracks settings per-activity, displayed within the block.
 *
 * @package    block_point_view
 * @copyright  2023 Astor Bizard
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_point_view;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

/**
 * In-block, per-activity form to edit reactions and difficulty tracks settings definition.
 *
 * @copyright  2023 Astor Bizard
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class module_form extends \moodleform {
    /**
     * {@inheritDoc}
     * @see \moodleform::definition()
     */
    public function definition() {
        $mform = $this->_form;

        $cmid = $this->_customdata['cmid'];
        $blockconfig = $this->_customdata['blockconfig'];

        $mform->addElement('hidden', 'cmid', $cmid);
        $mform->setType('cmid', PARAM_INT);

        $mform->addElement('hidden', 'returnurl', $this->_customdata['returnurl']);
        $mform->setType('returnurl', PARAM_RAW);

        $mform->addElement('hidden', 'blockinstanceid', $this->_customdata['blockinstanceid']);
        $mform->setType('blockinstanceid', PARAM_INT);

        $group = [];

        if (!empty($blockconfig->enable_point_views)) {
            // Checkbox for reactions.
            $group[] =& $mform->createElement( 'advcheckbox', 'enablereactions',
                    get_string('reactions', 'block_point_view'), null,
                    );
        }

        if (!empty($blockconfig->enable_difficultytracks)) {
            // Difficulty track.
            $group[] =& $mform->createElement( 'html',
                    '<span id="track_' . $cmid . '" class="block_point_view track selecttrack"></span>' );

            // Difficulty track select.
            $group[] =& $mform->createElement( 'select', 'difficultytrack', '',
                    [
                            get_string('nonetrack', 'block_point_view'),
                            get_string('greentrack', 'block_point_view'),
                            get_string('bluetrack', 'block_point_view'),
                            get_string('redtrack', 'block_point_view'),
                            get_string('blacktrack', 'block_point_view'),
                    ],
                    [ 'class' => 'moduletrackselect', 'data-id' => $cmid ]
                    );
        }

        if (!empty($group)) {
            $mform->addGroup( $group, 'point_view_for_module', '', '', false );
            $this->add_action_buttons(true, get_string('save'));
        }
    }
}
