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
require_once(elis::lib('data/data_object_with_custom_fields.class.php'));
require_once(elis::lib('data/customfield.class.php'));
require_once(elispm::lib('lib.php'));
require_once(elispm::lib('data/crssetcourse.class.php'));
require_once(elispm::lib('data/programcrsset.class.php'));

class courseset extends data_object_with_custom_fields {
    /* @const string the DB table */
    const TABLE = 'local_elisprogram_crsset';

    /* @var string the class's verbose name */
    protected $verbose_name = 'courseset';

    /* @var array the class's associations */
    // uncomment below once courseset association classes have been defined
    public static $associations = array(
        'programs' => array(
            'class' => 'programcrsset',
            'foreignidfield' => 'crssetid'
        ),
        'courses' => array(
            'class' => 'crssetcourse',
            'foreignidfield' => 'crssetid'
        )
    );

    // DB fields:
    protected $_dbfield_idnumber;
    protected $_dbfield_name;
    protected $_dbfield_description;
    protected $_dbfield_priority;
    protected $_dbfield_timemodified;
    protected $_dbfield_timecreated;

    /* @var bool whether delete is complex */
    public static $delete_is_complex = true;

    /**
     * get_field_context_level method
     * @return mixed context defined constant
     */
    protected function get_field_context_level() {
        return CONTEXT_ELIS_COURSESET;
    }

    /**
     * set_from_data method
     * @param object|array the data to initialize the courseset object & custom fields
     */
    public function set_from_data($data) {
        $fields = field::get_for_context_level($this->get_field_context_level());
        $fields = $fields ? $fields : array();
        foreach ($fields as $field) {
            $fieldname = "field_{$field->shortname}";
            if (isset($data->$fieldname)) {
                $this->$fieldname = $data->$fieldname;
            }
        }
        $this->_load_data_from_record($data, true);
    }

    /**
     * Method can_safely_delete checks if courseset is in use and has active enrolments
     * @param object $returnprgcrsset optional reference to return programcrsset object, on error, that failed
     * @return bool true if courseset can be safely deleted, false otherwise
     */
    public function can_safely_delete(&$returnprgcrsset = null) {
        foreach ($this->programs as $programcrsset) {
            if ($returnprgcrsset != null) {
                $returnprgcrsset = $programcrsset;
            }
            if ($programcrsset->is_active()) {
                return false;
            }
        }
        return true;
    }

    /**
     * delete method
     */
    public function delete() {
        // delete associated data
        require_once(elis::lib('data/data_filter.class.php'));
        require_once(elispm::lib('data/programcrsset.class.php'));
        require_once(elispm::lib('data/crssetcourse.class.php'));

        // filter specific for courseset
        $filter = new field_filter('crssetid', $this->id);
        programcrsset::delete_records($filter, $this->_db);
        crssetcourse::delete_records($filter, $this->_db);

        parent::delete();

        // clean up the courseset context instance
        $context = \local_elisprogram\context\courseset::instance($this->id);
        $context->delete();
    }

    /**
     * __toString method
     * @return string the courseset name
     */
    public function __toString() {
        return $this->name;
    }

    /**
     * Check for a duplicate record when doing an insert.
     *
     * @param boolean $record true if a duplicate is found false otherwise
     * note: output is expected and treated as boolean please ensure return values are boolean
     */
    public function duplicate_check($record = null) {
        if (empty($record)) {
            $record = $this;
        }

        // Check for valid idnumber - it can't already exist in the user table.
        if ($this->_db->record_exists($this->table, array('idnumber' => $record->idnumber))) {
            return true;
        }

        return false;
    }

    /////////////////////////////////////////////////////////////////////
    //                                                                 //
    //  STATIC FUNCTIONS:                                              //
    //                                                                 //
    /////////////////////////////////////////////////////////////////////

    /**
     * Method to get courseset object given a courseset idnumber string
     * @param string $idnumber the idnumber of desired courseset object
     * @return object courseset object or null if not found.
     */
    public static function get_by_idnumber($idnumber) {
        global $DB;

        $retval = $DB->get_record(courseset::TABLE, array('idnumber' => $idnumber));
        if (!empty($retval)) {
            $retval = new courseset($retval->id);
        } else {
            $retval = null;
        }

        return $retval;
    }

    /* @var array validation rules */
    public static $validation_rules = array(
        'validate_idnumber_not_empty',
        'validate_unique_idnumber'
    );

    /**
     * Validation method for rule: idnumber_not_empty
     * throws exception if invalid
     */
    public function validate_idnumber_not_empty() {
        return validate_not_empty($this, 'idnumber');
    }

    /**
     * Validation method for rule: unique_idnumber
     * throws exception if invalid
     */
    public function validate_unique_idnumber() {
        return validate_is_unique($this, array('idnumber'));
    }

    /**
     * save method
     */
    public function save() {
        parent::save();
        field_data::set_for_context_from_datarecord($this->get_field_context_level(), $this);
    }

