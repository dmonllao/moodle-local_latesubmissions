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
class forum_discussions extends \core_analytics\local\indicator\linear {

    /**
     * Returns the name.
     *
     * If there is a corresponding '_help' string this will be shown as well.
     *
     * @return \lang_string
     */
    public static function get_name() : \lang_string {
        return new \lang_string('forumdiscussions', 'local_latesubmissions');
    }

    /**
     * required_sample_data
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

        $course = $this->retrieve('course', $sampleid);
        $coursemodule = $this->retrieve('course_modules', $sampleid);
        $user = $this->retrieve('user', $sampleid);

        if (!$logstore = \core_analytics\manager::get_analytics_logstore()) {
            throw new \coding_exception('No available log stores');
        }

        $forums = [];

        $modinfo = get_fast_modinfo($course, $user->id);
        foreach ($modinfo->get_instances_of('forum') as $cm) {

            if (!$cm->uservisible) {
                continue;
            }

            if (empty($this->cms[$cm->id])) {
                $this->cms[$cm->id] = $DB->get_record($cm->modname, array('id' => $cm->instance));
            }
            $instance = $this->cms[$cm->id];

            if ($instance->type == 'news') {
                continue;
            }

            $forums[$cm->id] = $cm;
        }

        if (!$forums) {
            return null;
        }

        // New discussions presence indexed by context.
        $discussions = [];

        list($ctxsql, $ctxparams) = $DB->get_in_or_equal(array_keys($forums), SQL_PARAMS_NAMED);
        $select = "userid = :userid AND contextlevel = :contextlevel AND contextinstanceid $ctxsql AND " .
            "crud = :crud AND eventname = :eventname";
        $params = array('userid' => $user->id, 'contextlevel' => CONTEXT_MODULE,
            'crud' => 'u', 'eventname' => '\mod_forum\event\discussion_created') + $ctxparams;

        // We don't expect much logs so we can afford to retrieve them all.
        $logs = $logstore->get_events_select($select, $params, 'timecreated ASC', 0, 0);
        foreach ($logs as $log) {
            $discussions[$log->contextinstanceid] = true;
        }

        return ((count($discussions) / count($forums)) * 2) - 1;
    }
}
