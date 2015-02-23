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
 * @copyright  (C) 2013 onwards Remote-Learner.net Inc (http://www.remote-learner.net)
 * @author     James McQuillan <james.mcquillan@remote-learner.net>
 *
 */

require_once(__DIR__.'/filter.php');
require_once(__DIR__.'/json.php');
require_once(__DIR__.'/customfieldfilteringtrait.php');

/**
 * Extracts date information from inputs produced by the js function deepsight_render_date_selectors().
 *
 * @param array  $alldata An entire dataset returned from a form (including other non-date inputs.
 * @param string $prefix  A prefix identifying the date information we're interested in, if there's multiple date sets.
 * @return int A timestamp for the date.
 */
function ds_process_js_date_data(array $alldata, $prefix = '') {
    $monthparam = $prefix.'month';
    $dayparam = $prefix.'day';
    $yearparam = $prefix.'year';

    // We add +1 to the month as js months start at 0.
    $month = (!empty($alldata[$monthparam]) && is_numeric($alldata[$monthparam]) && $alldata[$monthparam] >= 1
              && $alldata[$monthparam] <= 12)
        ? (int)$alldata[$monthparam]+1
        : 1;

    $day = (!empty($alldata[$dayparam]) && is_numeric($alldata[$dayparam]) && $alldata[$dayparam] >= 1 && $alldata[$dayparam] <= 31)
        ? (int)$alldata[$dayparam] : 1;

    $year = (!empty($alldata[$yearparam]) && is_numeric($alldata[$yearparam]) && strlen($alldata[$yearparam]) === 4)
        ? (int)$alldata[$yearparam] : date('Y');

    return pm_timestamp(0, 0, 0, $month, $day, $year);
}

/**
 * Format a timestamp as a date for display.
 * @param int $time A UNIX timestamp.
 * @param bool $showtime Whether to include the time in the returned string (true), or just the date (false)
 * @return string The formatted date or a "-" to indicate an empty date.
 */
function ds_process_displaytime($time, $showtime = false) {
    if (empty($time)) {
        return '-';
    }
    $format = ($showtime === true)
            ? get_string('pm_datetime_format', 'local_elisprogram')
            : get_string('pm_date_format', 'local_elisprogram');

    return userdate($time, $format);
}

/**
 * Autoloader for DeepSight components.
 *
 * All deepsight classes are formatted like "deepsight_[type]_[subtype]"
 *     [type] is the type of object - action, filter, datatable, etc.
 *     [subtype] is the specific implementation of [type] i.e. "date" for the date filter, etc.
 * This will only attempt to load components that start with deepsight_
 *
 * @param string $class The class name that's being used.
 */
function deepsight_autoloader($class) {
    global $CFG;
    $dsbase = dirname(__FILE__);
    $parts = explode('_', $class);

    // All modules must have at least 3 parts - deepsight_[type]_[name].
    if (count($parts) >= 3) {
        if ($parts[0] === 'deepsight') {
            if ($parts[1] === 'action') {
                if (file_exists($dsbase.'/actions/'.$parts[2].'.action.php')) {
                    require_once($dsbase.'/actions/'.$parts[2].'.action.php');
                }
            } else if ($parts[1] === 'datatable') {
                if (file_exists($dsbase.'/tables/'.$parts[2].'.table.php')) {
                    require_once($dsbase.'/tables/'.$parts[2].'.table.php');
                }
            } else if ($parts[1]  === 'filter') {
                if (file_exists($dsbase.'/filters/'.$parts[2].'.filter.php')) {
                    require_once($dsbase.'/filters/'.$parts[2].'.filter.php');
                }
            }
        }
    }
}
spl_autoload_register('deepsight_autoloader');

/**
 * DeepSight Action Interface
 * Actions allow the user to do [something] with an element or set of elements.
 */
interface deepsight_action {

    /**
     * Constructor.
     *
     * @param moodle_database &$DB  The global moodle_database object used by the action.
     * @param string          $name The name of the action. Used to point requests to the right action.
     */
    public function __construct(moodle_database &$DB, $name);

    /**
     * Respond to a Javascript call that points to this action.
     *
     * This is usually a request to complete the action with an element or set of elements.
     * This should echo a response, however the actual method is left up to the individual implementation.
     */
    public function respond_to_js();

    /**
     * Get an array of options to be passed to the javascript object for this action.
     *
     * @return array The options array.
     */
    public function get_js_opts();

    /**
     * Sets an internal reference to the datatable this action will be used with.
     *
     * @param deepsight_datatable &$datatable A reference to the datatable object.
     */
    public function set_table(deepsight_datatable &$datatable);

    /**
     * Gets the name of the object.
     *
     * @return string The name.
     */
    public function get_name();
}

/**
 * A standard base implementation of an action.
 */
abstract class deepsight_action_standard implements deepsight_action {

    /**
     * The name of the javascript class to use. This the last part of the class name (i.e. "enrol" for "deepsight_action_enrol".)
     */
    const TYPE = null;

    /**
     * Whether the action supports bulk actions.
     */
    public $canbulk = true;

    /**
     * The name of the action. Used to point requests to the right action.
     */
    protected $name;

    /**
     * The label for the action. This will be used in the tooltip for the action in the datatable, and as a label next to the icon
     * in the bulk action panel.
     */
    public $label = '';

    /**
     * A CSS class used with the clickable element to activate the action to determine it's icon.
     */
    public $icon = '';

    /**
     * A string containing a javascript function that will be run on each element in the datatable to determine if this action
     * applies or not. For example, in the enrolment action, this is run on each element to ensure the element is not already
     * enroled.
     */
    public $condition = null;

    /**
     * Holds the reference to the deepsight_datatable object that this action is being used with.
     */
    protected $datatable;

    /**
     * Holds an internal reference to the global moodle_database object.
     */
    protected $DB;

    /**
     * Constructor.
     *
     * Sets internal data and runs postconstruct() function.
     *
     * @see deepsight_action::__construct()
     */
    public function __construct(moodle_database &$DB, $name) {
        $this->name = $name;
        $this->DB =& $DB;
        $this->postconstruct();
    }

    /**
     * Placeholder function run at the end of the constructor.
     *
     * Allows subclasses to perform any actions they need on construction without having to override the constructor.
     */
    protected function postconstruct() {
    }

    /**
     * Provides a wrapped around the subclass specific responses, allowing for automatic handling of errors.
     *
     * @see deepsight_action::respond_to_js()
     */
    public function respond_to_js() {
        global $SESSION;
        set_time_limit(0);
        try {
            // Capture any output so we can format it correctly and it won't break the JSON response.
            ob_start();
            require_sesskey();

            $elements = required_param('elements', PARAM_CLEAN);
            if ($elements === 'bulklist') {
                $bulkaction = true;
                $bulklistparam = $this->datatable->get_bulklist_sess_param();
                $elements = $SESSION->$bulklistparam;
            } else {
                $bulkaction = false;
                if (is_string($elements)) {
                    $elements = @json_decode($elements, true);
                }
            }

            if (empty($elements) || !is_array($elements)) {
                $elements = array();
            }
            $result = $this->_respond_to_js($elements, $bulkaction);

            $output = ob_get_contents();
            ob_end_clean();

            // Convert any output to an exception - _respond_to_js should only return an array, not output anything,
            // If it does, we have a problem.
            if (!empty($output)) {
                throw new Exception('Unexpected output: '.$output);
            } else {
                echo safe_json_encode($result);
            }
        } catch (Exception $e) {
            $output = ob_get_contents();
            ob_end_clean();
            $msg = $e->getMessage();
            if (!empty($output)) {
                $msg .= ' '.$output;
            }
            // Format exceptions as readable failure responses.
            echo safe_json_encode(array('result' => 'fail', 'msg' => $msg));
        }
    }

    /**
     * Perform action-specific tasks.
     *
     * @param array $elements    An array of elements to perform the action on. Although the values will differ, the indexes
     *                           will always be element IDs.
     * @param bool  $bulkaction Whether this is a bulk-action or not.
     * @return array An array to format as JSON and return to the Javascript.
     */
    abstract protected function _respond_to_js(array $elements, $bulkaction);

    /**
     * Provides basic information to set up the action.
     *
     * @see deepsight_action::get_js_opts();
     */
    public function get_js_opts() {
        return array(
            'canbulk' => $this->canbulk,
            'icon' => $this->icon,
            'name' => $this->name,
            'label' => $this->label,
            'type' => static::TYPE,
            'opts' => array(
                'sesskey' => sesskey(),
                'name' => $this->name
            ),
        );
    }

    /**
     * Sets the internal datatable reference to $datatable.
     *
     * @see deepsight_action::set_table()
     */
    public function set_table(deepsight_datatable &$datatable) {
        $this->datatable =& $datatable;
    }

    /**
     * Gets the $name property of the action.
     *
     * @see deepsight_action::get_name();
     */
    public function get_name() {
        return $this->name;
    }

    /**
     * Attempt an association edit.
     *
     * @param int $mainelementid The ID of the main element. The is the ID of the 'one', in a 'many-to-one' association.
     * @param array $elements An array of items to associate to the main element.
     * @param bool $bulkaction Whether this is a bulk-action or not.
     * @param string $assocclass The class used for this association.
     * @param string $assocparams Parameters used to check for/create the association. Must contain keys:
     *                                 'main' => The field name that corresponds to the main element's ID. Ex. 'courseid'
     *                                 'incoming' => The field name that corresponds to incoming elements' IDs. Ex. 'programid'
     * @param array $assocfields If the association has additional parameters, this is an array of field names of those parameters.
     * @param array $assocdata If the association has additional paramaters, this is that data.
     * @param string $faillang If there were elements that failed, use this string as an error message. If null, language string
     *                         ds_action_generic_bulkfail from local_elisprogram will be used.
     * @return array An array to format as JSON and return to the Javascript.
     */
    protected function attempt_associate($mainelementid, $elements, $bulkaction, $assocclass, $assocparams, $assocfields, $assocdata, $faillang = null) {
        if (!is_array($assocdata)) {
            throw new \Exception('Did not receive valid enrolment data.');
        }
        if (!isset($assocparams['main']) || !isset($assocparams['incoming'])) {
            throw new \Exception('Bad association params - must be fixed in code.');
        }
        $failedops = [];
        foreach ($elements as $incomingelementid => $label) {
            if ($this->can_manage_assoc($mainelementid, $incomingelementid) === true) {
                $assocclassconstructparams = [
                    $assocparams['main'] => $mainelementid,
                    $assocparams['incoming'] => $incomingelementid,
                ];
                $association = new $assocclass($assocclassconstructparams);
                foreach ($assocfields as $field) {
                    if (isset($assocdata[$field])) {
                        $association->$field = $assocdata[$field];
                    }
                }
                if ($bulkaction === true) {
                    try {
                        $association->save();
                    } catch (\Exception $e) {
                        $failedops[] = $incomingelementid;
                    }
                } else {
                    $association->save();
                }
            } else {
                $failedops[] = $incomingelementid;
            }
        }

        if ($bulkaction === true) {
            if (!empty($failedops)) {
                if ($faillang === null || !is_string($faillang)) {
                    $faillang = get_string('ds_action_generic_bulkfail', 'local_elisprogram');
                }
                return [
                    'result' => 'partialsuccess',
                    'msg' => $faillang,
                    'failedops' => $failedops,
                ];
            } else {
                return [
                    'result' => 'success',
                    'msg' => 'Success',
                ];
            }
        } else {
            $formatteddata = $this->format_assocdata_for_display($assocdata);
            return array(
                'result' => 'success',
                'msg' => 'Success',
                'displaydata' => $formatteddata,
                'saveddata' => $assocdata
            );
        }
    }

    /**
     * Attempt an association edit.
     *
     * @param int $mainelementid The ID of the main element. The is the ID of the 'one', in a 'many-to-one' association.
     * @param array $elements An array of items to associate to the main element.
     * @param bool $bulkaction Whether this is a bulk-action or not.
     * @param string $assocclass The class used for this association.
     * @param string $assocparams Parameters used to check for/create the association. Must contain keys:
     *                                 'main' => The field name that corresponds to the main element's ID. Ex. 'courseid'
     *                                 'incoming' => The field name that corresponds to incoming elements' IDs. Ex. 'programid'
     * @param array $assocfields If the association has additional parameters, this is an array of field names of those parameters.
     * @param array $assocdata If the association has additional paramaters, this is that data.
     * @param string $faillang If there were elements that failed, use this string as an error message. If null, language string
     *                         ds_action_generic_bulkfail from local_elisprogram will be used.
     * @return array An array to format as JSON and return to the Javascript.
     */
    protected function attempt_edit($mainelementid, $elements, $bulkaction, $assocclass, $assocparams, $assocfields, $assocdata, $faillang = null) {
        global $DB;
        if (!is_array($assocdata)) {
            throw new \Exception('Did not receive valid enrolment data.');
        }
        if (!isset($assocparams['main']) || !isset($assocparams['incoming'])) {
            throw new \Exception('Bad association params - must be fixed in code.');
        }
        $failedops = [];
        foreach ($elements as $incomingelementid => $label) {
            if ($this->can_manage_assoc($mainelementid, $incomingelementid) === true) {
                $assocqueryparams = [
                    $assocparams['main'] => $mainelementid,
                    $assocparams['incoming'] => $incomingelementid,
                ];
                $association = $DB->get_record($assocclass::TABLE, $assocqueryparams);
                if (!empty($association)) {
                    $association = new $assocclass($association);
                    foreach ($assocfields as $field) {
                        if (isset($assocdata[$field])) {
                            $association->$field = $assocdata[$field];
                        }
                    }
                    if ($bulkaction === true) {
                        try {
                            $association->save();
                        } catch (\Exception $e) {
                            $failedops[] = $incomingelementid;
                        }
                    } else {
                        $association->save();
                    }
                }
            } else {
                $failedops[] = $incomingelementid;
            }
        }

        if ($bulkaction === true) {
            if (!empty($failedops)) {
                if ($faillang === null || !is_string($faillang)) {
                    $faillang = get_string('ds_action_generic_bulkfail', 'local_elisprogram');
                }
                return [
                    'result' => 'partialsuccess',
                    'msg' => $faillang,
                    'failedops' => $failedops,
                ];
            } else {
                return [
                    'result' => 'success',
                    'msg' => 'Success',
                ];
            }
        } else {
            $formatteddata = $this->format_assocdata_for_display($assocdata);
            return array(
                'result' => 'success',
                'msg' => 'Success',
                'displaydata' => $formatteddata,
                'saveddata' => $assocdata
            );
        }
    }

    /**
     * Attempt an unassociation.
     *
     * @param int $mainelementid The ID of the main element. The is the ID of the 'one', in a 'many-to-one' association.
     * @param array $elements An array of items to associate to the main element.
     * @param bool $bulkaction Whether this is a bulk-action or not.
     * @param string $assocclass The class used for this association.
     * @param string $assocparams Parameters used to check for/create the association. Must contain keys:
     *                                 'main' => The field name that corresponds to the main element's ID. Ex. 'courseid'
     *                                 'incoming' => The field name that corresponds to incoming elements' IDs. Ex. 'programid'
     * @param string $faillang If there were elements that failed, use this string as an error message. If null, language string
     *                         ds_action_generic_bulkfail from local_elisprogram will be used.
     * @return array An array to format as JSON and return to the Javascript.
     */
    protected function attempt_unassociate($mainelementid, $elements, $bulkaction, $assocclass, $assocparams, $faillang = null) {
        global $DB;
        $failedops = [];
        foreach ($elements as $incomingelementid => $label) {
            if ($this->can_manage_assoc($mainelementid, $incomingelementid) === true) {
                $assocqueryparams = [
                    $assocparams['main'] => $mainelementid,
                    $assocparams['incoming'] => $incomingelementid,
                ];
                $assignrec = $DB->get_record($assocclass::TABLE, $assocqueryparams);
                if (!empty($assignrec)) {
                    try {
                        $association = new $assocclass($assignrec);
                        $association->delete();
                    } catch (\Exception $e) {
                        if ($bulkaction === true) {
                            $failedops[] = $incomingelementid;
                        } else {
                            throw $e;
                        }
                    }
                } else {
                    $failedops[] = $incomingelementid;
                }
            } else {
                $failedops[] = $incomingelementid;
            }
        }

        if ($bulkaction === true && !empty($failedops)) {
            if ($faillang === null || !is_string($faillang)) {
                $faillang = get_string('ds_action_generic_bulkfail', 'local_elisprogram');
            }
            return [
                'result' => 'partialsuccess',
                'msg' => $faillang,
                'failedops' => $failedops,
            ];
        } else {
            return [
                'result' => 'success',
                'msg' => 'Success',
            ];
        }
    }

    /**
     * Determine whether the current user can manage an association.
     *
     * @param int $mainelementid The ID of the main element. The is the ID of the 'one', in a 'many-to-one' association.
     * @param int $incomingelementid The ID of the incoming element. The is the ID of the 'many', in a 'many-to-one' association.
     * @return bool Whether the current can manage (true) or not (false)
     */
    protected function can_manage_assoc($mainelementid, $incomingelementid) {
        return false;
    }

    /**
     * Formats association data for display in the table post-edit.
     * @param array $assocdata The incoming association data
     * @return array The formatted association data.
     */
    protected function format_assocdata_for_display($assocdata) {
        return $assocdata;
    }
}

/**
 * A base action class for links.
 */
class deepsight_action_link extends deepsight_action_standard {
    const TYPE = 'link';
    public $canbulk = false;
    public $icon = 'elisicon-assoc';
    public $label = null;
    public $baseurl = null;
    public $params = null;

    /**
     * Constructor.
     *
     * Sets internal data.
     *
     * @param moodle_database $DB The active database connection.
     * @param string $name The unique name of the action to use.
     * @param string $baseurl The base url of the page to link to, minus query string, and relative to wwwroot.
     * @param array $params An array of parameters to add to the url, formatted like key=>value. To add rowdata, set value to the
     *                      key of the data in the rowdata member of the javascript opts variable (see js class for more info),
     *                      surrounded by curly braces - i.e. array('id' => '{element_id}');
     */
    public function __construct(moodle_database &$DB, $name, $label='', $baseurl='', array $params=array()) {
        parent::__construct($DB, $name);
        $this->label = $label;

        if (!empty($baseurl)) {
            $this->baseurl = $baseurl;
        }

        if (!empty($params)) {
            $this->params = $params;
        }
    }

    /**
     * Provide options to the javascript.
     *
     * @return array An array of options.
     */
    public function get_js_opts() {
        global $CFG;
        $opts = parent::get_js_opts();
        $opts['icon'] = $this->icon;
        $opts['opts']['linkwwwroot'] = $CFG->wwwroot;
        $opts['opts']['linkbaseurl'] = $this->baseurl;
        $opts['opts']['linkparams'] = $this->params;
        return $opts;
    }

    /**
     * Perform action-specific tasks. This action doesn't have any tasks, so do nothing.
     *
     * @param array $elements    An array of elements to perform the action on. Although the values will differ, the indexes
     *                           will always be element IDs.
     * @param bool  $bulkaction Whether this is a bulk-action or not.
     * @return array An array to format as JSON and return to the Javascript.
     */
    protected function _respond_to_js(array $elements, $bulkaction) {
        return array();
    }
}

/**
 * A base action class for confirmation actions.
 */
abstract class deepsight_action_confirm extends deepsight_action_standard {
    const TYPE = 'confirm';
    public $descsingle = '';
    public $descmultiple = '';

    /**
     * Constructor.
     *
     * Sets internal data.
     *
     * @param moodle_database $DB            The active database connection.
     * @param string          $name          The unique name of the action to use.
     * @param string          $descsingle   The description when the confirmation is for a single element.
     * @param string          $descmultiple The description when the confirmation is for the bulk list.
     */
    public function __construct(moodle_database &$DB, $name, $descsingle = '', $descmultiple = '') {
        parent::__construct($DB, $name);
        $this->label = get_string('assign', 'local_elisprogram');
    }

    /**
     * Provide options to the javascript.
     *
     * @return array An array of options.
     */
    public function get_js_opts() {
        $opts = parent::get_js_opts();
        $opts['condition'] = $this->condition;
        $opts['opts']['actionurl'] = $this->endpoint;
        $opts['opts']['desc_single'] = $this->descsingle;
        $opts['opts']['desc_multiple'] = $this->descmultiple;
        $opts['opts']['lang_bulk_confirm'] = get_string('ds_bulk_confirm', 'local_elisprogram');
        $opts['opts']['lang_working'] = get_string('ds_working', 'local_elisprogram');
        return $opts;
    }
}

/**
 * DeepSight BulkActionPanel Interface
 * Bulk action panels allow actions to be performed on multiple elements from the datatable at once.
 */
interface deepsight_bulkactionpanel {

    /**
     * Constructor.
     *
     * @param string              $name       The name of the panel. Used to refer to a specific implementation of the panel.
     * @param string              $label      A label for the panel.
     * @param deepsight_datatable &$datatable A reference to the datatable being used with the panel, allowing communication.
     * @param array               $actions    An array of deepsight_action objects that can be performed with the added elements.
     */
    public function __construct($name, $label, deepsight_datatable &$datatable, array $actions);

    /**
     * Get the HTML needed to output the panel. This is ONLY the html, not the javascript.
     *
     * @return string The HTML to output.
     */
    public function get_html();

    /**
     * Get the javascript needed to initialze the panel.
     *
     * @return string The javascript to initialize the panel.
     */
    public function get_init_js();
}

/**
 * A standard implementation of the bulkactionpanel interface.
 */
class deepsight_bulkactionpanel_standard implements deepsight_bulkactionpanel {
    /**
     * The name of the panel. This is used in several places to refer to a specific implementation of the panel.
     */
    protected $name = null;

    /**
     * A reference to the datatable being used with the panel, allowing communication.
     */
    protected $datatable = null;

    /**
     * An array of deepsight_action objects that can be performed with the added elements.
     */
    protected $actions = array();

    /**
     * Constructor.
     *
     * Performs the following:
     *     - Sets internal data
     *     - Checks the $actions array to ensure they are all valid deepsight_action objects.
     *     - Also sets the datatable reference for each action.
     *
     * @see deepsight_bulkactionpanel::__construct()
     */
    public function __construct($name, $label, deepsight_datatable &$datatable, array $actions) {
        $this->name = $name;
        $this->label = $label;
        $this->datatable =& $datatable;

        foreach ($actions as $action) {
            if ($action instanceof deepsight_action) {
                $action->set_table($datatable);
                $this->actions[] = $action;
            }
        }
    }

    /**
     * Returns a div with an ID that includes this panel's $name.
     *
     * @see deepsight_bulkactionpanel::get_html()
     */
    public function get_html() {
        return '<div id="ds_bulkactionpanel_'.$this->name.'"></div>';
    }

    /**
     * Returns the Javascript used to initialize the panel - including all actions and language strings.
     *
     * @see deepsight_bulkactionpanel::get_init_js()
     */
    public function get_init_js() {
        // Language strings.
        $langselectedelement = get_string('ds_selectedelement', 'local_elisprogram');
        $langselectedelements = get_string('ds_selectedelements', 'local_elisprogram');
        $langdefaultstatus = get_string('ds_bulk_defaultstatus', 'local_elisprogram');
        $langaddall = get_string('ds_bulk_addall', 'local_elisprogram');
        $langsearchresults = get_string('ds_searchresults', 'local_elisprogram');
        $langclear = get_string('clear', 'local_elisprogram');
        $langunloadconfirm = get_string('ds_bulk_unloadconfirm', 'local_elisprogram');
        $langresult = get_string('result', 'local_elisprogram');
        $langresults = get_string('results', 'local_elisprogram');
        $langshowing = get_string('ds_showing', 'local_elisprogram');
        $langpage = get_string('page', 'moodle');

        // Actions.
        $actionsjs = array();
        foreach ($this->actions as $action) {
            if ($action->canbulk === true) {
                $actionsjs[] = $action->get_js_opts();
            }
        }
        $actionsjs = json_encode_with_functions($actionsjs);

        $opts = "datatable: {$this->datatable->get_name()}_datatable,
                lang_title: '{$this->label}',
                lang_selected_element: '{$langselectedelement}',
                lang_selected_elements: '{$langselectedelements}',
                lang_default_status: '{$langdefaultstatus}',
                lang_add_all: '{$langaddall}',
                lang_search_results: '{$langsearchresults}',
                lang_clear: '{$langclear}',
                lang_unloadconfirm: '{$langunloadconfirm}',
                lang_result: '{$langresult}',
                lang_results: '{$langresults}',
                lang_showing: '{$langshowing}',
                lang_page: '{$langpage}',
                actions: {$actionsjs}";

        // The whole javascript.
        $js = "$('#ds_bulkactionpanel_{$this->name}').deepsight_bulkactionpanel({{$opts}});";
        return $js;
    }
}

/**
 * DeepSight DataTable Interface.
 *
 * Datatables are the central part DeepSight - they connect filters, list results, and launch actions.
 * Any type of element can be listed, along with any column of information. Tables can launch as many actions as needed and handle
 * unlimited amounts of data.
 */
interface deepsight_datatable {

    /**
     * Constructor.
     *
     * @param moodle_database &$DB      The global moodle_database object.
     * @param string          $name     The name of the table - used in various places to tie together parts for the same table.
     * @param string          $endpoint The URL where all AJAX requests will be sent. This will be appended with an 'm' GET or
     *                                  POST variable for different request types.
     * @param string          $uniqid   A unique identifier for a datatable session. This should be generated on the user-facing
     *                                  page, and retrieved from the ajax-response page. This can then be used to reference a
     *                                  single session. For example, in the standard implementation, this is used to generate the
     *                                  parameter name of the bulklist, allowing multiple browser windows to have
     *                                  independent, non-conflicting bulklists.
     */
    public function __construct(moodle_database &$DB, $name, $endpoint, $uniqid=null);

    /**
     * Gets the name of the table. This will return the value from $name in the constructor.
     *
     * @return string The name of the table.
     */
    public function get_name();

    /**
     * Gets the HTML needed to display the table.
     *
     * @return string the HTML string.
     */
    public function get_html();

    /**
     * Gets any dependencies needed to display the table.
     *
     * @return array An array of filenames of needed javascript libraries
     */
    public function get_js_dependencies();

    /**
     * Gets the javascript needed to initialize the table.
     *
     * @return string The initializing javascript.
     */
    public function get_init_js();

    /**
     * Searches for and returns a table's filter.
     *
     * @param string $name The name of the requested filter.
     * @return deepsight_filter The requested filter, or null if not found.
     */
    public function get_filter($name);

    /**
     * Searches for and returns a table's action.
     *
     * @param string $name The name of the requested action.
     * @return deepsight_action The requested action, or null if not found.
     */
    public function get_action($name);

    /**
     * Responds to an ajax request.
     *
     * @param string $mode The request mode indicated by the "m" GET or POST variable.
     */
    public function respond($mode);
}

/**
 * A standard base implementation of a DeepSight datatable.
 */
abstract class deepsight_datatable_standard implements deepsight_datatable {
    use customfieldfilteringtrait;

    /**
     * @var string URL where all AJAX requests will be sent. Will be appended with an 'm' GET or POST variable for different
     *             request types.
     */
    protected $endpoint = '';

    /**
     * @var string The name of the table - used in various places to tie together parts for the same table.
     */
    protected $name;

    /**
     * @var array A list of available deepsight_filter objects for the table, indexed by the filter's $name property.
     */
    protected $available_filters = array();

    /**
     * @var array An array of filters displayed when the user first visits the page.
     *            Formatted as an array of deepsight_filter $name properties.
     */
    protected $initial_filters = array();

    /**
     * @var array An array of deepsight_action objects for the table. Indexed by the action's $name property.
     */
    protected $actions = array();

    /**
     * @var array An array of columns that will always be present, regardless of active filters.
     *            Formatted like  [table-aliased field name (i.e. element.id)]=>[column label]
     */
    protected $fixed_columns = array();

    /**
     * @var string The main table results are pulled from. This forms that FROM clause.
     */
    protected $main_table;

    /**
     * @var moodle_database A reference to the global database object.
     */
    protected $DB;

    /**
     * @var string A unique identifier to link together sessions/browser windows
     */
    public $uniqid;

    /**
     * @var int The number of results displayed per page of the table.
     */
    const RESULTSPERPAGE = 20;

    /**
     * Gets a page of elements from the bulklist for display.
     *
     * @param array $ids An array of IDs to get information for.
     * @return array An array of information for the requested IDs. Contains labels indexed by IDs.
     */
    abstract protected function bulklist_get_info_for_ids(array $ids = array());

    /**
     * Gets search results for the datatable.
     *
     * @param array   $filters    The filter array received from js. It is an array consisting of filtername=>data, and can be
     *                            passed directly to $this->get_filter_sql() to generate the required WHERE sql.
     * @param array   $sort       An array of field=>direction to specify sorting for the results.
     * @param int $limit_from The position in the dataset from which to start returning results.
     * @param int $limit_num  The amount of results to return.
     * @return array An array with the first value being a page of results, and the second value being the total number of results
     */
    abstract protected function get_search_results(array $filters, array $sort = array(), $limitfrom=null, $limitnum=null);

    /**
     * Gets an array of available filters.
     *
     * @return array An array of deepsight_filter objects that will be available.
     */
    abstract protected function get_filters();

    /**
     * Gets an array of initial filters.
     *
     * @return array An array of deepsight_filter $name properties that will be present when the user first loads the page.
     */
    abstract protected function get_initial_filters();

    /**
     * Get an array of columns that will always be present.
     *
     * @return array An array of fixed columns formatted like [table-aliased field name (i.e. element.id)]=>[column label]
     */
    abstract protected function get_fixed_columns();

    /**
     * Gets an array of actions that can be used on the elements of the datatable.
     *
     * @return array An array of deepsight_action objects that will be available for each element.
     */
    abstract public function get_actions();

    /**
     * Constructor.
     *
     * Performs the following functions:
     *     - Sets internal data.
     *     - Runs $this->populate();
     *
     * @param moodle_database &$DB      The global moodle_database object.
     * @param string          $name     The name of the table - used in various places to tie together parts for the same table.
     * @param string          $endpoint The URL where all AJAX requests will be sent. This will be appended with an 'm' GET or
     *                                  POST variable for different request types.
     * @param string          $uniqid   A unique identifier for a datatable session.
     *
     * @see deepsight_datatable::__construct();
     * @uses deepsight_datatable_standard::populate()
     */
    public function __construct(moodle_database &$DB, $name, $endpoint, $uniqid=null) {
        $this->DB =& $DB;
        $this->name = $name;
        $this->endpoint = $endpoint;
        $this->uniqid = (!empty($uniqid)) ? $uniqid : uniqid();
        $this->populate();
    }

    /**
     * Populates the class.
     *
     * Sets the class's defined filters, initial filters, fixed columns, and actions. Also ensures properly formatted internal data.
     *
     * @uses deepsight_datatable_standard::get_filters()
     * @uses deepsight_datatable_standard::get_initial_filters()
     * @uses deepsight_datatable_standard::get_fixed_columns()
     * @uses deepsight_datatable_standard::get_actions()
     */
    protected function populate() {
        // Add filters.
        $filters = $this->get_filters();
        foreach ($filters as $filter) {
            if ($filter instanceof deepsight_filter) {
                $this->available_filters[$filter->get_name()] = $filter;
            }
        }

        // Add initial filters.
        $this->initial_filters = $this->get_initial_filters();

        // Add fixed columns.
        $this->fixed_columns = $this->get_fixed_columns();

        // Add actions.
        $actions = $this->get_actions();
        foreach ($actions as $action) {
            if ($action instanceof deepsight_action) {
                $action->set_table($this);
                $this->actions[$action->get_name()] = $action;
            }
        }
    }

    /**
     * Gets the name of the datatable.
     *
     * @see deepsight_datatable::get_name()
     */
    public function get_name() {
        return $this->name;
    }

    /**
     * Gets a table's filter by the filter's name property, or returns null.
     *
     * @see deepsight_datatable::get_filter();
     */
    public function get_filter($name) {
        return (isset($this->available_filters[$name])) ? $this->available_filters[$name] : null;
    }

    /**
     * Gets a table's action by the action's name property, or returns null.
     *
     * @see deepsight_datatable::get_action()
     */
    public function get_action($name) {
        return (isset($this->actions[$name])) ? $this->actions[$name] : null;
    }

    /**
     * Get the key of the bulklist in the $SESSION variable.
     *
     * @return string The name of the property in $SESSION that will store the bulklist.
     */
    public function get_bulklist_sess_param() {
        return "ds_datatable_{$this->name}_{$this->uniqid}_bulklist";
    }

    /**
     * Gets the HTML needed to display the datatable.
     *
     * @see deepsight_datatable::get_html()
     */
    public function get_html() {
        return '<style type="text/css"> @import url("lib/deepsight/css/base.css");</style>
                <div id="'.$this->name.'" class="deepsight_datatable_wrapper">
                    <span class="filterlabel">'.get_string('filters', 'local_elisprogram').': </span>
                    <div id="'.$this->name.'_filterbar" class="deepsight_filterbar"></div>
                    <table id="'.$this->name.'_datatable" class="generaltable deepsight_datatable"></table>
                </div>';
    }

    /**
     * Gets an array of javascript files needed for operation.
     *
     * @see deepsight_datatable::get_js_dependencies()
     */
    public function get_js_dependencies() {
        return array(
            '/local/elisprogram/lib/deepsight/js/jquery-ui-1.10.1.custom.min.js',
            '/local/elisprogram/lib/deepsight/js/deepsight.js',
            '/local/elisprogram/lib/deepsight/js/support.js',
            '/local/elisprogram/lib/deepsight/js/filters/deepsight_filter_generator.js',
            '/local/elisprogram/lib/deepsight/js/filters/deepsight_filter_switch.js',
            '/local/elisprogram/lib/deepsight/js/filters/deepsight_filter_date.js',
            '/local/elisprogram/lib/deepsight/js/filters/deepsight_filter_textsearch.js',
            '/local/elisprogram/lib/deepsight/js/filters/deepsight_filter_searchselect.js',
            '/local/elisprogram/lib/deepsight/js/filters/deepsight_filterbar.js',
        );
    }

    /**
     * Get an array of options to pass to the deepsight_datatable javascript object.
     *
     * Includes options, language string, and action options.
     *
     * @return array An array of options, ready to be passed to $this->get_init_js()
     */
    protected function get_table_js_opts() {
        $opts = array(
            'dataurl' => $this->endpoint,
            'dragdrop' => false,
            'multiselect' => false,
            'sesskey' => sesskey(),
            'uniqid' => $this->uniqid,
            'resultsperpage' => static::RESULTSPERPAGE,
            'initial_filters' => $this->initial_filters,
            'rowfilter' => null,
            'actions' => array(),
            'lang_no_results' => get_string('ds_noresults', 'local_elisprogram'),
            'lang_errormessage' => get_string('ds_errormessage', 'local_elisprogram'),
            'lang_error' => get_string('ds_errordetails', 'local_elisprogram'),
            'lang_actions' => get_string('ds_actions', 'local_elisprogram'),
            'lang_page' => get_string('page', 'moodle'),
            'lang_result' => get_string('result', 'local_elisprogram'),
            'lang_results' => get_string('results', 'local_elisprogram'),
            'lang_showing' => get_string('ds_showing', 'local_elisprogram'),
            'lang_loading' => get_string('ds_loading', 'local_elisprogram'),
        );
        foreach ($this->actions as $action) {
            $opts['actions'][] = $action->get_js_opts();
        }
        return $opts;
    }

    /**
     * Gets an array of options for the filterbar, which contains and controls the table's filters.
     *
     * @return array An array of options.
     */
    protected function get_filterbar_js_opts() {
        $filters = array();
        foreach ($this->available_filters as $name => $filter) {
            $filters[$name] = array(
                'type' => $filter::TYPE,
                'opts' => $filter->get_js_opts()
            );
        }
        $opts = array(
            json_encode($filters),
            json_encode($this->initial_filters)
        );
        return $opts;
    }

    /**
     * Gets javascript required to initialize the datatable.
     *
     * @uses deepsight_datatable_standard::get_table_js_opts()
     * @uses deepsight_datatable_standard::get_filterbar_js_opts()
     * @see deepsight_datatable::get_init_js()
     */
    public function get_init_js() {
        // The js for the table.
        $tablejsopts = json_encode_with_functions($this->get_table_js_opts());
        $js = "var {$this->name}_datatable = $('#{$this->name}_datatable').deepsight_datatable({$tablejsopts});";

        // The js for the filterbar.
        list($filterbarfilters, $filterbarstartingfilters) = $this->get_filterbar_js_opts();
        $js .= "$('#{$this->name}_filterbar').deepsight_filterbar({
                    datatable: {$this->name}_datatable,
                    filters: {$filterbarfilters},
                    starting_filters: {$filterbarstartingfilters},
                    lang_add: '".get_string('add', 'local_elisprogram')."'
                });";

        return $js;
    }

    /**
     * The main response function, handling AJAX requests from the table, filters, and actions.
     *
     * @see deepsight_datatable::respond()
     * @uses deepsight_datatable_standard::respond_action()
     * @uses deepsight_datatable_standard::respond_filter()
     * @uses deepsight_datatable_standard::respond_bulklist_get()
     * @uses deepsight_datatable_standard::respond_bulklist_modify()
     * @uses deepsight_datatable_standard::respond_add_all()
     * @uses deepsight_datatable_standard::respond_datatable_results()
     */
    public function respond($mode) {
        try {
            $function = 'respond_'.$mode;
            if (method_exists($this, $function)) {
                $this->$function();
            } else {
                throw new Exception('Do not know how to respond to that request.');
            }
        } catch (Exception $e) {
            echo safe_json_encode(array('result' => 'fail', 'msg' => $e->getMessage()));
        }
    }

    /**
     * Responds to a request aimed at a particular action.
     *
     * Requests using this mode MUST include an "actionname" parameter, referring the to action's $name property.
     *
     * @uses deepsight_datatable::get_action()
     * @uses deepsight_action::respond_to_js()
     */
    protected function respond_action() {
        $actionname = required_param('actionname', PARAM_CLEAN);
        $action = $this->get_action($actionname);
        $action->respond_to_js();
    }

    /**
     * Responds to a request aimed at a particular filter.
     *
     * Requests using this mode MUST include a "filtername" parameter, referring the to filter's $name property.
     *
     * @uses deepsight_datatable::get_filter()
     * @uses deepsight_filter::respond_to_js()
     */
    protected function respond_filter() {
        $filtername = required_param('filtername', PARAM_CLEAN);
        $filter = $this->get_filter($filtername);
        if (!empty($filter)) {
            echo $filter->respond_to_js();
        }
    }

    /**
     * Responds to a request for a page of the bulklist.
     *
     * Parameters from $_REQUEST:
     *     int $page The page to return, or falls back to page 1
     *
     * @uses deepsight_datatable_standard::bulklist_get_display()
     */
    protected function respond_bulklist_get() {
        $page = optional_param('page', 1, PARAM_INT);
        list($pageresults, $totalresults) = $this->bulklist_get_display($page);
        echo safe_json_encode(array(
            'result' => 'success',
            'page_results_ids' => array_keys($pageresults),
            'page_results_values' => array_values($pageresults),
            'total_results' => $totalresults
        ));
    }

    /**
     * Responds to a request to modify the bulklist.
     *
     * Required $_REQUEST parameters:
     *     string $sesskey The CSRF-protection 'sesskey' variable.
     *     string $modify  Either "add" or "remove" - determines what to do with the $ids input.
     *     array  $ids     An array of IDs to add or remove.
     *
     * Outputs XSSI-safe-JSON containing 'result', 'page_results', and 'total_results', outlined below:
     *     string  result              'success' to indicate we successfully completed the request.
     *     array   page_results_ids    An array of IDs for the current page of results in the same order as page_results_values.
     *     array   page_results_values An array of labels for the current page of results, in the same order as page_results_ids.
     *     int     total_results       The total number of results in the dataset.
     */
    protected function respond_bulklist_modify() {
        require_sesskey();

        $mode = required_param('modify', PARAM_CLEAN);
        if ($mode !== 'add' && $mode != 'remove') {
            throw new Exception('Did not understand request');
        }

        $ids = required_param_array('ids', PARAM_CLEAN);
        if ($mode === 'add') {
            $this->bulklist_modify($ids);
        } else if ($mode === 'remove') {
            $this->bulklist_modify(array(), $ids);
        }

        list($pageresults, $totalresults) = $this->bulklist_get_display(1);
        echo safe_json_encode(array(
            'result' => 'success',
            'page_results_ids' => array_keys($pageresults),
            'page_results_values' => array_values($pageresults),
            'total_results' => $totalresults
        ));
    }

    /**
     * Respond to request for all element ids in a given search.
     *
     * This is usually performed when adding all search results to a bulk action panel.
     *
     * $_REQUEST parameters:
     *     array $filters An array of filter data to determine the result set.
     *     int   $page    (Optional) The page of the bulklist to return, falls back to 1
     *
     * Outputs XSSI-safe-JSON containing 'result', 'page_results', and 'total_results', outlined below:
     *     string result              'success' to indicate we successfully completed the request.
     *     array  page_results_ids    An array of IDs for the current page of results, in the same order as page_results_values.
     *     array  page_results_values An array of labels for the current page of results, in the same order as page_results_ids.
     *     int    total_results       The total number of results in the dataset.
     */
    protected function respond_add_all() {
        $page = optional_param('page', 1, PARAM_INT);
        $filters = required_param('filters', PARAM_CLEAN);
        $filters = @json_decode($filters, true);
        if (empty($filters) || !is_array($filters)) {
            $filters = array();
        }

        $this->bulklist_add_by_filters($filters);
        list($pageresults, $totalresults) = $this->bulklist_get_display($page);

        echo safe_json_encode(array(
            'result' => 'success',
            'page_results_ids' => array_keys($pageresults),
            'page_results_values' => array_values($pageresults),
            'total_results' => $totalresults
        ));
    }

    /**
     * Responds to a request from js for a page of results for a given set of filters.
     *
     * Parameters from $_REQUEST:
     *     array $filters         An array of filters to use when getting the results formatted like [filtername]=>[data]
     *     array $sort            (Optional) An array of sorting information, formatted like [fieldname]=>[direction].
     *     int   $limit_from      (Optional) The position in the entire result set to start returning rows.
     *     int   $limit_num       (Optional) The number of rows to return.
     *     array $bulklist_add    (Optional) An array of IDs to add to the bulklist before the results are fetched.
     *     array $bulklist_remove (Optional) An array of IDs to remove from the bulklist before the results are fetched.
     *
     * Outputs XSSI-safe-JSON containing two possible members: 'bulklist_modify' and 'datatable_results', outlined below.
     *
     *     bulklist_modify
     *         Will be present if $bulklist_add or $bulklist_remove were included with the original request.
     *         Contains updated bulklist data used by the bulk action panel.
     *         Contains the following:
     *             string result              'success' to indicate we successfully completed the request.
     *             array  page_results_ids    Array of IDs for current page of results in the same order as page_results_values.
     *             array  page_results_values Array of labels for current page of results in the same order as page_results_ids.
     *             int    total_results       The total number of results in the dataset.
     *
     *     datatable_results
     *         Will always be present and holds the results for the datatable.
     *             string result        'success' to indicate we successfully completed the request.
     *             array  column_labels An array of column labels formatted like [fieldname]=>[label]
     *             array  results       An array of results for the requested page.
     *             int    total_results A number of results in the entire dataset for the given filters.
     */
    protected function respond_datatable_results() {
        require_sesskey();
        $response = array();

        // Inputs.
        $sort = optional_param_array('sort', array(), PARAM_CLEAN);
        $limitfrom = optional_param('limit_from', 1, PARAM_INT);
        $limitnum = optional_param('limit_num', static::RESULTSPERPAGE, PARAM_INT);

        // Parse incoming filters.
        $filters = required_param('filters', PARAM_CLEAN);
        $filters = @json_decode($filters, true);
        if (empty($filters) || !is_array($filters)) {
            $filters = array();
        }

        // Modify bulklist, if necessary.
        $bulklistadd = optional_param_array('bulklist_add', array(), PARAM_INT);
        $bulklistremove = optional_param_array('bulklist_remove', array(), PARAM_CLEAN);
        if (!empty($bulklistadd) || !empty($bulklistremove)) {
            $this->bulklist_modify($bulklistadd, $bulklistremove);
            list($pageresults, $totalresults) = $this->bulklist_get_display(1);
            $response['bulklist_modify'] = array(
                'result' => 'success',
                'page_results_ids' => array_keys($pageresults),
                'page_results_values' => array_values($pageresults),
                'total_results' => $totalresults
            );
        }

        // Determine display fields - i.e. columns of the table.
        $columnlabels = $this->get_column_labels($filters);

        $sort = array_intersect_key($sort, $columnlabels);

        // Get results.
        list($pageresults, $totalresults) = $this->get_search_results($filters, $sort, $limitfrom, $limitnum);
        $disabledresults = $this->get_num_disabled_search_results();
        $response['datatable_results'] = array(
            'result' => 'success',
            'column_labels' => $columnlabels,
            'results' => $pageresults,
            'total_results' => $totalresults,
            'usable_results' => $totalresults - $disabledresults,
        );

        // Respond to js.
        echo safe_json_encode($response);
    }

    /**
     * Get the number of disabled search results.
     *
     * This should be overridden in child classes and calculated based on the individual needs of the child datatable.
     *
     * @return int The number of disabled search results.
     */
    protected function get_num_disabled_search_results() {
        return 0;
    }

    /**
     * Gets a page of elements from the bulklist for display.
     *
     * @param int $page The page of results to return.
     * @return array An array consisting of two members:
     *                   An array of page results formatted like [id]=>[label]
     *                   The total number of elements in the bulklist.
     */
    public function bulklist_get_display($page=1) {
        global $SESSION;

        $page = (is_int($page)) ? $page : 1;
        $itemsperpage = 20;
        $bulklistsessionparam = $this->get_bulklist_sess_param();
        $limitfrom = (($page-1)*$itemsperpage);
        $totalresults = isset($SESSION->$bulklistsessionparam) ? count($SESSION->$bulklistsessionparam) : 0;
        $pageresults = array();

        if (!empty($SESSION->$bulklistsessionparam)) {
            // Ro get the items for this page, in the correct order, we first seek to requested start position in array
            // we use the internal pointer as the keys of the bulklist are the IDs (for fast deduplication and deletion).
            end($SESSION->$bulklistsessionparam);
            for ($i = 0; $i < $limitfrom; $i++) {
                prev($SESSION->$bulklistsessionparam);
            }

            // No we iterate for # of items per page backward through the array to build a list of ids to fetch info for.
            $ids = array(current($SESSION->$bulklistsessionparam));
            for ($i = 1; $i < $itemsperpage; $i++) {
                $id = prev($SESSION->$bulklistsessionparam);
                if (!empty($id)) {
                    $ids[] = $id;
                }
            }

            $pageresults = (!empty($ids)) ? $this->bulklist_get_info_for_ids($ids) : array();
        }

        return array($pageresults, $totalresults);
    }

    /**
     * Modifies the bulklist.
     *
     * @param array $add    An array of IDs to add to the bulklist.
     * @param array $remove An array of IDs to remove from the bulklist. If '*' is included, the entire list is removed.
     */
    public function bulklist_modify(array $add = array(), array $remove = array()) {
        global $SESSION;
        if (empty($add) && empty($remove)) {
            return false;
        }

        $bulklistsessparam = $this->get_bulklist_sess_param();

        if (!empty($add)) {
            foreach ($add as $id) {
                if (is_numeric($id)) {
                    $id = (int)$id;
                    $SESSION->{$bulklistsessparam}[$id] = $id;
                }
            }
        }

        if (!empty($remove)) {
            if (in_array('*', $remove, true)) {
                unset($SESSION->{$bulklistsessparam});
            } else {
                foreach ($remove as $id) {
                    if (is_numeric($id)) {
                        $id = (int)$id;
                        if (isset($SESSION->{$bulklistsessparam}[$id])) {
                            unset($SESSION->{$bulklistsessparam}[$id]);
                        }
                    }
                }
            }
        }
    }

    /**
     * Adds all elements returned from a search with a given set of filters to the bulklist.
     *
     * This is usually used when using the "add all search results" button when performing bulk actions.
     *
     * @param array $filters The filter array received from js. It is an array consisting of filtername=>data, and can be passed
     *                       directly to $this->get_filter_sql() to generate the required WHERE sql.
     * @return true Success.
     */
    protected function bulklist_add_by_filters(array $filters) {
        global $SESSION;

        list($filtersql, $filterparams) = $this->get_filter_sql($filters);

        $joinsql = implode(' ', $this->get_join_sql($filters));

        $query = 'SELECT element.id FROM {'.$this->main_table.'} element '.$joinsql.' '.$filtersql;
        $results = $this->DB->get_recordset_sql($query, $filterparams);
        $sessionparam = $this->get_bulklist_sess_param();
        foreach ($results as $result) {
            $id = (int)$result->id;
            $SESSION->{$sessionparam}[$id] = $id;
        }

        return true;
    }

    /**
     * Get any JOINs needed for the full SQL query.
     *
     * @param array $filters An array of active filters to use to determne join sql.
     * @return array An array of JOIN SQL fragments.
     */
    protected function get_join_sql(array $filters=array()) {
        return array();
    }

    /**
     * Get any GROUP BYs needed for the full SQL query.
     *
     * @param array $filters An array of active filters to use to determne group by sql.
     * @return array An array of GROUP BY field names to group results.
     */
    protected function get_groupby_sql(array $filters=array()) {
        return array();
    }

    /**
     * Transforms an array of [fieldname]=>[direction] into an SQL ORDER BY clause.
     *
     * @param array $sort An array of field=>direction to specify sorting for the results.
     * @return string The SQL ORDER BY clause.
     */
    protected function get_sort_sql(array $sort=array()) {
        $columns = array();

        // Assemble list of columns from filters.
        foreach ($this->available_filters as $filtername => $filter) {
            $columns = array_merge($columns, $this->available_filters[$filtername]->get_column_labels());
        }

        // Add fixed columns to list of valid columns.
        foreach ($this->fixed_columns as $column => $label) {
            if (!isset($columns[$column])) {
                $columns[$column] = $label;
            }
        }

        $sort = array_intersect_key($sort, $columns);

        $sortsql = '';
        if (!empty($sort)) {
            $sortparts = array();
            foreach ($sort as $field => $dir) {
                $sortparts[] = $field.' '.strtoupper($dir);
            }
            $sortsql = 'ORDER BY '.implode(',', $sortparts);
        }
        return $sortsql;
    }

    /**
     * Converts an array of requested filter data into an SQL WHERE clause.
     *
     * @param array $filters An array of requested filter data. Formatted like [filtername]=>[data].
     * @return array An array consisting of the SQL WHERE clause, and the parameters for the SQL.
     */
    protected function get_filter_sql(array $filters) {
        global $SESSION, $CFG, $DB;

        $filtersql = array();
        $filterparams = array();

        // Assemble filter SQL.
        foreach ($filters as $filtername => $data) {
            if (isset($this->available_filters[$filtername])) {
                list($sql, $params) = $this->available_filters[$filtername]->get_filter_sql($data);
                if (!empty($sql)) {
                    $filtersql[] = $sql;
                }
                if (!empty($params) && is_array($params)) {
                    $filterparams = array_merge($filterparams, $params);
                }
            }
        }

        // Exclude elements in the bulklist.
        $sessionparam = $this->get_bulklist_sess_param();
        if (!empty($SESSION->$sessionparam) && is_array($SESSION->$sessionparam)) {
            // It's very painful to properly parameterize the bulklist for large numbers here. although the bulklist
            // is segregated from any direct user input, and should never include anything but INTs, do a quick check here to
            // make sure...
            foreach ($SESSION->$sessionparam as $id => $val) {
                if (is_int($val) !== true) {
                    unset($SESSION->{$sessionparam}[$id]);
                }
            }

            $filtersql[] = 'element.id NOT IN ('.implode(',', $SESSION->$sessionparam).')';
        }

        $filtersql = (!empty($filtersql)) ? 'WHERE '.implode(' AND ', $filtersql) : '';
        return array($filtersql, $filterparams);
    }

    /**
     * Gets an array of fields to include in the search SQL's SELECT clause.
     *
     * Pulls information from $this->fixed_columns, and each filter's get_select_fields() function.
     *
     * @uses deepsight_filter::get_select_fields();
     * @uses deepsight_datatable_standard::$fixed_columns
     * @uses deepsight_datatable_standard::$available_filters
     * @param array $filters An Array of active filters to use to determine the needed select fields.
     * @return array An array of fields for the SELECT clause.
     */
    protected function get_select_fields(array $filters) {
        $selectfields = array('element.id AS element_id');
        foreach ($this->fixed_columns as $field => $label) {
            if (substr($field, 0, 3) == 'cf_') {
                $basetable = substr($field, 0, -5);
                $fielddefault = $basetable.'_default.data';
                $selectfields[] = "
                        (CASE
                            WHEN {$field} IS NULL THEN {$fielddefault}
                            ELSE {$field}
                         END) AS ".str_replace('.', '_', $field);
            } else {
                $selectfields[] = $field.' AS '.str_replace('.', '_', $field);
            }
        }

        foreach ($filters as $filtername => $data) {
            if (isset($this->available_filters[$filtername])) {
                $selectfields = array_merge($selectfields, $this->available_filters[$filtername]->get_select_fields());
            }
        }
        $selectfields = array_unique($selectfields);
        return $selectfields;
    }

    /**
     * Gets an array of column labels to send back to the javascript.
     *
     * Pulls information from $this->fixed_columns, and each filter's get_column_labels() function.
     *
     * @uses deepsight_filter::get_select_fields();
     * @uses deepsight_datatable_standard::$fixed_columns
     * @uses deepsight_datatable_standard::$available_filters
     * @param array $filters An array of active filters to determine which columns to return.
     * @return array An column labels formatted like [field alias (i.e. element_id)]=>[label]
     */
    protected function get_column_labels(array $filters) {
        $columnlabels = array();
        foreach ($this->fixed_columns as $field => $label) {
            $columnlabels[str_replace('.', '_', $field)] = $label;
        }
        foreach ($filters as $filtername => $data) {
            if (isset($this->available_filters[$filtername])) {
                $columnlabels = array_merge($columnlabels, $this->available_filters[$filtername]->get_column_labels());
            }
        }
        return $columnlabels;
    }

    /**
     * Gets all userset ids that are subsets of the given userset.
     * @param int $usersetid The ID of the userset to get subsets for.
     * @param bool $includeparent Whether to include the passed parent userset ID in the return array.
     * @return array An array of userset IDs
     */
    public static function get_userset_subsets($usersetid, $includeparent = true) {
        global $DB;
        $parentctx = \local_elisprogram\context\userset::instance($usersetid);
        if (empty($parentctx)) {
            return array();
        }

        $usersets = array();

        $path = ($includeparent === true) ? $parentctx->path.'%' : $parentctx->path.'/%';

        // Get sub usersets.
        $sql = 'SELECT userset.id as clusterid,
                       userset.*
                  FROM {'.userset::TABLE.'} userset
                  JOIN {context} ctx
                       ON userset.id = ctx.instanceid
                 WHERE ctx.contextlevel = ?
                       AND ctx.path LIKE ?';
        $params = array(CONTEXT_ELIS_USERSET, $path);
        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Get standard permission filters for a user - element available table.
     *
     * This takes into account the local/elisprogram:[element]_enrol, and elis:program/[element]_enrol_userset_user permissions.
     *
     * @param string $elementtype The type of element we're associating to. I.e. program, track, class, userset.
     * @param string $elementidsfromclusterids An SQL query to get ids for associated $elementtype from a list of clusters. Use
     *                                         the placeholder {clusterids} to include the $DB->get_in_or_equal call for the
     *                                         cluster ids.
     * @return array An array consisting of an array of additional filters as 0, and parameters as 1
     */
    protected function get_filter_sql_permissions_userelement_available($elementtype, $elementidsfromclusterids) {
        global $USER, $DB;

        $elementtype2ctxlevel = array(
            'program' => 'curriculum',
            'track' => 'track',
            'class' => 'pmclass',
            'userset' => 'cluster'
        );

        if (!isset($elementtype2ctxlevel[$elementtype])) {
            throw new Exception('Bad element type specified for get_filter_sql_permissions_userelement_available');
        }

        $enrolperm = 'local/elisprogram:'.$elementtype.'_enrol';
        $usersetenrolperm = 'local/elisprogram:'.$elementtype.'_enrol_userset_user';
        $ctxlevel = $elementtype2ctxlevel[$elementtype];

        $additionalfilters = array();
        $additionalparams = array();

        // Get filter for contexts/elements where user has $enrolperm permission.
        $elementenrolctxs = pm_context_set::for_user_with_capability($ctxlevel, $enrolperm, $USER->id);
        $elementenrolctxsfilterobject = $elementenrolctxs->get_filter('id', $ctxlevel);
        $elementenrolfilter = $elementenrolctxsfilterobject->get_sql(false, 'element', SQL_PARAMS_QM);

        // Get elements that are associated with a userset where:
        //     - $this->userid is a member of the userset.
        //     - $USER has $usersetenrolperm permission on the userset.
        $elementenrolusersetuserctxs = pm_context_set::for_user_with_capability('cluster', $usersetenrolperm, $USER->id);
        $assigneeclusters = cluster_get_user_clusters($this->userid);
        $clusters = array();
        foreach ($assigneeclusters as $assigneecluster) {
            $subsets = static::get_userset_subsets($assigneecluster->clusterid, true);
            $clusters = array_merge($clusters, $subsets);
        }

        $allowedclusters = $elementenrolusersetuserctxs->get_allowed_instances($clusters, 'cluster', 'clusterid');

        // Create the final filters.
        if (isset($elementenrolfilter['where'])) {
            if (empty($allowedclusters)) {
                // If there's no $usersetenrolperm clusters to worry about, just use elements where assigner
                // has $enrolperm perms.
                $additionalfilters[] = $elementenrolfilter['where'];
                $additionalparams = array_merge($additionalparams, $elementenrolfilter['where_parameters']);
            } else {
                // If we do have $usersetenrolperm clusters to worry about, we add a filter to require element ids to be either
                // elements associated with the clusters where that permission exists, or contexts where the assigner has
                // the $enrolperm permissions.
                list($allowclusterswhere, $allowclustersparams) = $DB->get_in_or_equal($allowedclusters);
                $elementidsfromclusterids = str_replace('{clusterids}', $allowclusterswhere, $elementidsfromclusterids);

                $additionalfilters[] = '(element.id IN ('.$elementidsfromclusterids.') OR '.$elementenrolfilter['where'].')';
                $additionalparams = array_merge($additionalparams, $allowclustersparams, $elementenrolfilter['where_parameters']);
            }
        }

        return array($additionalfilters, $additionalparams);
    }

    /**
     * Get standard permission filters for an element - user available table.
     *
     * This takes into account the local/elisprogram:[element]_enrol, and elis:program/[element]_enrol_userset_user permissions.
     *
     * @param string $elementtype The type of element we're associating to. I.e. program, track, class, userset.
     * @param int $elementid The ID of the base element we're associating to.
     * @param string $elementid2clusterscallable A callable that will get the associated cluster ids from an element id.
     * @return array An array consisting of an array of additional filters as 0, and parameters as 1
     */
    protected function get_filter_sql_permissions_elementuser($elementtype, $elementid, $elementid2clusterscallable) {
        global $USER, $DB;

        $elementtype2ctxlevel = array(
            'program' => 'curriculum',
            'track' => 'track',
            'class' => 'pmclass',
            'userset' => 'cluster'
        );

        if (!isset($elementtype2ctxlevel[$elementtype])) {
            throw new Exception('Bad element type specified for get_filter_sql_permissions_userelement_available');
        }

        $enrolperm = 'local/elisprogram:'.$elementtype.'_enrol';
        $usersetenrolperm = 'local/elisprogram:'.$elementtype.'_enrol_userset_user';
        $ctxlevel = $elementtype2ctxlevel[$elementtype];

        $additionalfilters = array();
        $additionalparams = array();

        // If $USER has $enrolperm permission for this element, we don't have to go any further.
        $enrolctxs = pm_context_set::for_user_with_capability($ctxlevel, $enrolperm, $USER->id);
        if ($enrolctxs->context_allowed($elementid, $ctxlevel) !== true) {

            // We now cross-reference the clusters the assigner has the $usersetenrolperm permission with clusters the element is
            // assigned to. We limit the users returned in the search results to users that are in the resulting clusters.
            $enrolusersetuserctxs = pm_context_set::for_user_with_capability('cluster', $usersetenrolperm, $USER->id);

            // Get the clusters and check the context against them.
            $clusters = call_user_func($elementid2clusterscallable, $elementid);
            $allowedclusters = $enrolusersetuserctxs->get_allowed_instances($clusters, 'cluster', 'clusterid');

            if (!empty($allowedclusters)) {
                list($clusterfilterwhere, $clusterfilterparams) = $DB->get_in_or_equal($allowedclusters);
                $useridsfromclusters = 'SELECT userid FROM {'.clusterassignment::TABLE.'} WHERE clusterid '.$clusterfilterwhere;
                $additionalfilters[] = 'element.id IN ('.$useridsfromclusters.')';
                $additionalparams = array_merge($additionalparams, $clusterfilterparams);
            } else {
                $additionalfilters[] = 'FALSE';
            }
        } else {
            $additionalfilters[] = 'TRUE';
        }

        return array($additionalfilters, $additionalparams);
    }

    /**
     * results_row_transform() method to transform custom datetime fields
     *
     * @param array $row An array for a single result.
     * @return array The transformed result.
     */
    protected function results_row_transform(array $row) {
        foreach ($this->customfields as $fieldname => $field) {
            $elem = $fieldname.'_data';
            if (isset($row[$elem]) && isset($field->params['control']) && $field->params['control'] == 'datetime') {
                $row[$elem] = ds_process_displaytime($row[$elem]);
            }
        }
        return $row;
    }
}
