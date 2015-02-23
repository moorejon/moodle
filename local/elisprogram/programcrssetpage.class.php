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
 * @copyright  (C) 2008-2014 Remote-Learner.net Inc (http://www.remote-learner.net)
 *
 */

defined('MOODLE_INTERNAL') or die();

require_once(elispm::lib('associationpage.class.php'));
require_once(elispm::lib('data/programcrsset.class.php'));
require_once(elispm::file('coursesetpage.class.php'));
require_once(elispm::file('curriculumpage.class.php'));

/**
 * Deepsight assignment page for program-courseset associations.
 */
class programcrssetpage extends deepsightpage {
    /** @var string the page name */
    public $pagename = 'prgcrsset';

    /** @var string the page's section */
    public $section = 'curr';

    /** @var string the entity's main tab page */
    public $tab_page = 'curriculumpage';

    /** @var string the association data class */
    public $data_class = 'programcrsset';

    /** @var string the page's parent */
    public $parent_page;

    /**
     * @var string The context level of the parent association.
     */
    protected $contextlevel = 'curriculum';

    /**
     * Construct the assigned datatable.
     * @param string $uniqid A unique ID to assign to the datatable object.
     * @return deepsight_datatable The datatable object.
     */
    protected function construct_assigned_table($uniqid = null) {
        global $DB;
        $prgid = $this->required_param('id', PARAM_INT);
        $endpoint = qualified_me().'&action=deepsight_response&tabletype=assigned&id='.$prgid;
        $table = new deepsight_datatable_programcrsset_assigned($DB, 'assigned', $endpoint, $uniqid);
        $table->set_id($prgid);
        return $table;
    }

    /**
     * Construct the unassigned datatable.
     * @param string $uniqid A unique ID to assign to the datatable object.
     * @return deepsight_datatable The datatable object.
     */
    protected function construct_unassigned_table($uniqid = null) {
        global $DB;
        $prgid = $this->required_param('id', PARAM_INT);
        $endpoint = qualified_me().'&action=deepsight_response&tabletype=unassigned&id='.$prgid;
        $table = new deepsight_datatable_programcrsset_available($DB, 'unassigned', $endpoint, $uniqid);
        $table->set_id($prgid);
        return $table;
    }

    /**
     * Assignment permission is handled at the action-object level.
     * @return bool true
     */
    public function can_do_action_programcrsset_assign() {
        return true;
    }

    /**
     * Edit permission is handled at the action-object level.
     * @return bool true
     */
    public function can_do_action_programcrsset_edit() {
        return true;
    }

    /**
     * Unassignment permission is handled at the action-object level.
     * @return bool true if has permissions, false otherwise
     */
    public function can_do_action_programcrsset_unassign() {
        return true; // TBD: active?
    }

    /**
     * Whether the user has access to see the main page (assigned list)
     * @return bool Whether the user has access.
     */
    public function can_do_default() {
        global $USER;
        $id = $this->required_param('id', PARAM_INT);
        $requiredperms = array('local/elisprogram:program_view', 'local/elisprogram:associate');
        foreach ($requiredperms as $perm) {
            $ctx = pm_context_set::for_user_with_capability('curriculum', $perm, $USER->id);
            if ($ctx->context_allowed($id, 'curriculum') !== true) {
                return false;
            }
        }
        return true;
    }

    /**
     * Determine whether the current user can assign coursesets to the viewed program.
     * @return bool Whether the user can assign coursesets to this program.
     */
    public function can_do_add() {
        return $this->can_do_default();
    }
}

/**
 * Deepsight assignment page for courseset-program associations.
 */
class crssetprogrampage extends deepsightpage {
    /** @var string the page name */
    public $pagename = 'crssetprg';

    /** @var string the page's section */
    public $section = 'curr';

    /** @var string the entity's main tab page */
    public $tab_page = 'coursesetpage';

    /** @var string the association data class */
    public $data_class = 'programcrsset';

    /** @var string the page's parent */
    public $parent_page;

    /**
     * @var string The context level of the parent association.
     */
    protected $contextlevel = 'courseset';

    /**
     * Construct the assigned datatable.
     * @param string $uniqid A unique ID to assign to the datatable object.
     * @return deepsight_datatable The datatable object.
     */
    protected function construct_assigned_table($uniqid = null) {
        global $DB;
        $crssetid = $this->required_param('id', PARAM_INT);
        $endpoint = qualified_me().'&action=deepsight_response&tabletype=assigned&id='.$crssetid;
        $table = new deepsight_datatable_crssetprogram_assigned($DB, 'assigned', $endpoint, $uniqid);
        $table->set_id($crssetid);
        return $table;
    }

    /**
     * Construct the unassigned datatable.
     * @param string $uniqid A unique ID to assign to the datatable object.
     * @return deepsight_datatable The datatable object.
     */
    protected function construct_unassigned_table($uniqid = null) {
        global $DB;
        $crssetid = $this->required_param('id', PARAM_INT);
        $endpoint = qualified_me().'&action=deepsight_response&tabletype=unassigned&id='.$crssetid;
        $table = new deepsight_datatable_crssetprogram_available($DB, 'unassigned', $endpoint, $uniqid);
        $table->set_id($crssetid);
        return $table;
    }

    /**
     * Assignment permission is handled at the action-object level.
     * @return bool true
     */
    public function can_do_action_crssetprogram_assign() {
        return true;
    }

    /**
     * Edit permission is handled at the action-object level.
     * @return bool true
     */
    public function can_do_action_crssetprogram_edit() {
        return true;
    }

    /**
     * Unassignment permission is handled at the action-object level.
     * @return bool true if has permission, false otherwise
     */
    public function can_do_action_crssetprogram_unassign() {
        return true; // TBD: active?
    }

    /**
     * Whether the user has access to see the main page (assigned list)
     * @return bool Whether the user has access.
     */
    public function can_do_default() {
        global $USER;
        $id = $this->required_param('id', PARAM_INT);
        $requiredperms = array('local/elisprogram:courseset_view', 'local/elisprogram:associate');
        foreach ($requiredperms as $perm) {
            $ctx = pm_context_set::for_user_with_capability('courseset', $perm, $USER->id);
            if ($ctx->context_allowed($id, 'courseset') !== true) {
                return false;
            }
        }
        return true;
    }

    /**
     * Determine whether the current user can assign programs to the viewed courseset.
     * @return bool Whether the user can assign programs to this courseset.
     */
    public function can_do_add() {
        return $this->can_do_default();
    }
}
