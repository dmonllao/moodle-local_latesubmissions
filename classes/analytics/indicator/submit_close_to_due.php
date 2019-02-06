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
 * Procrastination indicator based on previous assignment submissions.
 *
 * @package   local_latesubmissions
 * @copyright 2017 David Monllao {@link http://www.davidmonllao.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_latesubmissions\analytics\indicator;

defined('MOODLE_INTERNAL') || die();

/**
 * Procrastination indicator based on previous assignment submissions.
 *
 * @package   local_latesubmissions
 * @copyright 2016 David Monllao {@link http://www.davidmonllao.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class submit_close_to_due extends \core_analytics\local\indicator\linear {

    /**
     * @var \stdClass[] No memory usage worries, indicators are filled per-analysable basis.
     */
    protected $cms = [];

    /**
     * @var string
     */
    protected static $modulename = 'assign';

    /**
     * @var string
     */
    protected static $fieldname = 'duedate';

    /**
     * @var string
     */
    protected static $eventname = '\mod_assign\event\assessable_submitted';

    /**
     * get_name
     *
     * @return string
     */
    public static function get_name(): \lang_string {
        return new \lang_string('assignclosetodue', 'local_latesubmissions');
    }

    /**
     * A user, the course and a course module are required.
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
     * @param int|false $notusedstarttime
     * @param int|false $notusedendtime
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
        foreach ($modinfo->get_instances_of(static::$modulename) as $cm) {

            if ($cm->id == $coursemodule->id) {
                // Skip this sample assignment.
                continue;
            }

            if (empty($this->cms[$cm->id])) {
                $this->cms[$cm->id] = $DB->get_record($cm->modname, array('id' => $cm->instance));
            }
            $instance = $this->cms[$cm->id];

            if ($instance->{static::$fieldname} && $instance->{static::$fieldname} > $starttime &&
                    $instance->{static::$fieldname} < $endtime) {
                if ($cm->uservisible) {
                    $select = 'userid = :userid AND contextlevel = :contextlevel AND contextinstanceid = :cmid ' .
                        'AND eventname = :eventname';
                    $params = array('userid' => $user->id, 'contextlevel' => CONTEXT_MODULE,
                        'cmid' => $cm->id, 'eventname' => static::$eventname);
                    $events = $logstore->get_events_select($select, $params, 'timecreated ASC', 0, 1);
                    if (!$events) {
                        $scores[] = -1;
                        continue;
                    }

                    $submission = reset($events);
                    if ($submission->timecreated > $instance->{static::$fieldname}) {
                        $scores[] = -0.66;
                    } else if ($instance->{static::$fieldname} - DAYSECS < $submission->timecreated) {
                        $scores[] = -0.33;
                    } else if ($instance->{static::$fieldname} - (DAYSECS * 2) < $submission->timecreated) {
                        $scores[] = 0.5;
                    } else {
                        $scores[] = 1;
                    }
                }
            }
        }

        // Null if no activities found for this user.
        if (empty($scores)) {
            return null;
        }

        return array_sum($scores) / count($scores);
    }
}
