<?php
/**
 * ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2008-2014 Remote-Learner.net Inc (http://www.remote-learner.net)
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package    local_elisprogram
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2014 Remote-Learner.net Inc (http://www.remote-learner.net)
 *
 */

namespace local_elisprogram\lib\health;

/**
 * Checks for duplicate course completion elements
 */
class duplicatecourselos extends base {
    /** @var int Count of max course with duplicate completion elements. */
    protected $count;

    /**
     * Constructor. Sets up data.
     */
    public function __construct() {
        global $DB;
        $sql = "SELECT MAX(count)
                  FROM (SELECT COUNT('x') AS count FROM {local_elisprogram_crs_cmp} GROUP BY courseid, idnumber) duplos";
        $this->count = $DB->get_field_sql($sql);
    }

    /**
     * Check for problem existence.
     *
     * @return bool Whether the problem exists or not.
     */
    public function exists() {
        return ($this->count > 1) ? true : false;
    }

    /**
     * Get problem severity.
     *
     * @return string Severity of the problem.
     */
    public function severity() {
        return self::SEVERITY_SIGNIFICANT; // ANNOYANCE ???
    }

    /**
     * Get problem title.
     *
     * @return string Title of the problem.
     */
    public function title() {
        return get_string('health_dupcourselos', 'local_elisprogram');
    }

    /**
     * Get problem description.
     *
     * @return string Description of the problem.
     */
    public function description() {
        return get_string('health_dupcourselosdesc', 'local_elisprogram');
    }

    /**
     * Get problem solution.
     *
     * @return string Solution to the problem.
     */
    public function solution() {
        return get_string('health_dupcourselossoln', 'local_elisprogram');
    }
}
