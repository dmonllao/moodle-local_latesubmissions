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
 * Procrastination indicator based on previous choice activities.
 *
 * @package   local_latesubmissions
 * @copyright 2017 David Monllao {@link http://www.davidmonllao.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_latesubmissions\analytics\indicator;

defined('MOODLE_INTERNAL') || die();

/**
 * Procrastination indicator based on previous choice activities.
 *
 * @package   local_latesubmissions
 * @copyright 2016 David Monllao {@link http://www.davidmonllao.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class submit_choice_close_to_close extends submit_close_to_due {

    /**
     * @var string
     */
    protected static $modulename = 'choice';

    /**
     * @var string
     */
    protected static $fieldname = 'timeclose';

    /**
     * @var string
     */
    protected static $eventname = '\mod_choice\event\answer_submitted';

    /**
     * get_name
     *
     * @return string
     */
    public static function get_name(): \lang_string {
        return new \lang_string('choiceclosetoclose', 'local_latesubmissions');
    }
}
