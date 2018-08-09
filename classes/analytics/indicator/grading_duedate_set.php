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
class grading_duedate_set extends \core_analytics\local\indicator\binary {

    /**
     * @var int[] Assign instances gradingduedate values.
     */
    protected $gradingduedates;

    /**
     * Returns the name.
     *
     * If there is a corresponding '_help' string this will be shown as well.
     *
     * @return \lang_string
     */
    public static function get_name() : \lang_string {
        return new \lang_string('gradingduedateset', 'local_latesubmissions');
    }

    /**
     * required_sample_data
     *
     * @return string[]
     */
    public static function required_sample_data() {
        return array('assign');
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

        $cm = $this->retrieve('course_modules', $sampleid);

        if (!isset($this->gradeitems[$cm->id])) {
            if (!$instance =  $DB->get_record('assign', array('id' => $cm->instance), 'id, gradingduedate')) {
                $this->gradingduedates[$cm->id] = null;
            } else {
                $this->gradingduedates[$cm->id] = $instance->gradingduedate;
            }
        }

        // The indicator can not be calculated.
        if (is_null($this->gradingduedates[$cm->id])) {
            return null;
        }

        if ($this->gradingduedates[$cm->id]) {
            return self::get_max_value();
        }

        return self::get_min_value();
    }
}
