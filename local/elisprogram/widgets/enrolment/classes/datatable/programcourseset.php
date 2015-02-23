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
 * A datatable implementation that lists coursesets for a program.
 */
class programcourseset extends courseset {
    /** @var int The ID of the program we're getting coursesets for. */
    protected $programid = null;

    /**
     * Get a list of desired table joins to be used in the get_search_results method.
     *
     * @param array $filters An array of requested filter data. Formatted like [filtername]=>[data].
     * @return array Array with members: First item is an array of JOIN sql fragments, second is an array of parameters used by
     *               the JOIN sql fragments.
     */
    protected function get_join_sql(array $filters = array()) {
        list($sql, $params) = parent::get_join_sql($filters);
        $newsql = ['JOIN {local_elisprogram_prgcrsset} pgmcrsset ON pgmcrsset.crssetid = element.id AND pgmcrsset.prgid = ?'];
        $newparams = [$this->programid];
        return [array_merge($sql, $newsql), array_merge($params, $newparams)];
    }

    /**
     * Set the ID of the program we're getting program courses for.
     *
     * @param int $programid The ID of the program we're getting courses for.
     */
    public function set_programid($programid) {
        $this->programid = $programid;
    }
}
