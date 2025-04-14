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
$string['minscore'] = 'Minimum Score Percentage';
$string['minscoredesc'] = 'The minimum score percentage required for students to be included in the report (default: 80)';
$string['perpage'] = 'Students Per Page';
$string['perpagedesc'] = 'The number of students to display per page (default: 10)';

// TPR specific settings
$string['tpr_settings_heading'] = 'FMCSA Training Provider Registry (TPR) Settings';
$string['tpr_settings_desc'] = 'Configure settings for connecting with the FMCSA Training Provider Registry API';
$string['tpr_testmode'] = 'Test Mode';
$string['tpr_testmode_desc'] = 'When enabled, the system will use test credentials and not submit real data to the TPR';
$string['tpr_provider_location_id'] = 'Provider Location ID';
$string['tpr_provider_location_id_desc'] = 'The unique identifier for your TPR registered location (GUID format)';

// Certificate management
$string['tpr_certificate_management'] = 'TPR Certificate Management';
$string['tpr_certificate_notice'] = 'Certificate Management';
$string['tpr_certificate_notice_desc'] = 'TPR requires certificate-based authentication. Please use the <a href="{$a}">Certificate Management</a> page to upload your certificates.';
$string['tpr_certificate_info'] = 'This page allows you to manage the certificates used for TPR API authentication. You need to upload the PFX certificate files provided by the TPR portal. The system will extract the private keys needed for JWT token signing.';
$string['tpr_certificate_uploaded'] = 'Certificate uploaded and private key extracted successfully';
$string['tpr_certificate_extract_failed'] = 'Failed to extract private key from certificate';
$string['tpr_no_file_uploaded'] = 'No certificate file was uploaded';
$string['tpr_certificate_valid'] = 'Certificate is valid and can be used for JWT signing';
$string['tpr_certificate_invalid'] = 'Certificate is invalid for JWT signing';

// Form field help strings
$string['tpr_credentialid'] = 'Credential ID';
$string['tpr_credentialid_help'] = 'Enter the issuer ID from your TPR credentials. This is typically a GUID provided when you generate your credentials in the TPR portal.';
$string['tpr_pfxfile'] = 'PFX Certificate File';
$string['tpr_pfxfile_help'] = 'Upload the .pfx certificate file downloaded from the TPR portal or provided in the TPR Developer\'s Toolkit.';
$string['tpr_pfxpassword'] = 'PFX Password';
$string['tpr_pfxpassword_help'] = 'Enter the password used to protect the PFX file. This is required to extract the private key.';

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
$string['success_submission'] = 'Successfully submitted to TPR database';
$string['error_submission'] = 'Failed to submit to TPR database';
$string['confirm_submission'] = 'Are you sure you want to submit this student\'s data to the TPR?';

// Capability strings
$string['govreporting:viewreport'] = 'View government report';
$string['govreporting:submitdata'] = 'Submit student data to government';