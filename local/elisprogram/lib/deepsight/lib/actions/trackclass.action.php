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

require_once(elispm::lib('data/track.class.php'));

/**
 * Base class for assignment/editing
 */
abstract class deepsight_action_trackclass_assignedit extends deepsight_action_standard {

    /**
     * The javascript file to use (deepsight_action_trackclass_assignedit.js)
     */
    const TYPE = 'trackclass_assignedit';

    /**
     * @var string The mode to pass to javascript (assign/edit)
     */
    protected $mode = 'assign';

    /**
     * @var string The description to use for a single association.
     */
    protected $descsingle = '';

    /**
     * @var string The description to use for a bulk association.
     */
    protected $descmultiple = '';

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
        $opts['opts']['lang_bulk_confirm'] = get_string('ds_bulk_confirm', 'local_elisprogram');
        $opts['opts']['lang_working'] = get_string('ds_working', 'local_elisprogram');
        $opts['opts']['langautoenrol'] = get_string('trackassignmentform:track_autoenrol_long', 'local_elisprogram');
        $opts['opts']['langyes'] = get_string('yes', 'moodle');
        $opts['opts']['langno'] = get_string('no', 'moodle');
        return $opts;
    }

    /**
     * Determine whether the current user can unassign the class from the track.
     * @param int $trackid The ID of the track.
     * @param int $classid The ID of the class.
     * @return bool Whether the current can unassign (true) or not (false)
     */
    protected function can_manage_assoc($trackid, $classid) {
        global $USER;
        $perm = 'local/elisprogram:associate';
        $trkassocctx = pm_context_set::for_user_with_capability('track', $perm, $USER->id);
        $trackassociateallowed = ($trkassocctx->context_allowed($trackid, 'track') === true) ? true : false;
        $clsassocctx = pm_context_set::for_user_with_capability('class', $perm, $USER->id);
        $classassociateallowed = ($clsassocctx->context_allowed($classid, 'class') === true) ? true : false;
        return ($trackassociateallowed === true && $classassociateallowed === true) ? true : false;
    }
}

/**
 * An action to assign classes to a track and set the autoenrol flag.
 */
class deepsight_action_trackclass_assign extends deepsight_action_trackclass_assignedit {

    /**
     * @var string The label to use for the icon.
     */
    public $label = 'Assign';

    /**
     * @var string The icon class to use.
     */
    public $icon = 'elisicon-assoc';

    /**
     * @var string The mode to pass to javascript (assign/edit)
     */
    protected $mode = 'assign';

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
        $langelements->baseelement = strtolower(get_string('track', 'local_elisprogram'));
        $langelements->actionelement = strtolower(get_string('class', 'local_elisprogram'));
        $this->descsingle = (!empty($descsingle))
                ? $descsingle : get_string('ds_action_assign_confirm', 'local_elisprogram', $langelements);

        $langelements = new stdClass;
        $langelements->baseelement = strtolower(get_string('track', 'local_elisprogram'));
        $langelements->actionelement = strtolower(get_string('track_classes', 'local_elisprogram'));
        $this->descmultiple = (!empty($descmultiple))
                ? $descmultiple : get_string('ds_action_assign_confirm_multi', 'local_elisprogram', $langelements);
    }

    /**
     * Assign usersets to the track.
     * @param array $elements An array of userset information to assign to the track.
     * @param bool $bulkaction Whether this is a bulk-action or not.
     * @return array An array to format as JSON and return to the Javascript.
     */
    protected function _respond_to_js(array $elements, $bulkaction) {
        $trackid = required_param('id', PARAM_INT);
        $autoenrol = optional_param('autoenrol', 0, PARAM_BOOL);

        $assocclass = 'trackassignment';
        $assocparams = ['main' => 'trackid', 'incoming' => 'classid'];
        $assocfields = ['autoenrol'];
        $assocdata = ['autoenrol' => $autoenrol];
        return $this->attempt_associate($trackid, $elements, $bulkaction, $assocclass, $assocparams, $assocfields, $assocdata);
    }
}

