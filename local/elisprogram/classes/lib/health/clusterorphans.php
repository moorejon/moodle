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
 * Check for clusters where the indicated parent cluster does not exist.
 */
class clusterorphans extends base {
    /** @var array Array of clusters where the indicated parent cluster does not exist. */
    protected $parentbad;

    /**
     * Constructor. Sets up internal data.
     */
    public function __construct() {
        global $DB;

        // Needed for db table constants.
        require_once(\elispm::lib('data/userset.class.php'));

        $this->parentbad = [];

        $sql = "SELECT child.name
                FROM
                {".\userset::TABLE."} child
                WHERE NOT EXISTS (
                    SELECT *
                    FROM {".\userset::TABLE."} parent
                    WHERE child.parent = parent.id
                )
                AND child.parent != 0";

        if ($clusters = $DB->get_recordset_sql($sql)) {
            foreach ($clusters as $cluster) {
                $this->parentbad[] = $cluster->name;
            }
            $clusters->close();
        }
    }

    /**
     * Check for problem existence.
     *
     * @return bool Whether the problem exists or not.
     */
    public function exists() {
        return (count($this->parentbad) > 0) ? true : false;
    }

    /**
     * Get problem title.
     *
     * @return string Title of the problem.
     */
    public function title() {
        return get_string('health_cluster_orphans', 'local_elisprogram');
    }

    /**
     * Get problem severity.
     *
     * @return string Severity of the problem.
     */
    public function severity() {
        return self::SEVERITY_ANNOYANCE;
    }

    /**
     * Get problem description.
     *
     * @return string Description of the problem.
     */
    public function description() {
        if (count($this->parentbad) > 0) {
            $msg = get_string('health_cluster_orphansdesc', 'local_elisprogram', array('count' => count($this->parentbad)));
            foreach ($this->parentbad as $parentname) {
                $msg .= '<li>'.$parentname.'</li>';
            }
            $msg .= '</ul>';
        } else {
            // We should not reach here but put in just in case.
            $msg =  get_string('health_cluster_orphansdescnone', 'local_elisprogram');
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
        $msg = get_string('health_cluster_orphanssoln', 'local_elisprogram', $CFG->dirroot);
        return $msg;
    }
}
