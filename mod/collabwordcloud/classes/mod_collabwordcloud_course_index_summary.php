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
 * @package     mod_collabwordcloud
 * @copyright   2023 DNE - Ministere de l'Education Nationale
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_collabwordcloud;
defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/accesslib.php');


/**
 * Class wordcloud_course_index_summary
 */
class mod_collabwordcloud_course_index_summary implements \renderable {
    public $wordclouds = array();
    public $usesections = false;
    public $courseformatname = '';

    public function __construct($usesections, $courseformatname) {
        $this->usesections = $usesections;
        $this->courseformatname = $courseformatname;
    }

    public function add_wordcloud_info($cmid, $cmname, $sectionname) {
        $this->wordclouds[] = array('cmid'=>$cmid,
            'cmname' => $cmname,
            'sectionname' => $sectionname
        );
    }
}