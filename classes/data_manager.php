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
 * Data manager class for government reporting plugin.
 *
 * @package     local_govreporting
 * @copyright   2025 YourName <your.email@example.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_govreporting;

defined('MOODLE_INTERNAL') || die();

/**
 * Class data_manager
 *
 * This class manages data retrieval and processing for the Government Reporting plugin.
 * Optimized for handling all students and courses.
 */
class data_manager {
    /** @var array Cache for eligible students */
    private static $eligible_students_cache = null;
    
    /** @var array Cache for user courses */
    private static $user_courses_cache = [];
    
    /** @var array Cache for user grades */
    private static $user_grades_cache = [];
    
    /**
     * Get all students who are eligible for government reporting.
     * Eligible students are those who have scored at or above the minimum percentage
     * in ALL their courses and have not been successfully submitted yet.
     *
     * @param int $limitfrom Offset for pagination
     * @param int $limitnum Number of records to return
     * @return array List of eligible students with their details
     */
    public function get_eligible_students($limitfrom = 0, $limitnum = 0) {
        global $DB;
        
        // Use in-memory cache to avoid recalculating for pagination
        if (self::$eligible_students_cache !== null) {
            if ($limitnum > 0) {
                return array_slice(self::$eligible_students_cache, $limitfrom, $limitnum);
            }
            return self::$eligible_students_cache;
        }
        
        // Get minimum score threshold from settings.
        $minscore = get_config('local_govreporting', 'minscore');
        if (empty($minscore)) {
            $minscore = 80; // Default is 80% if not set
        }
        
        // Get users who haven't been successfully submitted yet.
        $submittedusers = $DB->get_records_sql("
            SELECT DISTINCT userid 
            FROM {local_govreporting_submissions} 
            WHERE status = 'submitted'
        ");
        
        $submittedids = array_keys($submittedusers);
        $sql_not_submitted = empty($submittedids) ? '' : ' AND u.id NOT IN (' . implode(',', $submittedids) . ')';
        
        // Process users in batches to avoid memory issues
        $userbatchsize = 500; // Number of users to process at once
        $useroffset = 0;
        $allusers = [];
        
        do {
            // SQL to find users with the required profile fields in batches
            $sql = "SELECT DISTINCT u.id, u.firstname, u.lastname 
                    FROM {user} u
                    WHERE u.deleted = 0 AND u.suspended = 0 AND u.confirmed = 1" . $sql_not_submitted . "
                    ORDER BY u.id
                    LIMIT $userbatchsize OFFSET $useroffset";
            
            $userbatch = $DB->get_records_sql($sql);
            
            if (empty($userbatch)) {
                break; // No more users to process
            }
            
            $allusers += $userbatch; // Merge batches
            $useroffset += $userbatchsize;
            
        } while (count($userbatch) == $userbatchsize);
        
        // Get profile fields for all users in one query
        $profilefields = [];
        if (!empty($allusers)) {
            $userids = array_keys($allusers);
            
            // Get all required profile fields in one query with a JOIN
            $sql = "SELECT uid.userid, uif.shortname, uid.data
                    FROM {user_info_data} uid
                    JOIN {user_info_field} uif ON uif.id = uid.fieldid
                    WHERE uid.userid IN (" . implode(',', $userids) . ")
                    AND uif.shortname IN ('DL', 'DOB', 'State', 'training_completion', 'theory_score')";
            
            $profiledata = $DB->get_recordset_sql($sql);
            
            // Organize profile data by user and field
            foreach ($profiledata as $profile) {
                if (!isset($profilefields[$profile->userid])) {
                    $profilefields[$profile->userid] = [];
                }
                $profilefields[$profile->userid][$profile->shortname] = $profile->data;
            }
            $profiledata->close();
            
            // Assign profile fields to user objects
            foreach ($allusers as $userid => $user) {
                if (isset($profilefields[$userid])) {
                    $fields = $profilefields[$userid];
                    $user->driver_license = isset($fields['DL']) ? $fields['DL'] : '';
                    $user->date_of_birth = isset($fields['DOB']) ? $fields['DOB'] : '';
                    $user->state = isset($fields['State']) ? $fields['State'] : '';
                    $user->training_completion = isset($fields['training_completion']) ? $fields['training_completion'] : '';
                    $user->theory_score = isset($fields['theory_score']) ? $fields['theory_score'] : '';
                }
            }
        }
        
        // Process all users and filter for eligible ones
        $eligiblestudents = [];
        
        foreach ($allusers as $user) {
            $courses = $this->get_user_courses_optimized($user->id);
            
            if (empty($courses)) {
                continue; // Skip users not enrolled in any courses
            }
            
            $allcoursespassed = true;
            $highscorecourses = [];
            $averagescore = 0;
            $totalcourses = 0;
            
            foreach ($courses as $course) {
                $score = $this->get_course_grade_percentage_optimized($user->id, $course->id);
                $course->score = $score;
                $totalcourses++;
                $averagescore += $score;
                
                if ($score >= $minscore) {
                    $highscorecourses[] = $course;
                } else {
                    $allcoursespassed = false;
                }
            }
            
            // Calculate average score
            if ($totalcourses > 0) {
                $averagescore = $averagescore / $totalcourses;
            }
            
            // Only include users who have passed ALL courses with a high enough score
            if ($allcoursespassed && !empty($highscorecourses)) {
                $user->courses = $highscorecourses;
                $user->average_score = round($averagescore, 2);
                $eligiblestudents[] = $user;
            }
        }
        
        // Cache results for later use (for pagination)
        self::$eligible_students_cache = $eligiblestudents;
        
        // Return all students or a subset if pagination is required
        if ($limitnum > 0) {
            return array_slice($eligiblestudents, $limitfrom, $limitnum);
        }
        
        return $eligiblestudents;
    }
    
    /**
     * Get courses for a specific user, optimized with caching.
     *
     * @param int $userid The user ID
     * @return array List of courses the user is enrolled in
     */
    protected function get_user_courses_optimized($userid) {
        global $DB;
        
        // Check cache first
        if (isset(self::$user_courses_cache[$userid])) {
            return self::$user_courses_cache[$userid];
        }
        
        // Use DISTINCT to ensure we get unique course IDs
        $sql = "SELECT DISTINCT c.id, c.fullname, c.shortname
                FROM {course} c
                JOIN {enrol} e ON e.courseid = c.id
                JOIN {user_enrolments} ue ON ue.enrolid = e.id
                WHERE ue.userid = :userid AND c.id != :siteid";
        
        $courses = $DB->get_records_sql($sql, ['userid' => $userid, 'siteid' => SITEID]);
        
        // Cache the result
        self::$user_courses_cache[$userid] = $courses;
        
        return $courses;
    }
    
    /**
     * Get the grade percentage for a user in a specific course, optimized with caching.
     *
     * @param int $userid The user ID
     * @param int $courseid The course ID
     * @return float The grade percentage (0-100)
     */
    protected function get_course_grade_percentage_optimized($userid, $courseid) {
        global $DB;
        
        // Check cache first
        if (isset(self::$user_grades_cache[$userid]) && isset(self::$user_grades_cache[$userid][$courseid])) {
            return self::$user_grades_cache[$userid][$courseid];
        }
        
        // Initialize cache for this user if needed
        if (!isset(self::$user_grades_cache[$userid])) {
            self::$user_grades_cache[$userid] = [];
        }
        
        try {
            // Optimized direct query for better performance
            $sql = "SELECT gi.grademax, g.finalgrade
                    FROM {grade_items} gi
                    JOIN {grade_grades} g ON gi.id = g.itemid
                    WHERE gi.courseid = ? 
                    AND gi.itemtype = 'course'
                    AND g.userid = ?";
    
            $grade_record = $DB->get_record_sql($sql, [$courseid, $userid]);
    
            // Calculate grade if possible
            if ($grade_record && !is_null($grade_record->finalgrade) && !empty($grade_record->grademax)) {
                $grade_percentage = round(($grade_record->finalgrade / $grade_record->grademax) * 100, 2);
                
                // Cache the result
                self::$user_grades_cache[$userid][$courseid] = $grade_percentage;
                
                return $grade_percentage;
            }
    
            // Cache the zero result
            self::$user_grades_cache[$userid][$courseid] = 0;
            return 0;
        } catch (\Exception $e) {
            // Cache the zero result
            self::$user_grades_cache[$userid][$courseid] = 0;
            return 0;
        }
    }
    
    /**
     * Count the total number of eligible students.
     *
     * @return int Number of eligible students
     */
    public function count_eligible_students() {
        // If we already have the eligible students cached, just count them
        if (self::$eligible_students_cache !== null) {
            return count(self::$eligible_students_cache);
        }
        
        // Otherwise, we need to get the eligible students first (which will cache them)
        $allstudents = $this->get_eligible_students();
        return count($allstudents);
    }
    
    /**
     * Update the theory_score profile field for a user.
     *
     * @param int $userid The user ID
     * @param float $score The average score to set
     * @return bool Success or failure
     */
    protected function update_theory_score($userid, $score) {
        global $DB;
        
        // First get the field ID for theory_score
        $fieldid = $DB->get_field('user_info_field', 'id', ['shortname' => 'theory_score']);
        
        if (!$fieldid) {
            return false; // Field doesn't exist
        }
        
        // Check if the record already exists
        $existing = $DB->get_record('user_info_data', [
            'userid' => $userid,
            'fieldid' => $fieldid
        ]);
        
        if ($existing) {
            // Update existing record
            $existing->data = $score;
            return $DB->update_record('user_info_data', $existing);
        } else {
            // Create new record
            $record = new \stdClass();
            $record->userid = $userid;
            $record->fieldid = $fieldid;
            $record->data = $score;
            return $DB->insert_record('user_info_data', $record) ? true : false;
        }
    }
    
    /**
     * Record a submission attempt for a student.
     *
     * @param int $userid The user ID
     * @param string $status The submission status (pending, submitted, failed)
     * @param string $response The API response
     * @return int The ID of the new submission record
     */
    public function record_submission($userid, $status, $response = '') {
        global $DB;
        
        $submission = new \stdClass();
        $submission->userid = $userid;
        $submission->timecreated = time();
        $submission->timemodified = time();
        $submission->status = $status;
        $submission->response = $response;
        
        // Check if there's an existing submission for this user.
        $existing = $DB->get_record('local_govreporting_submissions', ['userid' => $userid]);
        
        if ($existing) {
            $submission->id = $existing->id;
            $submission->attempt = $existing->attempt + 1;
            $DB->update_record('local_govreporting_submissions', $submission);
            return $existing->id;
        } else {
            $submission->attempt = 1;
            return $DB->insert_record('local_govreporting_submissions', $submission);
        }
    }
    
    /**
     * Update theory scores for all eligible students.
     * This should be run as a separate task, not during report generation.
     */
    public function update_all_theory_scores() {
        global $DB;
        
        // Get all users
        $users = $DB->get_records('user', ['deleted' => 0, 'suspended' => 0, 'confirmed' => 1], '', 'id');
        
        foreach ($users as $user) {
            $courses = $this->get_user_courses_optimized($user->id);
            
            if (empty($courses)) {
                continue; // Skip users not enrolled in any courses
            }
            
            $averagescore = 0;
            $totalcourses = 0;
            
            foreach ($courses as $course) {
                $score = $this->get_course_grade_percentage_optimized($user->id, $course->id);
                $totalcourses++;
                $averagescore += $score;
            }
            
            // Calculate average score
            if ($totalcourses > 0) {
                $averagescore = $averagescore / $totalcourses;
                $this->update_theory_score($user->id, round($averagescore, 2));
            }
        }
    }
}