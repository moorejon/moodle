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

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__).'/../../../../config.php');
require_once($CFG->dirroot.'/local/elisprogram/lib/setup.php');
require_once(elis::lib('data/data_object.class.php'));
require_once(elispm::lib('data/curriculum.class.php'));
require_once(elispm::lib('data/courseset.class.php'));

class programcrsset extends elis_data_object {
    /* @const string the DB table */
    const TABLE = 'local_elisprogram_prgcrsset';

    /* @var string the class's verbose name */
    protected $verbose_name = 'programcourseset';

    /* DB fields: */
    protected $_dbfield_id;
    protected $_dbfield_crssetid;
    protected $_dbfield_prgid;
    protected $_dbfield_reqcredits;
    protected $_dbfield_reqcourses;
    protected $_dbfield_andor;
    protected $_dbfield_timemodified;
    protected $_dbfield_timecreated;

    /* @var array the class's associations */
    public static $associations = array(
        'program' => array(
            'class' => 'curriculum',
            'idfield' => 'prgid'
        ),
        'crsset' => array(
            'class' => 'courseset',
            'idfield' => 'crssetid'
        )
    );

    /* @var array validation rules */
    public static $validation_rules = array(
        array('validation_helper', 'is_unique_prgid_crssetid'),
        'validate_reqcourses_in_courseset',
        'validate_reqcredits_in_courseset'
    );

    /**
     * Validation for number of required courses available in courseset
     */
    public function validate_reqcourses_in_courseset() {
        $totcourses = $this->crsset->total_courses();
        if ($totcourses < $this->reqcourses) {
            $a = new stdClass;
            $a->idnumber = $this->crsset->idnumber;
            $a->name = $this->crsset->name;
            $a->programname = $this->program->name;
            $a->programidnumber = $this->program->idnumber;
            $a->reqcourses = $this->reqcourses;
            $a->totcourses = $totcourses;
            throw new data_object_validation_exception('data_object_validation_reqcourses_not_in_courseset', 'local_elisprogram', '', $a);
        }
    }

    /**
     * Validation for number of required credits available in courseset
     */
    public function validate_reqcredits_in_courseset() {
        $totcredits = $this->crsset->total_credits();
        if ($totcredits < $this->reqcredits) {
            $a = new stdClass;
            $a->idnumber = $this->crsset->idnumber;
            $a->name = $this->crsset->name;
            $a->programname = $this->program->name;
            $a->programidnumber = $this->program->idnumber;
            $a->reqcredits = $this->reqcredits;
            $a->totcredits = $totcredits;
            throw new data_object_validation_exception('data_object_validation_reqcredits_not_in_courseset', 'local_elisprogram', '', $a);
        }
    }

    /**
     * get_verbose_name method to return human-readable name of entity
     * @return string the verbose name of entity
     */
    public function get_verbose_name() {
        return $this->verbose_name;
    }

    /**
     * toString method to return entity id
     * @return string the entity id
     */
    public function __toString() {
        return (string)$this->id; // TBD
    }

    /**
     * set_from_data method to initialize data object with specified data
     * @param object|array the data
     */
    public function set_from_data($data) {
        $this->_load_data_from_record($data, true);
    }

