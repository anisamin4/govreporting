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
 * Library of interface functions and constants for Government Reporting module.
 *
 * @package     local_govreporting
 * @copyright   2025 YourName <your.email@example.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Add navigation node to the Moodle navigation menu
 * 
 * @param navigation_node $navigation The navigation node to extend
 * @param stdClass $course The course to object for the report
 * @param stdClass $context The context of the course
 */
function local_govreporting_extend_navigation_course($navigation, $course, $context) {
    // Only add this settings item on non-site course pages.
    if ($context->contextlevel != CONTEXT_COURSE || $course->id == SITEID) {
        return;
    }

    // Only let users with the appropriate capability see this.
    if (has_capability('local/govreporting:viewreport', $context)) {
        $url = new moodle_url('/local/govreporting/report.php');
        $navigation->add(
            get_string('pluginname', 'local_govreporting'),
            $url,
            navigation_node::TYPE_SETTING,
            null,
            null,
            new pix_icon('i/report', '')
        );
    }
}

/**
 * Add menu items to the admin tree navigation
 *
 * @param settings_navigation $nav The settings navigation object
 * @param context $context The context of the course
 */
function local_govreporting_extend_settings_navigation($nav, $context) {
    global $CFG, $PAGE;
    
    // Only let users with the appropriate capability see this settings item.
    if (has_capability('local/govreporting:viewreport', $context)) {
        if ($settingnode = $nav->find('courseadmin', navigation_node::TYPE_COURSE)) {
            $url = new moodle_url('/local/govreporting/report.php');
            $node = navigation_node::create(
                get_string('pluginname', 'local_govreporting'),
                $url,
                navigation_node::NODETYPE_LEAF,
                'govreporting',
                'govreporting',
                new pix_icon('i/report', '')
            );
            
            if ($PAGE->url->compare($url, URL_MATCH_BASE)) {
                $node->make_active();
            }
            
            $settingnode->add_node($node);
        }
    }
}

/**
 * Check if necessary profile fields exist and create them if not
 * This function runs during plugin installation
 */
function local_govreporting_install_profile_fields() {
    global $DB;
    
    // Define the required profile fields
    $fields = [
        'DL' => ['name' => 'Driver License', 'datatype' => 'text', 'description' => 'Driver license number'],
        'DOB' => ['name' => 'Date of Birth', 'datatype' => 'datetime', 'description' => 'Student date of birth'],
        'State' => ['name' => 'State', 'datatype' => 'text', 'description' => 'State of residence'],
        'training_completion' => ['name' => 'Training Completion Date', 'datatype' => 'datetime', 'description' => 'Date training was completed'],
        'theory_score' => ['name' => 'Theory Score', 'datatype' => 'text', 'description' => 'Average score for theoretical exams']
    ];
    
    // Get existing profile field category for 'Other fields'
    $category = $DB->get_record('user_info_category', ['name' => 'Other fields']);
    if (!$category) {
        // Create category if it doesn't exist
        $category = new stdClass();
        $category->name = 'Other fields';
        $category->sortorder = 1;
        $category->id = $DB->insert_record('user_info_category', $category);
    }
    
    // Check and create each field if needed
    foreach ($fields as $shortname => $fieldinfo) {
        $existing = $DB->get_record('user_info_field', ['shortname' => $shortname]);
        if (!$existing) {
            $field = new stdClass();
            $field->shortname = $shortname;
            $field->name = $fieldinfo['name'];
            $field->datatype = $fieldinfo['datatype'];
            $field->description = $fieldinfo['description'];
            $field->descriptionformat = 1; // FORMAT_HTML
            $field->categoryid = $category->id;
            $field->sortorder = $DB->count_records('user_info_field', ['categoryid' => $category->id]) + 1;
            $field->required = 0;
            $field->locked = 0;
            $field->visible = 2; // VISIBLE_ALL
            $field->forceunique = 0;
            $field->signup = 0;
            $field->defaultdata = '';
            $field->defaultdataformat = 0;
            $field->param1 = $fieldinfo['datatype'] == 'datetime' ? '2010-01-01' : '30';
            $field->param2 = $fieldinfo['datatype'] == 'datetime' ? '2030-12-31' : '2048';
            
            $DB->insert_record('user_info_field', $field);
        }
    }
}