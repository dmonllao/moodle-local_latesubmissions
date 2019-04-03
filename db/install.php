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
 * Late submissions installer.
 *
 * @package    local_latesubmissions
 * @copyright  2019 David Monllao
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Late submissions plugin installer.
 *
 * @package    local_latesubmissions
 * @return null
 */
function xmldb_local_latesubmissions_install() {
    global $DB;

    // Enable new time splitting methods.
    $enabledtimesplittings = get_config('analytics', 'timesplittings');
    $newtimesplittingmethod = '\local_latesubmissions\analytics\time_splitting\really_close_to_deadline';
    if (!strstr($enabledtimesplittings, $newtimesplittingmethod)) {
        $enabledtimesplittings .= ',' . $newtimesplittingmethod;
        set_config('timesplittings', $enabledtimesplittings, 'analytics');
    }
}
