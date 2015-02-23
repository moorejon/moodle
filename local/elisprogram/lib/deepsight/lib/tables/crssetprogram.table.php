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

require_once(elispm::lib('data/programcrsset.class.php'));

/**
 * A base class for managing courseset - program associations. (one courseset, multiple programs)
 */
class deepsight_datatable_crssetprogram_base extends deepsight_datatable_program {

    /**
     * @var int The ID of the courseset being managed.
     */
    protected $id;

    /** @var \courseset The courseset object that matches $this->id. */
    protected $crsset = null;

    /** @var float The total number of credits in the courseset. */
    protected $crssettotalcredits = null;

    /** @var int The total number of courses in the courseset. */
    protected $crssettotalcourses = null;

    /**
     * Sets the current courseset ID
     * @param int $crssetid The ID of the courseset to use.
     */
    public function set_id($crssetid) {
        $this->id = (int)$crssetid;
    }

    /**
     * Populate the internal courseset object and attributes.
     */
    protected function populate_crsset() {
        if (empty($this->id)) {
            return false;
        }
        require_once(\elispm::lib('data/courseset.class.php'));
        $filters = [new \field_filter('id', $this->id)];
        $crssets = \courseset::find(new \AND_filter($filters));
        $crssets = $crssets->to_array();
        $this->crsset = array_shift($crssets);
        $this->crssettotalcredits = $this->crsset->total_credits();
        $this->crssettotalcourses = $this->crsset->total_courses();
    }

    /**
     * Gets an array of javascript files needed for operation.
     * @see deepsight_datatable::get_js_dependencies()
     */
    public function get_js_dependencies() {
        $deps = parent::get_js_dependencies();
        $deps[] = '/local/elisprogram/lib/deepsight/js/actions/deepsight_action_confirm.js';
        $deps[] = '/local/elisprogram/lib/deepsight/js/actions/deepsight_action_link.js';
        $deps[] = '/local/elisprogram/lib/deepsight/js/actions/deepsight_action_crssetprogram.js';
        $deps[] = '/local/elisprogram/lib/deepsight/js/actions/deepsight_action_programcrsset_assignedit.js';
        return $deps;
    }

