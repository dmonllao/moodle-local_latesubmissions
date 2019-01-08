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
 *
 * @package   core
 * @copyright 2017 David Monllao {@link http://www.davidmonllao.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_latesubmissions\analytics\analyser;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/assign/locallib.php');
require_once($CFG->dirroot . '/lib/enrollib.php');

/**
 *
 * @package   core
 * @copyright 2017 David Monllao {@link http://www.davidmonllao.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assign_submissions extends by_activity {

    protected function filter_by_mod() {
        return 'assign';
    }

    protected function get_analysable_class() {
        return '\local_latesubmissions\assign';
    }

    /**
     *
     * @return string
     */
    public function get_samples_origin() {
        return 'assign_submission';
    }

    protected function cmid_from_sampleid($sampleid) {
        global $DB;

        // TODO Add a request cache with 2 request-level arrays. One to list the
        // cms we already fetched and another one, indexed by ass.id and with cm.id
        // value.
        $sql = "SELECT cm.id FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module
                  JOIN {assign_submission} ass ON ass.assignment = cm.instance
                 WHERE ass.id = :id";
        $cm = $DB->get_record_sql($sql, ['id' => $sampleid]);
        return $cm->id;
    }

    /**
     * Returns the analysable of a sample
     *
     * @param int $sampleid
     * @return \core_analytics\analysable
     */
    public function get_sample_analysable($sampleid) {
        $cmid = $this->cmid_from_sampleid($sampleid);
        return new \local_latesubmissions\assign($cmid);
    }

    /**
     *
     * @return string[]
     */
    protected function provided_sample_data() {
        return array('course', 'user', 'user_enrolments', 'context', 'course_modules', 'assign', 'assign_submission');
    }

    /**
     * Returns the context of a sample.
     *
     * @param int $sampleid
     * @return \context
     */
    public function sample_access_context($sampleid) {
        $cmid = $this->cmid_from_sampleid($sampleid);
        return \context_module::instance($cmid);
    }

    /**
     *
     * @param \core_analytics\analysable $cm
     * @return array
     */
    protected function get_all_samples(\core_analytics\analysable $cm) {
        global $DB;

        $assign = new \assign($cm->get_cm_info()->context, $cm->get_cm_info(), $cm->get_cm_info()->get_course());

        $participants = $assign->list_participants(0, false);

        $samples = array([], []);
        if (!$participants) {
            return $samples;
        }

        $userids = array_keys($participants);

        list($usersql, $userparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $params = $userparams + ['assign' => $cm->get_cm_info()->instance, 'latest' => 1];
        $submissions = $DB->get_records_select('assign_submission', "assignment = :assign AND userid $usersql AND latest = :latest",
          $params);

        if (count($submissions) < count($participants)) {
            // Create the missing assign_submission objects, even if they are empty.
            foreach ($participants as $participant) {
                if ($newsubmission = $assign->get_user_submission($participant->id, true)) {
                    $submissions[$newsubmission->id] = $newsubmission;
                }
            }
        }

        $enrolments = enrol_get_course_users($cm->get_cm_info()->get_course()->id, true, $userids);
        $uebyuserid = array_reduce($enrolments, function($carry, $ue) {
            $carry[$ue->id] = (object)[
                'id' => $ue->ueid,
                'userid' => $ue->id,
                'status' => $ue->uestatus,
                'enrolid' => $ue->ueenrolid,
                'timestart' => $ue->uetimestart,
                'timeend' => $ue->uetimeend,
                'modifierid' => $ue->uemodifierid,
                'timecreated' => $ue->uetimecreated,
                'timemodified' => $ue->uetimemodified,
            ];
            return $carry;
        }, []);

        foreach ($submissions as $as) {
            $samples[0][$as->id] = $as->id;
            $samples[1][$as->id] = array(
                'course' => $assign->get_course(),
                'user' => $participants[$as->userid],
                'user_enrolments' => $uebyuserid[$as->userid],
                'context' => $cm->get_cm_info()->context,
                'course_modules' => $cm->get_cm_info()->get_course_module_record(),
                'assign' => $assign->get_instance(),
                'assign_submission' => $as
            );
        }
        return $samples;
    }

    /**
     * Returns samples data from sample ids.
     *
     * @param int[] $sampleids
     * @return array
     */
    public function get_samples($sampleids) {
        global $DB;

        list($sql, $params) = $DB->get_in_or_equal($sampleids, SQL_PARAMS_NAMED);
        $submissions = $DB->get_records_select('assign_submission', "id $sql", $params);

        // To save db queries.
        // TODO Maybe replace by ad-hoc caches with limited staticaccelerationsize for static models get_samples
        // call, it can end up containing a large amount of different assignments.
        $courses = [];
        $cms = [];
        $assigns = [];
        $participants = [];
        $uebyuserid = [];

        $samples = array([], []);
        foreach ($submissions as $as) {

            if (empty($assigns[$as->assignment])) {
                list($course, $cm) = get_course_and_cm_from_instance($as->assignment, 'assign', 0, -1);
                $cms[$as->assignment] = $cm;
                $courses[$cm->course] = $course;
                $assigns[$as->assignment] = new \assign($cm->context, $cm, $course);
                $participants[$as->assignment] = $assigns[$as->assignment]->list_participants(0, false);

                if (empty($uebyuserid[$cm->course])) {
                    $enrolments = enrol_get_course_users($course->id, true, array_keys($participants[$as->assignment]));
                    $uebyuserid[$course->id] = array_reduce($enrolments, function($carry, $ue) {
                        $carry[$ue->id] = (object)[
                            'id' => $ue->ueid,
                            'userid' => $ue->id,
                            'status' => $ue->uestatus,
                            'enrolid' => $ue->ueenrolid,
                            'timestart' => $ue->uetimestart,
                            'timeend' => $ue->uetimeend,
                            'modifierid' => $ue->uemodifierid,
                            'timecreated' => $ue->uetimecreated,
                            'timemodified' => $ue->uetimemodified,
                        ];
                        return $carry;
                    }, []);
                }
            }

            $samplecm = $cms[$as->assignment];

            $samples[0][$as->id] = $as->id;
            $samples[1][$as->id] = array(
                'course' => $assigns[$as->assignment]->get_course(),
                'user' => $participants[$as->assignment][$as->userid],
                'user_enrolments' => $uebyuserid[$samplecm->course][$as->userid],
                'context' => $samplecm->context,
                'course_modules' => $cm->get_course_module_record(),
                'assign' => $assigns[$as->assignment]->get_instance(),
                'assign_submission' => $as
            );
        }

        return $samples;
    }

    /**
     * Returns the sample description
     *
     * @param int $sampleid
     * @param int $contextid
     * @param array $sampledata
     * @return array array(string, \renderable)
     */
    public function sample_description($sampleid, $contextid, $sampledata) {
        $description = fullname($sampledata['user'], true, array('context' => $contextid));
        return array($description, new \user_picture($sampledata['user']));
    }
}
