<?php
// This file is part of VPL for Moodle - http://vpl.dis.ulpgc.es/
//
// VPL for Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// VPL for Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with VPL for Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Define restore structure for additional DB tables this block uses.
 *
 * @package    block_point_view
 * @copyright  2022 Astor Bizard
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Restore structure definition for point_view block.
 *
 * @copyright  2022 Astor Bizard
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_point_view_block_structure_step extends restore_structure_step {
    /**
     * Reaction data to be added after restore, as it references modules that may not be restored yet.
     * @var array
     */
    protected $pendingdatainsertions = [];

    /**
     * {@inheritDoc}
     * @see restore_structure_step::define_structure()
     */
    protected function define_structure() {
        $paths = [];

        if ($this->get_setting_value('users')) {
            $paths[] = new restore_path_element('reaction', '/block/point_view/reactions/reaction');
        }

        return $paths;
    }

    /**
     * Process restore of a single reaction entry.
     * @param mixed $data
     */
    protected function process_reaction($data) {
        // Postpone reaction votes process to be called in restore_point_view_block_task::after_restore()
        // because modules may be restored after the block.
        $this->pendingdatainsertions[] = (object) $data;
    }

    /**
     * Actually process restore of all reactions entries that are pending.
     * To be called only after restore of referenced modules.
     */
    public function process_reactions_after_restore() {
        global $DB;
        foreach ($this->pendingdatainsertions as $data) {
            $newcmid = $this->get_mappingid('course_module', $data->cmid);
            $newcourseid = $this->get_mappingid('course', $data->courseid);
            if ($newcmid && $newcourseid) {
                $data->courseid = $newcourseid;
                $data->cmid = $newcmid;
                $data->userid = $this->get_mappingid('user', $data->userid);
                // Insert the reaction vote record.
                $DB->insert_record('block_point_view', $data);
            }
        }
    }
}
