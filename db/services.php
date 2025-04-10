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
 * Government Reporting external services definition.
 *
 * @package    local_govreporting
 * @copyright  2025 YourName <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_govreporting_submit_student' => [
        'classname'     => 'local_govreporting\external\submit_student',
        'methodname'    => 'execute',
        'description'   => 'Submit student data to government database.',
        'type'          => 'write',
        'ajax'          => true,
        'capabilities'  => 'local/govreporting:submitdata'
    ]
];