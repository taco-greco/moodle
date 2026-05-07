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
 * Update reactions and difficulty track settings on a course module then redirect.
 *
 * @package    block_point_view
 * @copyright  2023 Astor Bizard
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');

require_login();
require_sesskey();

global $DB;

if (optional_param('submitbutton', false, PARAM_RAW) !== false
        && optional_param('cancel', false, PARAM_RAW) === false) {
    $blockinstanceid = required_param('blockinstanceid', PARAM_INT);

    $contextid = $DB->get_record('block_instances', [ 'id' => $blockinstanceid ])->parentcontextid;
    require_capability('moodle/block:edit', context::instance_by_id($contextid));

    $cmid = required_param('cmid', PARAM_INT);

    $blockrecord = $DB->get_record('block_instances', [ 'id' => $blockinstanceid, 'blockname' => 'point_view' ]);

    if ($blockrecord !== false) {
        $blockinstance = block_instance('point_view', $blockrecord);
        // Params are optional because some instances have reactions or tracks disabled.
        if (($reactions = optional_param('enablereactions', null, PARAM_BOOL)) !== null) {
            $blockinstance->config->{'moduleselectm' . $cmid} = $reactions ? $cmid : 0;
        }
        if (($track = optional_param('difficultytrack', null, PARAM_INT)) !== null) {
            $blockinstance->config->{'difficulty_' . $cmid} = $track;
        }
        $blockinstance->instance_config_commit();
    }
}

redirect(required_param('returnurl', PARAM_RAW));
