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
    
    // Main report page
    $ADMIN->add('local_govreporting_settings', new admin_externalpage('local_govreporting_report', 
        get_string('reportpage', 'local_govreporting'),
        new moodle_url('/local/govreporting/report.php')));
    
    // TPR certificate management page
    $ADMIN->add('local_govreporting_settings', new admin_externalpage('local_govreporting_certificates', 
        get_string('tpr_certificate_management', 'local_govreporting'),
        new moodle_url('/local/govreporting/certificates.php')));
    
    // Settings page
    $settings = new admin_settingpage('local_govreporting_config', get_string('settings', 'local_govreporting'));
    $ADMIN->add('local_govreporting_settings', $settings);
    
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
    
    // FMCSA TPR API Settings Section
    $settings->add(new admin_setting_heading(
        'local_govreporting/tpr_settings',
        get_string('tpr_settings_heading', 'local_govreporting'),
        get_string('tpr_settings_desc', 'local_govreporting')
    ));
    
    // Test mode setting
    $settings->add(new admin_setting_configcheckbox(
        'local_govreporting/tpr_testmode',
        get_string('tpr_testmode', 'local_govreporting'),
        get_string('tpr_testmode_desc', 'local_govreporting'),
        '1'
    ));
    
    // Provider location ID
    $settings->add(new admin_setting_configtext(
        'local_govreporting/tpr_provider_location_id',
        get_string('tpr_provider_location_id', 'local_govreporting'),
        get_string('tpr_provider_location_id_desc', 'local_govreporting'),
        '',
        PARAM_TEXT
    ));
    
    // Certificate notice
    $certurl = new moodle_url('/local/govreporting/certificates.php');
    $settings->add(new admin_setting_heading(
        'local_govreporting/tpr_cert_notice',
        get_string('tpr_certificate_notice', 'local_govreporting'),
        get_string('tpr_certificate_notice_desc', 'local_govreporting', $certurl->out())
    ));
}