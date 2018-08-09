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
 * @package   local_latesubmissions
 * @copyright 2016 David Monllao {@link http://www.davidmonllao.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_latesubmissions\analytics\indicator;

defined('MOODLE_INTERNAL') || die();

/**
 * @package   local_latesubmissions
 * @copyright 2016 David Monllao {@link http://www.davidmonllao.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_activity extends \core_analytics\local\indicator\linear {

    /**
     * Returns the name.
     *
     * If there is a corresponding '_help' string this will be shown as well.
     *
     * @return \lang_string
     */
    public static function get_name() : \lang_string {
        return new \lang_string('useractivity', 'local_latesubmissions');
    }

    /**
     * required_sample_data
     *
     * @return string[]
     */
    public static function required_sample_data() {
        return array('user');
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

        $user = $this->retrieve('user', $sampleid);
        $select = "userid = :userid AND ";
        $params = array('userid' => $user->id);

        if (!$logstore = \core_analytics\manager::get_analytics_logstore()) {
            throw new \coding_exception('No available log stores');
        }

        // Filter by context to use the logstore_standard_log db table index.
        $context = \context_course::instance(SITEID);
        $select .= "contextlevel = :contextlevel AND contextinstanceid = :contextinstanceid";
        $params = $params + array('contextlevel' => $context->contextlevel,
            'contextinstanceid' => $context->instanceid);

        if ($starttime) {
            $select .= " AND timecreated > :starttime";
            $params = $params + array('starttime' => $starttime);
        }
        if ($endtime) {
            $select .= " AND timecreated <= :endtime";
            $params = $params + array('endtime' => $endtime);
        }

        $nrecords = $logstore->get_events_select_count($select, $params);

        // We need to adapt the limits to the time range duration.
        $nweeks = $this->get_time_range_weeks_number($starttime, $endtime);

        // Careful with this, depends on the course.
        $limit = $nweeks * 3 * 10;
        $ranges = array(
            array('eq', 0),
            array('le', 3),
            array('le', $limit),
            array('gt', $limit)
        );
        return $this->classify_value($nrecords, $ranges);
    }
}
