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
 * Accessed section activities
 *
 * This indicator represents the percentage of activities and resources
 * in the activity section that were by the student.
 *
 * @package   local_latesubmissions
 * @copyright 2016 David Monllao {@link http://www.davidmonllao.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_latesubmissions\analytics\indicator;

defined('MOODLE_INTERNAL') || die();

/**
 * Accessed section activities
 *
 * This indicator represents the percentage of activities and resources
 * in the activity section that were by the student.
 *
 * @package   local_latesubmissions
 * @copyright 2016 David Monllao {@link http://www.davidmonllao.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class accessed_section_activities extends \core_analytics\local\indicator\linear {

    /**
     * Returns the name.
     *
     * If there is a corresponding '_help' string this will be shown as well.
     *
     * @return \lang_string
     */
    public static function get_name() : \lang_string {
        return new \lang_string('accessedsectionactivities', 'local_latesubmissions');
    }

    /**
     * This indicator requires a user and a course module to be calculated.
     *
     * @return string[]
     */
    public static function required_sample_data() {
        return array('user', 'course_modules');
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

        if (!$logstore = \core_analytics\manager::get_analytics_logstore()) {
            throw new \coding_exception('No available log stores');
        }

        $user = $this->retrieve('user', $sampleid);
        $cm = $this->retrieve('course_modules', $sampleid);

        $modinfo = get_fast_modinfo($cm->course, $user->id);
        $modinstances = $modinfo->get_instances();

        $cmsectionactivities = [];
        foreach ($modinstances as $modname => $instances) {
            foreach ($instances as $cminfo) {
                if ($cminfo->section == $cm->section) {
                    $cmsectionactivities[$cminfo->id] = $cminfo->context->instanceid;
                }
            }
        }

        if (empty($cmsectionactivities)) {
            return null;
        }

        $select = "userid = :userid AND ";
        $params = array('userid' => $user->id);

        // Filter by context to use the logstore_standard_log db table index.
        list($contextsql, $contextparams) = $DB->get_in_or_equal($cmsectionactivities, SQL_PARAMS_NAMED);
        $select .= "contextlevel = :contextlevel AND contextinstanceid $contextsql";
        $params = $params + ['contextlevel' => CONTEXT_MODULE] + $contextparams;

        if ($starttime) {
            $select .= " AND timecreated > :starttime";
            $params = $params + array('starttime' => $starttime);
        }
        if ($endtime) {
            $select .= " AND timecreated <= :endtime";
            $params = $params + array('endtime' => $endtime);
        }
        $nrecords = $logstore->get_events_select_count($select, $params);

        // One access per resource to get the max score (multiple accesses to 1 single resource count as multiple accesses).
        $limit = count($cmsectionactivities);
        if ($limit > 2) {
            $halflimit = round($limit / 2);
        } else {
            $halflimit = 2;
        }

        $ranges = array(
            array('eq', 0),
            array('le', $halflimit),
            array('le', $limit),
            array('gt', $limit)
        );
        return $this->classify_value($nrecords, $ranges);
    }
}
