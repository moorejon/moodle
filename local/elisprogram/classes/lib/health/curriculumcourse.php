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
 * Checks that the local_elisprogram_pgm_crs table doesn't contain any links to stale CM course records.
 */
class curriculumcourse extends base {
    /** @var int Number of problem courses. */
    protected $count;

    /**
     * Constructor. Sets up data.
     */
    public function __construct() {
        require_once(\elispm::lib('data/curriculumcourse.class.php'));
        require_once(\elispm::lib('data/course.class.php'));
        global $DB;
        $sql = "SELECT COUNT(*)
                  FROM {".\curriculumcourse::TABLE."} curcrs
             LEFT JOIN {".\course::TABLE."} crs on curcrs.courseid = crs.id
                 WHERE crs.id IS NULL";
        $this->count = $DB->count_records_sql($sql);
    }

    /**
     * Check for problem existence.
     *
     * @return bool Whether the problem exists or not.
     */
    public function exists() {
        return ($this->count > 0) ? true : false;
    }

    /**
     * Get problem severity.
     *
     * @return string Severity of the problem.
     */
    public function severity() {
        return self::SEVERITY_CRITICAL;
    }

    /**
     * Get problem title.
     *
     * @return string Title of the problem.
     */
    public function title() {
        return get_string('health_curriculum', 'local_elisprogram');
    }

    /**
     * Get problem description.
     *
     * @return string Description of the problem.
     */
    public function description() {
        global $CFG;
        $strparams = ['count' => $this->count, 'table' => $CFG->prefix.\curriculumcourse::TABLE];
        return get_string('health_curriculumdesc', 'local_elisprogram', $strparams);
    }

    /**
     * Get problem solution.
     *
     * @return string Solution to the problem.
     */
    public function solution() {
        global $CFG;

        $solution = "<br/> USE {$CFG->dbname}; <br/>";
        $solution .= "DELETE FROM {$CFG->prefix}".\curriculumcourse::TABLE." WHERE courseid NOT IN (
                 SELECT id FROM {$CFG->prefix}".\course::TABLE." );";
        $msg = get_string('health_curriculumsoln', 'local_elisprogram').$solution;
        return $msg;
    }
}
