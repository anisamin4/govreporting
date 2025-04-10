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
 * Renderer for the Government Reporting plugin.
 *
 * @package     local_govreporting
 * @copyright   2025 YourName <your.email@example.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_govreporting\output;

defined('MOODLE_INTERNAL') || die();

use plugin_renderer_base;

/**
 * Renderer for the Government Reporting plugin.
 */
class renderer extends plugin_renderer_base {
    /**
     * Render the report page.
     *
     * @param report_page $page The report page renderable
     * @return string HTML output
     */
    public function render_report_page(report_page $page) {
        $data = $page->export_for_template($this);
        return $this->render_from_template('local_govreporting/report', $data);
    }
}