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
 * Define all the backup steps that will be used by the backup_collabwordcloud_activity_task.
 *
 * @package     mod_collabwordcloud
 * @category    backup
 * @copyright   2023 DNE - Ministere de l'Education Nationale
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Class backup_collabwordcloud_activity_structure_step.
 *
 * Define the complete collabwordcloud structure for backup, with file and id annotations.
 *
 * @package     mod_collabwordcloud
 * @category    backup
 * @copyright   2021 Reseau-Canope
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_collabwordcloud_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {
        
        $userinfo = $this->get_setting_value('userinfo');
        
        // Define each element separated
        $wordcloud = new backup_nested_element('collabwordcloud', array('id'), array(
            'name', 'intro', 'introformat', 'instructions', 'wordsallowed', 'wordsrequired',
            'timestart', 'timeend', 'completionwords', 'timecreated', 'timemodified'));

        // Define sources
        $wordcloud->set_source_table('collabwordcloud', array('id' => backup::VAR_ACTIVITYID));

        if ($userinfo) {
            $wordcloud_words = new backup_nested_element('collabwordcloud_words', array('id'),
                array('wcid', 'groupid', 'userid', 'word', 'timecreated', 'timemodified'));
            
            $wordcloud->add_child($wordcloud_words);
            
            $wordcloud_words->set_source_table('collabwordcloud_words', array('wcid' => backup::VAR_ACTIVITYID));
            
            // Define id annotations
            $wordcloud_words->annotate_ids('user', 'userid');
            $wordcloud_words->annotate_ids('group', 'groupid');
        }

        // Define file annotations
        $wordcloud->annotate_files('mod_collabwordcloud', 'intro', null);
        $wordcloud->annotate_files('mod_collabwordcloud', 'instructions', null);

        // Return the root element (wordcloud), wrapped into standard activity structure
        return $this->prepare_activity_structure($wordcloud);
    }
}
