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
 * Event triggered when a user removes their reaction on a module.
 * @package    block_point_view
 * @copyright  2025 Astor Bizard
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_point_view\event;

/**
 * Event triggered when a user removes their reaction on a module.
 *
 * Required data for event creation: relateduserid, other['blockid'], other['cmid'].
 * @copyright  2025 Astor Bizard
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class reaction_removed extends \core\event\base {
    /**
     * Init method.
     */
    protected function init() {
        $this->data['crud'] = 'd';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
    }

    /**
     * Returns description of what happened.
     * @return string
     */
    public function get_description() {
        return "The user with id '$this->relateduserid' has removed their reaction" .
            " on the course module with id '" . $this->other['cmid'] . "'.";
    }

    /**
     * Returns localised general event name.
     * @return string
     */
    public static function get_name() {
        return get_string('eventreactionremoved', 'block_point_view');
    }

    /**
     * Returns relevant URL.
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/blocks/point_view/overview.php', [
                'instanceid' => $this->other['blockid'],
                'courseid' => $this->contextinstanceid,
        ]);
    }

    /**
     * Custom validation.
     * @throws \coding_exception
     */
    protected function validate_data() {
        parent::validate_data();

        if (!isset($this->relateduserid)) {
            throw new \coding_exception('The \'relateduserid\' must be set.');
        }

        if (!array_key_exists('blockid', $this->other)) {
            throw new \coding_exception('The \'blockid\' value must be set in other.');
        }

        if (!array_key_exists('cmid', $this->other)) {
            throw new \coding_exception('The \'cmid\' value must be set in other.');
        }
    }

    /**
     * {@inheritDoc}
     * @see \core\event\base::get_other_mapping()
     */
    public static function get_other_mapping() {
        return [
                'blockid' => [ 'db' => 'block_instances', 'restore' => 'block_instance' ],
                'cmid' => [ 'db' => 'course_modules', 'restore' => 'course_module' ],
        ];
    }
}
