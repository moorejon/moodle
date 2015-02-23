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

require_once(__DIR__.'/lib.php');

/**
 * Trait providing custom field filtering functions to deepsight tables.
 */
trait customfieldfilteringtrait {
    /** @var array An array of custom field records, indexed by the name of the \deepsight_filter object that filters it. */
    protected $customfields = array();

	/**
     * Gets custom field information for a given context level.
     *
     * Sets the internal $this->customfields array with the returned field information, and returns an array of filters
     * for each custom field found.
     * NOTE: This will only look for filterable custom fields, which at the moment are "char" or "text" fields.
     *
     * @param int $contextlevel The context level of the fields we want. i.e. CONTEXT_ELIS_USER, CONTEXT_ELIS_CLASS, etc.
     * @param array $menuofchoicesadditionalparams An array of additonal search parameters to set in any menu of choices filters.
     * @return array An array of deepsight_filter objects for each found filterable field.
     */
    protected function get_custom_field_info($contextlevel, array $menuofchoicesadditionalparams = array()) {
        $fieldfilters = array();
        $fielddata = array();

        // Add custom fields.
        $sql = 'SELECT field.id, field.name, field.shortname, field.datatype, owner.params
                  FROM {local_eliscore_field} field
                  JOIN {local_eliscore_field_clevels} ctx ON ctx.fieldid = field.id
                  JOIN {local_eliscore_field_owner} owner ON owner.fieldid = field.id AND plugin = "manual"
                 WHERE field.datatype != "bool"
                       AND ctx.contextlevel = ?';
        $customfields = $this->DB->get_recordset_sql($sql, array($contextlevel));
        foreach ($customfields as $field) {
            $field->params = @unserialize($field->params);
            if (!is_array($field->params)) {
                $field->params = array();
            }

            $field->shortname = strtolower($field->shortname); // TBD: Moodle DB API converting them to lower case so we must too to get value!
            $filtername = 'cf_'.$field->shortname;
            $fielddata[$filtername] = $field;

            $filterfielddata = array($filtername.'.data' => $field->name);

            if (isset($field->params['control']) && $field->params['control'] === 'menu' && !empty($field->params['options'])) {
                $filtermenu = new \deepsight_filter_menuofchoices($this->DB, $filtername, $field->name, $filterfielddata,
                                                                 $this->endpoint);
                $choices = explode("\n", $field->params['options']);
                foreach ($choices as $i => $choice) {
                    $choices[$i] = trim($choice);
                }
                $filtermenu->set_choices(array_combine($choices, $choices));
                if (!empty($menuofchoicesadditionalparams)) {
                    $filtermenu->set_additionalsearchparams($menuofchoicesadditionalparams);
                }
                $fieldfilters[] = $filtermenu;
            } else if (isset($field->params['control']) && $field->params['control'] === 'datetime') {
                $fieldfilters[] = new \deepsight_filter_date($this->DB, $filtername, $field->name, $filterfielddata);
            } else {
                $fieldfilters[] = new \deepsight_filter_textsearch($this->DB, $filtername, $field->name, $filterfielddata);
            }
        }
        $this->customfields = $fielddata;
        return $fieldfilters;
    }

    /**
     * Get SQL joins for custom fields.
     *
     * @param int $ctxlevel The context level for the fields.
     * @param array $fields Array of \field objects, indexed by fieldname ("cf_[fieldshortname]")
     * @return array Array of joins needed to select and search on custom fields.
     */
    protected function get_custom_field_joins($ctxlevel, array $fields) {
        $ctxlevel = (int)$ctxlevel;
        $joinsql = [];
        if (!empty($fields)) {
            $joinsql[] = 'JOIN {context} ctx ON ctx.instanceid = element.id AND ctx.contextlevel='.$ctxlevel;
            foreach ($fields as $fieldname => $field) {
                $datatable = ($field->datatype == 'datetime') ? 'int' : $field->datatype;
                $customfieldjoin = 'LEFT JOIN {local_eliscore_fld_data_'.$datatable.'} '.$fieldname.' ON ';
                $customfieldjoin .= $fieldname.'.contextid = ctx.id AND '.$fieldname.'.fieldid = '.$field->id;
                $customfieldjoin .= '
                    LEFT JOIN {local_eliscore_fld_data_'.$datatable.'} '.$fieldname.'_default ON ';
                $customfieldjoin .= $fieldname.'_default.contextid IS NULL AND '.$fieldname.'_default.fieldid = '.$field->id;
                $joinsql[] = $customfieldjoin;
            }
        }
        return $joinsql;
    }
}
