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
 * This indicator represents the percentage of assignments completed by the student.
 *
 * @package   local_latesubmissions
 * @copyright 2016 David Monllao {@link http://www.davidmonllao.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_latesubmissions\analytics\indicator;

defined('MOODLE_INTERNAL') || die();

/**
 * This indicator represents the percentage of assignments completed by the student.
 *
 * @package   local_latesubmissions
 * @copyright 2016 David Monllao {@link http://www.davidmonllao.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assignment_submissions extends \core_analytics\local\indicator\linear {

    /**
     * Returns the name.
     *
     * If there is a corresponding '_help' string this will be shown as well.
     *
     * @return \lang_string
     */
    public static function get_name() : \lang_string {
        return new \lang_string('assignmentsubmissions', 'local_latesubmissions');
    }

    /**
     * The user, the course module and the course are required to process this indicator.
     *
     * @return string[]
     */
    public static function required_sample_data() {
        return array('user', 'course_modules', 'course');
    }

    /**
     * calculate_sample
     *
     * @param int $sampleid
     * @param string $sampleorigin
     * @param int $starttime
     * @param int $endtime
     * @return float
     */
    protected function calculate_sample($sampleid, $sampleorigin, $starttime = false, $endtime = false) {
        global $DB;

        $course = $this->retrieve('course', $sampleid);
        $coursemodule = $this->retrieve('course_modules', $sampleid);
        $user = $this->retrieve('user', $sampleid);

        if (!$logstore = \core_analytics\manager::get_analytics_logstore()) {
            throw new \coding_exception('No available log stores');
        }

        $assignments = [];

        $modinfo = get_fast_modinfo($course, $user->id);
        foreach ($modinfo->get_instances_of('assign') as $cm) {

            if ($cm->id == $coursemodule->id) {
                // Skip this sample assignment.
                continue;
            }

            if (!$cm->uservisible) {
                continue;
            }

            if (empty($this->cms[$cm->id])) {
                $this->cms[$cm->id] = $DB->get_record($cm->modname, array('id' => $cm->instance));
            }
            $instance = $this->cms[$cm->id];

            if ($instance->nosubmissions == 1 || $instance->teamsubmission || $instance->duedate > $endtime) {
                // Skip all of them (this ties this indicator to late_assign_submission target).
                continue;
            }

            $assignments[$cm->id] = $cm;
        }

        if (!$assignments) {
            return null;
        }

        // Number of submissions indexed by context.
        $submissions = [];

        list($ctxsql, $ctxparams) = $DB->get_in_or_equal(array_keys($assignments), SQL_PARAMS_NAMED);
        $select = "userid = :userid AND contextlevel = :contextlevel AND contextinstanceid $ctxsql AND " .
            "crud = :crud AND eventname = :eventname";
        $params = array('userid' => $user->id, 'contextlevel' => CONTEXT_MODULE,
            'crud' => 'u', 'eventname' => '\mod_assign\event\assessable_submitted') + $ctxparams;

        // We don't expect much logs so we can afford to retrieve them all.
        $logs = $logstore->get_events_select($select, $params, 'timecreated ASC', 0, 0);
        foreach ($logs as $log) {
            $submissions[$log->contextinstanceid] = true;
        }

        return ((count($submissions) / count($assignments)) * 2) - 1;
    }
}
