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
 * Plugin upgrade steps are defined here.
 *
 * @package     local_govreporting
 * @category    upgrade
 * @copyright   2025 YourName <your.email@example.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Execute local_govreporting upgrade from the given old version.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_local_govreporting_upgrade($oldversion) {
    global $DB, $CFG;

    $dbman = $DB->get_manager();

    // For future upgrades, increment the version number in version.php
    // and add upgrade steps here.

    // Check for profile fields in case they weren't created during installation
    if ($oldversion < 2025040400) {
        require_once($CFG->dirroot . '/local/govreporting/lib.php');
        local_govreporting_install_profile_fields();

        // Savepoint reached.
        upgrade_plugin_savepoint(true, 2025040400, 'local', 'govreporting');
    }

    return true;
}