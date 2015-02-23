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

namespace eliswidget_enrolment;

/**
 * Dashboard AJAX response class.
 *
 * This receives AJAX requests, processes the request (get or change information), and sends the response.
 */
class ajax {

    /** @var string The URL that receives requests. */
    protected $endpoint;

    /**
     * Constructor.
     *
     * @param string $endpoint The URL that receives request.
     */
    public function __construct($endpoint) {
        $this->endpoint = $endpoint;
    }

    /**
     * Respond to an ajax request.
     *
     * @param string $requestmethod The request method - "GET" or "POST".
     * @param string $mode The requested mode.
     * @param array $data An array of received data.
     * @return string Response JSON.
     */
    public function respond($requestmethod, $mode, $data) {
        try {
            // Validate $requestmethod.
            if (!is_string($requestmethod)) {
                throw new \coding_exception('Bad request method passed.');
            }
            $requestmethod = strtolower($requestmethod);
            if (in_array($requestmethod, ['get', 'post'], true) !== true) {
                throw new \coding_exception('Request method must be get or post.');
            }

            // Validate $mode.
            if (!is_string($mode)) {
                throw new \coding_exception('Bad mode received.');
            }

            $method = $requestmethod.'_'.$mode;
            if (method_exists($this, $method)) {
                $result = $this->$method($data);
                return $this->makeresponse('success', $result);
            } else {
                throw new \coding_exception('Mode not found.');
            }
        } catch (\Exception $e) {
            return $this->makeresponse('fail', [], $e->getMessage());
        }
    }

    /**
     * Make a JSON response from received data.
     *
     * @param string $status Response status - "success" or "fail".
     * @param array $data Response data.
     * @param string $msg A human-readable message to go along with the response. Optional.
     * @return string JSON-encoded response.
     */
    protected function makeresponse($status, $data = array(), $msg = '') {
        if ($data instanceof \moodle_recordset) {
            $data = $this->results2array($data);
        }
        return json_encode(['status' => $status, 'msg' => $msg, 'data' => $data]);
    }

    /**
     * Takes either an array or recordset as input and ensures array output. Used for preparing search results for json encoding.
     *
     * @param array|\moodle_recordset $data Search results in array or recordset form.
     * @return array Search results in array form.
     */
    protected function results2array($data) {
        if (is_array($data)) {
            return $data;
        } else if ($data instanceof \moodle_recordset) {
            $dataar = [];
            foreach ($data as $id => $record) {
                $dataar[] = $record;
            }
            return $dataar;
        } else {
            throw new \coding_exception('results2array recieved something that wasn\'t an array or a recordset');
        }
    }

    /**
     * Convert an array of filter objects into a more lightweight array that can be json encoded.
     *
     * @param array $filterobjs An array of \deepsight_filter objects.
     * @return array An array of filter information and javascript options, indexed by filter name.
     */
    protected function preparefilters(array $filterobjs = array()) {
        $filters = [];
        foreach ($filterobjs as $i => $filter) {
            if ($filter instanceof \deepsight_filter) {
                $filters[$filter->get_name()] = [
                    'type' => $filter::TYPE,
                    'opts' => $filter->get_js_opts()
                ];
            }
        }
        return $filters;
    }

    /**
     * Respond to a request aimed at a specific filter.
     *
     * @param array $data Incoming data.
     */
    protected function get_filter(array $data) {
        global $DB;
        $table = optional_param('table', '', PARAM_TEXT);
        $filtername = optional_param('filtername', '', PARAM_TEXT);
        if (empty($table) || empty($filtername)) {
            throw new \Exception('No table or filter received.');
        }
        $table = '\\'.$table;
        if (strpos($table, '\eliswidget_enrolment\datatable\\') !== 0 || !class_exists($table)) {
            throw new \Exception('Invalid table name received.');
        }
        $datatable = new $table($DB, $this->endpoint);
        $filter = $datatable->get_filter($filtername);
        if (!empty($filter)) {
            echo $filter->respond_to_js();
            die();
        } else {
            throw new \Exception('No filter found');
        }
    }

    /**
     * Get a list of coursesets present in a program.
     *
     * @param array $data Array containing "programid", "filters", and "page".
     * @return array Array of response information containing information on filters to render, search results, and other details
     *               needed by the front-end.
     */
    protected function get_coursesetsforprogram(array $data) {
        global $DB;
        if (empty($data['programid']) || !is_numeric($data['programid'])) {
            throw new \Exception('No valid Program ID received.');
        }

        $datatable = new \eliswidget_enrolment\datatable\programcourseset($DB, $this->endpoint);
        $datatable->set_programid($data['programid']);

        return $this->get_listing_response($datatable, $data);
    }

    /**
     * Get a list of courses present in a courseset.
     *
     * @param array $data Array containing "programid", "filters", and "page".
     * @return array Array of response information containing information on filters to render, search results, and other details
     *               needed by the front-end.
     */
    protected function get_coursesforcourseset(array $data) {
        global $DB;
        if (empty($data['coursesetid']) || !is_numeric($data['coursesetid'])) {
            throw new \Exception('No valid Courseset ID received.');
        }

        $datatable = new \eliswidget_enrolment\datatable\coursesetcourse($DB, $this->endpoint);
        $datatable->set_coursesetid($data['coursesetid']);

        return $this->get_listing_response($datatable, $data);
    }