    /**
     * Get an array of options to pass to the deepsight_datatable javascript object. Enables drag and drop, and multiselect.
     * @return array An array of options, ready to be passed to $this->get_init_js()
     */
    public function get_table_js_opts() {
        $opts = parent::get_table_js_opts();
        $opts['dragdrop'] = true;
        $opts['activelist'] = 0;
        $opts['multiselect'] = true;
        $opts['desc_single'] = get_string('ds_action_crssetprg_unassign', 'local_elisprogram');
        $opts['desc_single_active'] = get_string('ds_action_crssetprg_unassign_active', 'local_elisprogram');
        $opts['desc_multiple'] = get_string('ds_bulk_confirm', 'local_elisprogram');
        $opts['desc_multiple_active'] = get_string('ds_bulk_confirm_prg_active', 'local_elisprogram');
        $opts['desc_multiple_edit_active'] = get_string('ds_bulk_confirm_edit_crsset_active', 'local_elisprogram');
        $opts['langbulkconfirm'] = get_string('ds_bulk_confirm', 'local_elisprogram');
        $opts['langbulkconfirmactive'] = get_string('ds_bulk_confirm_prg_active', 'local_elisprogram');
        $opts['langbulkconfirmeditactive'] = get_string('ds_bulk_confirm_edit_crsset_active', 'local_elisprogram');
        $opts['langconfirmactive'] = get_string('confirm_delete_active_courseset_program', 'local_elisprogram');
        $opts['langconfirmeditactive'] = get_string('confirm_edit_active_courseset_program', 'local_elisprogram');
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
 * A datatable for listing currently assigned programs.
 */
class deepsight_datatable_crssetprogram_assigned extends deepsight_datatable_crssetprogram_base {
    /** @var int $disabledresults number of disabled rows */
    protected $disabledresults = 0;

    /** @var coursesetpage $crssetpage courseset page object */
    protected $crssetpage;

    /**
     * Sets the current courseset ID
     * @param int $crssetid The ID of the courseset to use.
     */
    public function set_id($crssetid) {
        require_once(elispm::file('coursesetpage.class.php'));
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
        $row = parent::results_row_transform($row);
        $filters = array();
        $filters[] = new field_filter('crssetid', $this->id);
        $filters[] = new field_filter('prgid', $row['element_id']);
        $prgcrssets = programcrsset::find(new AND_filter($filters));
        $isactive = -1;
        foreach ($prgcrssets as $prgcrsset) {
            if ($isactive == -1) {
                $isactive = 0;
            }
            if ($prgcrsset->is_active()) {
                $isactive++;
                break;
            }
        }
        if ($this->crsset === null) {
            $this->populate_crsset();
        }
        $row['meta']['numcredits'] = $this->crssettotalcredits;
        $row['meta']['numcourses'] = $this->crssettotalcourses;
        $row['meta']['isactive'] = ($isactive > 0);
        $row['meta']['candel'] = !$row['meta']['isactive'] || $this->crssetpage->_has_capability('local/elisprogram:courseset_delete_active');
        $row['meta']['canedit'] = !$row['meta']['isactive'] || $this->crssetpage->_has_capability('local/elisprogram:courseset_edit_active');
        if (!$row['meta']['candel'] && !$row['meta']['canedit']) {
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

        // Edit action.
        $edit = new deepsight_action_crssetprogram_edit($this->DB, 'crssetprogram_edit');
        $edit->endpoint = (strpos($this->endpoint, '?') !== false)
                ? $this->endpoint.'&m=action' : $this->endpoint.'?m=action';
        $edit->condition = 'function(rowdata) { return(rowdata.meta.canedit); }';
        $actions[] = $edit;

        // Unassign action.
        $unassign = new deepsight_action_crssetprogram_unassign($this->DB, 'crssetprogram_unassign');
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
        $fields[] = 'prgcrsset.id AS prgcrsset_id';
        $fields[] = 'prgcrsset.crssetid AS prgcrsset_crssetid';
        $fields[] = 'prgcrsset.reqcredits AS assocdata_reqcredits';
        $fields[] = 'prgcrsset.reqcourses AS assocdata_reqcourses';
        $fields[] = 'prgcrsset.andor AS assocdata_andor';
        return $fields;
    }

    /**
     * Adds the assignment table.
     * @param array $filters An array of active filters to use to determne join sql.
     * @return string A SQL string containing any JOINs needed for the full query.
     */
    protected function get_join_sql(array $filters = array()) {
        $joinsql = parent::get_join_sql($filters);
        $joinsql[] = 'JOIN {'.programcrsset::TABLE.'} prgcrsset ON prgcrsset.crssetid = '.$this->id.'
                           AND prgcrsset.prgid = element.id';
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
                                      window.lepprgactivelist.push(rowdata.element_id);
                                  }
                                  if (!rowdata.meta.candel && !rowdata.meta.canedit) {
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
            $filters = array();
            $filters[] = new field_filter('crssetid', $this->id);
            $filters[] = new field_filter('prgid', $result->id);
            $prgcrssets = programcrsset::find(new AND_filter($filters));
            $isactive = -1;
            foreach ($prgcrssets as $prgcrsset) {
                if ($isactive == -1) {
                    $isactive = 0;
                }
                if ($prgcrsset->is_active()) {
                    $isactive++;
                }
            }
            if ($isactive == 0 || $this->crssetpage->_has_capability('local/elisprogram:courseset_delete_active') ||
                    $this->crssetpage->_has_capability('local/elisprogram:courseset_edit_active')) {
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
 * A datatable listing programs that are available to assign to the course set, and are not currently assigned.
 */
class deepsight_datatable_crssetprogram_available extends deepsight_datatable_crssetprogram_base {

    /**
     * Gets the assign action.
     * @return array An array of deepsight_action objects that will be available for each element.
     */
    public function get_actions() {
        $actions = parent::get_actions();

        // Assign action.
        $assign = new deepsight_action_crssetprogram_assign($this->DB, 'crssetprogram_assign');
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
        $joinsql[] = 'LEFT JOIN {'.programcrsset::TABLE.'} prgcrsset ON prgcrsset.crssetid = '.$this->id.'
                                AND prgcrsset.prgid = element.id';
        return $joinsql;
    }

    /**
     * Gets filter sql for permissions.
     * @return array An array consisting of additional WHERE conditions, and parameters.
     */
    protected function get_filter_sql_permissions() {
        global $USER;
        $ctxlevel = 'curriculum';
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
     * Formats the delete active permission params.
     *
     * @param array $row An array for a single result.
     * @return array The transformed result.
     */
    protected function results_row_transform(array $row) {
        $row = parent::results_row_transform($row);
        if ($this->crsset === null) {
            $this->populate_crsset();
        }
        $row['meta']['numcredits'] = $this->crssettotalcredits;
        $row['meta']['numcourses'] = $this->crssettotalcourses;
        return $row;
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
        $additionalfilters[] = 'prgcrsset.id IS NULL';

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
