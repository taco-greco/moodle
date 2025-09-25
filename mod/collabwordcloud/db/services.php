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
 * usagemonitor external functions and service definitions.
 *
 * @package    mod_collabwordcloud
 * @copyright  2023 DNE - Ministere de l'Education Nationale
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$services = array(
    'mod_collabwordcloud_service' => array(
        'functions' => array (
            'mod_collabwordcloud_getdata',
            'mod_collabwordcloud_getwordinfo',
            'mod_collabwordcloud_addword',
            'mod_collabwordcloud_updateword',
            'mod_collabwordcloud_simupdateword',
            'mod_collabwordcloud_removeword',
            'mod_collabwordcloud_exportdata'
        ),
        'requiredcapability' => 'mod/collabwordcloud:submitword',
        'restrictedusers' => 0,
        'enabled' => 1,
        'shortname' =>  'mod_collabwordcloud_service',
        'downloadfiles' => 0,
        'uploadfiles'  => 0
    )
);

$functions = array(
    'mod_collabwordcloud_getdata' => array(
        'classname' => 'mod_collabwordcloud_external',
        'methodname' => 'getdata',
        'classpath' => 'mod/collabwordcloud/externallib.php',
        'description' => 'Get a wordcloud data',
        'type' => 'read',
        'capabilities' => 'mod/collabwordcloud:submitword',
        'ajax' => true
    ),
    'mod_collabwordcloud_getwordinfo' => array(
        'classname' => 'mod_collabwordcloud_external',
        'methodname' => 'getwordinfo',
        'classpath' => 'mod/collabwordcloud/externallib.php',
        'description' => 'Get a wordcloud word information',
        'type' => 'read',
        'capabilities' => 'mod/collabwordcloud:manageword',
        'ajax' => true
    ),
    'mod_collabwordcloud_addword' => array(
        'classname' => 'mod_collabwordcloud_external',
        'methodname' => 'addword',
        'classpath' => 'mod/collabwordcloud/externallib.php',
        'description' => 'Add a word to the wordcloud',
        'type' => 'write',
        'capabilities' => 'mod/collabwordcloud:manageword',
        'ajax' => true
    ),
    'mod_collabwordcloud_updateword' => array(
        'classname' => 'mod_collabwordcloud_external',
        'methodname' => 'updateword',
        'classpath' => 'mod/collabwordcloud/externallib.php',
        'description' => 'Update a wordcloud word',
        'type' => 'write',
        'capabilities' => 'mod/collabwordcloud:manageword',
        'ajax' => true
    ),
    'mod_collabwordcloud_simupdateword' => array(
        'classname' => 'mod_collabwordcloud_external',
        'methodname' => 'simupdateword',
        'classpath' => 'mod/collabwordcloud/externallib.php',
        'description' => 'Simulate the update of a wordcloud word',
        'type' => 'read',
        'capabilities' => 'mod/collabwordcloud:manageword',
        'ajax' => true
    ),
    'mod_collabwordcloud_removeword' => array(
        'classname' => 'mod_collabwordcloud_external',
        'methodname' => 'removeword',
        'classpath' => 'mod/collabwordcloud/externallib.php',
        'description' => 'Remove a wordcloud word',
        'type' => 'write',
        'capabilities' => 'mod/collabwordcloud:manageword',
        'ajax' => true
    ),
    'mod_collabwordcloud_exportdata' => array(
        'classname' => 'mod_collabwordcloud_external',
        'methodname' => 'exportdata',
        'classpath' => 'mod/collabwordcloud/externallib.php',
        'description' => 'Export a wordcloud data as a CSV file',
        'type' => 'read',
        'capabilities' => 'mod/collabwordcloud:manageword',
        'ajax' => true
    ),
);
