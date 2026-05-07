<?php
// This file is part of Moodle - http://moodle.org/.
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

namespace block_aipromptgen\form;

defined('MOODLE_INTERNAL') || die();

// Ensure $CFG is in scope before using it in a namespaced file.
global $CFG;
require_once($CFG->libdir . '/formslib.php');

/**
 * Prompt builder form for the AI for Teachers block.
 *
 * @package    block_aipromptgen
 * @author     Boban Blagojevic
 * @copyright  2025 AI4Teachers
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class prompt_form extends \moodleform {
    /**
     * Define the form fields and defaults.
     *
     * @return void
     */
    protected function definition() {
        $mform = $this->_form;
        $subjectdefault = $this->_customdata['subjectdefault'] ?? '';
        $coursename = $this->_customdata['coursename'] ?? '';

        // Main title at the beginning of the form.
        // Form title (use plugin name string).
        $mform->addElement('header', 'aipromptgen_title', \get_string('pluginname', 'block_aipromptgen'));
        $mform->setExpanded('aipromptgen_title');

        $templates = $this->_customdata['templates'] ?? [];
        if (!empty($templates)) {
            $templateoptions = ['' => \get_string('form:custom_prompt', 'block_aipromptgen')];
            $promptsdata = [];
            foreach ($templates as $idx => $t) {
                // Use string index for options to avoid breaking HTML with long text and quotes.
                $templateoptions[(string)$idx] = $t->title;
                $promptsdata[(string)$idx] = $t->prompt;
            }
            $templateelems = [];
            $templateelems[] = $mform->createElement(
                'select',
                'template_select',
                '',
                $templateoptions,
                [
                    'id' => 'ai4t-template-select',
                    'data-prompts' => json_encode($promptsdata, JSON_HEX_TAG | JSON_HEX_APOS),
                ]
            );
            $hint = '<span class="text-muted small ml-2">'
                . \get_string('form:templates_hint', 'block_aipromptgen') . '</span>';
            $templateelems[] = $mform->createElement('static', 'template_hint', '', $hint);
            $mform->addGroup(
                $templateelems,
                'templategroup',
                \get_string('form:templates_label', 'block_aipromptgen'),
                ' ',
                false
            );
            $mform->setType('templategroup[template_select]', PARAM_INT);
        }

        $mform->addElement('text', 'subject', \get_string('form:subjectlabel', 'block_aipromptgen'));
        $mform->setType('subject', PARAM_TEXT);
        // Set a default only if provided and not empty.
        if (is_string($subjectdefault)) {
            $subjectdefault = trim($subjectdefault);
            if ($subjectdefault !== '') {
                $mform->setDefault('subject', $subjectdefault);
            }
        }
        // Tooltip and placeholder showing the course name inside the control.
        $subjectattrs = [
            'id' => 'id_subject',
            // Explicit name attribute for compatibility with custom JS selectors or validation.
            'name' => 'subject',
            'title' => \get_string('help:subjectchange', 'block_aipromptgen'),
        ];
        if (is_string($coursename) && trim($coursename) !== '') {
            $subjectattrs['placeholder'] = \format_string($coursename);
        }
        $mform->getElement('subject')->setAttributes($subjectattrs);
        // Subject is optional; prompt generation will fall back to course name if left blank.

        // Student age/grade: free text with a Browse button to open a modal for exact age or range selection.
        $ageelems = [];
        $ageelems[] = $mform->createElement('text', 'agerange', '', [
            'id' => 'id_agerange',
            'size' => 20,
            'title' => \get_string('help:agerange', 'block_aipromptgen'),
            'placeholder' => \get_string('placeholder:agerange', 'block_aipromptgen'),
        ]);
        $ageelems[] = $mform->createElement('button', 'agebrowse', \get_string('form:lessonbrowse', 'block_aipromptgen'), [
            'type' => 'button',
            'id' => 'ai4t-age-browse',
            'class' => 'btn btn-secondary btn-sm',
            'title' => 'Browse age or range',
        ]);
        $mform->addGroup($ageelems, 'agegroup', \get_string('form:agerangelabel', 'block_aipromptgen'), ' ', false);
        $mform->setType('agerange', PARAM_TEXT);
        $mform->setDefault('agerange', '15');

        // Topic (editable text with suggestions + a Browse button that opens a modal picker).
        $topics = $this->_customdata['topics'] ?? [];
        $topicelems = [];
        // Use empty string for label instead of null to avoid strrpos() deprecation inside QuickForm.
        $topicelems[] = $mform->createElement('text', 'topic', '', [
            'id' => 'id_topic',
            'size' => 60,
            'list' => 'ai4t-topiclist',
            'title' => \get_string('help:topic', 'block_aipromptgen'),
        ]);
        $topicelems[] = $mform->createElement('button', 'topicbrowse', \get_string('form:topicbrowse', 'block_aipromptgen'), [
            'type' => 'button',
            'id' => 'ai4t-topic-browse',
            'class' => 'btn btn-secondary btn-sm',
            'title' => \get_string('form:topicbrowse', 'block_aipromptgen'),
        ]);
        $mform->addGroup($topicelems, 'topicgroup', \get_string('form:topiclabel', 'block_aipromptgen'), ' ', false);
        $mform->setType('topic', PARAM_TEXT);
        // Topic is optional; users can generate a prompt without selecting a topic.
        // Attach HTML5 datalist for suggestions while allowing free text.
        if (!empty($topics) && is_array($topics)) {
            $optionshtml = '';
            foreach ($topics as $t) {
                $optionshtml .= '<option>' . \s($t) . '</option>';
            }
            $mform->addElement('html', '<datalist id="ai4t-topiclist">' . $optionshtml . '</datalist>');
        }

        // Lesson title: keep as textbox, with a Browse button to open a modal picker.
        $lessonelems = [];
        // Use empty string for label instead of null to avoid strrpos() deprecation inside QuickForm.
        $lessonelems[] = $mform->createElement('text', 'lesson', '', [
            'id' => 'id_lesson',
            'size' => 60,
            'title' => \get_string('help:lesson', 'block_aipromptgen'),
        ]);
        $lessonelems[] = $mform->createElement('button', 'lessonbrowse', \get_string('form:lessonbrowse', 'block_aipromptgen'), [
            'type' => 'button',
            'id' => 'ai4t-lesson-browse',
            'class' => 'btn btn-secondary btn-sm',
            'title' => \get_string('help:lessonbrowse', 'block_aipromptgen'),
        ]);
        $mform->addGroup($lessonelems, 'lessongroup', \get_string('form:lessonlabel', 'block_aipromptgen'), ' ', false);
        $mform->setType('lesson', PARAM_TEXT);

        // Class type: free text with a Browse button to open a modal picker.
        $classgroupelems = [];
        $classgroupelems[] = $mform->createElement('text', 'classtype', '', [
            'id' => 'id_classtype',
            'size' => 40,
            'title' => \get_string('help:classtype', 'block_aipromptgen'),
        ]);
        $classgroupelems[] = $mform->createElement(
            'button',
            'classtypebrowse',
            get_string('form:lessonbrowse', 'block_aipromptgen'),
            [
                'type' => 'button',
                'id' => 'ai4t-classtype-browse',
                'class' => 'btn btn-secondary btn-sm',
                'title' => \get_string('form:classtypebrowse', 'block_aipromptgen'),
            ]
        );
        $mform->addGroup($classgroupelems, 'classtypegroup', get_string('form:class_typelabel', 'block_aipromptgen'), ' ', false);
        $mform->setType('classtype', PARAM_TEXT);

        // Number of classes: HTML5 numeric spinner (min 1, step 1), default 1.
        $mform->addElement('text', 'lessoncount', get_string('form:lessoncount', 'block_aipromptgen'), [
            'id' => 'id_lessoncount',
            'size' => 6,
            // QuickForm "text" may render as type="text"; we'll enforce type via updateAttributes and JS fallback.
            'min' => 1,
            'step' => 1,
            'title' => get_string('form:lessoncount', 'block_aipromptgen'),
            'inputmode' => 'numeric',
            'pattern' => '[0-9]*',
        ]);
        $mform->setType('lessoncount', PARAM_INT);
        $mform->setDefault('lessoncount', 1);
        // Enforce HTML5 number type where supported.
        if ($mform->elementExists('lessoncount')) {
            $mform->getElement('lessoncount')->updateAttributes(['type' => 'number']);
        }
        // Client-side numeric rule (helps validation even if the input type falls back to text).
        $mform->addRule('lessoncount', get_string('err_numeric', 'form'), 'numeric', null, 'client');

        // Lesson duration: choose 45 or 60 minutes (default 45).
        $mform->addElement('select', 'lessonduration', get_string('form:lessonduration', 'block_aipromptgen'), [
            45 => '45',
            60 => '60',
        ]);
        $mform->setType('lessonduration', PARAM_INT);
        $mform->setDefault('lessonduration', 45);

        // Outcomes textarea with a Browse button to pick competencies.
        $outcomeselems = [];
        $outcomeselems[] = $mform->createElement('textarea', 'outcomes', '', [
            'id' => 'id_outcomes',
            'wrap' => 'virtual', 'rows' => 6, 'cols' => 60,
            'title' => \get_string('help:outcomes', 'block_aipromptgen'),
        ]);
        $outcomeselems[] = $mform->createElement(
            'button',
            'outcomesbrowse',
            get_string('form:outcomesbrowse', 'block_aipromptgen'),
            [
                'type' => 'button',
                'id' => 'ai4t-outcomes-browse',
                'class' => 'btn btn-secondary btn-sm',
                'title' => \get_string('help:outcomesbrowse', 'block_aipromptgen'),
            ]
        );
        $mform->addGroup($outcomeselems, 'outcomesgroup', get_string('form:outcomeslabel', 'block_aipromptgen'), ' ', false);
        $mform->setType('outcomes', PARAM_TEXT);

        // Prompt language: text + Browse, plus hidden languagecode for precise mapping.
        $langgroupelems = [];
        $langgroupelems[] = $mform->createElement('text', 'language', '', [
            'id' => 'id_language',
            'size' => 40,
            'title' => \get_string('help:language', 'block_aipromptgen'),
        ]);
        $langgroupelems[] = $mform->createElement(
            'button',
            'languagebrowse',
            get_string('form:lessonbrowse', 'block_aipromptgen'),
            [
                'type' => 'button',
                'id' => 'ai4t-language-browse',
                'class' => 'btn btn-secondary btn-sm',
                'title' => 'Browse languages',
            ]
        );
        $mform->addGroup($langgroupelems, 'languagegroup', get_string('form:language', 'block_aipromptgen'), ' ', false);
        $mform->setType('language', PARAM_TEXT);
        $mform->addElement('hidden', 'languagecode');
        $mform->setType('languagecode', PARAM_ALPHANUMEXT);

        // Prompt purpose: text + Browse.
        $purposeelems = [];
        $purposeelems[] = $mform->createElement('text', 'purpose', '', [
            'id' => 'id_purpose',
            'size' => 40,
            'title' => \get_string('help:purpose', 'block_aipromptgen'),
        ]);
        $purposeelems[] = $mform->createElement(
            'button',
            'purposebrowse',
            get_string('form:lessonbrowse', 'block_aipromptgen'),
            [
                'type' => 'button',
                'id' => 'ai4t-purpose-browse',
                'class' => 'btn btn-secondary btn-sm',
                'title' => 'Browse purposes',
            ]
        );
        $mform->addGroup($purposeelems, 'purposegroup', get_string('form:purpose', 'block_aipromptgen'), ' ', false);
        $mform->setType('purpose', PARAM_TEXT);

        // Audience: text + Browse.
        $audienceelems = [];
        $audienceelems[] = $mform->createElement('text', 'audience', '', [
            'id' => 'id_audience',
            'size' => 40,
            'title' => \get_string('help:audience', 'block_aipromptgen'),
        ]);
        $audienceelems[] = $mform->createElement(
            'button',
            'audiencebrowse',
            get_string('form:lessonbrowse', 'block_aipromptgen'),
            [
                'type' => 'button',
                'id' => 'ai4t-audience-browse',
                'class' => 'btn btn-secondary btn-sm',
                'title' => 'Browse audiences',
            ]
        );
        $mform->addGroup($audienceelems, 'audiencegroup', get_string('form:audience', 'block_aipromptgen'), ' ', false);
        $mform->setType('audience', PARAM_TEXT);

        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);
        $mform->getElement('courseid')->setAttributes([
            'id' => 'id_courseid',
            'name' => 'courseid',
        ]);

        // Standard action buttons are removed as prompt generation is now dynamic client-side.
        // Users can use the 'Back to course' button in the template if they wish to leave.

        // No inline script here; handled on the page to open a modal and populate the textbox.
    }
}
