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
 * TPR API manager class for government reporting plugin.
 *
 * @package     local_govreporting
 * @copyright   2025 YourName <your.email@example.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_govreporting;

defined('MOODLE_INTERNAL') || die();

// Include the Moodle curl library
require_once($CFG->libdir . '/filelib.php');

/**
 * Class tpr_api_manager
 *
 * This class handles TPR API interactions for the Government Reporting plugin.
 */
class tpr_api_manager {
    /** @var string TPR API endpoint URL */
    private $apiurl;
    
    /** @var string Credential ID (issuer) */
    private $credentialid;
    
    /** @var string Private key for signing JWT tokens */
    private $privatekey;
    
    /** @var bool Whether we're in test mode or not */
    private $testmode;
    
    /**
     * Constructor.
     */
    public function __construct() {
        global $CFG;
        
        $this->testmode = get_config('local_govreporting', 'tpr_testmode');
        $this->apiurl = 'https://tpr.fmcsa.dot.gov/api/Training/Add';
        
        if ($this->testmode) {
            // Use test credentials from the settings
            $this->credentialid = get_config('local_govreporting', 'tpr_test_credentialid');
            $this->privatekey = get_config('local_govreporting', 'tpr_test_privatekey');
            
            // Fallback to default test credentials if not configured
            if (empty($this->credentialid) || empty($this->privatekey)) {
                // These are placeholder values and should be replaced with actual test credentials
                $this->credentialid = '0612e638-ae15-404d-857d-6d7282ec15c6';
                $this->privatekey = "-----BEGIN PRIVATE KEY-----\nMIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQC/s2UJwBK7\n-----END PRIVATE KEY-----";
            }
        } else {
            // Use production credentials from settings
            $this->credentialid = get_config('local_govreporting', 'tpr_credentialid');
            $this->privatekey = get_config('local_govreporting', 'tpr_privatekey');
        }
    }
    
    /**
     * Generate JWT token for authentication
     *
     * @return string The JWT token
     * @throws \Exception If the token generation fails
     */
    private function generate_token() {
        // Check that we have credentials configured
        if (empty($this->credentialid)) {
            throw new \Exception('Missing credential ID for JWT token generation');
        }
        
        if (empty($this->privatekey)) {
            throw new \Exception('Missing private key for JWT token generation');
        }
        
        // Calculate not before (current time) and expiration (current time + 10 minutes)
        $now = time();
        $exp = $now + 600; // 10 minutes
        
        // Create token payload
        $payload = [
            'nbf' => $now,
            'exp' => $exp,
            'iss' => $this->credentialid
        ];
        
        // Sign the token with RS256 algorithm
        return $this->sign_jwt($payload);
    }
    
    /**
     * Sign a JWT payload with RS256 algorithm
     * 
     * @param array $payload The payload to sign
     * @return string The JWT token
     * @throws \Exception If the signing fails
     */
    private function sign_jwt($payload) {
        // Create JWT header
        $header = [
            'alg' => 'RS256',
            'typ' => 'JWT'
        ];
        
        // Encode header and payload
        $header_encoded = $this->base64url_encode(json_encode($header));
        $payload_encoded = $this->base64url_encode(json_encode($payload));
        $data = $header_encoded . '.' . $payload_encoded;
        
        // Ensure private key is in the correct format
        $privatekey = $this->privatekey;
        
        // If the private key doesn't contain BEGIN/END markers, add them
        if (strpos($privatekey, '-----BEGIN') === false) {
            $privatekey = "-----BEGIN PRIVATE KEY-----\n" . $privatekey . "\n-----END PRIVATE KEY-----";
        }
        
        // Sign the data
        $signature = '';
        if (!openssl_sign($data, $signature, $privatekey, OPENSSL_ALGO_SHA256)) {
            $error = openssl_error_string();
            throw new \Exception('Failed to sign JWT: ' . ($error ? $error : 'Unknown OpenSSL error'));
        }
        
        // Encode signature and create token
        $signature_encoded = $this->base64url_encode($signature);
        return $header_encoded . '.' . $payload_encoded . '.' . $signature_encoded;
    }
    
