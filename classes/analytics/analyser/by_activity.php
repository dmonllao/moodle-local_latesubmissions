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
 * Abstract analyser for activities.
 *
 * @package   core_analytics
 * @copyright 2017 David Monllao {@link http://www.davidmonllao.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_latesubmissions\analytics\analyser;

defined('MOODLE_INTERNAL') || die();

/**
 * Abstract analyser for activities.
 *
 * @package   core_analytics
 * @copyright 2017 David Monllao {@link http://www.davidmonllao.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class by_activity extends \core_analytics\local\analyser\base {

    /**
     * Allow sub-classes to reuse this class easily.
     *
     * @return string|false
     */
    protected function filter_by_mod() {
        return false;
    }

    /**
     * Returns the analysable class.
     *
     * @todo This shouldn't be abstract, we should have a course_module analysable.
     * @return string
     */
    abstract protected function get_analysable_class();

    /**
     * Return the list of activities to analyse.
     *
     * @return \local_latesubmissions\assign[]
     */
    public function get_analysables() {
        global $DB;

        $modname = $this->filter_by_mod();

        $analysableclass = $this->get_analysable_class();

        $analysables = array();

        // Default to all system courses.
        if (!empty($this->options['filter'])) {
            $courses = [$DB->get_record('course', ['id' => $this->options['filter']])];
        } else {
            // Iterate through all potentially valid courses.
            $courses = get_courses('all', 'c.sortorder ASC', 'c.id');
        }

        foreach ($courses as $course) {
            $modinfo = get_fast_modinfo($course, -1);
            if (!$modname) {
                $cms = $modinfo->get_instances();
            } else {
                $cms = $modinfo->get_instances_of($modname);
            }

            foreach ($cms as $cm) {
                $analysables[$cm->id] = new $analysableclass($cm->id);
            }
        }

        if (empty($analysables)) {
            $this->log[] = get_string('noactivities', 'local_latesubmissions');
        }

        return $analysables;
    }
}
