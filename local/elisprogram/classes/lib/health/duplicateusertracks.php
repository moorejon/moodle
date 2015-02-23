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
 * Checks for duplicate usertrack records.
 */
class duplicateusertracks extends base {
    /** @var int Count of max course with duplicate completion elements. */
    protected $count;

    /**
     * Constructor.
     */
    public function __construct() {
        global $DB;
        $tablename = \usertrack::TABLE;
        $sql = "SELECT COUNT(ut1.id)
                  FROM {".$tablename."} ut1, {".$tablename."} ut2
                 WHERE ut1.id > ut2.id AND ut1.userid = ut2.userid AND ut1.trackid = ut2.trackid";
        $this->count = $DB->get_field_sql($sql);
    }

    /**
     * Check for problem existence.
     *
     * @return bool Whether the problem exists or not.
     */
    public function exists() {
        return($this->count > 0) ? true : false;
    }

    /**
     * Problem severity.
     *
     * @return string Severity of the problem.
     */
    public function severity() {
        return self::SEVERITY_SIGNIFICANT;
    }

    /**
     * Problem title.
     *
     * @return string Title of the problem.
     */
    public function title() {
        return get_string('health_dupusertrack', 'local_elisprogram');
    }

    /**
     * Problem description.
     *
     * @return string Description of the problem.
     */
    public function description() {
        $strparams = ['count' => $this->count, 'name' => \usertrack::TABLE];
        return get_string('health_dupusertrackdesc', 'local_elisprogram', $strparams);
    }

    /**
     * Problem solution.
     *
     * @return string Solution to the problem.
     */
    public function solution() {
        global $CFG;
        $strparams = ['name' => \usertrack::TABLE, 'wwwroot' => $CFG->wwwroot];
        return get_string('health_dupusertracksoln', 'local_elisprogram', $strparams);
    }

    /**
     * Get the number of duplicate usertracks.
     *
     * @return int The number of duplicate usertracks.
     */
    public function get_amount() {
        return $this->count;
    }
}
