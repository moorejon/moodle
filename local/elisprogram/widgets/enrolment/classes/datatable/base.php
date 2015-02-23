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

require_once(__DIR__.'/../../../../lib/deepsight/lib/customfieldfilteringtrait.php');

/**
 * Abstract base class for deepsight datatables used in the widget.
 */
abstract class base {
    use \customfieldfilteringtrait;

    /** @var array A list of available deepsight_filter objects for the table, indexed by the filter's $name property. */
    protected $availablefilters = [];

    /** @var array An array of deepsight_filter $name properties determining filters shown on page load. */
    protected $initialfilters = [];

    /** @var string The main table results are pulled from. This forms that FROM clause. */
    protected $maintable = '';

    /** @var \moodle_database A reference to the global database object. */
    protected $DB;

    /** @var array An array of fields that will always be selected, regardless of what has been enabled. */
    protected $fixedfields = [];

    /** @var string URL where all AJAX requests will be sent. */
    protected $endpoint = '';

    /**
     * Constructor.
     * @param \moodle_database $DB An active database connection.
     */
    public function __construct(\moodle_database &$DB, $ajaxendpoint) {
        $this->DB =& $DB;
        $this->endpoint = $ajaxendpoint;
        $this->populate();
    }

    /**
     * Populates the class.
     *
     * Sets the class's defined filters, initial filters, and fixed columns. Also ensures properly formatted internal data.
     */
    protected function populate() {
        // Add filters.
        $filters = $this->get_filters();
        foreach ($filters as $filter) {
            if ($filter instanceof \deepsight_filter) {
                $this->availablefilters[$filter->get_name()] = $filter;
            }
        }

        // Add initial filters.
        $this->initialfilters = $this->get_initial_filters();

        // Add fixed fields.
        $this->fixedfields = $this->get_fixed_select_fields();
    }

    /**
     * Gets an array of initial filters.
     *
     * @return array An array of \deepsight_filter $name properties that will be present when the user first loads the list.
     */
    public function get_initial_filters() {
        return [];
    }

    /**
     * Gets an array of available filters.
     *
     * @return array An array of \deepsight_filter objects that will be available.
     */
    public function get_filters() {
        return [];
    }

    /**
     * Searches for and returns a table's filter.
     *
     * @param string $name The name of the requested filter.
     * @return deepsight_filter The requested filter, or null if not found.
     */
    public function get_filter($name) {
        return (isset($this->availablefilters[$name])) ? $this->availablefilters[$name] : null;
    }

    /**
     * Gets an array of fields that will always be selected, regardless of what has been enabled.
     *
     * @return array An array of fields that will always be selected.
     */
    public function get_fixed_select_fields() {
        return [];
    }

    /**
     * Get an array of datafields that will always be visible.
     *
     * @return array Array of filter aliases for fields that will always be visible.
     */
    public function get_fixed_visible_datafields() {
        return [];
    }

    /**
     * Get an array containing a list of visible and hidden datafields.
     *
     * For fields that are not fixed (see self::get_fixed_visible_datafields), additional fields are displayed when the user
     * searches on them. For fields that are not being searched on, they can be viewed by clicking a "more" link.
     *
     * @param array $filters An array of requested filter data. Formatted like [filtername]=>[data].
     * @return array Array of field information, first item is visible fields, second is hidden fields.
     */
    public function get_datafields_by_visibility(array $filters = array()) {
        $visiblefields = [];
        $hiddenfields = [];

        $fixedvisible = array_flip($this->get_fixed_visible_datafields());
        foreach ($this->availablefilters as $filtername => $filter) {
            $fields = array_combine(array_values($filter->get_field_list()), array_values($filter->get_column_labels()));
            if (isset($fixedvisible[$filtername]) || isset($filters[$filtername])) {
                $visiblefields = array_merge($visiblefields, $fields);
            } else {
                $hiddenfields = array_merge($hiddenfields, $fields);
            }
        }
        return [array_unique($visiblefields), array_unique($hiddenfields)];
    }

