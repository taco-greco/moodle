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
 * Point of View external lib
 *
 * @package    block_point_view
 * @copyright  2020 Quentin Fombaron, 2021 Astor Bizard
 * @author     Quentin Fombaron <q.fombaron@outlook.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/externallib.php');

/**
 * Class block_point_view_external
 *
 * @package block_point_view
 * @copyright  2020 Quentin Fombaron
 * @author     Quentin Fombaron <q.fombaron@outlook.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_point_view_external extends external_api {
    /**
     * Parameters definition for update_db.
     */
    public static function update_db_parameters() {
        return new external_function_parameters(
            [
                'func' => new external_value(PARAM_TEXT, 'function name to call', VALUE_REQUIRED),
                'courseid' => new external_value(PARAM_INT, 'id of course', VALUE_REQUIRED),
                'cmid' => new external_value(PARAM_INT, 'id of course module', VALUE_DEFAULT, 0),
                'vote' => new external_value(PARAM_INT, 'id of vote', VALUE_DEFAULT, 0),
            ]
        );
    }

    /**
     * Update the database after added, updated or removed vote on a course module, or a full reset of course reactions.
     *
     * @param string $func Function name of database update ('update' or 'reset').
     * @param int $courseid Course ID
     * @param int $cmid Course Module ID
     * @param int $vote Vote ID
     * @return string Log message
     */
    public static function update_db(string $func, int $courseid, int $cmid, int $vote) {
        global $DB, $USER;

        [ $func, $courseid, $cmid, $vote ] = self::validate_parameters(self::update_db_parameters(), [
                'func' => $func,
                'courseid' => $courseid,
                'cmid' => $cmid,
                'vote' => $vote,
        ]);

        $table = 'block_point_view';
        $coursecontext = context_course::instance($courseid);

        switch ($func) {
            case 'update':
                $blockrecord = $DB->get_record('block_instances', [
                        'blockname' => 'point_view',
                        'parentcontextid' => $coursecontext->id,
                ], '*', MUST_EXIST);
                $blockinstance = block_instance('point_view', $blockrecord);

                $canreact = isset($blockinstance->config->enable_point_views)
                        && $blockinstance->config->enable_point_views
                        && isset($blockinstance->config->{'moduleselectm' . $cmid})
                        && $blockinstance->config->{'moduleselectm' . $cmid}
                        && get_fast_modinfo($courseid, $USER->id)->cms[$cmid]->uservisible
                        && has_capability('block/point_view:addreaction', $blockinstance->context, $USER->id);

                if (!$canreact) {
                    throw new moodle_exception('reactionsunavailable', 'block_point_view');
                }

                $dbparams = [
                        'userid' => $USER->id,
                        'courseid' => $courseid,
                        'cmid' => $cmid,
                ];

                if ($vote === 0) {
                    $DB->delete_records($table, $dbparams);
                    $eventclass = '\block_point_view\event\reaction_removed';
                } else {
                    $currentvote = $DB->get_record($table, $dbparams);
                    if ($currentvote === false) {
                        $dbparams['vote'] = $vote;
                        $DB->insert_record($table, $dbparams);
                        $eventclass = '\block_point_view\event\reaction_added';
                    } else {
                        $currentvote->vote = $vote;
                        $DB->update_record($table, $currentvote);
                        $eventclass = '\block_point_view\event\reaction_updated';
                    }
                }

                $event = $eventclass::create([
                        'relateduserid' => $USER->id,
                        'other' => [ 'blockid' => $blockrecord->id, 'cmid' => $cmid ],
                        'context' => $coursecontext,
                ]);
                $event->trigger();

                break;
            case 'reset':
                // Reset all reactions of course or module.
                require_capability('moodle/site:manageblocks', $coursecontext);
                if ($cmid == 0) {
                    // Delete for all the course.
                    $DB->delete_records($table, [ 'courseid' => $courseid ]);
                } else {
                    // Delete only for a module.
                    $DB->delete_records($table, [ 'courseid' => $courseid, 'cmid' => $cmid ]);
                }
                break;
            case 'cleanup':
                // Cleanup reactions of users no longer enrolled in course.
                require_capability('moodle/site:manageblocks', $coursecontext);
                $users = get_enrolled_users($coursecontext, '', 0, 'u.id');
                [ $notin, $sqlparams ] = $DB->get_in_or_equal(array_column($users, 'id'), SQL_PARAMS_QM, '', false);
                $sqlparams[] = $courseid;
                $DB->delete_records_select($table, "userid $notin AND courseid = ?", $sqlparams);
                break;
            default:
                break;
        }
        return '';
    }

    /**
     * Returns a log message
     *
     * @return external_description
     */
    public static function update_db_returns() {
        return new external_value(PARAM_TEXT, 'Log message');
    }

    /**
     * Parameters definition for delete_custom_pix.
     */
    public static function delete_custom_pix_parameters() {
        return new external_function_parameters([
                'contextid' => new external_value(PARAM_INT, 'id of context', VALUE_REQUIRED),
                'courseid' => new external_value(PARAM_INT, 'id of course', VALUE_REQUIRED),
                'draftitemid' => new external_value(PARAM_INT, 'id of draft file area', VALUE_REQUIRED),
        ]);
    }

    /**
     * Delete custom emoji for a block instance.
     *
     * @param int $contextid Context in which files are stored.
     * @param int $courseid Course id.
     * @param int $draftitemid Draft area id.
     * @return boolean true on success
     */
    public static function delete_custom_pix($contextid, $courseid, $draftitemid) {
        global $USER;
        [ $contextid, $courseid, $draftitemid ] = self::validate_parameters(self::delete_custom_pix_parameters(), [
                'contextid' => $contextid,
                'courseid' => $courseid,
                'draftitemid' => $draftitemid,
        ]);
        require_capability('moodle/site:manageblocks', context_course::instance($courseid));
        $enclosingcoursecontext = context::instance_by_id($contextid)->get_course_context();
        if ($enclosingcoursecontext === null || $enclosingcoursecontext->instanceid != $courseid) {
            throw new moodle_exception('invalidcontext', 'error');
        }
        $fs = get_file_storage();
        $success = $fs->delete_area_files($contextid, 'block_point_view', 'point_views_pix');
        $success = $success && $fs->delete_area_files(context_user::instance($USER->id)->id, 'user', 'draft', $draftitemid);
        return $success;
    }

    /**
     * Return true on success
     */
    public static function delete_custom_pix_returns() {
        return new external_value(PARAM_BOOL, 'Whether operation was successful', VALUE_REQUIRED);
    }
}
