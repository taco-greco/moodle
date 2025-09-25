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
 * Display a wordcloud with this own words created by course users.
 *
 * @package     mod_collabwordcloud
 * @copyright   2023 DNE - Ministere de l'Education Nationale
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(dirname(dirname(__DIR__)).'/config.php');
require_once($CFG->dirroot.'/mod/collabwordcloud/collabwordcloud.php');

$id = required_param('id', PARAM_INT);
$g = optional_param('g', 0, PARAM_INT);

$cm = get_coursemodule_from_id('collabwordcloud', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);

require_login($course, true, $cm);

$params = array();
if ($g > 0) {
    $params['g'] = $g;
}
$params['id'] = $id;

$PAGE->set_url('/mod/collabwordcloud/view.php', $params);
$PAGE->set_heading(format_string($course->fullname));

$wordcloud = new collabwordcloud($cm->instance);
$PAGE->set_title($wordcloud->activity->name);

$subform = collabwordcloud_process_submit($wordcloud,$g);

$cmcontext = context_module::instance($cm->id);

$completion = new completion_info($course);
$completion->set_module_viewed($cm);

$event = \mod_collabwordcloud\event\course_module_viewed::create([
    'objectid' => $cm->instance,
    'context' => $cmcontext,
]);
$event->add_record_snapshot('course', $course);
$event->trigger();

echo $OUTPUT->header();

$renderer = $PAGE->get_renderer('mod_collabwordcloud');

$output = '';

if (method_exists($OUTPUT,'add_encart_activity')) {
    $heading = $OUTPUT->heading(format_string($wordcloud->activity->name), 2);
    echo $OUTPUT->add_encart_activity($heading);
} else {
    $output .= '<div class="wc_name">'.$wordcloud->get_name().'</div>';
}

$instructions = $wordcloud->get_instructions();
if (strlen($instructions) > 0  ) {
    $output .= '<div class="wc_instructions">'.$instructions.'</div>';
}

$closed = false;
if (!$wordcloud->is_started()) {
    $output .= get_string('activitynotstarted','mod_collabwordcloud', strftime(get_string('strftimedaydatetime','core_langconfig'), $wordcloud->activity->timestart));
    $closed = true;
}

if ($wordcloud->has_ended()) {
    $output .= get_string('activityclosed','mod_collabwordcloud', strftime(get_string('strftimedaydatetime','core_langconfig'), $wordcloud->activity->timeend));
    $closed = true;
}

if ($closed && !$wordcloud->is_editor()) {
    echo $output;
    echo $OUTPUT->footer();
    exit;
}

$apiurl = (new moodle_url('/mod/collabwordcloud/api.php'))->out();

$params_js = array(
    'cmid' => $wordcloud->cm->id,
    'selector' => '#region-main .wordcloudcontainer',
    'apiurl' => $apiurl,
    'editor' => $wordcloud->is_editor()
);

$PAGE->requires->js_call_amd('mod_collabwordcloud/collabwordcloud','showWordcloud', $params_js);

$d3url = new moodle_url('/mod/collabwordcloud/js/d3js/d3.min.js');
$d3cloudurl = new moodle_url('/mod/collabwordcloud/js/d3cloud/d3.cloud.js');

echo '<script src="'.$d3url.'"></script>';
echo '<script src="'.$d3cloudurl.'"></script>';

if (has_capability('mod/collabwordcloud:submitword', $cmcontext) || $wordcloud->is_editor()) {
    $cloudmod = (count($wordcloud->get_user_words($USER->id, $g)) > 0);
    
    $output .= $renderer->display_wordcloud_groupselector($wordcloud, $g);
    $output .= $renderer->display_wordcloud_submit(!$cloudmod, ($subform?$subform:''));
    $output .= $renderer->display_wordcloud_cloud();
    
    // Editor interface
    if ($wordcloud->is_editor()) {
        $output .= $renderer->display_wordcloud_editor($wordcloud, $cloudmod);
    }
    
} else {
    $output .= get_string('viewactivitynotallowed','mod_collabwordcloud');
}

echo $output;

echo $OUTPUT->footer();