/**
 * An action to edit trackassignment associations.
 */
class deepsight_action_trackclass_edit extends deepsight_action_trackclass_assignedit {

    /**
     * @var string The label to use for the icon.
     */
    public $label = 'Edit';

    /**
     * @var string The icon class to use.
     */
    public $icon = 'elisicon-edit';

    /**
     * @var string The mode to pass to javascript (assign/edit)
     */
    protected $mode = 'edit';

    /**
     * Sets the action's label from language string.
     */
    protected function postconstruct() {
        $this->label = ucwords(get_string('edit', 'local_elisprogram'));
    }

    /**
     * Assign usersets to the track.
     * @param array $elements An array of userset information to assign to the track.
     * @param bool $bulkaction Whether this is a bulk-action or not.
     * @return array An array to format as JSON and return to the Javascript.
     */
    protected function _respond_to_js(array $elements, $bulkaction) {
        global $DB;
        $trackid = required_param('id', PARAM_INT);
        $autoenrol = optional_param('autoenrol', 0, PARAM_BOOL);

        $assocclass = 'trackassignment';
        $assocparams = ['main' => 'trackid', 'incoming' => 'classid'];
        $assocfields = ['autoenrol'];
        $assocdata = ['autoenrol' => $autoenrol];
        return $this->attempt_edit($trackid, $elements, $bulkaction, $assocclass, $assocparams, $assocfields, $assocdata);
    }
}

/**
 * An action to unassign classes from a track.
 */
class deepsight_action_trackclass_unassign extends deepsight_action_confirm {

    /**
     * @var string The label to use for the icon.
     */
    public $label = 'Unassign';

    /**
     * @var string The icon class to use.
     */
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
        $langelements->baseelement = strtolower(get_string('track', 'local_elisprogram'));
        $langelements->actionelement = strtolower(get_string('class', 'local_elisprogram'));
        $this->descsingle = (!empty($descsingle))
                ? $descsingle : get_string('ds_action_unassign_confirm', 'local_elisprogram', $langelements);

        $langelements = new stdClass;
        $langelements->baseelement = strtolower(get_string('track', 'local_elisprogram'));
        $langelements->actionelement = strtolower(get_string('track_classes', 'local_elisprogram'));
        $this->descmultiple = (!empty($descmultiple))
                ? $descmultiple : get_string('ds_action_unassign_confirm_multi', 'local_elisprogram', $langelements);
    }

    /**
     * Unassign the usersets from the track.
     * @param array $elements An array of userset information to unassign from the track.
     * @param bool $bulkaction Whether this is a bulk-action or not.`
     * @return array An array to format as JSON and return to the Javascript.
     */
    protected function _respond_to_js(array $elements, $bulkaction) {
        global $DB;
        $trackid = required_param('id', PARAM_INT);
        $assocclass = 'trackassignment';
        $assocparams = ['main' => 'trackid', 'incoming' => 'classid'];
        return $this->attempt_unassociate($trackid, $elements, $bulkaction, $assocclass, $assocparams);
    }

    /**
     * Determine whether the current user can unassign the class from the track.
     * @param int $trackid The ID of the track.
     * @param int $classid The ID of the class.
     * @return bool Whether the current can unassign (true) or not (false)
     */
    protected function can_manage_assoc($trackid, $classid) {
        global $USER;
        $perm = 'local/elisprogram:associate';
        $trkassocctx = pm_context_set::for_user_with_capability('track', $perm, $USER->id);
        $trackassociateallowed = ($trkassocctx->context_allowed($trackid, 'track') === true) ? true : false;
        $clsassocctx = pm_context_set::for_user_with_capability('class', $perm, $USER->id);
        $classassociateallowed = ($clsassocctx->context_allowed($classid, 'class') === true) ? true : false;
        return ($trackassociateallowed === true && $classassociateallowed === true) ? true : false;
    }
}