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
 * A datatable implementation that lists courses a user is enroled in, but which are not part of a program the user
 * is enroled in.
 */
class nonprogramcourse extends course {
    /** @var int The ID of the user we're getting non-program courses for. */
    protected $userid = null;

    /**
     * Get a list of desired table joins to be used in the get_search_results method.
     *
     * @param array $filters An array of filter data received from the front-end. Indexed by filter alias.
     * @return array Array with members: First item is an array of JOIN sql fragments, second is an array of parameters used by
     *               the JOIN sql fragments.
     */
    protected function get_join_sql(array $filters = array()) {
        global $CFG;
        require_once(\elispm::lib('data/pmclass.class.php'));
        require_once(\elispm::lib('data/student.class.php'));
        require_once(\elispm::lib('data/curriculumcourse.class.php'));
        require_once(\elispm::lib('data/curriculumstudent.class.php'));
        require_once(\elispm::lib('data/crssetcourse.class.php'));
        require_once(\elispm::lib('data/programcrsset.class.php'));

        list($sql, $params) = parent::get_join_sql($filters);

        $newsql = [
                'JOIN {'.\pmclass::TABLE.'} cls ON cls.courseid = element.id',
                'JOIN {'.\student::TABLE.'} stu ON stu.classid = cls.id',
                'LEFT JOIN {'.\curriculumcourse::TABLE.'} pgmcrs ON pgmcrs.courseid = element.id',
                'LEFT JOIN {'.\curriculumstudent::TABLE.'} pgmstu ON pgmstu.userid = stu.userid AND pgmstu.curriculumid = pgmcrs.curriculumid',
                'LEFT JOIN {'.\crssetcourse::TABLE.'} crssetcrs ON crssetcrs.courseid = element.id',
                'LEFT JOIN {'.\programcrsset::TABLE.'} pgmcrsset ON pgmcrsset.crssetid = crssetcrs.crssetid',
                'LEFT JOIN {'.\curriculumstudent::TABLE.'} pgmstu2 ON pgmstu2.userid = stu.userid AND pgmstu2.curriculumid = pgmcrsset.prgid',
        ];
        $newparams = [];
        return [array_merge($sql, $newsql), array_merge($params, $newparams)];
    }

    /**
     * Converts an array of requested filter data into an SQL WHERE clause.
     *
     * @param array $filters An array of requested filter data. Formatted like [filtername]=>[data].
     * @return array An array consisting of the SQL WHERE clause, and the parameters for the SQL.
     */
    protected function get_filter_sql(array $filters = array()) {
        $filters[] = ['sql' => 'stu.userid = ?', 'params' => [$this->userid]];
        $filters[] = ['sql' => '(pgmcrs.id IS NULL or pgmstu.id IS NULL)'];
        $filters[] = ['sql' => '(pgmstu2.id IS NULL or crssetcrs.id IS NULL or pgmcrsset.id IS NULL)'];
        return parent::get_filter_sql($filters);
    }

    /**
     * Get an array of fields to select in the get_search_results method.
     *
     * @param array $filters An array of requested filter data. Formatted like [filtername]=>[data].
     * @return array Array of fields to select.
     */
    protected function get_select_fields(array $filters = array()) {
        $selectfields = parent::get_select_fields($filters);
        $selectfields[] = '1 AS numenrol';
        $selectfields[] = '0 AS higheststatus';
        return $selectfields;
    }

    /**
     * Set the ID of the user we're getting non-program courses for.
     *
     * @param int $userid The ID of the user we're getting non-program courses for.
     */
    public function set_userid($userid) {
        $this->userid = $userid;
    }
}