    /**
     * Converts an array of requested filter data into an SQL WHERE clause.
     *
     * @param array $filters An array of requested filter data. Formatted like [filtername]=>[data].
     * @return array An array consisting of the SQL WHERE clause, and the parameters for the SQL.
     */
    protected function get_filter_sql(array $filters = array()) {
        $filtersql = [];
        $filterparams = [];

        // Assemble filter SQL.
        foreach ($filters as $filtername => $data) {
            if (isset($this->availablefilters[$filtername])) {
                list($sql, $params) = $this->availablefilters[$filtername]->get_filter_sql($data);
                if (!empty($sql)) {
                    $filtersql[] = $sql;
                }
                if (!empty($params) && is_array($params)) {
                    $filterparams = array_merge($filterparams, $params);
                }
            } else if (is_numeric($filtername) && isset($data['sql'])) {
                // Raw SQL fragments can be added as filters if they are an array containing at least 'sql', and have a numeric id.
                $filtersql[] = $data['sql'];
                if (isset($data['params']) && is_array($data['params'])) {
                    $filterparams = array_merge($filterparams, $data['params']);
                }
            }
        }

        $filtersql = (!empty($filtersql)) ? 'WHERE '.implode(' AND ', $filtersql) : '';
        return [$filtersql, $filterparams];
    }

    /**
     * Get an array of fields to select in the get_search_results method.
     *
     * @param array $filters An array of requested filter data. Formatted like [filtername]=>[data].
     * @return array Array of fields to select.
     */
    protected function get_select_fields(array $filters = array()) {
        $selectfields = ['element.id AS element_id'];
        foreach ($this->fixedfields as $field => $label) {
            $selectfields[] = $field.' AS '.str_replace('.', '_', $field);
        }

        foreach ($this->availablefilters as $filtername => $filter) {
            $selectfields = array_merge($selectfields, $filter->get_select_fields());
        }
        $selectfields = array_unique($selectfields);
        return $selectfields;
    }

    /**
     * Get a list of desired table joins to be used in the get_search_results method.
     *
     * @param array $filters An array of requested filter data. Formatted like [filtername]=>[data].
     * @return array Array with members: First item is an array of JOIN sql fragments, second is an array of parameters used by
     *               the JOIN sql fragments.
     */
    protected function get_join_sql(array $filters = array()) {
        return [[], []];
    }

    /**
     * Get an ORDER BY sql fragment to be used in the get_searcH_results method.
     *
     * @return string An ORDER BY sql fragment, if desired.
     */
    protected function get_sort_sql() {
        return '';
    }

    /**
     * Get a GROUP BY sql fragment to be used in the get_search_results method.
     *
     * @return string A GROUP BY sql fragment, if desired.
     */
    protected function get_groupby_sql() {
        return '';
    }

    /**
     * Get search results/
     *
     * @param array $filters An array of requested filter data. Formatted like [filtername]=>[data].
     * @param int $page The page being displayed.
     * @return \moodle_recordset A recordset of course information.
     */
    public function get_search_results(array $filters = array(), $page = 1) {
        global $CFG;

        $selectfields = $this->get_select_fields($filters);
        array_unshift($selectfields, 'element.id');
        $selectfields = array_unique($selectfields);

        list($joinsql, $joinparams) = $this->get_join_sql($filters);
        $joinsql = implode(' ', $joinsql);

        list($filtersql, $filterparams) = $this->get_filter_sql($filters);

        $params = array_merge($joinparams, $filterparams);

        $sortsql = $this->get_sort_sql();
        $groupbysql = $this->get_groupby_sql();

        if (empty($page) || !is_int($page) || $page <= 0) {
            $page = 1;
        }
        $limitfrom = ($page - 1) * static::RESULTSPERPAGE;
        $limitnum = static::RESULTSPERPAGE;

        if (empty($this->maintable)) {
            throw new \coding_error('You must specify a main table ($this->maintable) in subclasses.');
        }

        // Get the number of results in the full dataset.
        if (!empty($groupbysql)) {
            // If the query has a group by statement, we have to put it in a subquery to avoid interaction with our count().
            $sqlparts = [
                    'SELECT element.id',
                    'FROM {'.$this->maintable.'} element',
                    $joinsql,
                    $filtersql,
                    $groupbysql,
            ];
            $query = implode(' ', $sqlparts);
            $query = 'SELECT count(1) as count FROM ('.$query.') results';
        } else {
            $sqlparts = [
                    'SELECT count(1) as count',
                    'FROM {'.$this->maintable.'} element',
                    $joinsql,
                    $filtersql
            ];
            $query = implode(' ', $sqlparts);
        }
        $totalresults = $this->DB->count_records_sql($query, $params);

        // Generate and execute query for a single page of results.
        $sqlparts = [
                'SELECT '.implode(', ', $selectfields),
                'FROM {'.$this->maintable.'} element',
                $joinsql,
                $filtersql,
                $groupbysql,
                $sortsql,
        ];
        $query = implode(' ', $sqlparts);
        $results = $this->DB->get_recordset_sql($query, $params, $limitfrom, $limitnum);

        return [$results, $totalresults];
    }
}
