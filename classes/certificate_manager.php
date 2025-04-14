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
 * Certificate manager class for TPR integration.
 *
 * @package     local_govreporting
 * @copyright   2025 YourName <your.email@example.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_govreporting;

defined('MOODLE_INTERNAL') || die();

/**
 * Class certificate_manager
 *
 * This class manages certificates for TPR API integration.
 */
class certificate_manager {
    /**
     * Extract the private key from a PFX file
     *
     * @param string $pfxdata The binary content of the PFX file
     * @param string $password The password to decrypt the PFX file
     * @return array ['success' => bool, 'privatekey' => string, 'message' => string]
     */
    public static function extract_private_key($pfxdata, $password) {
        // Create a temporary file
        $tempfile = tempnam(sys_get_temp_dir(), 'pfx');
        file_put_contents($tempfile, $pfxdata);
        
        $result = ['success' => false, 'privatekey' => '', 'message' => ''];
        
        // Try to parse the PFX file
        $certs = [];
        if (openssl_pkcs12_read(file_get_contents($tempfile), $certs, $password)) {
            if (isset($certs['pkey'])) {
                $result['success'] = true;
                $result['privatekey'] = $certs['pkey'];
                $result['message'] = 'Private key extracted successfully';
            } else {
                $result['message'] = 'PFX file does not contain a private key';
            }
        } else {
            $result['message'] = 'Failed to read PFX file: ' . openssl_error_string();
        }
        
        // Clean up the temporary file
        unlink($tempfile);
        
        return $result;
    }
    
    /**
     * Save certificate data to the plugin configuration
     *
     * @param string $type The certificate type (test or production)
     * @param string $privatekey The private key content
     * @param string $credentialid The credential ID
     * @return bool Whether the save was successful
     */
    public static function save_certificate_data($type, $privatekey, $credentialid) {
        $prefix = ($type == 'test') ? 'tpr_test' : 'tpr';
        
        set_config($prefix . '_privatekey', $privatekey, 'local_govreporting');
        set_config($prefix . '_credentialid', $credentialid, 'local_govreporting');
        
        return true;
    }
    
    /**
     * Get stored certificate info
     *
     * @param string $type The certificate type (test or production)
     * @return array Certificate information
     */
    public static function get_certificate_info($type) {
        $prefix = ($type == 'test') ? 'tpr_test' : 'tpr';
        
        $credentialid = get_config('local_govreporting', $prefix . '_credentialid');
        $hasPrivateKey = !empty(get_config('local_govreporting', $prefix . '_privatekey'));
        
        return [
            'credentialid' => $credentialid,
            'has_privatekey' => $hasPrivateKey,
            'type' => $type,
            'type_name' => ($type == 'test') ? 'Test' : 'Production'
        ];
    }
    
    /**
     * Test if a certificate is valid for JWT signing
     *
     * @param string $type The certificate type (test or production)
     * @return array ['success' => bool, 'message' => string]
     */
    public static function test_certificate($type) {
        $prefix = ($type == 'test') ? 'tpr_test' : 'tpr';
        
        $credentialid = get_config('local_govreporting', $prefix . '_credentialid');
        $privatekey = get_config('local_govreporting', $prefix . '_privatekey');
        
        if (empty($credentialid) || empty($privatekey)) {
            return [
                'success' => false,
                'message' => 'Certificate data is missing or incomplete'
            ];
        }
        
        // Generate a test JWT to verify the private key works
        try {
            // Current time and expiration (5 minutes from now)
            $now = time();
            $exp = $now + 300;
            
            // Create token payload
            $payload = [
                'nbf' => $now,
                'exp' => $exp,
                'iss' => $credentialid
            ];
            
            // Create JWT header
            $header = [
                'alg' => 'RS256',
                'typ' => 'JWT'
            ];
            
            // Encode header and payload
            $header_encoded = self::base64url_encode(json_encode($header));
            $payload_encoded = self::base64url_encode(json_encode($payload));
            $data = $header_encoded . '.' . $payload_encoded;
            
            // Sign the data
            $signature = '';
            if (!openssl_sign($data, $signature, $privatekey, OPENSSL_ALGO_SHA256)) {
                return [
                    'success' => false,
                    'message' => 'Failed to sign test JWT: ' . openssl_error_string()
                ];
            }
            
            return [
                'success' => true,
                'message' => 'Certificate validated successfully'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Exception during test: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Base64URL encoding helper function
     *
     * @param string $data The data to encode
     * @return string The base64url encoded string
     */
    private static function base64url_encode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}