    /**
     * method get_verbose_name
     * @return string the class's verbose name
     */
    public function get_verbose_name() {
        return $this->verbose_name;
    }

    /**
     * Method total_courses to return number of courses in courseset
     * @return int number of courses in courseset
     */
    public function total_courses() {
        return $this->count_courses();
    }

    /**
     * Method total_credits to return number of credits available in courseset
     * @return float total number of credits in courseset
     */
    public function total_credits() {
        $totcredits = 0.0;
        foreach ($this->courses as $crssetcrs) {
            $totcredits += $crssetcrs->course->credits;
        }
        return $totcredits;
    }
}

// Non-class supporting functions. (These may be able to replaced by a generic container/listing class)

/**
 * Gets a courseset listing with specific sort and other filters.
 *
 * @param   string        $sort        Field to sort on.
 * @param   string        $dir         Direction of sort.
 * @param   int           $startrec    Record number to start at.
 * @param   int           $perpage     Number of records per page.
 * @param   string        $namesearch  Search string for courseset name.
 * @param   string        $alpha       Start initial of courseset name filter.
 * @param   array         $contexts    Contexts to search
 * @param   int           $userid      The id of the user accessing the courseset
 * @uses    $CFG
 * @uses    $DB
 * @uses    $USER
 * @return  object array               Returned records.
 */
function courseset_get_listing($sort = 'name', $dir = 'ASC', $startrec = 0, $perpage = 0, $namesearch = '', $alpha = '', $contexts = null, $userid = 0) {
    global $CFG, $DB, $USER;

    require_once(elispm::lib('data/programcrsset.class.php'));

    $select = 'SELECT crsset.*';
    $select .= ', (SELECT COUNT(*) FROM {'.crssetcourse::TABLE.'} WHERE crssetid = crsset.id) as courses';
    $select .= ', (SELECT COUNT(*) FROM {'.programcrsset::TABLE.'} WHERE crssetid = crsset.id) as programs';
    $tables = '  FROM {'.courseset::TABLE.'} crsset ';
    $join   = ' ';
    $on     = ' ';

    $where = array();
    $params = array();

    if (!empty($namesearch)) {
        $namesearch = trim($namesearch);
        $namelike  = $DB->sql_like('name', '?', false);
        $where[]    = "($namelike)";
        $params[]   = "%$namesearch%";
    }

    if ($alpha) {
        $namelike = $DB->sql_like('name', '?', false);
        $where[]   = "($namelike)";
        $params[]  = "$alpha%";
    }

    if ($contexts !== null) {
        $filterobject = $contexts->get_filter('id', 'courseset');
        $filtersql = $filterobject->get_sql(false, 'crsset');
        if (isset($filtersql['where'])) {
            $where[] = $filtersql['where'];
            $params = array_merge($params, $filtersql['where_parameters']);
        }
    }

    if (!empty($userid)) {
        // get the context for the capability
        $context = pm_context_set::for_user_with_capability('courseset', 'local/elisprogram:courseset_view', $userid); // TBD
        $filterobject = $context->get_filter('id', 'courseset');
        $filtersql = $filterobject->get_sql(false, 'crsset');
        if (isset($filtersql['where'])) {
            $where[] = $filtersql['where'];
            $params = array_merge($params, $filtersql['where_parameters']);
        }
    }

    if (!empty($where)) {
        $where = 'WHERE '.implode(' AND ', $where).' ';
    } else {
        $where = '';
    }

    if ($sort) {
        $sort = 'ORDER BY '.$sort.' '.$dir.' ';
    }

    $sql = $select.$tables.$join.$on.$where.$sort;
    return $DB->get_recordset_sql($sql, $params, $startrec, $perpage);
}

/**
 * Count of courseset records matching criteria
 *
 * @param string $namesearch Search string for courseset name.
 * @param string $alpha Start initial of courseset name filter.
 * @param array  $contexts Contexts to search
 * @return int count of courseset records matching criteria
 */
function courseset_count_records($namesearch = '', $alpha = '', $contexts = null) {
    global $DB;

    $where = array();
    $params = array();

    if (!empty($namesearch)) {
        $namelike = $DB->sql_like('name', '?', false);
        $where[] = "($namelike)";
        $params[] = "%$namesearch%";
    }

    if ($alpha) {
        $namelike = $DB->sql_like('name', '?', false);
        $where[] = "($namelike)";
        $params[] = "$alpha%";
    }

    if ($contexts != null) {
        $filterobject = $contexts->get_filter('id', 'courseset');
        $filtersql = $filterobject->get_sql();
        if (isset($filtersql['where'])) {
            $where[] = $filtersql['where'];
            $params = array_merge($params, $filtersql['where_parameters']);
        }
    }

    $where = empty($where) ? false : implode(' AND ', $where).' ';

    return $DB->count_records_select(courseset::TABLE, $where, $params);
}
