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
 * Checks if there are more Moodle users than ELIS users.
 */
class usersync extends base {
    /** @var int Number of Moodle users that do not have a corresponding ELIS user. */
    protected $count;

    /** @var int Number of duplicate idnumbers found within Moodle users. */
    protected $dupids;

    /**
     * Constructor. Sets up data.
     */
    public function __construct() {
        global $CFG, $DB;
        $params = array($CFG->mnet_localhost_id, $CFG->mnet_localhost_id);
        $sql = "SELECT COUNT('x')
                  FROM {user}
                 WHERE username != 'guest'
                   AND deleted = 0
                   AND confirmed = 1
                   AND mnethostid = ?
                   AND idnumber != ''
                   AND firstname != ''
                   AND lastname != ''
                   AND email != ''
                   AND NOT EXISTS (
                         SELECT 'x'
                           FROM {".\user::TABLE."} cu
                          WHERE cu.idnumber = {user}.idnumber
                     )
                   AND NOT EXISTS (
                       SELECT 'x'
                         FROM {".\user::TABLE."} cu
                        WHERE cu.username = {user}.username
                          AND {user}.mnethostid = ?
                     )";

        $this->count = $DB->count_records_sql($sql, $params);

        $sql = "SELECT COUNT('x')
                  FROM {user} usr
                 WHERE deleted = 0
                   AND idnumber IN (
                         SELECT idnumber
                           FROM {user}
                          WHERE username != 'guest'
                            AND deleted = 0
                            AND confirmed = 1
                            AND mnethostid = ?
                            AND id != usr.id
                     )";

        $this->dupids = $DB->count_records_sql($sql, $params);
    }

    /**
     * Check for problem existence.
     *
     * @return bool Whether the problem exists or not.
     */
    public function exists() {
        return ($this->count > 0 || $this->dupids > 0) ? true : false;
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
        return get_string('health_user_sync', 'local_elisprogram');
    }

    /**
     * Get problem description.
     *
     * @return string Description of the problem.
     */
    public function description() {
        $msg = '';
        if ($this->count > 0) {
            $msg = get_string('health_user_syncdesc', 'local_elisprogram', $this->count);
        }
        if ($this->dupids > 0) {
            if (!empty($msg)) {
                $msg .= "<br/>\n";
            }
            $msg .= get_string('health_user_dupiddesc', 'local_elisprogram', $this->dupids);
        }
        return $msg;
    }

    /**
     * Get problem solution.
     *
     * @return string Solution to the problem.
     */
    public function solution() {
        global $CFG;

        $msg = '';
        if ($this->dupids > 0) {
            $msg = get_string('health_user_dupidsoln', 'local_elisprogram');
        }
        if ($this->count > $this->dupids) {
            // ELIS-3963: Only run migrate script if more mismatches then dups.
            if (!empty($msg)) {
                $msg .= "<br/>\n";
            }
            $msg .= get_string('health_user_syncsoln', 'local_elisprogram', $CFG->wwwroot);
        }
        return $msg;
    }
}
