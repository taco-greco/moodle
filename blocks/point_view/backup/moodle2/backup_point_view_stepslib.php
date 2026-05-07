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
 * Define backup structure for additional DB tables this block uses.
 *
 * @package    block_point_view
 * @copyright  2022 Astor Bizard
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Backup structure definition for point_view block.
 *
 * @copyright  2022 Astor Bizard
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_point_view_block_structure_step extends backup_block_structure_step {
    /**
     * {@inheritDoc}
     * @see backup_structure_step::define_structure()
     */
    protected function define_structure() {
        $pointview = new backup_nested_element('point_view');

        if ($this->get_setting_value('users')) {
            $reactions = new backup_nested_element('reactions');
            $reaction = new backup_nested_element('reaction', [ 'id' ], [ 'courseid', 'cmid', 'userid', 'vote' ]);
            $reaction->set_source_table('block_point_view', [ 'courseid' => backup::VAR_COURSEID ]);
            $reaction->annotate_ids('user', 'userid');
            $reactions->add_child($reaction);
            $pointview->add_child($reactions);
        }

        return $this->prepare_block_structure($pointview);
    }
}
