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
 * @package    eliswidget_enrolment
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2014 Remote-Learner.net Inc (http://www.remote-learner.net)
 * @author     James McQuillan <james.mcquillan@remote-learner.net>
 *
 */

namespace eliswidget_enrolment\datatable;

/**
 * A datatable implementation that lists courses for a courseset.
 */
class coursesetcourse extends course {
    /** @var int The ID of the courseset we're getting courses for. */
    protected $coursesetid = null;

    /**
     * Get a list of desired table joins to be used in the get_search_results method.
     *
     * @param array $filters An array of requested filter data. Formatted like [filtername]=>[data].
     * @return array Array with members: First item is an array of JOIN sql fragments, second is an array of parameters used by
     *               the JOIN sql fragments.
     */
    protected function get_join_sql(array $filters = array()) {
        require_once(\elispm::lib('data/user.class.php'));
        $euserid = \user::get_current_userid();
        list($sql, $params) = parent::get_join_sql($filters);
        $newsql = [
                'JOIN {local_elisprogram_crssetcrs} crssetcrs ON crssetcrs.courseid = element.id AND crssetcrs.crssetid = ?',
                'LEFT JOIN {local_elisprogram_cls} cls ON cls.courseid = element.id',
                'LEFT JOIN {local_elisprogram_cls_enrol} enrol ON enrol.classid = cls.id AND enrol.userid = ?',
                'LEFT JOIN {local_elisprogram_waitlist} waitlist ON waitlist.classid = cls.id AND waitlist.userid = ?',
        ];
        $newparams = [$this->coursesetid, $euserid, $euserid];
        return [array_merge($sql, $newsql), array_merge($params, $newparams)];
    }

    /**
     * Get an array of fields to select in the get_search_results method.
     *
     * @param array $filters An array of requested filter data. Formatted like [filtername]=>[data].
     * @return array Array of fields to select.
     */
    protected function get_select_fields(array $filters = array()) {
        $selectfields = parent::get_select_fields($filters);
        $selectfields[] = 'count(enrol.id) AS numenrol';
        $selectfields[] = 'max(enrol.completestatusid) AS higheststatus';
        $selectfields[] = 'count(waitlist.id) AS numwaitlist';
        return $selectfields;
    }

    /**
     * Set the ID of the courseset we're getting courseset courses for.
     *
     * @param int $coursesetid The ID of the courseset we're getting courses for.
     */
    public function set_coursesetid($coursesetid) {
        $this->coursesetid = $coursesetid;
    }

    /**
     * Get a GROUP BY sql fragment to be used in the get_search_results method.
     *
     * @return string A GROUP BY sql fragment, if desired.
     */
    protected function get_groupby_sql() {
        return 'GROUP BY element.id';
    }

    /**
     * Get an ORDER BY sql fragment to be used in the get_search_results method.
     *
     * @return string An ORDER BY sql fragment, if desired.
     */
    protected function get_sort_sql() {
        return 'ORDER BY element.idnumber ASC, enrol.id DESC, waitlist.id DESC';
    }
}
