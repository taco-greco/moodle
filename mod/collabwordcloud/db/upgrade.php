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
 * This file keeps track of upgrades to
 * the wordcloud module
 *
 * Sometimes, changes between versions involve
 * alterations to database structures and other
 * major things that may break installations.
 *
 * The upgrade function in this file will attempt
 * to perform all the necessary actions to upgrade
 * your older installation to the current version.
 *
 * If there's something it cannot do itself, it
 * will tell you what you need to do.
 *
 * The commands in here will all be database-neutral,
 * using the methods of database_manager class
 *
 * Please do not forget to use upgrade_set_timeout()
 * before any action that may take longer time to finish.
 *
 * @package     mod_collabwordcloud
 * @copyright   2023 DNE - Ministere de l'Education Nationale
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function xmldb_collabwordcloud_upgrade($oldversion) {
    global $DB;

    if ($oldversion < 2021030403) {
        $dbman = $DB->get_manager();
        
        // Add column
        $table = new xmldb_table('collabwordcloud');
        $field = new xmldb_field('wordsallowed', XMLDB_TYPE_INTEGER, '10', null,
            XMLDB_NOTNULL, null, 0, 'instructions');
        
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        
        // wordcloud savepoint reached.
        upgrade_mod_savepoint(true, 2021030403, 'collabwordcloud');
    }
    if ($oldversion < 2021042302) {
        $dbman = $DB->get_manager();
        
        $table = new xmldb_table('collabwordcloud');
        $field = new xmldb_field('wordmaxlenght', XMLDB_TYPE_INTEGER, '10', null,
            XMLDB_NOTNULL, null, 0);
        
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }
        
        // wordcloud savepoint reached.
        upgrade_mod_savepoint(true, 2021042302, 'collabwordcloud');
    }

    return true;
}
