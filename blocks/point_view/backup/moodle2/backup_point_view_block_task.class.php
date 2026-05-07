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
 * Define how point_view blocks should behave on backup.
 * @package    block_point_view
 * @copyright  2022 Astor Bizard, 2003 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/backup_point_view_stepslib.php');

/**
 * Specialised backup task for the point_view block.
 *
 * @copyright  2022 Astor Bizard, 2003 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_point_view_block_task extends backup_block_task {
    /**
     * {@inheritDoc}
     * @see backup_block_task::define_my_settings()
     */
    protected function define_my_settings() {
    }

    /**
     * {@inheritDoc}
     * @see backup_block_task::define_my_steps()
     */
    protected function define_my_steps() {
        $this->add_step(new backup_point_view_block_structure_step('point_view_structure', 'point_view.xml'));
    }

    /**
     * {@inheritDoc}
     * @see backup_block_task::get_fileareas()
     */
    public function get_fileareas() {
        return [ 'point_views_pix' ];
    }

    /**
     * {@inheritDoc}
     * @see backup_block_task::get_configdata_encoded_attributes()
     */
    public function get_configdata_encoded_attributes() {
        return [];
    }

    /**
     * {@inheritDoc}
     * @param mixed $content
     * @see backup_block_task::encode_content_links()
     */
    public static function encode_content_links($content) {
        return $content; // No special encoding of links.
    }
}
