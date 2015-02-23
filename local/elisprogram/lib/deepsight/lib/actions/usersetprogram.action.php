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
 * Trait containing shared methods.
 */
trait deepsight_action_usersetprogram {
    /**
     * Determine whether the current user can manage an association.
     *
     * @param int $usersetid The ID of the main element. The is the ID of the 'one', in a 'many-to-one' association.
     * @param int $programid The ID of the incoming element. The is the ID of the 'many', in a 'many-to-one' association.
     * @return bool Whether the current can manage (true) or not (false)
     */
    protected function can_manage_assoc($usersetid, $programid) {
        global $USER;
        $perm = 'local/elisprogram:associate';
        $pgmassocctx = pm_context_set::for_user_with_capability('curriculum', $perm, $USER->id);
        $programassociateallowed = ($pgmassocctx->context_allowed($programid, 'curriculum') === true) ? true : false;
        $clstassocctx = pm_context_set::for_user_with_capability('cluster', $perm, $USER->id);
        $usersetassociateallowed = ($clstassocctx->context_allowed($usersetid, 'cluster') === true) ? true : false;
        return ($programassociateallowed === true && $usersetassociateallowed === true) ? true : false;
    }
}

/**
 * An action to assign programs to a userset and set the autoenrol flag.
 */
class deepsight_action_usersetprogram_assign extends deepsight_action_standard {
    use deepsight_action_usersetprogram;

    const TYPE = 'usersetprogram_assignedit';
    public $label = 'Assign';
    public $icon = 'elisicon-assoc';

    /**
     * Constructor.
     * @param moodle_database $DB The active database connection.
     * @param string $name The unique name of the action to use.
     * @param string $descsingle The description when the confirmation is for a single element.
     * @param string $descmultiple The description when the confirmation is for the bulk list.
     */
    public function __construct(moodle_database &$DB, $name, $descsingle='', $descmultiple='') {
        parent::__construct($DB, $name);
        $this->label = ucwords(get_string('assign', 'local_elisprogram'));

        $langelements = new stdClass;
        $langelements->baseelement = strtolower(get_string('cluster', 'local_elisprogram'));
        $langelements->actionelement = strtolower(get_string('curriculum', 'local_elisprogram'));
        $this->descsingle = (!empty($descsingle))
                ? $descsingle : get_string('ds_action_assign_confirm', 'local_elisprogram', $langelements);

        $langelements = new stdClass;
        $langelements->baseelement = strtolower(get_string('cluster', 'local_elisprogram'));
        $langelements->actionelement = strtolower(get_string('curricula', 'local_elisprogram'));
        $this->descmultiple = (!empty($descmultiple))
                ? $descmultiple : get_string('ds_action_assign_confirm_multi', 'local_elisprogram', $langelements);
    }

    /**
     * Provide options to the javascript.
     * @return array An array of options.
     */
    public function get_js_opts() {
        global $CFG;
        $usersetid = required_param('id', PARAM_INT);
        $usersetclassification = usersetclassification::get_for_cluster($usersetid);
        $autoenroldefault = (!empty($usersetclassification->param_autoenrol_curricula)) ? 1 : 0;
        $opts = parent::get_js_opts();
        $opts['condition'] = $this->condition;
        $opts['opts']['actionurl'] = $this->endpoint;
        $opts['opts']['autoenroldefault'] = $autoenroldefault;
        $opts['opts']['desc_single'] = $this->descsingle;
        $opts['opts']['desc_multiple'] = $this->descmultiple;
        $opts['opts']['mode'] = 'assign';
        $opts['opts']['lang_bulk_confirm'] = get_string('ds_bulk_confirm', 'local_elisprogram');
        $opts['opts']['lang_working'] = get_string('ds_working', 'local_elisprogram');
        $opts['opts']['langautoenrol'] = get_string('usersetprogramform_auto_enrol', 'local_elisprogram');
        $opts['opts']['langyes'] = get_string('yes', 'moodle');
        $opts['opts']['langno'] = get_string('no', 'moodle');
        return $opts;
    }

    /**
     * Assign programs to the userset.
     * @param array $elements An array of program information to assign to the userset.
     * @param bool $bulkaction Whether this is a bulk-action or not.
     * @return array An array to format as JSON and return to the Javascript.
     */
    protected function _respond_to_js(array $elements, $bulkaction) {
        global $DB;
        $usersetid = required_param('id', PARAM_INT);
        $autoenrol = optional_param('autoenrol', 0, PARAM_INT);
        $failedops = [];

        foreach ($elements as $programid => $label) {
            if ($this->can_manage_assoc($usersetid, $programid) === true) {
                try {
                    clustercurriculum::associate($usersetid, $programid, $autoenrol);
                } catch (\Exception $e) {
                    if ($bulkaction === true) {
                        $failedops[] = $programid;
                    } else {
                        throw $e;
                    }
                }
            } else {
                $failedops[] = $programid;
            }
        }

        if ($bulkaction === true && !empty($failedops)) {
             return [
                'result' => 'partialsuccess',
                'msg' => get_string('ds_action_generic_bulkfail', 'local_elisprogram'),
                'failedops' => $failedops,
            ];
        } else {
            return array('result' => 'success', 'msg' => 'Success');
        }
    }
}

