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
 * The main report page for the Government Reporting plugin.
 *
 * @package     local_govreporting
 * @copyright   2025 YourName <your.email@example.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Increase memory and time limits for this script
ini_set('memory_limit', '512M');
set_time_limit(600); // 10 minutes

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

// Security checks.
require_login();
$context = context_system::instance();
require_capability('local/govreporting:viewreport', $context);

// Get pagination parameters.
$page = optional_param('page', 0, PARAM_INT);    // Current page number

// Get configured students per page.
$perpage = get_config('local_govreporting', 'perpage');
if (empty($perpage)) {
    $perpage = 10; // Default if not set
}

// Setup page.
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/govreporting/report.php', ['page' => $page]));
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('report_title', 'local_govreporting'));
$PAGE->set_heading(get_string('report_title', 'local_govreporting'));

// Add necessary JS for the page.
$PAGE->requires->js_call_amd('local_govreporting/report_actions', 'init');

// Add page CSS.
$PAGE->requires->css('/local/govreporting/styles.css');

// Display a loading message first
echo $OUTPUT->header();
echo '<div class="loading-container">';
echo '<div class="alert alert-info" id="loading-message">
        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
        Loading eligible students. This might take a moment...
      </div>';
echo '</div>';
// Flush output buffer to show loading message
flush();

// Start timing the process
$start_time = microtime(true);

// Get the data manager.
$datamanager = new \local_govreporting\data_manager();

// Get total count first for pagination.
$totalcount = $datamanager->count_eligible_students();

// Get paginated list of eligible students.
$eligiblestudents = $datamanager->get_eligible_students($page * $perpage, $perpage);

// Calculate execution time
$execution_time = round(microtime(true) - $start_time, 2);

// Setup pagination.
$baseurl = new moodle_url('/local/govreporting/report.php');
$pagingbar = new paging_bar($totalcount, $page, $perpage, $baseurl);

// Output the page content.
$output = $PAGE->get_renderer('local_govreporting');
$reportpage = new \local_govreporting\output\report_page($eligiblestudents, $totalcount, $pagingbar);

echo '<div id="report-content">';
echo $output->render($reportpage);
echo '</div>';

// Add execution time information
echo '<div class="small text-muted mt-3">Report generated in ' . $execution_time . ' seconds</div>';

// Add JavaScript to hide the loading message when content is loaded
echo '<script>
document.addEventListener("DOMContentLoaded", function() {
    document.getElementById("loading-message").style.display = "none";
});
</script>';

echo $OUTPUT->footer();