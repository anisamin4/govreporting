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
 * Report page renderable for the Government Reporting plugin.
 *
 * @package     local_govreporting
 * @copyright   2025 YourName <your.email@example.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_govreporting\output;

defined('MOODLE_INTERNAL') || die();

use renderable;
use templatable;
use renderer_base;
use stdClass;
use paging_bar;

/**
 * Class report_page
 *
 * @package     local_govreporting\output
 */
class report_page implements renderable, templatable {
    /**
     * @var array List of eligible students for the current page
     */
    protected $students;
    
    /**
     * @var int Total number of eligible students (across all pages)
     */
    protected $totalcount;
    
    /**
     * @var paging_bar The pagination bar
     */
    protected $pagingbar;
    
    /**
     * Constructor.
     *
     * @param array $students The list of eligible students for current page
     * @param int $totalcount Total number of eligible students
     * @param paging_bar $pagingbar The pagination bar
     */
    public function __construct($students, $totalcount, $pagingbar) {
        $this->students = $students;
        $this->totalcount = $totalcount;
        $this->pagingbar = $pagingbar;
    }
    
    /**
     * Export data for the template.
     *
     * @param renderer_base $output The renderer
     * @return stdClass Data for the template
     */
    public function export_for_template(renderer_base $output) {
        global $PAGE;
        
        $data = new stdClass();
        $data->title = get_string('report_title', 'local_govreporting');
        $data->description = get_string('report_description', 'local_govreporting');
        $data->has_students = !empty($this->students);
        $data->no_students_message = get_string('no_eligible_students', 'local_govreporting');
        
        // Add pagination information.
        $data->has_pagination = ($this->totalcount > 0);
        $data->total_students = $this->totalcount;
        if ($this->pagingbar) {
            $data->pagination = $output->render($this->pagingbar);
        }
        
        $data->students = [];
        
        foreach ($this->students as $student) {
            $studentdata = new stdClass();
            $studentdata->id = $student->id;
            $studentdata->firstname = $student->firstname;
            $studentdata->lastname = $student->lastname;
            $studentdata->driver_license = !empty($student->driver_license) ? $student->driver_license : 'N/A';
            $studentdata->date_of_birth = !empty($student->date_of_birth) ? $student->date_of_birth : 'N/A';
            $studentdata->state = !empty($student->state) ? $student->state : 'N/A';
            $studentdata->training_completion = !empty($student->training_completion) ? $student->training_completion : 'N/A';
            $studentdata->theory_score = !empty($student->average_score) ? number_format($student->average_score, 1) . '%' : 'N/A';
            
            // Format courses and scores.
            $studentdata->courses = [];
            foreach ($student->courses as $course) {
                $coursedata = new stdClass();
                $coursedata->name = $course->fullname;
                $coursedata->score = number_format($course->score, 1) . '%';
                $studentdata->courses[] = $coursedata;
            }
            
            // Format course list as a string for display.
            $coursestrings = array_map(function($course) {
                return $course->name . ' (' . $course->score . ')';
            }, $studentdata->courses);
            $studentdata->course_list = implode(', ', $coursestrings);
            
            // Submit button.
            $studentdata->submit_button = get_string('submit_to_government', 'local_govreporting');
            
            $data->students[] = $studentdata;
        }
        
        return $data;
    }
}