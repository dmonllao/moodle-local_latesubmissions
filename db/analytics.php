<?php
// This file is part of Moodle - https://moodle.org/
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
 * Defines the plugin models.
 *
 * @package     local_latesubmissions
 * @category    analytics
 * @copyright   2019 David Monllaó
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$models = [
    [
        'target' => '\local_latesubmissions\analytics\target\late_assign_submission',
        'indicators' => [
            '\core\analytics\indicator\any_write_action',
            '\core\analytics\indicator\any_write_action_in_course',
            '\core\analytics\indicator\read_actions',
            '\local_latesubmissions\analytics\indicator\accessed_section_activities',
            '\local_latesubmissions\analytics\indicator\any_read_action',
            '\local_latesubmissions\analytics\indicator\any_read_action_in_course',
            '\local_latesubmissions\analytics\indicator\assignment_submissions',
            '\local_latesubmissions\analytics\indicator\forum_posts',
            '\local_latesubmissions\analytics\indicator\grade_to_pass_set',
            '\local_latesubmissions\analytics\indicator\guest_access_enabled',
            '\local_latesubmissions\analytics\indicator\is_user_self_enrolled',
            '\local_latesubmissions\analytics\indicator\quiz_attempts',
            '\local_latesubmissions\analytics\indicator\self_enrol_enabled',
            '\local_latesubmissions\analytics\indicator\submit_choice_close_to_close',
            '\local_latesubmissions\analytics\indicator\submit_close_to_close',
            '\local_latesubmissions\analytics\indicator\submit_close_to_due',
            '\local_latesubmissions\analytics\indicator\user_activity',
            '\local_latesubmissions\analytics\indicator\write_actions_amount',
            '\core_course\analytics\indicator\completion_enabled',
            '\core_user\analytics\indicator\user_profile_set'
        ],
        'timesplitting' => '\local_latesubmissions\analytics\time_splitting\really_close_to_deadline',
    ],
];
