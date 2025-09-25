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
 * Basic unit tests for mod_collabwordcloud.
 *
 * @package     mod_collabwordcloud
 * @copyright   2023 DNE - Ministere de l'Education Nationale
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG, $DB;
require_once($CFG->dirroot . '/mod/collabwordcloud/collabwordcloud.php');
require_once($CFG->dirroot . '/mod/collabwordcloud/lib.php');

/**
 * Class mod_collabwordcloud_lib_testcase.
 *
 * @package     mod_collabwordcloud
 * @copyright   2021 Reseau-Canope
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_collabwordcloud_lib_testcase extends advanced_testcase {
    
    /**
     * Setup
     * {@inheritDoc}
     * @see \PHPUnit\Framework\TestCase::setUp()
     */
    protected function setUp(){
        
        $this->resetAfterTest();
    }

    public function test_collabwordcloud_get_completion_state() {

        // Course creation.
        $course = $this->getDataGenerator()->create_course();

        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->setUser($student);

        // Wordcloud module creation.
        $wordcloud = array(
            'course'                => $course->id,
            'name'                  => 'collabwordcloud test completion',
            'wordsallowed'          => 8,
            'wordsrequired'         => 4,
            'timestart'             => time()-500,
            'timeend'               => time()+500,
        );

        $module = $this->getDataGenerator()->create_module('collabwordcloud', $wordcloud);

        // Check the course-module is correct.
        $cm = get_coursemodule_from_instance('collabwordcloud', $module->id);

        $wordcloud = new collabwordcloud($module->id);

        // Add several valid words less than the number of required words.
        $words = array("word1", "word2", "word3");
        $result = $wordcloud->add_words($student->id, $words);
        $this->assertEquals(true, $result);

        $this->assertFalse(wordcloud_get_completion_state($course, $cm, $student->id, true));

        // Add a valid word equal to the number of required words.
        $result = $wordcloud->add_word($student->id, "word4");
        $this->assertEquals(true, $result);

        $this->assertTrue(wordcloud_get_completion_state($course, $cm, $student->id, true));
    }
}
