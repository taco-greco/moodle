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
 * Define all the restore steps that will be used by the restore_url_activity_task
 *
 * @package     mod_collabwordcloud
 * @category    backup
 * @copyright   2023 DNE - Ministere de l'Education Nationale
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Class restore_collabwordcloud_activity_structure_step.
 *
 * Structure step to restore one collabwordcloud activity.
 *
 * @package     mod_collabwordcloud
 * @category    backup
 * @copyright   2021 Reseau-Canope
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_collabwordcloud_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {

        $paths = array();
        $paths[] = new restore_path_element('collabwordcloud', '/activity/collabwordcloud');
        
        $paths[] = new restore_path_element('collabwordcloud_words', '/activity/collabwordcloud/collabwordcloud_words');

        // Return the paths wrapped into standard activity structure
        return $this->prepare_activity_structure($paths);
    }
    
    protected function process_collabwordcloud($data) {
        global $DB;
        
        $data = (object)$data;
        $data->course = $this->get_courseid();
        
        // insert the wordcloud record
        $newitemid = $DB->insert_record('collabwordcloud', $data);
        
        // immediately after inserting "activity" record, call this
        $this->apply_activity_instance($newitemid);
    }
    
    protected function process_collabwordcloud_words($data) {
        global $DB;
        
        $data = (object)$data;
        $data->wcid = $this->get_new_parentid('collabwordcloud');
        
        // insert the wordcloud_words record
        $DB->insert_record('collabwordcloud_words', $data);
        
        // immediately after inserting "activity" record, call this
        //$this->apply_activity_instance($newitemid);
    }

    protected function after_execute() {
        // Add wordcloud related files, no need to match by itemname (just internally handled context)
        $this->add_related_files('mod_collabwordcloud', 'intro', null);
        $this->add_related_files('mod_collabwordcloud', 'instructions', null);
    }
	
}
