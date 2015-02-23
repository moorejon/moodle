<?php
/**
 * ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2008-2013 Remote-Learner.net Inc (http://www.remote-learner.net)
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
 * @copyright  (C) 2013 Remote Learner.net Inc http://www.remote-learner.net
 * @author     James McQuillan <james.mcquillan@remote-learner.net>
 *
 */

/**
 * DeepSight Filter Interface
 * Filters provide a method to filter the data displayed in a datatable. They can add additional conditions to the sql and add
 * columns to the table.
 */
interface deepsight_filter {

    /**
     * Constructor.
     *
     * @param moodle_database &$DB       The global moodle_database object.
     * @param string          $name      The name of the filter. Used when receiving data to determine where to send the data.
     * @param string          $label     The label that will be displayed on the filter button.
     * @param array           $fielddata An array of field information used by the filter. Formatted like [field]=>[label].
     *                                   Usually this is what field the filter will use to affect the datatable results, but refer
     *                                   to the individual filter for specifics.
     */
    public function __construct(moodle_database &$DB, $name, $label, array $fielddata = array());

    /**
     * Provides a map between fields in the datatable results, and the labels for their columns.
     *
     * Note: The fields must map to fields returned in $this->get_select_fields, and thus, the datatable result set.
     *       So, if these fields are aliased (see docblock for get_select_fields), the keys of this array must also be the aliased
     *       names of the fields.
     *
     * @return array An array of fields present in the results and labels, formatted like [field]=>[label]
     */
    public function get_column_labels();

    /**
     * Provides an array of fields to be included in the datatable's SELECT sql clause.
     *
     * These should be the fields that the filter wants the table to show. If you are joining multiple tables, this should alias
     * the select fields to include the table name in the field name.
     *
     * @return array An array of fields to be included in the datatable's SELECT sql clause.
     */
    public function get_select_fields();

    /**
     * Gets information about the fields covered by the filter.
     *
     * @return array Array of fields the filter searches. Indexes are fully-qualified field names, values are field aliases.
     */
    public function get_field_list();

    /**
     * Provide part of a WHERE clause to the datatable to affect the results.
     *
     * The aggregate of all filter's get_filter_sql() sql will be joined together with AND and used to filter to entire dataset.
     *
     * @param mixed $data The data from the filter send from the javascript.
     * @return array An array consisting of filter sql as index 0, and an array of parameters as index 1
     */
    public function get_filter_sql($data);

    /**
     * Get an array of options to pass to the javascript as an options object.
     *
     * @return array An array of options to pass to the javascript object.
     */
    public function get_js_opts();

    /**
     * Gets the name of the filter.
     *
     * @return string The name of the filter.
     */
    public function get_name();

    /**
     * Function that is run by the datatable when it receives a request aimed at this filter.
     *
     * @return string Response JSON.
     */
    public function respond_to_js();
}

/**
 * A standard base implementation for a filter.
 */
abstract class deepsight_filter_standard implements deepsight_filter {

    /**
     * Indicates the JS class the filter is using. This should only be the end part of the js class
     * For example, this would be "date" if using the date filter.
     */
    const TYPE = null;

    /**
     * The name of the filter. This is used when receiving data to determine where to send the data.
     */
    protected $name = '';

    /**
     * An array of field information used by the filter. This is formatted like [field]=>[label]
     */
    protected $fields = array();

    /**
     * A map of the original fields and their associated aliased names. Formatted like [field name]=>[aliased field name]
     */
    protected $field_aliases = array();

    /**
     * The label of the filter, displayed on the filter button.
     */
    protected $label;

    /**
     * Internal moodle_database object used by the filter.
     */
    protected $DB;

    /**
     * Standard constructor - sets internal data, and generates field aliases.
     *
     * @see deepsight_filter::__construct();
     */
    public function __construct(moodle_database &$DB, $name, $label, array $fielddata = array()) {
        $this->DB =& $DB;
        $this->name = $name;
        $this->label = $label;

        foreach ($fielddata as $fieldname => $label) {
            $this->fields[$fieldname] = $label;
        }

        // Var $field will usually be table-aliased, which makes it difficult to use when referring to it in the result object
        // so we create an usable column alias here (x.y is not a valid alias, so we use x_y) and record the association.
        foreach ($this->fields as $fieldname => $label) {
            $this->field_aliases[$fieldname] = str_replace('.', '_', $fieldname);
        }

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
     * Returns all fields passed into the filter constructor using their aliased names, and their associated labels.
     *
     * @see deepsight_filter::get_column_labels()
     */
    public function get_column_labels() {
        $columnlabels = array();
        foreach ($this->field_aliases as $fieldname => $alias) {
            $columnlabels[$alias] = $this->fields[$fieldname];
        }
        return $columnlabels;
    }

    /**
     * Returns all fields passed into the constructor using thier original table-aliased names, and their resulting aliased names.
     *
     * @see deepsight_filter::get_select_fields()
     */
    public function get_select_fields() {
        $displayfields = array();
        foreach ($this->field_aliases as $field => $fieldalias) {
            if (substr($field, 0, 3) == 'cf_') {
                $basetable = substr($field, 0, -5);
                $fielddefault = $basetable.'_default.data';
                $displayfields[] = "
                        (CASE
                            WHEN {$field} IS NULL THEN {$fielddefault}
                            ELSE {$field}
                         END) AS ".str_replace('.', '_', $field);
            } else {
                $displayfields[] = $field.' AS '.$fieldalias;
            }
        }
        return $displayfields;
    }

    /**
     * Gets information about the fields covered by the filter.
     *
     * @return array Array of fields the filter searches. Indexes are fully-qualified field names, values are field aliases.
     */
    public function get_field_list() {
        return $this->field_aliases;
    }

    /**
     * Provides the basic required options common to all filters.
     *
     * If you are overriding this method to provide additional options, you should merge your options array with
     * parent::get_js_opts() to include these.
     *
     * @see deepsight_filter::get_js_opts()
     */
    public function get_js_opts() {
        return array(
            'name' => $this->name,
            'label' => $this->label,
        );
    }

    /**
     * Returns the name of the filter.
     *
     * @see deepsight_filter::get_name()
     */
    public function get_name() {
        return $this->name;
    }

    /**
     * In the standard implementation, this does nothing. Subclasses implement this as needed.
     *
     * @see deepsight_filter::respond_to_js()
     */
    public function respond_to_js() {
        return '';
    }
}