    /**
     * Base64URL encoding helper function
     *
     * @param string $data The data to encode
     * @return string The base64url encoded string
     */
    private function base64url_encode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    /**
     * Submit student training data to TPR
     *
     * @param \stdClass $student The student data object
     * @return array ['success' => bool, 'message' => string, 'code' => int]
     */
    public function submit_training_data($student) {
        global $CFG;
        
        try {
            // Check if API settings are configured
            if (empty($this->credentialid) || empty($this->privatekey)) {
                return [
                    'success' => false,
                    'message' => 'TPR API credentials not configured. Please upload a certificate in the TPR Certificate Management page.',
                    'code' => 0
                ];
            }
            
            // Verify the student object has required fields
            if (empty($student) || !is_object($student)) {
                return [
                    'success' => false,
                    'message' => 'Invalid student data provided',
                    'code' => 0
                ];
            }
            
            // Check required fields
            if (empty($student->firstname) || empty($student->lastname)) {
                return [
                    'success' => false,
                    'message' => 'Student data missing required fields (firstname, lastname)',
                    'code' => 0
                ];
            }
            
            // Generate JWT token
            $token = $this->generate_token();
            
            // Prepare data for submission
            $data = $this->prepare_training_data($student);
            
            // Set up the curl request - using Moodle's curl class
            $curl = new \curl();
            $curl->setHeader('Content-Type: application/json');
            $curl->setHeader('Authorization: Bearer ' . $token);
            
            // Make the API call
            $jsondata = json_encode($data);
            if ($jsondata === false) {
                return [
                    'success' => false,
                    'message' => 'Failed to encode student data to JSON: ' . json_last_error_msg(),
                    'code' => 0
                ];
            }
            
            $response = $curl->post($this->apiurl, $jsondata);
            $info = $curl->info;
            
            // Process the response
            $result = [
                'success' => false,
                'message' => 'Unknown error',
                'code' => 0
            ];
            
            if ($info && isset($info['http_code'])) {
                $result['code'] = $info['http_code'];
                
                if ($info['http_code'] == 201) { // TPR uses 201 for successful creation
                    $result['success'] = true;
                    $result['message'] = 'Training data successfully submitted to TPR';
                } else {
                    // Try to decode the error message if available
                    $error = json_decode($response);
                    if ($error && isset($error->detail)) {
                        $result['message'] = $error->detail;
                    } else {
                        $result['message'] = 'HTTP Error: ' . $info['http_code'] . ' - ' . $response;
                    }
                }
            } else {
                $result['message'] = 'Failed to connect to TPR API: No HTTP response code';
            }
            
            return $result;
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
                'code' => 0
            ];
        }
    }
    
    /**
     * Prepare student data for TPR API submission
     *
     * @param \stdClass $student The student object
     * @return array The formatted data for the TPR API
     */
    private function prepare_training_data($student) {
        // Format date fields
        $dob = !empty($student->date_of_birth) ? date('Y-m-d', strtotime($student->date_of_birth)) : '';
        $training_completion = !empty($student->training_completion) ? date('Y-m-d', strtotime($student->training_completion)) : date('Y-m-d');
        
        // Determine class/endorsement application type
        $applicationtype = 'New'; // Default
        if (!empty($student->application_type)) {
            $applicationtype = $student->application_type;
        }
        
        // Ensure we have a valid provider location ID
        $providerLocationId = get_config('local_govreporting', 'tpr_provider_location_id');
        if (empty($providerLocationId)) {
            // Use a default Provider Location ID for testing
            $providerLocationId = 'c1d6ad9f-833c-4257-8e23-b1369ee09e8f';
        }
        
        // Format training elements (theory, range, public road)
        $trainingElements = [];
        
        // Add theory training element
        if (isset($student->theory_score) || isset($student->average_score)) {
            $score = isset($student->theory_score) ? $student->theory_score : $student->average_score;
            // Ensure score is an integer and within valid range (80-100)
            $score = max(80, min(100, intval($score)));
            
            $trainingElements[] = [
                'TrainingType' => 'Theory',
                'CompletionDate' => $training_completion,
                'TrainingMethod' => 'InPerson',
                'Score' => $score,
                'InternalId' => 'TPRSTUDENT-' . $student->id
            ];
        }
        
        // Ensure state is in correct format (US-XX)
        $state = !empty($student->state) ? $student->state : 'XX';
        // Remove any non-alphanumeric characters
        $state = preg_replace('/[^A-Za-z0-9]/', '', $state);
        // Take only the first 2 characters
        if (strlen($state) > 2) {
            $state = substr($state, 0, 2);
        }
        $state = 'US-' . strtoupper($state);
        
        // Ensure the driver license field is properly formatted
        $driverLicense = !empty($student->driver_license) ? $student->driver_license : 'DL' . $student->id;
        
        // Build the data array in TPR format
        $data = [
            'Number' => $driverLicense,
            'State' => $state,
            'FirstName' => $student->firstname,
            'LastName' => $student->lastname,
            'DateOfBirth' => $dob,
            'ClassEndorsementCode' => 'A', // Default to Class A
            'ApplicationType' => $applicationtype,
            'ProviderLocationId' => $providerLocationId,
            'TrainingElements' => $trainingElements
        ];
        
        return $data;
    }
}