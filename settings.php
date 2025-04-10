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
 * Plugin administration pages are defined here.
 *
 * @package     local_govreporting
 * @category    admin
 * @copyright   2025 YourName <your.email@example.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// This is the main admin page for the plugin.
if ($hassiteconfig) {
    $ADMIN->add('localplugins', new admin_category('local_govreporting_settings', get_string('pluginname', 'local_govreporting')));
    $ADMIN->add('local_govreporting_settings', new admin_externalpage('local_govreporting_report', 
        get_string('reportpage', 'local_govreporting'),
        new moodle_url('/local/govreporting/report.php')));
    
    // Settings page
    $settings = new admin_settingpage('local_govreporting_config', get_string('settings', 'local_govreporting'));
    $ADMIN->add('local_govreporting_settings', $settings);
    
    // API endpoint setting
    $settings->add(new admin_setting_configtext(
        'local_govreporting/apiendpoint',
        get_string('apiendpoint', 'local_govreporting'),
        get_string('apiendpointdesc', 'local_govreporting'),
        'https://api.government-example.org/students/submit',
        PARAM_URL
    ));
    
    // API key setting
    $settings->add(new admin_setting_configtext(
        'local_govreporting/apikey',
        get_string('apikey', 'local_govreporting'),
        get_string('apikeydesc', 'local_govreporting'),
        '',
        PARAM_TEXT
    ));
    
    // Minimum score percentage
    $settings->add(new admin_setting_configtext(
        'local_govreporting/minscore',
        get_string('minscore', 'local_govreporting'),
        get_string('minscoredesc', 'local_govreporting'),
        '80',
        PARAM_INT
    ));
    
    // Students per page setting
    $settings->add(new admin_setting_configtext(
        'local_govreporting/perpage',
        get_string('perpage', 'local_govreporting'),
        get_string('perpagedesc', 'local_govreporting'),
        '10',
        PARAM_INT
    ));
}