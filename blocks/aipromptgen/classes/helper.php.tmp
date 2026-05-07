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

namespace block_aipromptgen;

use core_competency\api as competency_api;
use grade_outcome;
use stdClass;

/**
 * Helper class for AI Prompt Generator block.
 *
 * @package    block_aipromptgen
 * @copyright  2025 AI4Teachers
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class helper
{
    /**
     * Get list of formatted competencies and outcomes for a course.
     *
     * @param int $courseid The course ID.
     * @return array List of strings (competency/outcome names + descriptions).
     */
    public static function get_course_competencies_and_outcomes(int $courseid): array {
        $competencies = [];

        // 1. Gather Gradebook Outcomes (if enabled).
        global $CFG;
        if (!empty($CFG->enableoutcomes)) {
            require_once($CFG->libdir . '/gradelib.php');
            require_once($CFG->libdir . '/grade/grade_outcome.php');

            $seenoutcomes = [];

            // Local outcomes.
            if (class_exists('grade_outcome') && method_exists('grade_outcome', 'fetch_all_local')) {
                $locals = grade_outcome::fetch_all_local($courseid);
                if (!empty($locals) && is_array($locals)) {
                    foreach ($locals as $o) {
                        $text = self::format_outcome($o);
                        if ($text !== '') {
                            $competencies[] = $text;
                            if (!empty($o->id)) {
                                $seenoutcomes[(int) $o->id] = true;
                            }
                        }
                    }
                }
            }

            // Global outcomes.
            if (class_exists('grade_outcome') && method_exists('grade_outcome', 'fetch_all_global')) {
                $globals = grade_outcome::fetch_all_global();
                if (!empty($globals) && is_array($globals)) {
                    foreach ($globals as $o) {
                        if (!empty($o->id) && isset($seenoutcomes[(int) $o->id])) {
                            continue;
                        }
                        $text = self::format_outcome($o);
                        if ($text !== '') {
                            $competencies[] = $text;
                        }
                    }
                }
            }
        }

        // 2. Gather CBE Competencies (core_competency).
        $coursecompetencies = [];
        if (class_exists('\\core_competency\\api')) {
            if (method_exists('\\core_competency\\api', 'list_course_competencies')) {
                $coursecompetencies = competency_api::list_course_competencies($courseid);
            } else if (method_exists('\\core_competency\\api', 'list_course_competencies_in_course')) {
                $coursecompetencies = competency_api::list_course_competencies_in_course($courseid);
            }
        }

        // Toolbar LP fallback.
        if (empty($coursecompetencies)) {
            $toollpapi = '\\tool_lp\\api';
            if (class_exists($toollpapi) && method_exists($toollpapi, 'list_course_competencies')) {
                $coursecompetencies = $toollpapi::list_course_competencies($courseid);
            }
        }

        foreach ($coursecompetencies as $cc) {
            $comp = self::resolve_competency_object($cc);
            if ($comp) {
                $competencies[] = self::format_competency($comp);
            }
        }

        // 3. Fallback: Module-level competencies if course-level list is empty.
        if (empty($competencies)) {
            $competencies = self::get_module_competencies_fallback($courseid);
        }

        // 4. Final Fallback: Direct DB query.
        if (empty($competencies)) {
            $competencies = self::get_competencies_from_db($courseid);
        }

        // Unique and sort.
        if (!empty($competencies)) {
            $competencies = array_unique($competencies);
            \core_collator::asort($competencies);
            $competencies = array_values($competencies);
        }

        return $competencies;
    }

    /**
     * Get course topics (sections) and a structured list of lessons (activities).
     *
     * @param stdClass $course The course object.
     * @return array Tuple [$topics (array of strings), $lessonoptions (array of groups)].
     */
    public static function get_course_content(stdClass $course): array {
        $topics = [];
        $lessonoptions = [];

        try {
            $modinfo = get_fast_modinfo($course);
            foreach ($modinfo->get_section_info_all() as $section) {
                $name = '';
                if (!empty($section->name)) {
                    $name = $section->name;
                } else {
                    $name = get_section_name($course, $section);
                }
                $name = trim(format_string($name));

                if ($name !== '' && !in_array($name, $topics, true)) {
                    $topics[] = $name;
                }

                // Build lesson options group.
                $group = ['text' => $name, 'options' => []];
                if ($name !== '') {
                    // Option value = Clean Name, Option Label = Decorated Name.
                    $group['options'][] = [
                        'value' => $name,
                        'label' => '📘 ' . $name,
                    ];
                }

                foreach ($modinfo->get_cms() as $cm) {
                    if (!$cm->uservisible) {
                        continue;
                    }
                    if ((int) $cm->sectionnum !== (int) $section->section) {
                        continue;
                    }
                    $cmname = trim(format_string($cm->name));
                    if ($cmname === '') {
                        continue;
                    }

                    $icon = self::get_module_icon($cm->modname);
                    // Use standard space indentation for the select/picker option text.
                    // Option value = Clean Name, Option Label = Indented Decorated Name.
                    $group['options'][] = [
                        'value' => $cmname,
                        'label' => '    ' . $icon . ' ' . $cmname,
                    ];
                }

                if (!empty($group['options'])) {
                    $lessonoptions[] = $group;
                }
            }
        } catch (\Throwable $e) {
            debugging('block_aipromptgen helper::get_course_content failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }

        return [$topics, $lessonoptions];
    }

    /**
     * Resolve legacy grade_outcome object to string.
     *
     * @param object $o The grade outcome object.
     * @return string The formatted outcome string.
     */
    private static function format_outcome($o): string {
        $name = '';
        if (!empty($o->shortname)) {
            $name = format_string($o->shortname);
        } else if (!empty($o->fullname)) {
            $name = format_string($o->fullname);
        }
        $name = trim((string) $name);
        if ($name === '') {
            return '';
        }
        $desc = '';
        if (!empty($o->description)) {
            $desc = trim(strip_tags(format_text($o->description, FORMAT_HTML)));
        }
        return $name . ($desc !== '' ? ' — ' . $desc : '');
    }

    /**
     * Resolve competency linkage object to actual competency.
     *
     * @param mixed $cc The competency linkage object or array.
     * @return \core_competency\competency|object|null The competency object or null.
     */
    private static function resolve_competency_object($cc) {
        $competencyid = null;
        if (is_object($cc)) {
            if (method_exists($cc, 'get')) {
                $competencyid = $cc->get('competencyid');
            } else if (property_exists($cc, 'competencyid')) {
                $competencyid = $cc->competencyid;
            } else if (method_exists($cc, 'get_competencyid')) {
                $competencyid = $cc->get_competencyid();
            }
        } else if (is_array($cc) && isset($cc['competencyid'])) {
            $competencyid = $cc['competencyid'];
        }

        if (empty($competencyid)) {
            return null;
        }

        if (class_exists('\\core_competency\\api') && method_exists('\\core_competency\\api', 'read_competency')) {
            return competency_api::read_competency($competencyid);
        }

        $toollpapi = '\\tool_lp\\api';
        if (class_exists($toollpapi) && method_exists($toollpapi, 'read_competency')) {
            return $toollpapi::read_competency($competencyid);
        }

        return null;
    }

    /**
     * Format a competency object to string.
     *
     * @param object $comp The competency object.
     * @return string The formatted competency string.
     */
    private static function format_competency($comp): string {
        $shortname = method_exists($comp, 'get') ? (string) $comp->get('shortname')
            : ((isset($comp->shortname) ? (string) $comp->shortname : ''));
        $idnumber = method_exists($comp, 'get') ? (string) $comp->get('idnumber')
            : ((isset($comp->idnumber) ? (string) $comp->idnumber : ''));

        $descraw = method_exists($comp, 'get') ? $comp->get('description')
            : (isset($comp->description) ? $comp->description : '');
        $descfmt = method_exists($comp, 'get') ? ($comp->get('descriptionformat') ?? FORMAT_HTML)
            : (isset($comp->descriptionformat) ? $comp->descriptionformat : FORMAT_HTML);

        $name = trim(format_string($shortname !== '' ? $shortname : $idnumber));
        if ($name === '') {
            $id = method_exists($comp, 'get') ? (string) $comp->get('id') : (isset($comp->id) ? (string) $comp->id : '');
            $name = $id !== '' ? $id : get_string('competency', 'core_competency');
        }

        $desc = '';
        if (!empty($descraw)) {
            $desc = trim(strip_tags(format_text($descraw, $descfmt)));
        }

        return $name . ($desc !== '' ? ' — ' . $desc : '');
    }

    /**
     * Fallback to module-level competencies.
     *
     * @param int $courseid The course ID.
     * @return array List of formatted competencies.
     */
    private static function get_module_competencies_fallback(int $courseid): array {
        $competencies = [];
        $seen = [];
        $modinfo = get_fast_modinfo($courseid);
        foreach ($modinfo->get_cms() as $cm) {
            if (!$cm->uservisible) {
                continue;
            }
            $links = [];
            try {
                if (class_exists('\\core_competency\\api')) {
                    $links = competency_api::list_course_module_competencies($cm->id);
                }
            } catch (\Throwable $ignore) {
                continue;
            }

            foreach ($links as $link) {
                // Logic assumes $link is a course_module_competency object.
                $comp = self::resolve_competency_object($link);
                if ($comp) {
                    // Deduplicate by ID if possible, but formatted string is simpler here.
                    // Better to deduplicate by ID if we can access it.
                    $cid = method_exists($comp, 'get') ? $comp->get('id') : (isset($comp->id) ? $comp->id : 0);
                    if ($cid && isset($seen[$cid])) {
                        continue;
                    }
                    $text = self::format_competency($comp);
                    $competencies[] = $text;
                    if ($cid) {
                        $seen[$cid] = true;
                    }
                }
            }
        }
        return $competencies;
    }

    /**
     * Fallback to direct DB queries for competencies.
     *
     * @param int $courseid The course ID.
     * @return array List of formatted competencies.
     */
    private static function get_competencies_from_db(int $courseid): array {
        global $DB;
        $competencies = [];
        try {
            // Course-level.
            $sql = 'SELECT c.id, c.shortname, c.idnumber, c.description, c.descriptionformat
                      FROM {competency} c
                      JOIN {competency_coursecomp} cc ON cc.competencyid = c.id
                     WHERE cc.courseid = :cid';
            $recs = $DB->get_records_sql($sql, ['cid' => $courseid]);
            foreach ($recs as $r) {
                $competencies[] = self::format_competency($r);
            }

            // Module-level if empty.
            if (empty($competencies)) {
                $sql2 = 'SELECT DISTINCT c.id, c.shortname, c.idnumber, c.description, c.descriptionformat
                           FROM {competency} c
                           JOIN {competency_modulecomp} mc ON mc.competencyid = c.id
                           JOIN {course_modules} cm ON cm.id = mc.cmid
                          WHERE cm.course = :cid2';
                $recs2 = $DB->get_records_sql($sql2, ['cid2' => $courseid]);
                foreach ($recs2 as $r) {
                    $competencies[] = self::format_competency($r);
                }
            }
        } catch (\Throwable $e) {
            debugging('block_aipromptgen helper::get_competencies_from_db failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
        return $competencies;
    }

    /**
     * Get emoji icon for module type.
     *
     * @param string $modname The module name.
     * @return string The emoji icon.
     */
    private static function get_module_icon(string $modname): string {
        switch ($modname) {
            case 'assign':
                return '📝';
            case 'book':
                return '📚';
            case 'chat':
                return '💬';
            case 'choice':
                return '☑️';
            case 'feedback':
                return '🗳️';
            case 'folder':
                return '📁';
            case 'forum':
                return '💬';
            case 'glossary':
                return '📔';
            case 'h5pactivity':
                return '▶️';
            case 'label':
                return '🏷️';
            case 'lesson':
                return '📘';
            case 'lti':
                return '🌐';
            case 'page':
                return '📄';
            case 'quiz':
                return '❓';
            case 'resource':
                return '📄';
            case 'scorm':
                return '🎯';
            case 'survey':
                return '📊';
            case 'url':
                return '🔗';
            case 'wiki':
                return '🧭';
            case 'workshop':
                return '🛠️';
            default:
                return '📄';
        }
    }
}
