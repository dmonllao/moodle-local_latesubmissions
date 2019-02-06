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
 * Percent of quizzes attempted by the student.
 *
 * @package   local_latesubmissions
 * @copyright 2017 David Monllao {@link http://www.davidmonllao.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_latesubmissions\analytics\indicator;

defined('MOODLE_INTERNAL') || die();

/**
 * Percent of quizzes attempted by the student.
 *
 * @package   local_latesubmissions
 * @copyright 2016 David Monllao {@link http://www.davidmonllao.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quiz_attempts extends \core_analytics\local\indicator\linear {

    /**
     * get_name
     *
     * @return string
     */
    public static function get_name(): \lang_string {
        return new \lang_string('quizattempts', 'local_latesubmissions');
    }

    /**
     * required_sample_data
     *
     * @return string[]
     */
    public static function required_sample_data() {
        return array('course', 'user', 'course_modules');
    }

    /**
     * calculate_sample
     *
     * @param int $sampleid
     * @param string $sampleorigin
     * @param int|false $starttime
     * @param int|false $endtime
     * @return float
     */
    public function calculate_sample($sampleid, $sampleorigin, $starttime = false, $endtime = false) {
        global $DB;

        // TODO Not proper OOP with sibling classes extending from here, this needs
        // a parent class for all of them.
        $course = $this->retrieve('course', $sampleid);
        $coursemodule = $this->retrieve('course_modules', $sampleid);
        $user = $this->retrieve('user', $sampleid);

        if (!$logstore = \core_analytics\manager::get_analytics_logstore()) {
            throw new \coding_exception('No available log stores');
        }

        $scores = [];

        $modinfo = get_fast_modinfo($course, $user->id);
        foreach ($modinfo->get_instances_of('quiz') as $cm) {

            if (empty($this->cms[$cm->id])) {
                $this->cms[$cm->id] = $DB->get_record($cm->modname, array('id' => $cm->instance));
            }
            $instance = $this->cms[$cm->id];

            if (!$cm->uservisible) {
                continue;
            }

            $select = 'userid = :userid AND contextlevel = :contextlevel AND contextinstanceid = :cmid ' .
                'AND eventname = :eventname';
            $params = array('userid' => $user->id, 'contextlevel' => CONTEXT_MODULE,
                'cmid' => $cm->id, 'eventname' => '\mod_quiz\event\attempt_submitted');
            if ($starttime) {
                $select .= " AND timecreated >= :starttime";
                $params['starttime'] = $starttime;
            }
            if ($endtime) {
                $select .= " AND timecreated <= :endtime";
                $params['endtime'] = $endtime;
            }
            $events = $logstore->get_events_select($select, $params, 'timecreated ASC', 0, 1);

            if (!$events) {
                $scores[] = -1;
                continue;
            }

            if ($instance->attempts == 1) {
                $scores[] = 1;
                continue;
            }

            $nevents = count($events);
            if ($nevents < 2) {
                $scores[] = 0.5;
            } else {
                $scores[] = 1;
            }
        }

        // Null if no activities found for this user.
        if (empty($scores)) {
            return null;
        }

        return array_sum($scores) / count($scores);
    }

}
