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
 * JavaScript for the Government Reporting plugin report page.
 *
 * @module     local_govreporting/report_actions
 * @copyright  2025 YourName <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/ajax', 'core/str', 'core/notification'], 
    function($, Ajax, Str, Notification) {
        
        /**
         * Module initialization function.
         */
        function init() {
            registerEventListeners();
        }
        
        /**
         * Register event listeners for the page.
         */
        function registerEventListeners() {
            // Submit student data button click.
            $('.local-govreporting-report').on('click', '.submit-student', function(e) {
                e.preventDefault();
                var userId = $(this).data('userid');
                var button = $(this);
                var studentRecord = button.closest('.student-record');
                
                // Confirm before submitting.
                Str.get_string('confirm_submission', 'local_govreporting').done(function(confirmMessage) {
                    if (confirm(confirmMessage)) {
                        submitStudentData(userId, button, studentRecord);
                    }
                });
            });
        }
        
        /**
         * Submit student data to the government API.
         *
         * @param {number} userId - The user ID to submit
         * @param {object} button - The jQuery button object
         * @param {object} studentRecord - The jQuery student record object
         */
        function submitStudentData(userId, button, studentRecord) {
            // Show processing state.
            button.addClass('processing');
            
            // Hide any previous notifications.
            $('#gov-report-success, #gov-report-error').hide();
            
            // Make the AJAX call.
            Ajax.call([{
                methodname: 'local_govreporting_submit_student',
                args: {
                    userid: userId
                },
                done: function(response) {
                    button.removeClass('processing');
                    
                    if (response.success) {
                        // Show success message.
                        $('#success-message').text(response.message);
                        $('#gov-report-success').show();
                        
                        // Add success animation class.
                        studentRecord.addClass('success-submission');
                        
                        // Remove the student record after animation.
                        setTimeout(function() {
                            studentRecord.fadeOut(500, function() {
                                studentRecord.remove();
                                
                                // Check if there are any students left on this page.
                                if ($('.student-record').length === 0) {
                                    // If no records left, reload the page to show the next set or the "no students" message
                                    window.location.reload();
                                }
                            });
                        }, 1000);
                    } else {
                        // Show error message.
                        $('#error-message').text(response.message);
                        $('#gov-report-error').show();
                        
                        // Add error animation class.
                        studentRecord.addClass('error-submission');
                        
                        // Remove the error class after animation.
                        setTimeout(function() {
                            studentRecord.removeClass('error-submission');
                        }, 500);
                    }
                },
                fail: function(error) {
                    button.removeClass('processing');
                    
                    // Show error notification.
                    Notification.exception(error);
                    
                    // Add error animation class.
                    studentRecord.addClass('error-submission');
                    
                    // Remove the error class after animation.
                    setTimeout(function() {
                        studentRecord.removeClass('error-submission');
                    }, 500);
                }
            }]);
        }
        
        return {
            init: init
        };
    });