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
 * External service for submitting student data.
 *
 * @package    local_govreporting
 * @copyright  2025 YourName <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_govreporting\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;
use local_govreporting\api_manager;
use local_govreporting\data_manager;

/**
 * External service for submitting student data to government database.
 *
 * @package    local_govreporting\external
 */
class submit_student extends external_api {
    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'userid' => new external_value(PARAM_INT, 'User ID to submit')
        ]);
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function execute_returns() {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether the submission was successful'),
            'message' => new external_value(PARAM_TEXT, 'Success or error message'),
            'code' => new external_value(PARAM_INT, 'Response code from the API')
        ]);
    }

    /**
     * Submit student data to government database.
     *
     * @param int $userid The user ID to submit
     * @return array Response data
     */
    public static function execute($userid) {
        global $DB;
        
        // Parameter validation.
        $params = self::validate_parameters(self::execute_parameters(), ['userid' => $userid]);
        $userid = $params['userid'];
        
        // Security checks.
        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/govreporting:submitdata', $context);
        
        // Get the user data.
        $datamanager = new data_manager();
        $students = $datamanager->get_eligible_students();
        
        $student = null;
        foreach ($students as $s) {
            if ($s->id == $userid) {
                $student = $s;
                break;
            }
        }
        
        if (!$student) {
            return [
                'success' => false,
                'message' => get_string('error_submission', 'local_govreporting') . ': Student not found or not eligible',
                'code' => 404
            ];
        }
        
        // Submit to API.
        $apimanager = new api_manager();
        $result = $apimanager->submit_student_data($student);
        
        // Record the submission.
        $status = $result['success'] ? 'submitted' : 'failed';
        $datamanager->record_submission($userid, $status, json_encode($result));
        
        // Return the result.
        return [
            'success' => $result['success'],
            'message' => $result['success'] ? 
                get_string('success_submission', 'local_govreporting') : 
                get_string('error_submission', 'local_govreporting') . ': ' . $result['message'],
            'code' => $result['code']
        ];
    }
}