    /**
     * is_complete method to determine if a crsset has been completed for the program
     * @param int $userid the user to check courseset completion on
     * @param float $percentcomplete reference param to return percentage of courseset complete
     * @return bool true if complete, false otherwise
     */
    public function is_complete($userid, &$percentcomplete = null) {
        if ($percentcomplete !== null) {
            $percentcomplete = 100.0;
        }
        if ($this->reqcredits <= 0.0 || $this->reqcourses <= 0) {
            return true;
        }
        require_once(elispm::lib('data/pmclass.class.php'));
        require_once(elispm::lib('data/student.class.php'));
        $complete = true;
        $curcredits = 0.0;
        $curcourses = 0;
        $courses = $this->crsset->courses;
        if (!$courses || !$courses->valid()) {
            return true;
        }
        $crsarray = array();
        foreach ($courses as $crs) {
            $crsarray[] = $crs->courseid;
        }
        $crsarray = array_unique($crsarray);
        if (empty($crsarray)) {
            return true;
        }
        list($inorequal, $params) = $this->_db->get_in_or_equal($crsarray);
        $enrolmentsql = 'SELECT cce.id, cce.completestatusid, cce.credits
                           FROM {'.pmclass::TABLE.'} cls
                           JOIN {'.student::TABLE."} cce ON cls.id = cce.classid
                                AND cce.userid = ?
                          WHERE cls.courseid {$inorequal}";
        $params = array_merge(array($userid), $params);
        $enrolments = $this->_db->get_recordset_sql($enrolmentsql, $params);
        foreach ($enrolments as $enrolment) {
            if ($enrolment->completestatusid == STUSTATUS_PASSED) {
                $curcourses++;
                $curcredits += $enrolment->credits;
            }
        }
        $prcntcomplete = 100.0;
        if ($this->reqcredits > 0.0 && $curcredits < $this->reqcredits) {
            $prcntcomplete = $curcredits / $this->reqcredits * 100.0;
            $complete = false;
        }
        if ($this->reqcourses > 0 && ($complete == false || $this->andor)) {
            if ($curcourses < $this->reqcourses) {
                $prcntcrs = $curcourses / $this->reqcourses * 100.0;
                if ($prcntcomplete < 100.0) {
                    if (!$this->andor) {
                        $prcntcomplete = max($prcntcomplete, $prcntcrs);
                    } else {
                        $prcntcomplete = $prcntcomplete / 2.0 + $prcntcrs / 2.0;
                    }
                } else {
                    $prcntcomplete = $prcntcrs;
                }
                $complete = false;
            } else if (!$this->andor) {
                $complete = true;
                $prcntcomplete = 100.0;
            }
        }
        if ($percentcomplete != null) {
            $percentcomplete = $prcntcomplete;
        }
        return $complete;
    }

    /**
     * is_active method to determine if a crsset has active enrolments
     * @param int $courseid if non-zero & in crsset, only that course is checked for activety. Otherwise (if zero) all courseset courses are checked for activetly.
     * @return bool true if active, false otherwise
     */
    public function is_active($courseid = 0) {
        if ($this->reqcredits <= 0.0 && $this->reqcourses <= 0) {
            return false;
        }
        require_once(elispm::lib('data/pmclass.class.php'));
        require_once(elispm::lib('data/student.class.php'));
        require_once(elispm::lib('data/curriculumstudent.class.php'));
        if (!$courseid) {
            $courses = $this->crsset->courses;
            if (!$courses || !$courses->valid()) {
                return false;
            }
            $crsarray = array();
            foreach ($courses as $crs) {
                $crsarray[] = $crs->courseid;
            }
            $crsarray = array_unique($crsarray);
            if (empty($crsarray)) {
                return false;
            }
            list($inorequal, $params) = $this->_db->get_in_or_equal($crsarray);
        } else {
            if (!$this->_db->record_exists(crssetcourse::TABLE, array('crssetid' => $this->crsset->id, 'courseid' => $courseid))) {
                return false;
            }
            list($inorequal, $params) = $this->_db->get_in_or_equal(array($courseid));
        }
        $enrolmentsql = 'SELECT \'x\'
                           FROM {'.pmclass::TABLE.'} cls
                           JOIN {'.student::TABLE.'} cce ON cls.id = cce.classid
                           JOIN {'.curriculumstudent::TABLE."} cca ON cce.userid = cca.userid
                                AND cca.curriculumid = ?
                          WHERE cls.courseid {$inorequal}
                                AND cce.completestatusid = ?";
        array_unshift($params, $this->prgid);
        $params[] = STUSTATUS_NOTCOMPLETE;
        return $this->_db->record_exists_sql($enrolmentsql, $params);
    }
}

/**
 * Gets a program courseset listing with specific sort and other filters.
 *
 * @param int $curid The curriculum ID.
 * @param string $sort Field to sort on.
 * @param string $dir Direction of sort.
 * @param int $startrec Record number to start at.
 * @param int $perpage Number of records per page.
 * @param string $namesearch Search string for curriculum name.
 * @param string $descsearch Search string for curriculum description.
 * @param string $alpha Start initial of curriculum name filter.
 * @param array $extrafilters Additional filters to apply to the count
 * @return recordset Returned records.
 */
function programcourseset_get_listing($curid, $sort='position', $dir='ASC', $startrec=0, $perpage=0, $namesearch='', $alpha='',
                                      $extrafilters = array()) {
    global $DB;

    $select = 'SELECT crsset.*
                 FROM {'.curriculum::TABLE.'} cur
                 JOIN {'.programcrsset::TABLE.'} prgcrsset ON prgcrsset.prgid = cur.id
                 JOIN {'.courseset::TABLE.'} crsset ON crsset.id = prgcrsset.crssetid';

    $where = 'cur.id = ?';
    $params = array($curid);

    if (!empty($namesearch)) {
        $namesearch = trim($namesearch);
        $name_like = $DB->sql_like('crsset.name', '?', FALSE);

        $where .= (!empty($where) ? ' AND ' : '') . "($name_like) ";
        $params[] = "%$namesearch%";
    }

    if ($alpha) {
        $name_like = $DB->sql_like('crsset.name', '?', FALSE);
        $where .= (!empty($where) ? ' AND ' : '') . "($name_like) ";
        $params[] = "$alpha%";
    }

    if (!empty($extrafilters['contexts'])) {
        // apply a filter related to filtering on particular courseset contexts
        $filter_object = $extrafilters['contexts']->get_filter('id', 'courseset');
        $filter_sql = $filter_object->get_sql(false, 'crsset');
        if (!empty($filter_sql)) {
            // user does not have access at the system context
            $where .= 'AND '.$filter_sql['where'];
            $params = array_merge($params, $filter_sql['where_parameters']);
        }
    }

    if (!empty($where)) {
        $where = ' WHERE '.$where.' ';
    }

    if ($sort) {
        $sort = ' ORDER BY '.$sort.' '.$dir.' ';
    }

    $sql = $select.$where.$sort;

    return $DB->get_recordset_sql($sql, $params, $startrec, $perpage);
}

