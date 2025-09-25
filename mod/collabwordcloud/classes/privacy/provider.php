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
 * Privacy class for requesting user data.
 *
 * @package    mod_collabwordcloud
 * @category   privacy
 * @copyright  2023 DNE - Ministere de l'Education Nationale
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_collabwordcloud\privacy;

use \core_privacy\local\metadata\collection;
use \core_privacy\local\request\writer;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\approved_userlist;

defined('MOODLE_INTERNAL') || die();

/**
 * Implementation of the privacy subsystem plugin provider for the collabwordcloud activity module.
 *
 * @package     mod_collabwordcloud
 * @category    privacy
 * @copyright   2021 Reseau-Canope
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements 
        \core_privacy\local\metadata\provider,
        \core_privacy\local\request\plugin\provider,
        \core_privacy\local\request\core_userlist_provider {

    /**
     * Returns meta data about this system.
     *
     * @param   collection     $items The initialised collection to add items to.
     * @return  collection     A listing of user data stored through this system.
     */
    public static function get_metadata(collection $items) : collection {
 
        // Here you will add more items into the collection.
        $items->add_database_table('collabwordcloud_words', [
            'groupid' => 'privacy:metadata:collabwordcloud_words:groupid',
            'userid' => 'privacy:metadata:collabwordcloud_words:userid',
            'word' => 'privacy:metadata:collabwordcloud_words:word',
            'timecreated' => 'privacy:metadata:collabwordcloud_words:timecreated',
            'timemodified' => 'privacy:metadata:collabwordcloud_words:timemodified',
        ], 'privacy:metadata:collabwordcloud_words');

        return $items;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param   int         $userid     The user to search.
     * @return  contextlist   $contextlist  The contextlist containing the list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid) : contextlist {
        $contextlist = new \core_privacy\local\request\contextlist();

        $params = [
            'modname'       => 'collabwordcloud',
            'contextlevel'  => CONTEXT_MODULE,
            'userid'        => $userid,
        ];

        // Users who have entered words
        $sql = "SELECT c.id
                FROM {context} c
                  JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {collabwordcloud} wc ON wc.id = cm.instance
                  JOIN {collabwordcloud_words} wcw ON wcw.wcid = wc.id
                WHERE wcw.userid = :userid
        ";
        $contextlist->add_from_sql($sql, $params);
        return $contextlist;
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param   approved_contextlist    $contextlist    The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist)) {
            return;
        }

        $user = $contextlist->get_user();
        $userid = $user->id;

        list($contextsql, $contextparams) = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);
        $params = $contextparams;

        // collabwordcloud_words
        $sql = "SELECT
                    wcw.id AS wordid,
                    c.id AS contextid,
                    cm.id as coursemoduleid,
                    wcw.userid AS userid,
                    wcw.word AS word,
                    wcw.timecreated AS timecreated,
                    wcw.timemodified AS timemodified
                  FROM {context} c
                  JOIN {course_modules} cm ON cm.id = c.instanceid
                  JOIN {collabwordcloud} wc ON wc.id = cm.instance
                  JOIN {collabwordcloud_words} wcw ON wcw.wcid = wc.id
                 WHERE (
                    wcw.userid = :userid AND
                    c.id {$contextsql}
                )
        ";
        $params['userid'] = $userid;
        $words = $DB->get_records_sql($sql, $params);

        $wordsByWordcloud = array();
        foreach ($words as $word) {
            $wordsByWordcloud[$word->coursemoduleid][] = [
                'userid' => $word->userid,
                'word' => $word->word,
                'timecreated' => transform::datetime($word->timecreated),
                'timemodified' => transform::datetime($word->timemodified),
            ];
        }

        foreach($wordsByWordcloud as $key => $words) {
            writer::with_context(\context_module::instance($key))
            ->export_data(["collabwordcloud_words"], (object) $words);
        }

    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param   context   $context   The specific context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;
 
        if ($context->contextlevel != CONTEXT_MODULE) {
            return;
        }

        $cm = get_coursemodule_from_id('collabwordcloud', $context->instanceid);
        if (!$cm) {
            return;
        }
     
        $DB->set_field('collabwordcloud_words', 'userid', 0, ['wcid' => $cm->instance]);
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param   approved_contextlist    $contextlist    The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;
 
        if (empty($contextlist->count())) {
            return;
        }

        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            $instanceid = $DB->get_field('course_modules', 'instance', ['id' => $context->instanceid], MUST_EXIST);
            $DB->set_field('collabwordcloud_words', 'userid', 0, ['wcid' => $instanceid, 'userid' => $userid]);
        }
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param   userlist    $userlist   The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if (!is_a($context, \context_module::class)) {
            return;
        }

        $params = [
            'instanceid'    => $context->instanceid,
            'modulename'    => 'collabwordcloud',
        ];

        // users who have entered words
        $sql = "SELECT wcw.userid
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modulename
                  JOIN {collabwordcloud} wc ON wc.id = cm.instance
                  JOIN {collabwordcloud_words} wcw ON wcw.wcid = wc.id
                 WHERE cm.id = :instanceid";

        $userlist->add_from_sql('userid', $sql, $params);
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param   approved_userlist       $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;
 
        $context = $userlist->get_context();
        $cm = $DB->get_record('course_modules', ['id' => $context->instanceid]);
        $wordcloud = $DB->get_record('collabwordcloud', ['id' => $cm->instance]);

        list($userinsql, $userinparams) = $DB->get_in_or_equal($userlist->get_userids(), SQL_PARAMS_NAMED);
        $params = array_merge(['wcid' => $wordcloud->id], $userinparams);
        $sql = "wcid = :wcid AND userid {$userinsql}";

        // we anonymize with user 0 
        $DB->set_field_select('collabwordcloud_words', 'userid', 0, $sql, $params);
    }
}