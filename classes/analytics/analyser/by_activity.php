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
 * Abstract analyser for activity-level analysable elements.
 *
 * @package   local_atesubmissions
 * @copyright 2017 David Monllao {@link http://www.davidmonllao.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_latesubmissions\analytics\analyser;

defined('MOODLE_INTERNAL') || die();

/**
 * Abstract analyser for activity-level analysable elements.
 *
 * @package   local_atesubmissions
 * @copyright 2017 David Monllao {@link http://www.davidmonllao.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class by_activity extends \core_analytics\local\analyser\base {

    /**
     * Allow sub-classes to limit activities to a specific activity type.
     *
     * @return string|false The modname (no frankenstyle). False if all activity types.
     */
    protected function filter_by_mod() {
        return false;
    }

    /**
     * Returns the analysable class this analyser will use.
     *
     * @todo This class and method shouldn't be abstract, we should have a course_module analysable.
     * @return string
     */
    abstract protected function get_analysable_class();

    /**
     * Return the list of courses to analyse.
     *
     * @param string|null $action 'prediction', 'training' or null if no specific action needed.
     * @param \context[] $contexts Only analysables that depend on the provided contexts. All analysables in the system if empty.
     * @return \Iterator
     */
    public function get_analysables_iterator(?string $action = null, array $contexts = []) {
        global $DB;

        $analysableclass = $this->get_analysable_class();

        list($sql, $params) = $this->get_iterator_sql('course_modules', CONTEXT_MODULE, $action, 'cm', $contexts);

        $modname = $this->filter_by_mod();
        if ($modname) {
            $moduleid = $DB->get_field('modules', 'id', ['name' => $modname], MUST_EXIST);
            $sql .= " AND cm.module = :moduleid";
            $params = $params + ['moduleid' => $moduleid];
        }

        // Order by course so that get_fast_modinfo cached courses are reused.
        $ordersql = $this->order_sql('course', 'ASC', 'cm');

        $recordset = $DB->get_recordset_sql($sql . $ordersql, $params);

        if (!$recordset->valid()) {
            $this->add_log(get_string('noactivities', 'local_latesubmissions'));
            return new \ArrayIterator([]);
        }

        return new \core\dml\recordset_walk($recordset, function($record) use ($analysableclass) {

            // This should all be read from modinfo's cache.
            $modinfo = get_fast_modinfo($record->course, -1);
            $cminfo = $modinfo->get_cm($record->id);
            if (!$cminfo) {
                throw new \coding_exception('No course module with the provided id.');
            }

            $context = \context_helper::preload_from_record($record);
            return new $analysableclass($cminfo, $context);
        });
    }

    /**
     * Can be limited to course categories, courses or specific activities.
     *
     * @return array
     */
    public static function context_restriction_support(): array {
        return [CONTEXT_COURSE, CONTEXT_COURSECAT];
    }
}
