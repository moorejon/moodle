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
require_once(elispm::lib('data/course.class.php'));
require_once(elispm::lib('data/courseset.class.php'));

class crssetcourse extends elis_data_object {
    /* @const string the DB table */
    const TABLE = 'local_elisprogram_crssetcrs';

    /* @const int return codes for method can_safely_delete */
    const CAN_SAFELY_DELETE_SUCCESS = 0;
    const CAN_SAFELY_DELETE_ERROR_ACTIVE = 1;
    const CAN_SAFELY_DELETE_ERROR_COURSES = 2;
    const CAN_SAFELY_DELETE_ERROR_CREDITS = 4;

    /* @var string the class's verbose name */
    protected $verbose_name = 'courseset-course';

    /* DB fields: */
    protected $_dbfield_id;
    protected $_dbfield_crssetid;
    protected $_dbfield_courseid;
    protected $_dbfield_timemodified;
    protected $_dbfield_timecreated;

    /* @var array the class's associations */
    public static $associations = array(
        'course' => array(
            'class' => 'course',
            'idfield' => 'courseid'
        ),
        'crsset' => array(
            'class' => 'courseset',
            'idfield' => 'crssetid'
        )
    );

    /* @var array validation rules */
    public static $validation_rules = array(
        array('validation_helper', 'is_unique_courseid_crssetid')
    );

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
     * Method can_safely_delete checks if course is in use and has active enrolments
     * @param object $returnprgcrsset optional reference to return programcrsset object, on error, that failed
     * @return int -1 if program(s) non-completable, 0 if course can be safely removed from courseset, 1 if enrolments exist
     */
    public function can_safely_delete(&$returnprgcrsset = null) {
        foreach ($this->crsset->programs as $programcrsset) {
            if ($returnprgcrsset != null) {
                $returnprgcrsset = $programcrsset;
            }
            // TBD: should we consider the andor flag and allow half-broken programcrssets to exist?
            if ($programcrsset->reqcourses > 0 && ($programcrsset->crsset->total_courses() - 1) < $programcrsset->reqcourses) {
                return crssetcourse::CAN_SAFELY_DELETE_ERROR_COURSES;
            }
            if ($programcrsset->reqcredits > 0.0 && ($programcrsset->crsset->total_credits() - $this->course->credits) < $programcrsset->reqcredits) {
                return crssetcourse::CAN_SAFELY_DELETE_ERROR_CREDITS;
            }
            if ($programcrsset->is_active($this->courseid)) {
                return crssetcourse::CAN_SAFELY_DELETE_ERROR_ACTIVE;
            }
        }
        return crssetcourse::CAN_SAFELY_DELETE_SUCCESS;
    }
}
