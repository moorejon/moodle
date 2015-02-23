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
 * @copyright  (C) 2014 onwards Remote Learner.net Inc http://www.remote-learner.net
 * @author     Brent Boghosian <brent.boghosian@remote-learner.net>
 *
 */

require_once(elispm::lib('data/crssetcourse.class.php'));

/**
 * A base class for managing courseset - courses associations. (one courseset, multiple courses)
 */
class deepsight_datatable_crssetcourse_base extends deepsight_datatable_course {

    /**
     * @var int The ID of the courseset being managed.
     */
    protected $id;

    /**
     * Sets the current courseset ID
     * @param int $crssetid The ID of the courseset to use.
     */
    public function set_id($crssetid) {
        $this->id = (int)$crssetid;
    }

    /**
     * Gets an array of javascript files needed for operation.
     * @see deepsight_datatable::get_js_dependencies()
     */
    public function get_js_dependencies() {
        $deps = parent::get_js_dependencies();
        $deps[] = '/local/elisprogram/lib/deepsight/js/actions/deepsight_action_confirm.js';
        $deps[] = '/local/elisprogram/lib/deepsight/js/actions/deepsight_action_link.js';
        $deps[] = '/local/elisprogram/lib/deepsight/js/actions/deepsight_action_crssetcourse.js';
        return $deps;
    }

    /**
     * Get an array of options to pass to the deepsight_datatable javascript object. Enables drag and drop, and multiselect.
     * @return array An array of options, ready to be passed to $this->get_init_js()
     */
    public function get_table_js_opts() {
        $opts = parent::get_table_js_opts();
        $opts['dragdrop'] = true;
        $opts['multiselect'] = true;
        $opts['desc_single'] = get_string('ds_action_crssetcrs_unassign', 'local_elisprogram');
        $opts['desc_single_active'] = get_string('ds_action_crssetcrs_unassign_active', 'local_elisprogram');
        $opts['desc_multiple'] = get_string('ds_bulk_confirm', 'local_elisprogram');
        $opts['desc_multiple_active'] = get_string('ds_bulk_confirm_crs_active', 'local_elisprogram');
        $opts['langbulkconfirm'] = get_string('ds_bulk_confirm', 'local_elisprogram');
        $opts['langbulkconfirmactive'] = get_string('ds_bulk_confirm_crs_active', 'local_elisprogram');
        $opts['langconfirmactive'] = get_string('confirm_delete_active_courseset_course', 'local_elisprogram');
        $opts['langworking'] = get_string('ds_working', 'local_elisprogram');
        $opts['langyes'] = get_string('yes', 'moodle');
        $opts['langno'] = get_string('no', 'moodle');
        $opts['langchanges'] = get_string('ds_changes', 'local_elisprogram');
        $opts['langnochanges'] = get_string('ds_nochanges', 'local_elisprogram');
        $opts['langgeneralerror'] = get_string('ds_unknown_error', 'local_elisprogram');
        $opts['langtitle'] = get_string('ds_assocdata', 'local_elisprogram');
        $opts['no_permission'] = get_string('not_permitted', 'local_elisprogram');
        return $opts;
    }
}

/**
 * A datatable for listing currently assigned courses.
 */
class deepsight_datatable_crssetcourse_assigned extends deepsight_datatable_crssetcourse_base {
    /** @var int $disabledresults number of disabled rows */
    protected $disabledresults = 0;

    /** @var coursesetpage $crssetpage courseset page object */
    protected $crssetpage;

    /**
     * Sets the current courseset ID
     * @param int $crssetid The ID of the courseset to use.
     */
    public function set_id($crssetid) {
        parent::set_id($crssetid);
        $this->crssetpage = new coursesetpage(array('id' => $this->id));
    }

    /**
     * Gets an array of available filters.
     * @return array An array of deepsight_filter objects that will be available.
     */
    protected function get_filters() {
        $filters = parent::get_filters();
        return $filters;
    }

    /**
     * Gets an array of initial filters.
     * @return array An array of deepsight_filter $name properties that will be present when the user first loads the page.
     */
    protected function get_initial_filters() {
        $initialfilters = parent::get_initial_filters();
        return $initialfilters;
    }

    /**
     * Formats the delete active permission params.
     * @param array $row An array for a single result.
     * @return array The transformed result.
     */
    protected function results_row_transform(array $row) {
        require_once(elispm::file('coursesetpage.class.php'));
        $row = parent::results_row_transform($row);
        $row['meta']['isactive'] = $this->crssetpage->is_active($row['element_id']);
        $row['meta']['candel'] = !$row['meta']['isactive'] || $this->crssetpage->_has_capability('local/elisprogram:courseset_delete_active');
        if (!$row['meta']['candel']) {
            $this->disabledresults++;
        }
        return $row;
    }

    /**
     * Gets the edit and unassignment actions.
     * @return array An array of deepsight_action objects that will be available for each element.
     */
    public function get_actions() {
        $actions = parent::get_actions();

        // Unassign action.
        $unassign = new deepsight_action_crssetcourse_unassign($this->DB, 'crssetcourse_unassign');
        $unassign->endpoint = (strpos($this->endpoint, '?') !== false)
                ? $this->endpoint.'&m=action' : $this->endpoint.'?m=action';
        $unassign->condition = 'function(rowdata) { return(rowdata.meta.candel); }';
        $actions[] = $unassign;

        return $actions;
    }

