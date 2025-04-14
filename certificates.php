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
 * Certificate management page for TPR integration.
 *
 * @package     local_govreporting
 * @copyright   2025 YourName <your.email@example.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/formslib.php');

// Define the certificate upload form
class certificate_form extends moodleform {
    public function definition() {
        $mform = $this->_form;
        
        // Get current tab
        $currenttab = optional_param('tab', 'test', PARAM_ALPHA);
        
        // Add hidden fields
        $mform->addElement('hidden', 'action', 'upload');
        $mform->setType('action', PARAM_ALPHA);
        $mform->addElement('hidden', 'certtype', $currenttab);
        $mform->setType('certtype', PARAM_ALPHA);
        $mform->addElement('hidden', 'tab', $currenttab);
        $mform->setType('tab', PARAM_ALPHA);
        
        // Credential ID
        $mform->addElement('text', 'credentialid', 'Credential ID (issuer)', ['size' => 50]);
        $mform->setType('credentialid', PARAM_TEXT);
        $mform->addHelpButton('credentialid', 'tpr_credentialid', 'local_govreporting');
        $mform->addRule('credentialid', get_string('required'), 'required', null, 'client');
        
        // PFX file upload
        $mform->addElement('filepicker', 'pfxfile', 'PFX Certificate File', null, 
                         ['maxbytes' => 1048576, 'accepted_types' => '.pfx']);
        $mform->addHelpButton('pfxfile', 'tpr_pfxfile', 'local_govreporting');
        $mform->addRule('pfxfile', get_string('required'), 'required', null, 'client');
        
        // PFX password
        $mform->addElement('password', 'pfxpassword', 'PFX Password');
        $mform->setType('pfxpassword', PARAM_RAW);
        $mform->addHelpButton('pfxpassword', 'tpr_pfxpassword', 'local_govreporting');
        $mform->addRule('pfxpassword', get_string('required'), 'required', null, 'client');
        
        // Submit button
        $this->add_action_buttons(false, 'Upload Certificate');
    }
}

// Security checks
require_login();
$context = context_system::instance();
require_capability('moodle/site:config', $context);

// Setup page
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/govreporting/certificates.php'));
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('tpr_certificate_management', 'local_govreporting'));
$PAGE->set_heading(get_string('tpr_certificate_management', 'local_govreporting'));

// Get current tab
$currenttab = optional_param('tab', 'test', PARAM_ALPHA);
$action = optional_param('action', '', PARAM_ALPHA);
$certtype = optional_param('certtype', '', PARAM_ALPHA);

// Create the form
$mform = new certificate_form();

// Handle form submission for testing
if ($action == 'test' && ($certtype == 'test' || $certtype == 'production')) {
    require_sesskey();
    
    // Test certificate
    $result = \local_govreporting\certificate_manager::test_certificate($certtype);
    
    if ($result['success']) {
        \core\notification::success(get_string('tpr_certificate_valid', 'local_govreporting'));
    } else {
        \core\notification::error(get_string('tpr_certificate_invalid', 'local_govreporting') . ': ' . $result['message']);
    }
}

// Process the form
if ($formdata = $mform->get_data()) {
    if ($formdata->action == 'upload' && ($formdata->certtype == 'test' || $formdata->certtype == 'production')) {
        // Get uploaded file
        $draftitemid = $formdata->pfxfile;
        
        if ($draftitemid) {
            $fs = get_file_storage();
            $usercontext = context_user::instance($USER->id);
            $files = $fs->get_area_files($usercontext->id, 'user', 'draft', $draftitemid, 'id', false);
            
            if ($files) {
                $file = reset($files);
                $pfxdata = $file->get_content();
                $password = $formdata->pfxpassword;
                $credentialid = $formdata->credentialid;
                
                // Extract private key from PFX
                $result = \local_govreporting\certificate_manager::extract_private_key($pfxdata, $password);
                
                if ($result['success']) {
                    // Save the certificate data
                    \local_govreporting\certificate_manager::save_certificate_data($formdata->certtype, $result['privatekey'], $credentialid);
                    
                    // Show success message
                    \core\notification::success(get_string('tpr_certificate_uploaded', 'local_govreporting'));
                } else {
                    // Show error message
                    \core\notification::error(get_string('tpr_certificate_extract_failed', 'local_govreporting') . ': ' . $result['message']);
                }
            } else {
                \core\notification::error(get_string('tpr_no_file_uploaded', 'local_govreporting'));
            }
        } else {
            \core\notification::error(get_string('tpr_no_file_uploaded', 'local_govreporting'));
        }
    }
}

// Get current certificate info
$testCertInfo = \local_govreporting\certificate_manager::get_certificate_info('test');
$prodCertInfo = \local_govreporting\certificate_manager::get_certificate_info('production');
$certInfo = ($currenttab == 'test') ? $testCertInfo : $prodCertInfo;

// Set credential ID in the form
if (!empty($certInfo['credentialid'])) {
    $mform->set_data(['credentialid' => $certInfo['credentialid']]);
}

// Output the page
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('tpr_certificate_management', 'local_govreporting'));

echo '<div class="alert alert-info">';
echo format_text(get_string('tpr_certificate_info', 'local_govreporting'));
echo '</div>';

// Display tabs
echo '<ul class="nav nav-tabs mb-3" role="tablist">';
echo '<li class="nav-item">';
echo '<a class="nav-link ' . ($currenttab == 'test' ? 'active' : '') . '" href="' . new moodle_url('/local/govreporting/certificates.php', ['tab' => 'test']) . '" role="tab">Test Certificate</a>';
echo '</li>';
echo '<li class="nav-item">';
echo '<a class="nav-link ' . ($currenttab == 'production' ? 'active' : '') . '" href="' . new moodle_url('/local/govreporting/certificates.php', ['tab' => 'production']) . '" role="tab">Production Certificate</a>';
echo '</li>';
echo '</ul>';

// Current certificate status
echo '<div class="card mb-4">';
echo '<div class="card-header">Current Certificate Status</div>';
echo '<div class="card-body">';

if (!empty($certInfo['credentialid']) && $certInfo['has_privatekey']) {
    echo '<div class="alert alert-success">Certificate is configured</div>';
    echo '<dl class="row">';
    echo '<dt class="col-sm-3">Credential ID</dt>';
    echo '<dd class="col-sm-9">' . s($certInfo['credentialid']) . '</dd>';
    echo '<dt class="col-sm-3">Private Key</dt>';
    echo '<dd class="col-sm-9"><i class="fa fa-check-circle"></i> Stored securely</dd>';
    echo '</dl>';
    
    // Test button
    echo '<form method="post" action="' . $PAGE->url . '">';
    echo '<input type="hidden" name="action" value="test">';
    echo '<input type="hidden" name="certtype" value="' . $currenttab . '">';
    echo '<input type="hidden" name="tab" value="' . $currenttab . '">';
    echo '<input type="hidden" name="sesskey" value="' . sesskey() . '">';
    echo '<button type="submit" class="btn btn-secondary">Test Certificate</button>';
    echo '</form>';
} else {
    echo '<div class="alert alert-warning">Certificate not configured</div>';
}
echo '</div>';
echo '</div>';

// Upload new certificate form
echo '<div class="card">';
echo '<div class="card-header">Upload New Certificate</div>';
echo '<div class="card-body">';
$mform->display();
echo '</div>';
echo '</div>';

echo $OUTPUT->footer();