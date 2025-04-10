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
 * API manager class for government reporting plugin.
 *
 * @package     local_govreporting
 * @copyright   2025 YourName <your.email@example.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_govreporting;

defined('MOODLE_INTERNAL') || die();

/**
 * Class api_manager
 *
 * This class handles API interactions for the Government Reporting plugin.
 */
class api_manager {
    /**
     * @var string The API endpoint URL
     */
    private $apiurl;
    
    /**
     * @var string The API key for authentication
     */
    private $apikey;
    
    /**
     * Constructor.
     */
    public function __construct() {
        $this->apiurl = get_config('local_govreporting', 'apiendpoint');
        $this->apikey = get_config('local_govreporting', 'apikey');
    }
    
    /**
     * Submit student data to the government API.
     *
     * @param \stdClass $student The student data object
     * @return array ['success' => bool, 'message' => string, 'code' => int]
     */
    public function submit_student_data($student) {
        global $CFG;
        
        // Check if API settings are configured.
        if (empty($this->apiurl) || empty($this->apikey)) {
            return [
                'success' => false,
                'message' => 'API settings not configured',
                'code' => 0
            ];
        }
        
        // Prepare data for submission.
        $data = $this->prepare_student_data($student);
        
        // Set up the curl request.
        $curl = new \curl();
        $curl->setHeader('Content-Type: application/json');
        $curl->setHeader('Authorization: Bearer ' . $this->apikey);
        
        // Make the API call.
        $response = $curl->post($this->apiurl, json_encode($data));
        $info = $curl->info;
        
        // Process the response.
        $result = [
            'success' => false,
            'message' => 'Unknown error',
            'code' => 0
        ];
        
        if ($info['http_code']) {
            $result['code'] = $info['http_code'];
            
            if ($info['http_code'] == 200) {
                $result['success'] = true;
                $result['message'] = 'Data successfully submitted';
            } else {
                // Try to decode the error message if available.
                $error = json_decode($response);
                if ($error && isset($error->message)) {
                    $result['message'] = $error->message;
                } else {
                    $result['message'] = 'HTTP Error: ' . $info['http_code'];
                }
            }
        }
        
        return $result;
    }
    
    /**
     * Prepare student data for API submission.
     *
     * @param \stdClass $student The student object
     * @return array The formatted data for the API
     */
    private function prepare_student_data($student) {
        // Format courses and scores.
        $coursedata = [];
        foreach ($student->courses as $course) {
            $coursedata[] = [
                'course_name' => $course->fullname,
                'course_code' => $course->shortname,
                'score' => $course->score
            ];
        }
        
        // Format date fields if they exist
        $dob = !empty($student->date_of_birth) ? $student->date_of_birth : '';
        $training_completion = !empty($student->training_completion) ? $student->training_completion : '';
        
        // Build the data array.
        $data = [
            'student' => [
                'id' => $student->id,
                'first_name' => $student->firstname,
                'last_name' => $student->lastname,
                'driver_license' => !empty($student->driver_license) ? $student->driver_license : 'N/A',
                'date_of_birth' => $dob,
                'state' => !empty($student->state) ? $student->state : 'N/A',
                'training_completion_date' => $training_completion,
                'theory_score' => !empty($student->average_score) ? $student->average_score : 0,
                'courses' => $coursedata
            ],
            'submission_date' => date('Y-m-d H:i:s'),
            'institution_id' => get_config('core', 'siteidentifier')
        ];
        
        return $data;
    }
}