    /**
     * Gets an array of fields to include in the search SQL's SELECT clause.
     *
     * @param array $filters An Array of active filters to use to determine the needed select fields.
     * @return array An array of fields for the SELECT clause.
     */
    protected function get_select_fields(array $filters) {
        $fields = parent::get_select_fields($filters);
        $fields[] = 'crssetcrs.id AS crssetcrs_id';
        $fields[] = 'crssetcrs.crssetid AS crssetcrs_crssetid';
        return $fields;
    }

    /**
     * Adds the assignment table.
     * @param array $filters An array of active filters to use to determne join sql.
     * @return string A SQL string containing any JOINs needed for the full query.
     */
    protected function get_join_sql(array $filters = array()) {
        $joinsql = parent::get_join_sql($filters);
        $joinsql[] = 'JOIN {'.crssetcourse::TABLE.'} crssetcrs ON crssetcrs.crssetid = '.$this->id.'
                           AND crssetcrs.courseid = element.id';
        return $joinsql;
    }

    /**
     * Get an array of options to pass to the deepsight_datatable javascript object. Enables drag and drop, and multiselect.
     * @return array An array of options, ready to be passed to $this->get_init_js()
     */
    public function get_table_js_opts() {
        $opts = parent::get_table_js_opts();
        $langactive = get_string('active', 'local_elisprogram');
        $opts['rowfilter'] = 'function(row, rowdata) {
                                  if (rowdata.meta.isactive) {
                                      window.lepcrsactivelist.push(rowdata.element_id);
                                  }
                                  if (!rowdata.meta.candel) {
                                      row.addClass(\'disabled\').find(\'td.actions\').html(\''.$langactive.'\');
                                  }
                                  return row;
                              }';
        return $opts;
    }

    /**
     * Get the number of disabled search results.
     * @return int The number of disabled search results.
     */
    protected function get_num_disabled_search_results() {
        return $this->disabledresults;
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

        $this->disabledresults = 0;
        list($filtersql, $filterparams) = $this->get_filter_sql($filters);
        $joinsql = implode(' ', $this->get_join_sql($filters));
        $query = 'SELECT element.id FROM {'.$this->main_table.'} element '.$joinsql.' '.$filtersql;
        $results = $this->DB->get_recordset_sql($query, $filterparams);
        $sessionparam = $this->get_bulklist_sess_param();
        foreach ($results as $result) {
            if ($this->crssetpage->_has_capability('local/elisprogram:courseset_delete_active') || !$this->crssetpage->is_active($result->id)) {
                $id = (int)$result->id;
                $SESSION->{$sessionparam}[$id] = $id;
            } else {
                $this->disabledresults++;
            }
        }

        return true;
    }
}

/**
 * A datatable listing courses that are available to assign to the program, and are not currently assigned.
 */
class deepsight_datatable_crssetcourse_available extends deepsight_datatable_crssetcourse_base {

    /**
     * Gets the assign action.
     * @return array An array of deepsight_action objects that will be available for each element.
     */
    public function get_actions() {
        $actions = parent::get_actions();

        // Assign action.
        $assign = new deepsight_action_crssetcourse_assign($this->DB, 'crssetcourse_assign');
        $assign->endpoint = (strpos($this->endpoint, '?') !== false)
                ? $this->endpoint.'&m=action' : $this->endpoint.'?m=action';
        $actions[] = $assign;

        return $actions;
    }

    /**
     * Adds the assignment table.
     * @param array $filters An array of active filters to use to determne join sql.
     * @return string A SQL string containing any JOINs needed for the full query.
     */
    protected function get_join_sql(array $filters=array()) {
        $joinsql = parent::get_join_sql($filters);
        $joinsql[] = 'LEFT JOIN {'.crssetcourse::TABLE.'} crssetcrs ON crssetcrs.crssetid = '.$this->id.'
                                AND crssetcrs.courseid = element.id';
        return $joinsql;
    }

    /**
     * Gets filter sql for permissions.
     * @return array An array consisting of additional WHERE conditions, and parameters.
     */
    protected function get_filter_sql_permissions() {
        global $USER;
        $ctxlevel = 'course';
        $perm = 'local/elisprogram:associate';
        $additionalfilters = array();
        $additionalparams = array();
        $associatectxs = pm_context_set::for_user_with_capability($ctxlevel, $perm, $USER->id);
        $associatectxsfilerobject = $associatectxs->get_filter('id', $ctxlevel);
        $associatefilter = $associatectxsfilerobject->get_sql(false, 'element', SQL_PARAMS_QM);
        if (isset($associatefilter['where'])) {
            $additionalfilters[] = $associatefilter['where'];
            $additionalparams = array_merge($additionalparams, $associatefilter['where_parameters']);
        }
        return array($additionalfilters, $additionalparams);
    }

    /**
     * Removes assigned programs, and limits results according to permissions.
     * @param array $filters An array of requested filter data. Formatted like [filtername]=>[data].
     * @return array An array consisting of the SQL WHERE clause, and the parameters for the SQL.
     */
    protected function get_filter_sql(array $filters) {
        global $USER;

        list($filtersql, $filterparams) = parent::get_filter_sql($filters);

        $additionalfilters = array();

        // Remove assigned users.
        $additionalfilters[] = 'crssetcrs.id IS NULL';

        // Permissions.
        list($permadditionalfilters, $permadditionalparams) = $this->get_filter_sql_permissions();
        $additionalfilters = array_merge($additionalfilters, $permadditionalfilters);
        $filterparams = array_merge($filterparams, $permadditionalparams);

        // Add our additional filters.
        if (!empty($additionalfilters)) {
            $filtersql = (!empty($filtersql))
                    ? $filtersql.' AND '.implode(' AND ', $additionalfilters)
                    : 'WHERE '.implode(' AND ', $additionalfilters);
        }

        return array($filtersql, $filterparams);
    }
}
