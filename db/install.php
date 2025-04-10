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
 * Custom installation steps for the Government Reporting plugin.
 *
 * @package    local_govreporting
 * @copyright  2025 YourName <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Custom installation steps.
 */
function xmldb_local_govreporting_install() {
    global $CFG;
    
    require_once($CFG->dirroot . '/local/govreporting/lib.php');
    
    // Create necessary profile fields if they don't exist
    local_govreporting_install_profile_fields();
    
    return true;
}