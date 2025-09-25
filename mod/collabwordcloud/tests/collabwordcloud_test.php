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
 * @package    mod_collabwordcloud
 * @category   test
 * @copyright  2023 DNE - Ministere de l'Education Nationale
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Class collabwordcloud_testcase.
 *
 * @package    mod_collabwordcloud
 * @category   test
 * @copyright  2021 Reseau-Canope
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class collabwordcloud_testcase extends advanced_testcase {

    /** @var stdClass $course New course created to hold the assignments */
    protected $course = null;

    /** @var stdClass $wordcloud New wordcloud created */
    protected $wordcloud = null;

    /** @var stdClass $student New participant created */
    protected $student = null;

    /** @var stdClass $teacher New teacher created */
    protected $teacher = null;

    protected function setUp(){
        global $CFG;
        require_once($CFG->dirroot . '/mod/collabwordcloud/collabwordcloud.php');
        
        $this->resetAfterTest();
        
        // Create course.
        $this->course = $this->getDataGenerator()->create_course();

        // Create users.
        $this->student = $this->getDataGenerator()->create_and_enrol($this->course, 'student');
        $this->teacher = $this->getDataGenerator()->create_and_enrol($this->course, 'teacher');

        $this->setUser($this->student);
        $this->setUser($this->teacher);
        
        // Create wordcloud activity.
        $instructionEditor = array(
            'itemid' => 4,
            'text' => ''
        );
        
        $wordcloud = array(
            'course'                => $this->course->id,
            'name'                  => 'wordcloud name',
            'introformat'           => '1',
            'wordsallowed'          => 5,
            'wordsrequired'         => 4,
            'instructionseditor'    => $instructionEditor,
            'timestart'             => time()-500,
            'timeend'               => time()+500
        );
        
        $module = $this->getDataGenerator()->create_module('collabwordcloud', $wordcloud);
        $this->wordcloud = new collabwordcloud($module->id);
    }
    
    /**
     * Test function add_word()
     */
    public function test_add_word(){
        // Add correct word.
        $mot = "test";
        $result = $this->wordcloud->add_word($this->student->id, $mot);
        $this->assertEquals(true, $result);
        
        // Add word which already exist.
        $result = $this->wordcloud->add_word($this->student->id, $mot);
        $this->assertEquals(collabwordcloud::ERROR_WORD_ALREADY_EXIST, $result);
        
        // Add empty word.
        $mot = "";
        $result = $this->wordcloud->add_word($this->student->id, $mot);
        $this->assertEquals(collabwordcloud::ERROR_INVALID_WORD, $result);
        
        // Add too long word.
        $limit = get_config('collabwordcloud', 'wordmaxlenght'); 
        $mot = 'a';
        for($i = 0 ; $i < $limit ; $i++){
            $mot .= 'a';
        }
        
        $result = $this->wordcloud->add_word($this->student->id, $mot);
        $this->assertEquals(collabwordcloud::ERROR_WORD_TOO_LONG, $result);
        
        // Add word with spaces only.
        $mot = "     ";
        $result = $this->wordcloud->add_word($this->student->id, $mot);
        $this->assertEquals(collabwordcloud::ERROR_INVALID_WORD, $result);
    }

    /**
     *  Test function add_words()
     */
    public function test_add_words() {
        // Add no word.
        $words = array();
        $result = $this->wordcloud->add_words($this->student->id, $words);
        $this->assertEquals(true, $result);
        
        // Add empty word
        $words = array("test", "");
        $result = $this->wordcloud->add_words($this->student->id, $words);
        $this->assertEquals(true, $result);
        
        // Add multiple words.
        $words = array("firstword", "secondword", "thirdword");
        $result = $this->wordcloud->add_words($this->student->id, $words);
        $this->assertEquals(true, $result);
    }
    
    /**
     *  Test function get_user_words()
     */
    public function test_get_user_words() {
        $wordlist = array("word", "word2", "word3");
        
        foreach ($wordlist as $m){
            $this->wordcloud->add_word($this->student->id, $m);
        }
        
        $lines = $this->wordcloud->get_cloud_users_words();
        $motsUser = array();

        foreach ($lines as $line){
            $motsUser[] = $line->word;
        }
        
       $this->assertEquals($wordlist, $motsUser);
    }
    
    /**
     *  Test function rename_word()
     */
    public function test_rename_word() {
        // Rename a word which already exist.
        $word = "word1";
        $newword = "word2";
        $this->wordcloud->add_word($this->student->id, $word);
        $result = $this->wordcloud->rename_word($word,$newword);
        $this->assertEquals(true, $result);
        
        // Rename a word with one which already exist.
        $result = $this->wordcloud->rename_word($newword, $newword);
        $this->assertEquals(collabwordcloud::ERROR_NEW_WORD_IS_THE_SAME, $result);
        
        // Rename a word which doesnot exist.
        $result = $this->wordcloud->rename_word("motmot", $newword);
        $this->assertEquals(collabwordcloud::ERROR_NO_WORD_FOUND, $result);
    }

    /**
     *  Test function remove_word()
     */
    public function test_remove_word() {
        // Add correct word.
        $mot = "remove";
        $result = $this->wordcloud->add_word($this->student->id, $mot);
        $this->assertEquals(true, $result);

        // Delete created word.
        $result = $this->wordcloud->remove_word($mot);
        $this->assertEquals(true, $result);
    }

    /**
     *  Test function get_cloud_words()
     */
    public function test_get_cloud_words() {
        // Add list of correct words.
        $wordlist = array("word", "word2", "word3");

        foreach ($wordlist as $m){
            $this->wordcloud->add_word($this->student->id, $m);
        }

        $result = $this->wordcloud->get_cloud_words();
        $this->assertEquals(count($wordlist), count($result));
    }

}