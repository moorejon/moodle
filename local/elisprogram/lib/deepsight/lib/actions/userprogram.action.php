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

require_once(elispm::lib('data/clusterassignment.class.php'));

/**
 * Trait containing shared methods.
 */
trait deepsight_action_userprogram {
    /**
     * Determine whether the current user can manage an association.
     *
     * @param int $userid The ID of the main element. The is the ID of the 'one', in a 'many-to-one' association.
     * @param int $programid The ID of the incoming element. The is the ID of the 'many', in a 'many-to-one' association.
     * @return bool Whether the current can manage (true) or not (false)
     */
    protected function can_manage_assoc($userid, $programid) {
        return curriculumstudent::can_manage_assoc($userid, $programid);
    }
}

/**
 * An action to assign a program to a user.
 */
class deepsight_action_userprogram_assign extends deepsight_action_confirm {
    use deepsight_action_userprogram;

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

        $langelements = new stdClass;
        $langelements->baseelement = strtolower(get_string('user', 'local_elisprogram'));
        $langelements->actionelement = strtolower(get_string('curriculum', 'local_elisprogram'));
        $this->descsingle = (!empty($descsingle))
                ? $descsingle : get_string('ds_action_assign_confirm', 'local_elisprogram', $langelements);

        $langelements = new stdClass;
        $langelements->baseelement = strtolower(get_string('user', 'local_elisprogram'));
        $langelements->actionelement = strtolower(get_string('curricula', 'local_elisprogram'));
        $this->descmultiple = (!empty($descmultiple))
                ? $descmultiple : get_string('ds_action_assign_confirm_multi', 'local_elisprogram', $langelements);
    }

    /**
     * Assign the program to the user.
     * @param array $elements An array of elements to perform the action on.
     * @param bool $bulkaction Whether this is a bulk-action or not.
     * @return array An array to format as JSON and return to the Javascript.
     */
    protected function _respond_to_js(array $elements, $bulkaction) {
        global $DB;
        $userid = required_param('id', PARAM_INT);

        // Permissions.
        $upage = new userpage();
        if ($upage->_has_capability('local/elisprogram:user_view', $userid) !== true) {
            return array('result' => 'fail', 'msg' => get_string('not_permitted', 'local_elisprogram'));
        }

        $assocclass = 'curriculumstudent';
        $assocparams = ['main' => 'userid', 'incoming' => 'curriculumid'];
        $assocfields = [];
        $assocdata = [];
        return $this->attempt_associate($userid, $elements, $bulkaction, $assocclass, $assocparams, $assocfields, $assocdata);
    }
}

/**
 * An action to unassign a program from a user.
 */
class deepsight_action_userprogram_unassign extends deepsight_action_confirm {
    use deepsight_action_userprogram;

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
        $langelements->baseelement = strtolower(get_string('user', 'local_elisprogram'));
        $langelements->actionelement = strtolower(get_string('curriculum', 'local_elisprogram'));
        $this->descsingle = (!empty($descsingle))
                ? $descsingle : get_string('ds_action_unassign_confirm', 'local_elisprogram', $langelements);

        $langelements = new stdClass;
        $langelements->baseelement = strtolower(get_string('user', 'local_elisprogram'));
        $langelements->actionelement = strtolower(get_string('curricula', 'local_elisprogram'));
        $this->descmultiple = (!empty($descmultiple))
                ? $descmultiple : get_string('ds_action_unassign_confirm_multi', 'local_elisprogram', $langelements);
    }

    /**
     * Unassign the program from the user.
     * @param array $elements An array of elements to perform the action on.
     * @param bool $bulkaction Whether this is a bulk-action or not.
     * @return array An array to format as JSON and return to the Javascript.
     */
    protected function _respond_to_js(array $elements, $bulkaction) {
        global $DB;
        $userid = required_param('id', PARAM_INT);

        // Permissions.
        $upage = new userpage();
        if ($upage->_has_capability('local/elisprogram:user_view', $userid) !== true) {
            return array('result' => 'fail', 'msg' => get_string('not_permitted', 'local_elisprogram'));
        }

        $assocclass = 'curriculumstudent';
        $assocparams = ['main' => 'userid', 'incoming' => 'curriculumid'];
        return $this->attempt_unassociate($userid, $elements, $bulkaction, $assocclass, $assocparams);
    }
}