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
 * Define all the backup steps that will be used by the backup_block_task
 * @package    block_point_view
 * @copyright  2022 Astor Bizard, 2015 Stephen Bourget, 2003 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/restore_point_view_stepslib.php');

/**
 * Specialised restore task for the point_view block (using execute_after_tasks for recoding of target activity)
 *
 * @copyright  2022 Astor Bizard, 2015 Stephen Bourget, 2003 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_point_view_block_task extends restore_block_task {
    /**
     * {@inheritDoc}
     * @see restore_block_task::define_my_settings()
     */
    protected function define_my_settings() {
    }

    /**
     * {@inheritDoc}
     * @see restore_block_task::define_my_steps()
     */
    protected function define_my_steps() {
        $this->add_step(new restore_point_view_block_structure_step('point_view_structure', 'point_view.xml'));
    }

    /**
     * {@inheritDoc}
     * @see restore_block_task::get_fileareas()
     */
    public function get_fileareas() {
        return [ 'point_views_pix' ];
    }

    /**
     * {@inheritDoc}
     * @see restore_block_task::get_configdata_encoded_attributes()
     */
    public function get_configdata_encoded_attributes() {
        return []; // No special handling of configdata.
    }

    /**
     * This function, executed after all the tasks in the plan have been executed,
     * will perform the recode of the target activities for the block.
     * This must be done here and not in normal execution steps because the activities can be restored after the block.
     */
    public function after_restore() {
        global $DB;

        // Get the blockid.
        $blockid = $this->get_blockid();

        if ($configdata = $DB->get_field('block_instances', 'configdata', [ 'id' => $blockid ])) {
            $config = unserialize(base64_decode($configdata));

            if (!empty($config)) {
                // Get the mapping and replace cmids in config.
                $newconfig = clone($config);
                foreach ($config as $key => $value) {
                    $match = null;
                    if (preg_match('/^(moduleselectm|difficulty_)(\d+)/', $key, $match)) {
                        $field = $match[1];
                        $cmid = $match[2];
                        $cmidmapping = restore_dbops::get_backup_ids_record($this->get_restoreid(), 'course_module', $cmid);
                        unset($newconfig->{$field . $cmid});
                        if ($cmidmapping) {
                            $newconfig->{$field . $cmidmapping->newitemid} = $value;
                        }
                    }
                }

                // Copy reaction votes if user data is included.
                if ($this->get_setting_value('users')) {
                    // Find restore step where some processing is done.
                    foreach ($this->get_steps() as $step) {
                        if ($step->get_name() == 'point_view_structure') {
                            $step->process_reactions_after_restore();
                        }
                    }
                }

                // Encode and save the config.
                $configdata = base64_encode(serialize($newconfig));
                $DB->set_field('block_instances', 'configdata', $configdata, [ 'id' => $blockid ]);
            }
        }

    }

    /**
     * {@inheritDoc}
     * @see restore_block_task::define_decode_contents()
     */
    public static function define_decode_contents() {
        return [];
    }

    /**
     * {@inheritDoc}
     * @see restore_block_task::define_decode_rules()
     */
    public static function define_decode_rules() {
        return [];
    }
}
