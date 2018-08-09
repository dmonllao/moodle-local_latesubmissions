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

require_once($CFG->libdir . '/gradelib.php');

/**
 * @package   local_latesubmissions
 * @copyright 2016 David Monllao {@link http://www.davidmonllao.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class grade_to_pass_set extends \core_analytics\local\indicator\binary {

    /**
     * @var \stdClass[] No memory usage worries, indicators are filled per-analysable basis.
     */
    protected $gradeitems = [];

    /**
     * Returns the name.
     *
     * If there is a corresponding '_help' string this will be shown as well.
     *
     * @return \lang_string
     */
    public static function get_name() : \lang_string {
        return new \lang_string('gradetopassset', 'local_latesubmissions');
    }

    /**
     * required_sample_data
     *
     * @return string[]
     */
    public static function required_sample_data() {
        return array('course');
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

        // It may not be available, but if it is the indicator checks if completion is enabled for the cm.
        $cm = $this->retrieve('course_modules', $sampleid);

        // If $cm is available for a sample will be available for all samples in the analysable so we can use
        // $id without worrying about courses and course_modules being mixed up.
        if ($cm) {
            $id = $cm->id;
        } else {
            $id = $course->id;
        }

        if (!isset($this->gradeitems[$id])) {
            if ($cm) {
                $module = $DB->get_record('modules', array('id' => $cm->module));
                $params = array('itemtype' => 'mod', 'itemmodule' => $module->name, 'iteminstance' => $cm->instance);
            } else {
                $params = array('itemtype' => 'course', 'courseid' => $course->id);
            }

            $this->gradeitems[$id] = \grade_item::fetch($params);
        }

        if (!$this->gradeitems[$id]) {
            return null;
        }

        if (floatval($this->gradeitems[$id]->gradepass) > 0) {
            return self::get_max_value();
        }
        return self::get_min_value();
    }
}
