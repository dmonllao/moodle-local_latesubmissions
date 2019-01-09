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
 * This indicator represents the number of attempts allowed for an assignment activity.
 *
 * @package   local_latesubmissions
 * @copyright 2016 David Monllao {@link http://www.davidmonllao.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_latesubmissions\analytics\indicator;

defined('MOODLE_INTERNAL') || die();

/**
 * This indicator represents the number of attempts allowed for an assignment activity.
 *
 * @package   local_latesubmissions
 * @copyright 2016 David Monllao {@link http://www.davidmonllao.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class maxattempts_set extends \core_analytics\local\indicator\discrete {

    /**
     * Returns the name.
     *
     * If there is a corresponding '_help' string this will be shown as well.
     *
     * @return \lang_string
     */
    public static function get_name() : \lang_string {
        return new \lang_string('maxattemptsset', 'local_latesubmissions');
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
     * This discrete indicator returns four possible values.
     *
     * @return int[] An array of classes.
     */
    protected static function get_classes() {
        return [0, 1, 2, 3];
    }

    /**
     * Displays the indicator calculated value in human-ready language.
     *
     * @param  float  $value
     * @param  string $subtype
     * @return string
     */
    public function get_display_value($value, $subtype = false) {
        if ($value == 0) {
            return get_string('unlimitedattempts', 'assign');
        }
        return $value;
    }

    /**
     * This indicator does not really imply that something is ok or not ok.
     *
     * @param  float $value
     * @param  string $subtype
     * @return string
     */
    public function get_calculation_outcome($value, $subtype = false) {
        return self::OUTCOME_OK;
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
        $assign = $this->retrieve('assign', $sampleid);

        if ($assign->maxattempts == -1) {
            return 0;
        } else if ($assign->maxattempts == 1) {
            return 1;
        } else if ($assign->maxattempts == 2) {
            return 2;
        } else {
            return 3;
        }
    }
}
