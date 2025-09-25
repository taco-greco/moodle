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
 * Defines mod_collabwordcloud data generator class.
 *
 * @package    mod_collabwordcloud
 * @category   test
 * @copyright  2023 DNE - Ministere de l'Education Nationale
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Wordcloud mod data generator class.
 *
 * @package    mod_collabwordcloud
 * @category   test
 * @copyright  2021 Reseau-Canope
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_collabwordcloud_generator extends testing_module_generator {

    public function create_instance($record = null, array $options = null) {
        $instructionEditor = array(
            'itemid' => 1,
            'text' => 'hello there !'
        );
        $record = (object)(array)$record;

        if (!isset($record->wordsallowed)) {
            $record->wordsallowed = 4;
        }

        if (!isset($record->wordsrequired)) {
            $record->wordsrequired = 3;
        }

        if (!isset($record->completionwords)) {
            $record->completionwords = 1;
        }

        if (!isset($record->instructionseditor)) {
            $record->instructionseditor = $instructionEditor;
        }

        if (!isset($record->timestart)) {
            $record->timestart = time()-500;
        }

        if (!isset($record->timeend)) {
            $record->timeend = time()+500;
        }

        return parent::create_instance($record, $options);
    }
}