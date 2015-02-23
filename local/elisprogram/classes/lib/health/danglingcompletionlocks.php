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
 * Checks for any passing completion scores that are unlocked and linked to Moodle grade items which do not exist.
 */
class danglingcompletionlocks extends base {
    /** @var int Number of problem grades. */
    protected $count;

    /**
     * Constructor. Sets up data.
     */
    public function __construct() {
        global $CFG, $DB;

        require_once($CFG->dirroot.'/local/elisprogram/lib/setup.php');
        require_once(\elispm::lib('data/student.class.php'));

        // Check for unlocked, passed completion scores which are not associated with a valid Moodle grade item.
        $sql = "SELECT COUNT('x')
                  FROM {".\student_grade::TABLE."} ccg
            INNER JOIN {".\coursecompletion::TABLE."} ccc ON ccc.id = ccg.completionid
            INNER JOIN {".\classmoodlecourse::TABLE."} ccm ON ccm.classid = ccg.classid
            INNER JOIN {course} c ON c.id = ccm.moodlecourseid
             LEFT JOIN {grade_items} gi ON (gi.idnumber = ccc.idnumber AND gi.courseid = c.id)
                 WHERE ccg.locked = 0
                       AND ccc.idnumber != ''
                       AND ccg.grade >= ccc.completion_grade
                       AND gi.id IS NULL";

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
        return self::SEVERITY_SIGNIFICANT;
    }

    /**
     * Get problem title.
     *
     * @return string Title of the problem.
     */
    public function title() {
        return get_string('health_danglingcompletionlocks', 'local_elisprogram');
    }

    /**
     * Get problem description.
     *
     * @return string Description of the problem.
     */
    public function description() {
        return get_string('health_danglingcompletionlocksdesc', 'local_elisprogram', $this->count);
    }

    /**
     * Get problem solution.
     *
     * @return string Solution to the problem.
     */
    public function solution() {
        $msg = get_string('health_danglingcompletionlockssoln', 'local_elisprogram');
        return $msg;
    }
}
