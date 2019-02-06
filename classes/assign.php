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
 * Assignment analysable.
 *
 * @package   local_latesubmissions
 * @copyright 2017 David Monllao {@link http://www.davidmonllao.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_latesubmissions;

defined('MOODLE_INTERNAL') || die();

/**
 * Assignment analysable.
 *
 * @package   local_latesubmissions
 * @copyright 2017 David Monllao {@link http://www.davidmonllao.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assign implements \core_analytics\analysable {

    /**
     * @var boolean
     */
    protected $loaded = false;


    /**
     * @var int
     */
    protected $cmid = null;

    /**
     * @var \cm_info
     */
    protected $cminfo = null;

    /**
     * @var \stdClass
     */
    protected $instance = null;

    /**
     * Lightweight constructor as all site assign will be loaded into memory.
     *
     * Use self::load as a lazy loader so this instance is garbage collected
     * once the analytics API is done with it.
     *
     * @param int|\cm_info $cm
     */
    public function __construct($cm) {
        if (is_scalar($cm)) {
            $this->cmid = $cm;
        } else {
            $this->cmid = $cm->cminfo->id;
            $this->cminfo = $cm;
        }
    }

    /**
     * The unique identifier this analysable element has in the site.
     *
     * @return int.
     */
    public function get_id() {
        return $this->cmid;
    }

    /**
     * Analysable lazy loader.
     *
     * @return void
     */
    public function load() {
        global $DB;

        if ($this->cminfo === null) {
            list($ignored, $this->cminfo) = get_course_and_cm_from_cmid($this->cmid, 'assign', 0, -1);
        }

        $this->instance = $DB->get_record('assign', array('id' => $this->cminfo->instance));

        $this->loaded = true;
    }

    /**
     * The analysable human readable name.
     *
     * @return string
     */
    public function get_name() {
        if (!$this->loaded) {
            $this->load();
        }
        return $this->cminfo->get_formatted_name();
    }

    /**
     * The analysable context.
     *
     * @return \context
     */
    public function get_context() {
        if (!$this->loaded) {
            $this->load();
        }
        return $this->cminfo->context;
    }

    /**
     * The start of this analysable is the start of the course.
     *
     * @return int|false
     */
    public function get_start() {
        if (!$this->loaded) {
            $this->load();
        }

        $coursestart = $this->get_cm_info()->get_course()->startdate;

        if (!$coursestart) {
            // We can't use this assignment.
            return false;
        }

        $assignend = $this->get_end();
        if (!$assignend) {
            // We can't check that the course start date is alright.
            return false;
        }

        if ($coursestart + YEARSECS + (WEEKSECS * 4) <  $assignend) {
            // The course start is probably wrong and it does not matter if it is
            // not wrong, we do not consider courses that last more than 1 year.
            return false;
        }

        if ($coursestart > $assignend) {
            // Wrong dates.
            return false;
        }

        return $coursestart;
    }

    /**
     * The end is the assignment due date.
     *
     * @return int|false
     */
    public function get_end() {
        if (!$this->loaded) {
            $this->load();
        }

        if ($this->instance->duedate) {
            return $this->instance->duedate;
        }
        return false;
    }

    /**
     * Returns the cm_info
     * @return \cm_info
     */
    public function get_cm_info() {
        if (!$this->loaded) {
            $this->load();
        }
        return $this->cminfo;
    }

    /**
     * Returns the assignment activity instance.
     *
     * @return \stdClass
     */
    public function get_instance() {
        if (!$this->loaded) {
            $this->load();
        }
        return $this->instance;
    }
}
