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
 * Provides some custom settings for the wordcloud module.
 *
 * @package     mod_collabwordcloud
 * @category    admin
 * @copyright   2023 DNE - Ministere de l'Education Nationale
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {

    $settings->add(new admin_setting_heading('collabwordcloud_config',
        '<strong>'.get_string('pluginconfig', 'collabwordcloud').'</strong>', '')
    );

    $settings->add(new admin_setting_configtext('collabwordcloud/wordmaxlenght', get_string('wordmaxlenght', 'collabwordcloud'),
        get_string('wordmaxlenghtsetting', 'collabwordcloud'), 30, PARAM_INT)
    );

    $settings->add(new admin_setting_configtext('collabwordcloud/maxwordsallowed', get_string('maxwordsallowed', 'collabwordcloud'),
        get_string('maxwordsallowedsetting', 'collabwordcloud'), 10, PARAM_INT)
    );

}
