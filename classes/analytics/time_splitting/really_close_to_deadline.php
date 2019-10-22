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
 * Time splitting method to execute predictions two days before the end date.
 *
 * @package   local_latesubmissions
 * @copyright 2017 David Monllao {@link http://www.davidmonllao.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_latesubmissions\analytics\time_splitting;

defined('MOODLE_INTERNAL') || die();

/**
 * Time splitting method to execute predictions two days before the end date.
 *
 * @package   local_latesubmissions
 * @copyright 2017 David Monllao {@link http://www.davidmonllao.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class really_close_to_deadline extends \core_analytics\local\time_splitting\base
        implements \core_analytics\local\time_splitting\before_now {

    public static function get_name() : \lang_string {
        return new \lang_string('timesplitting:reallyclosetodeadline', 'local_latesubmissions');
    }

    /**
     * Returns whether the course can be processed by this time splitting method or not.
     *
     * @param \core_analytics\analysable $analysable
     * @return bool
     */
    public function is_valid_analysable(\core_analytics\analysable $analysable) {

        $now = time();

        if (!$analysable->get_start()) {
            // No time start == no analysable.
            return false;
        }

        if (!$analysable->get_end()) {
            // No time end == no analysable.
            return false;
        }

        if ($now < $analysable->get_start()) {
            // Does not make sense to analyse something that has not yet begun.
            return false;
        }

        if ($analysable->get_end() - $analysable->get_start() < (DAYSECS * 4)) {
            // We will not have time to provide useful insights.
            return false;
        }

        if ($analysable->get_end() < $now) {
            // Past stuff is good for training.
            return true;
        }

        if ($now + (2 * DAYSECS) < $analysable->get_end()) {
            // We can not use this to get predictions as we have not reached 'due date' - 2 days.
            return false;
        }

        return true;
    }

    /**
     * This time-splitting method returns one single range, the start to two days before the end.
     *
     * @return array The list of ranges, each of them including 'start', 'end' and 'time'
     */
    protected function define_ranges() {

        $analysablestart = $this->analysable->get_start();

        $ranges = array(
            array(
                'start' => $analysablestart,
                'end' => $this->analysable->get_end() - (2 * DAYSECS),
                'time' => $this->analysable->get_end() - (2 * DAYSECS),
            )
        );

        return $ranges;
    }

    /**
     * Whether to cache or not the indicator calculations.
     * @return bool
     */
    public function cache_indicator_calculations(): bool {
        return false;
    }
}
