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
 * Displays information about all the wordcloud modules in the requested course.
 *
 * @package    mod_collabwordcloud
 * @copyright  2023 DNE - Ministere de l'Education Nationale
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(dirname(dirname(__DIR__)).'/config.php');

$id = optional_param('id', null, PARAM_INT);    // Course ID.
if ($id == null || !($course = $DB->get_record('course', array('id' => $id)))) {
    print_error('course ID is incorrect');
}

require_login($course->id);

$context = context_course::instance($course->id);

$PAGE->set_url('/mod/collabwordcloud/index.php', array('id' => $id));
$PAGE->set_pagelayout('incourse');
$PAGE->navbar->add(get_string('modulenameplural', 'collabwordcloud'));
$PAGE->set_title(get_string('modulename', 'collabwordcloud'));
$PAGE->set_heading($course->fullname);


echo $OUTPUT->header();
echo $OUTPUT->heading(format_string(get_string('modulenameplural', 'collabwordcloud')));

$wordclouds = new \mod_collabwordcloud\mod_collabwordcloud_index($context, $course);

echo $wordclouds->view();

echo $OUTPUT->footer();