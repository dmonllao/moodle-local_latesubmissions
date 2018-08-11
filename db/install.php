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


defined('MOODLE_INTERNAL') || die();

function xmldb_local_latesubmissions_install() {
    global $DB;

    \core\session\manager::set_user(get_admin());

    $usedtargets = $DB->get_fieldset_select('analytics_models', 'DISTINCT target', '');

    // Enable new time splitting methods.
    $enabledtimesplittings = get_config('analytics', 'timesplittings');
    $newtimesplittingmethod = '\local_latesubmissions\analytics\time_splitting\really_close_to_deadline';
    if (!strstr($enabledtimesplittings, $newtimesplittingmethod)) {
        $enabledtimesplittings .= ',' . $newtimesplittingmethod;
        set_config('timesplittings', $enabledtimesplittings, 'analytics');
    }

    if (!in_array('\local_latesubmissions\analytics\target\late_assign_submission', $usedtargets)) {

        $indicators = [];
        $indicatorclasses = ["\\core\\analytics\\indicator\\any_write_action","\\core\\analytics\\indicator\\any_write_action_in_course","\\core\\analytics\\indicator\\read_actions","\\local_latesubmissions\\analytics\\indicator\\accept_statement_set","\\local_latesubmissions\\analytics\\indicator\\accessed_section_activities","\\local_latesubmissions\\analytics\\indicator\\any_read_action","\\local_latesubmissions\\analytics\\indicator\\any_read_action_in_course","\\local_latesubmissions\\analytics\\indicator\\assignment_submissions","\\local_latesubmissions\\analytics\\indicator\\cutoff_set","\\local_latesubmissions\\analytics\\indicator\\grade_item_weight","\\local_latesubmissions\\analytics\\indicator\\grade_to_pass_set","\\local_latesubmissions\\analytics\\indicator\\grading_duedate_set","\\local_latesubmissions\\analytics\\indicator\\guest_access_enabled","\\local_latesubmissions\\analytics\\indicator\\is_user_self_enrolled","\\local_latesubmissions\\analytics\\indicator\\maxattempts_set","\\local_latesubmissions\\analytics\\indicator\\self_enrol_enabled","\\local_latesubmissions\\analytics\\indicator\\send_notifications_set","\\local_latesubmissions\\analytics\\indicator\\send_student_notifications_set","\\local_latesubmissions\\analytics\\indicator\\submit_choice_close_to_close","\\local_latesubmissions\\analytics\\indicator\\submit_close_to_close","\\local_latesubmissions\\analytics\\indicator\\submit_close_to_due","\\local_latesubmissions\\analytics\\indicator\\user_activity","\\local_latesubmissions\\analytics\\indicator\\write_actions_amount","\\core_course\\analytics\\indicator\\completion_enabled","\\core_user\\analytics\\indicator\\user_profile_set"];

        foreach ($indicatorclasses as $indicatorclass) {
            $indicator = \core_analytics\manager::get_indicator($indicatorclass);
            $indicators[$indicator->get_id()] = $indicator;
        }
        $target = \core_analytics\manager::get_target('\local_latesubmissions\analytics\target\late_assign_submission');
        $model = \core_analytics\model::create($target, $indicators, '\local_latesubmissions\analytics\time_splitting\really_close_to_deadline');
    }
}
