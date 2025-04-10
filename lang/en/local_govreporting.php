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
 * Language strings for the Government Reporting plugin.
 *
 * @package     local_govreporting
 * @copyright   2025 YourName <your.email@example.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Basic plugin strings
$string['pluginname'] = 'Government Reporting';
$string['reportpage'] = 'Student Report';
$string['settings'] = 'Settings';

// Settings strings
$string['apiendpoint'] = 'API Endpoint URL';
$string['apiendpointdesc'] = 'The URL for the government API endpoint';
$string['apikey'] = 'API Key';
$string['apikeydesc'] = 'The API key for authentication with the government API';
$string['minscore'] = 'Minimum Score Percentage';
$string['minscoredesc'] = 'The minimum score percentage required for students to be included in the report (default: 80)';
$string['perpage'] = 'Students Per Page';
$string['perpagedesc'] = 'The number of students to display per page (default: 10)';

// Report page strings
$string['report_title'] = 'Government Reporting - Eligible Students';
$string['report_description'] = 'This report shows students who have scored 80% or higher in ALL their exams and are eligible for government reporting.';
$string['no_eligible_students'] = 'No eligible students found.';
$string['submit_to_government'] = 'Submit to Government';
$string['first_name'] = 'First Name';
$string['last_name'] = 'Last Name';
$string['driver_license'] = 'Driver License';
$string['date_of_birth'] = 'Date of Birth';
$string['state'] = 'State';
$string['training_completion'] = 'Training Completion Date';
$string['theory_score'] = 'Theory Score (Average)';
$string['courses'] = 'Courses';
$string['exam_scores'] = 'Exam Scores';
$string['actions'] = 'Actions';
$string['showing_students'] = 'Showing students (total: {$a})';
$string['page_x_of_y'] = 'Page {$a->page} of {$a->pages}';

// AJAX response strings
$string['success_submission'] = 'Successfully submitted to government database';
$string['error_submission'] = 'Failed to submit to government database';
$string['confirm_submission'] = 'Are you sure you want to submit this student\'s data to the government database?';

// Capability strings
$string['govreporting:viewreport'] = 'View government report';
$string['govreporting:submitdata'] = 'Submit student data to government';