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

/**
 * A datatable implementation for lists of coursesets.
 */
class courseset extends base {
    /** @var string The main table results are pulled from. This forms that FROM clause. */
    protected $maintable = 'local_elisprogram_crsset';

    /** @var int The number of results displayed per page of the table. */
    const RESULTSPERPAGE = 10;

    /**
     * Gets an array of available filters.
     *
     * @return array An array of \deepsight_filter objects that will be available.
     */
    public function get_filters() {
        global $CFG;

        // If availablefilters has been populated, we can just use that.
        if (!empty($this->availablefilters)) {
            return $this->availablefilters;
        }

        require_once(\elispm::lib('deepsight/lib/filter.php'));
        require_once(\elispm::lib('deepsight/lib/filters/textsearch.filter.php'));

        // Basic fields.
        $langname = get_string('courseset_name', 'local_elisprogram');
        $langidnumber = get_string('courseset_idnumber', 'local_elisprogram');
        $langdesc = get_string('description', 'local_elisprogram');
        $filters = [
                new \deepsight_filter_textsearch($this->DB, 'idnumber', $langidnumber, ['element.idnumber' => $langidnumber]),
                new \deepsight_filter_textsearch($this->DB, 'name', $langname, ['element.name' => $langname]),
                new \deepsight_filter_textsearch($this->DB, 'description', $langdesc, ['element.description' => $langdesc]),
        ];

        // Add custom fields.
        $crssetctxlevel = \local_eliscore\context\helper::get_level_from_name('courseset');
        $customfieldfilters = $this->get_custom_field_info($crssetctxlevel, ['table' => get_called_class()]);
        $filters = array_merge($filters, $customfieldfilters);

        // Restrict to configured enabled fields.
        $enabledfields = get_config('eliswidget_enrolment', 'coursesetenabledfields');
        if (!empty($enabledfields)) {
            $enabledfields = explode(',', $enabledfields);
            foreach ($filters as $i => $filter) {
                if (!in_array($filter->get_name(), $enabledfields)) {
                    unset($filters[$i]);
                }
            }
        }

        return $filters;
    }

    /**
     * Gets an array of fields that will always be selected, regardless of what has been enabled.
     *
     * @return array An array of fields that will always be selected.
     */
    public function get_fixed_select_fields() {
        return ['element.idnumber' => '', 'element.name' => ''];
    }

    /**
     * Get an array of datafields that will always be visible.
     *
     * @return array Array of filter aliases for fields that will always be visible.
     */
    public function get_fixed_visible_datafields() {
        return ['idnumber', 'description'];
    }

    /**
     * Get a list of desired table joins to be used in the get_search_results method.
     *
     * @param array $filters An array of requested filter data. Formatted like [filtername]=>[data].
     * @return array Array with members: First item is an array of JOIN sql fragments, second is an array of parameters used by
     *               the JOIN sql fragments.
     */
    protected function get_join_sql(array $filters = array()) {
        list($sql, $params) = parent::get_join_sql($filters);

        $enabledcfields = array_intersect_key($this->customfields, $this->availablefilters);
        $ctxlevel = \local_eliscore\context\helper::get_level_from_name('courseset');
        $newsql = $this->get_custom_field_joins($ctxlevel, $enabledcfields);
        return [array_merge($sql, $newsql), $params];
    }
}
