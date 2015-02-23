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
 * Looks for unassociated class instances found in tracks.
 */
class trackclasses extends base {
    /** @var array Array of unattached class instances found in tracks. */
    protected $unattachedclasses;

    /**
     * Constructor. Sets up data.
     */
    public function __construct() {
        global $DB;

        // Needed for db table constants.
        require_once(\elispm::lib('data/track.class.php'));
        require_once(\elispm::lib('data/curriculumcourse.class.php'));

        $this->unattachedclasses = [];

        $sql = 'SELECT trkcls.id, trkcls.trackid, trkcls.courseid, trkcls.classid, trk.curid
                FROM {'.\trackassignment::TABLE.'} trkcls
                JOIN {'.\track::TABLE.'} trk ON trk.id = trkcls.trackid
                JOIN {'.\pmclass::TABLE.'} cls ON trkcls.classid = cls.id
                WHERE NOT EXISTS (
                    SELECT *
                    FROM {'.\curriculumcourse::TABLE.'} curcrs
                    WHERE trk.curid = curcrs.curriculumid
                    AND cls.courseid = curcrs.courseid
                )';
        $trackclasses = $DB->get_recordset_sql($sql);

        foreach ($trackclasses as $trackclass) {
            $this->unattachedclasses[] = $trackclass->id;
        }
        $trackclasses->close();
    }

    /**
     * Check for problem existence.
     *
     * @return bool Whether the problem exists or not.
     */
    public function exists() {
        return (count($this->unattachedclasses) > 0) ? true : false;
    }

    /**
     * Get problem title.
     *
     * @return string Title of the problem.
     */
    public function title() {
        return get_string('health_trackcheck', 'local_elisprogram');
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
     * Get problem description.
     *
     * @return string Description of the problem.
     */
    public function description() {
        return get_string('health_trackcheckdesc', 'local_elisprogram', count($this->unattachedclasses));
    }

    /**
     * Get problem solution.
     *
     * @return string Solution to the problem.
     */
    public function solution() {
        global $CFG;
        return get_string('health_trackchecksoln', 'local_elisprogram', $CFG->wwwroot);
    }
}
