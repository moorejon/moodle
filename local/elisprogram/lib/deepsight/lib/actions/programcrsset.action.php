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
 * @copyright  (C) 2014 onwards Remote-Learner.net Inc (http://www.remote-learner.net)
 * @author     Brent Boghosian <brent.boghosian@remote-learner.net>
 *
 */

/**
 * Action to assign courseset to a program.
 */
abstract class deepsight_action_programcrsset_assignedit extends deepsight_action_crssetprogram_assignedit {
    /**
     * @var string The label for the action.
     */
    public $label = 'Assign';

    /**
     * @var string The icon for the action.
     */
    public $icon = 'elisicon-assoc';

    /**
     * @var string Mode indicating to the javascript how to operate.
     */
    public $mode = 'assign';

    /**
     * Constructor.
     * @param moodle_database $DB The active database connection.
     * @param string $name The unique name of the action to use.
     */
    public function __construct(moodle_database &$DB, $name) {
        parent::__construct($DB, $name);
        $this->label = ucwords(get_string('assign', 'local_elisprogram'));
    }

    /**
     * Provide options to the javascript.
     * @return array An array of options.
     */
    public function get_js_opts() {
        $opts = parent::get_js_opts();
        $opts['opts']['activelist'] = 1;
        return $opts;
    }

    /**
     * Determine whether the current user can manage the crsset - program association.
     * @param int $prgid The ID of the Program.
     * @param int $crssetid The ID of the courseset.
     * @return bool Whether the current can manage (true) or not (false)
     */
    protected function can_manage_assoc($prgid, $crssetid) {
        global $USER;
        $perm = 'local/elisprogram:associate';
        $crssetassocctx = pm_context_set::for_user_with_capability('courseset', $perm, $USER->id);
        $crssetassociateallowed = ($crssetassocctx->context_allowed($crssetid, 'courseset') === true) ? true : false;
        $programassocctx = pm_context_set::for_user_with_capability('curriculum', $perm, $USER->id);
        $programassociateallowed = ($programassocctx->context_allowed($prgid, 'curriculum') === true) ? true : false;
        return ($crssetassociateallowed === true && $programassociateallowed === true) ? true : false;
    }
}

/**
 * Action to assign courseset to a program.
 */
class deepsight_action_programcrsset_assign extends deepsight_action_programcrsset_assignedit {
    /**
     * @var string The label for the action.
     */
    public $label = 'Assign';

    /**
     * @var string The icon for the action.
     */
    public $icon = 'elisicon-assoc';

    /**
     * @var string Mode indicating to the javascript how to operate.
     */
    public $mode = 'assign';

    /**
     * Constructor.
     * @param moodle_database $DB The active database connection.
     * @param string $name The unique name of the action to use.
     */
    public function __construct(moodle_database &$DB, $name) {
        parent::__construct($DB, $name);
        $this->label = ucwords(get_string('assign', 'local_elisprogram'));
    }

    /**
     * Create courseset-program associations.
     * @param array $elements An array of program information to edit.
     * @param bool $bulkaction Whether this is a bulk-action or not.
     * @return array An array to format as JSON and return to the Javascript.
     */
    protected function _respond_to_js(array $elements, $bulkaction) {
        global $DB;
        $prgid = required_param('id', PARAM_INT);
        $assocdata = required_param('assocdata', PARAM_CLEAN);
        $assocdata = $this->process_incoming_assoc_data($assocdata, $bulkaction);
        if (!is_array($assocdata)) {
            throw new Exception('Did not receive valid association data.');
        }

        $assocclass = 'programcrsset';
        $assocparams = ['main' => 'prgid', 'incoming' => 'crssetid'];
        $assocfields = ['reqcredits', 'reqcourses', 'andor'];
        $faillang = get_string('ds_action_crssetprg_bulkmaxexceeded', 'local_elisprogram');
        return $this->attempt_associate($prgid, $elements, $bulkaction, $assocclass, $assocparams, $assocfields, $assocdata, $faillang);
    }
}

/**
 * Edit the program - courseset assignment.
 */
class deepsight_action_programcrsset_edit extends deepsight_action_programcrsset_assignedit {
    /**
     * @var string The label for the action.
     */
    public $label = 'Edit';

    /**
     * @var string The icon for the action.
     */
    public $icon = 'elisicon-edit';

    /**
     * @var string Mode indicating to the javascript how to operate.
     */
    public $mode = 'edit';

    /**
     * Constructor.
     * @param moodle_database $DB The active database connection.
     * @param string $name The unique name of the action to use.
     */
    public function __construct(moodle_database &$DB, $name) {
        parent::__construct($DB, $name);
        $this->label = ucwords(get_string('edit', 'local_elisprogram'));
    }


    /**
     * Edit program-courseset associations.
     * @param array $elements An array of program information to edit.
     * @param bool $bulkaction Whether this is a bulk-action or not.
     * @return array An array to format as JSON and return to the Javascript.
     */
    protected function _respond_to_js(array $elements, $bulkaction) {
        global $DB;
        $prgid = required_param('id', PARAM_INT);
        $assocdata = required_param('assocdata', PARAM_CLEAN);
        $assocdata = $this->process_incoming_assoc_data($assocdata, $bulkaction);
        if (!is_array($assocdata)) {
            throw new Exception('Did not receive valid association data.');
        }

        $assocclass = 'programcrsset';
        $assocparams = ['main' => 'prgid', 'incoming' => 'crssetid'];
        $assocfields = ['reqcredits', 'reqcourses', 'andor'];
        $faillang = get_string('ds_action_crssetprg_bulkmaxexceeded', 'local_elisprogram');
        return $this->attempt_edit($prgid, $elements, $bulkaction, $assocclass, $assocparams, $assocfields, $assocdata, $faillang);
    }
}

/**
 * An action to unassign courseset from a program.
 */
class deepsight_action_programcrsset_unassign extends deepsight_action_standard {
    /**
     * The javascript class to use.
     */
    const TYPE = 'programcrsset';

    /**
     * @var string The label for the action.
     */
    public $label = 'Unassign';

    /**
     * @var string The icon for the action.
     */
    public $icon = 'elisicon-unassoc';

    /**
     * @var string Mode indicating to the javascript how to operate.
     */
    public $mode = 'remove'; // TBD

    /**
     * Constructor.
     * @param moodle_database $DB The active database connection.
     * @param string $name The unique name of the action to use.
     * @param string $descsingle The description when the confirmation is for a single element.
     *        TBD: coursesets require different confirm msg when active
     * @param string $descmultiple The description when the confirmation is for the bulk list.
     */
    public function __construct(moodle_database &$DB, $name, $descsingle='', $descmultiple='') {
        parent::__construct($DB, $name);
        $this->label = ucwords(get_string('unassign', 'local_elisprogram'));

        $langelements = new stdClass;
        $langelements->baseelement = strtolower(get_string('curriculum', 'local_elisprogram'));
        $langelements->actionelement = strtolower(get_string('courseset', 'local_elisprogram'));
        $this->descsingle = (!empty($descsingle))
                ? $descsingle : get_string('ds_action_unassign_confirm', 'local_elisprogram', $langelements);

        $langelements = new stdClass;
        $langelements->baseelement = strtolower(get_string('curricula', 'local_elisprogram'));
        $langelements->actionelement = strtolower(get_string('coursesets', 'local_elisprogram'));
        $this->descmultiple = (!empty($descmultiple))
                ? $descmultiple : get_string('ds_action_unassign_confirm_multi', 'local_elisprogram', $langelements);
    }

    /**
     * Provide options to the javascript.
     * @return array An array of options.
     */
    public function get_js_opts() {
        global $CFG;
        $opts = parent::get_js_opts();
        $opts['condition'] = $this->condition;
        $opts['opts']['actionurl'] = $this->endpoint;
        $opts['opts']['desc_single'] = $this->descsingle;
        $opts['opts']['desc_multiple'] = $this->descmultiple;
        $opts['opts']['mode'] = $this->mode;
        $opts['opts']['activelist'] = 1;
        $opts['opts']['langbulkconfirm'] = get_string('ds_bulk_confirm', 'local_elisprogram');
        $opts['opts']['langbulkconfirmactive'] = get_string('confirm_delete_active_courseset_program', 'local_elisprogram');
        $opts['opts']['langconfirmactive'] = get_string('confirm_delete_active_courseset_program', 'local_elisprogram');
        $opts['opts']['langworking'] = get_string('ds_working', 'local_elisprogram');
        $opts['opts']['langyes'] = get_string('yes', 'moodle');
        $opts['opts']['langno'] = get_string('no', 'moodle');
        $opts['opts']['langchanges'] = get_string('ds_changes', 'local_elisprogram');
        $opts['opts']['langnochanges'] = get_string('ds_nochanges', 'local_elisprogram');
        $opts['opts']['langgeneralerror'] = get_string('ds_unknown_error', 'local_elisprogram');
        $opts['opts']['langtitle'] = get_string('ds_assocdata', 'local_elisprogram');
        return $opts;
    }

    /**
     * Unassign the coursesets from the program.
     * @param array $elements An array of courseset information to unassign from the course.
     * @param bool $bulkaction Whether this is a bulk-action or not.
     * @return array An array to format as JSON and return to the Javascript.
     */
    protected function _respond_to_js(array $elements, $bulkaction) {
        $prgid = required_param('id', PARAM_INT);
        $assocclass = 'programcrsset';
        $assocparams = ['main' => 'prgid', 'incoming' => 'crssetid'];
        $faillang = get_string('ds_action_crssetprg_bulkmaxexceeded', 'local_elisprogram');
        return $this->attempt_unassociate($prgid, $elements, $bulkaction, $assocclass, $assocparams, $faillang);
    }

    /**
     * Determine whether the current user can unassign the courseset from the course.
     * @param int $prgid The ID of the program.
     * @param int $crssetid The ID of the courseset.
     * @return bool Whether the current can unassign (true) or not (false)
     */
    protected function can_manage_assoc($prgid, $crssetid) {
        global $USER;

        $crssetctx = \local_elisprogram\context\courseset::instance($crssetid);
        $candelactive = has_capability('local/elisprogram:courseset_delete_active', $crssetctx);
        if (!$candelactive) {
            $filters = array();
            $filters[] = new field_filter('crssetid', $crssetid);
            $filters[] = new field_filter('prgid', $prgid);
            $prgcrssets = programcrsset::find(new AND_filter($filters));
            if ($prgcrssets && $prgcrssets->valid()) {
                $prgcrsset = $prgcrssets->current();
                if ($prgcrsset && $prgcrsset->is_active()) {
                    return false;
                }
            }
        }

        $perm = 'local/elisprogram:associate';
        $crssetassocctx = pm_context_set::for_user_with_capability('courseset', $perm, $USER->id);
        $crssetassociateallowed = ($crssetassocctx->context_allowed($crssetid, 'courseset') === true) ? true : false;
        $programassocctx = pm_context_set::for_user_with_capability('curriculum', $perm, $USER->id);
        $programassociateallowed = ($programassocctx->context_allowed($prgid, 'curriculum') === true) ? true : false;
        return ($crssetassociateallowed === true && $programassociateallowed === true) ? true : false;
    }
}