    /**
     * Get a list of courses present in a program.
     *
     * @param array $data Array containing "programid", "filters", and "page".
     * @return array Array of response information containing information on filters to render, search results, and other details
     *               needed by the front-end.
     */
    protected function get_coursesforprogram(array $data) {
        global $DB;
        if (empty($data['programid']) || (!is_numeric($data['programid']) && $data['programid'] !== 'none')) {
            throw new \Exception('No valid Program ID received.');
        }
        if (is_numeric($data['programid'])) {
            $datatable = new \eliswidget_enrolment\datatable\programcourse($DB, $this->endpoint);
            $datatable->set_programid($data['programid']);
        } else if ($data['programid'] === 'none') {
            require_once(\elispm::lib('data/user.class.php'));
            $euserid = \user::get_current_userid();
            $datatable = new \eliswidget_enrolment\datatable\nonprogramcourse($DB, $this->endpoint);
            $datatable->set_userid($euserid);
        }
        return $this->get_listing_response($datatable, $data);
    }

    /**
     * Get a list of classes for a specified course.
     *
     * @param array $data Array containing "courseid", "filters", and "page".
     * @return array Array of response information containing information on filters to render, search results, and other details
     *               needed by the front-end.
     */
    protected function get_classesforcourse(array $data) {
        global $DB;
        if (empty($data['courseid']) || !is_numeric($data['courseid'])) {
            throw new \Exception('No valid course ID received.');
        }
        $datatable = new \eliswidget_enrolment\datatable\pmclass($DB, $this->endpoint);
        $datatable->set_courseid($data['courseid']);
        return $this->get_listing_response($datatable, $data);
    }

    /**
     * Respond to a listing request for a given entity type.
     *
     * @param \eliswidget_enrolment\datatable\base $datatable The datatable that handles the list.
     * @param array $data Received data. Includes 'filters', and 'page' keys.
     * @return array Array of JSON-able response information.
     */
    protected function get_listing_response(\eliswidget_enrolment\datatable\base $datatable, $data) {
        if (!empty($data['filters'])) {
            $data['filters'] = @json_decode($data['filters'], true);
        }
        if (empty($data['filters']) || !is_array($data['filters'])) {
            $data['filters'] = [];
        }
        $page = (isset($data['page']) && is_numeric($data['page'])) ? (int)$data['page'] : 1;

        // Assemble response.
        list($pageresults, $totalresultsamt) = $datatable->get_search_results($data['filters'], $page);
        list($visibledatafields, $hiddendatafields) = $datatable->get_datafields_by_visibility($data['filters']);
        return [
            'filters' => $this->preparefilters($datatable->get_filters()),
            'initialfilters' => $datatable->get_initial_filters(),
            'fields' => ['visible' => $visibledatafields, 'hidden' => $hiddendatafields],
            'children' => $this->results2array($pageresults),
            'perpage' => $datatable::RESULTSPERPAGE,
            'totalresults' => $totalresultsamt,
        ];
    }

    /**
     * Change the student's status within a class.
     *
     * @param array $data Received data from the front-end. Contains 'action' and 'classid', representing what the student wants to
     *                    do, and what class they want to do it in, respectively.
     * @return array Array of response information to send to the front end, contains 'newstatus', representing the student's
     *               updated status within the class.
     */
    protected function post_changeclassstatus(array $data) {
        global $DB;

        require_once(\elispm::lib('data/user.class.php'));
        $euserid = \user::get_current_userid();

        if (empty($data['action']) || empty($data['classid'])) {
            throw new \Exception('No action or classid received.');
        }

        switch($data['action']) {
            case 'enrol':
                $enrolallowed = get_config('enrol_elis', 'enrol_from_course_catalog');
                if (empty($enrolallowed) || $enrolallowed != '1') {
                    throw new \Exception('Self-enrolments from dashboard not allowed.');
                }
                require_once(\elispm::lib('data/student.class.php'));
                // Search for existing enrolment.
                $enrolment = $DB->get_records(\student::TABLE, ['classid' => $data['classid'], 'userid' => $euserid]);
                if (!empty($enrolment)) {
                    throw new \Exception('User already enroled.');
                }
                try {
                    $enroldata = [
                        'classid' => $data['classid'],
                        'userid' => $euserid,
                        'enrolmenttime' => time(),
                    ];
                    $student = new \student($enroldata);
                    $student->save();
                } catch (\pmclass_enrolment_limit_validation_exception $e) {
                    require_once(\elispm::lib('data/waitlist.class.php'));
                    $waitlist = new \waitlist(['classid' => $data['classid'], 'userid' => $euserid]);
                    $waitlist->save();
                    return ['newstatus' => 'waitlist'];
                }
                return ['newstatus' => 'enroled'];

            case 'unenrol':
                $unenrolallowed = get_config('enrol_elis', 'unenrol_from_course_catalog');
                if (empty($unenrolallowed) || $unenrolallowed != '1') {
                    throw new \Exception('Self-unenrolments from dashboard not allowed.');
                }
                require_once(\elispm::lib('data/student.class.php'));
                // Search for existing enrolment.
                $enrolment = $DB->get_record(\student::TABLE, ['classid' => $data['classid'], 'userid' => $euserid]);
                if (empty($enrolment)) {
                    throw new \Exception('User not enroled.');
                }
                $student = new \student($enrolment);
                $student->delete();
                return ['newstatus' => 'available'];

            case 'leavewaitlist':
                require_once(\elispm::lib('data/waitlist.class.php'));
                // Search for existing waitlist entry.
                $waitlist = $DB->get_record(\waitlist::TABLE, ['classid' => $data['classid'], 'userid' => $euserid]);
                if (empty($waitlist)) {
                    throw new \Exception('User not on waitlist.');
                }
                $waitlist = new \waitlist($waitlist);
                $waitlist->delete();
                return ['newstatus' => 'available'];

            default:
                throw new \Exception('Invalid action received.');
        }
    }
}