/**
 * An action to edit the autoenrol flag on a clustercurriculum assignment.
 */
class deepsight_action_usersetprogram_edit extends deepsight_action_standard {
    use deepsight_action_usersetprogram;

    const TYPE = 'usersetprogram_assignedit';
    public $label = '';
    public $icon = 'elisicon-edit';

    /**
     * Sets the action's label from language string.
     */
    protected function postconstruct() {
        $this->label = get_string('ds_action_edit', 'local_elisprogram');
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
        $opts['opts']['desc_single'] = '';
        $opts['opts']['desc_multiple'] = '';
        $opts['opts']['mode'] = 'edit';
        $opts['opts']['lang_bulk_confirm'] = get_string('ds_bulk_confirm', 'local_elisprogram');
        $opts['opts']['lang_working'] = get_string('ds_working', 'local_elisprogram');
        $opts['opts']['langautoenrol'] = get_string('usersetprogramform_auto_enrol', 'local_elisprogram');
        $opts['opts']['langyes'] = get_string('yes', 'moodle');
        $opts['opts']['langno'] = get_string('no', 'moodle');
        return $opts;
    }

    /**
     * Edit clustercurriculum information.
     * @param array $elements An array of program information to assign to the userset.
     * @param bool $bulkaction Whether this is a bulk-action or not.
     * @return array An array to format as JSON and return to the Javascript.
     */
    protected function _respond_to_js(array $elements, $bulkaction) {
        global $DB;
        $usersetid = required_param('id', PARAM_INT);
        $autoenrol = required_param('autoenrol', PARAM_INT);
        $failedops = [];

        foreach ($elements as $programid => $label) {
            if ($this->can_manage_assoc($usersetid, $programid) === true) {
                $associationfilters = array('clusterid' => $usersetid, 'curriculumid' => $programid);
                $association = $DB->get_record(clustercurriculum::TABLE, $associationfilters);
                if (!empty($association)) {
                    try {
                        clustercurriculum::update_autoenrol($association->id, $autoenrol);
                    } catch (\Exception $e) {
                        if ($bulkaction === true) {
                            $failedops[] = $programid;
                        } else {
                            throw $e;
                        }
                    }
                } else {
                    $failedops[] = $programid;
                }
            } else {
                $failedops[] = $programid;
            }
        }

        if ($bulkaction === true && !empty($failedops)) {
             return [
                'result' => 'partialsuccess',
                'msg' => get_string('ds_action_generic_bulkfail', 'local_elisprogram'),
                'failedops' => $failedops,
            ];
        } else {
            return array('result' => 'success', 'msg' => 'Success');
        }
    }
}

/**
 * An action to unassign programs from a userset.
 */
class deepsight_action_usersetprogram_unassign extends deepsight_action_confirm {
    use deepsight_action_usersetprogram;

    public $label = 'Unassign';
    public $icon = 'elisicon-unassoc';

    /**
     * Constructor.
     * @param moodle_database $DB The active database connection.
     * @param string $name The unique name of the action to use.
     * @param string $descsingle The description when the confirmation is for a single element.
     * @param string $descmultiple The description when the confirmation is for the bulk list.
     */
    public function __construct(moodle_database &$DB, $name, $descsingle='', $descmultiple='') {
        parent::__construct($DB, $name);
        $this->label = ucwords(get_string('unassign', 'local_elisprogram'));

        $langelements = new stdClass;
        $langelements->baseelement = strtolower(get_string('cluster', 'local_elisprogram'));
        $langelements->actionelement = strtolower(get_string('curriculum', 'local_elisprogram'));
        $this->descsingle = (!empty($descsingle))
                ? $descsingle : get_string('ds_action_unassign_confirm', 'local_elisprogram', $langelements);

        $langelements = new stdClass;
        $langelements->baseelement = strtolower(get_string('cluster', 'local_elisprogram'));
        $langelements->actionelement = strtolower(get_string('curricula', 'local_elisprogram'));
        $this->descmultiple = (!empty($descmultiple))
                ? $descmultiple : get_string('ds_action_unassign_confirm_multi', 'local_elisprogram', $langelements);
    }

    /**
     * Unassign the programs from the userset.
     * @param array $elements An array of program information to unassign from the userset.
     * @param bool $bulkaction Whether this is a bulk-action or not.
     * @return array An array to format as JSON and return to the Javascript.
     */
    protected function _respond_to_js(array $elements, $bulkaction) {
        global $DB;
        $usersetid = required_param('id', PARAM_INT);
        $assocclass = 'clustercurriculum';
        $assocparams = ['main' => 'clusterid', 'incoming' => 'curriculumid'];
        return $this->attempt_unassociate($usersetid, $elements, $bulkaction, $assocclass, $assocparams);
    }
}