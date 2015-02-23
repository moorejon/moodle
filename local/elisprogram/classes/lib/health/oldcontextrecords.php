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
 * Checks for pre-2.6 context records.
 */
class oldcontextrecords extends base {
    /** @var array Array of tables and the number of old context records. */
    protected $count = array();

    /**
     * Constructor.
     */
    public function __construct() {
        global $DB;

        $tables = array('context', 'role_context_levels', 'local_eliscore_field_clevels', 'local_eliscore_fld_cat_ctx');
        foreach ($tables as $table) {
            $sql = 'SELECT count(1) FROM {'.$table.'} WHERE contextlevel IN (1001, 1002, 1003, 1004, 1005, 1006)';
            $this->count[$table] = $DB->count_records_sql($sql);
        }
    }

    /**
     * Check for problem existence.
     *
     * @return bool Whether the problem exists or not.
     */
    public function exists() {
        foreach ($this->count as $table => $count) {
            if ($count > 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * Problem severity.
     *
     * @return string Severity of the problem.
     */
    public function severity() {
        return self::SEVERITY_NOTICE;
    }

    /**
     * Problem title.
     *
     * @return string Title of the problem.
     */
    public function title() {
        return get_string('health_oldcontextrecs', 'local_elisprogram');
    }

    /**
     * Problem description.
     *
     * @return string Description of the problem.
     */
    public function description() {
        $problemtables = [];
        foreach ($this->count as $table => $count) {
            if ($count > 0) {
                $problemtables[] = $table;
            }
        }
        $problemtables = implode(', ', $problemtables);
        return get_string('health_oldcontextrecsdesc', 'local_elisprogram', $problemtables);
    }

    /**
     * Problem solution.
     *
     * @return string Solution to the problem.
     */
    public function solution() {
        $msg = get_string('health_oldcontextrecssoln', 'local_elisprogram');
        return $msg;
    }
}
