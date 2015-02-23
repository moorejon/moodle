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

defined('MOODLE_INTERNAL') || die();

require_once(elispm::lib('data/curriculum.class.php'));
require_once(elispm::lib('data/courseset.class.php'));
require_once(elispm::lib('managementpage.class.php'));
require_once(elispm::lib('contexts.php'));
require_once(elispm::lib('datedelta.class.php'));
require_once(elispm::file('form/coursesetform.class.php'));
require_once(elispm::file('crssetcoursepage.class.php'));
require_once(elispm::file('rolepage.class.php'));

/**
 * CourseSet management page class
 */
class coursesetpage extends managementpage {
    /** @var string the page name */
    public $pagename = 'crsset';

    /** @var string the page's section */
    public $section = 'curr';

    /** @var string the courseset data class */
    public $data_class = 'courseset';

    /** @var string the courseset form class */
    public $form_class = 'cmCoursesetForm';

    /** @var array courseset view columns */
    public $view_columns = array('name', 'description');

    /** @var array page contexts */
    public static $contexts = array();

    /**
     * static method to get contexts
     * @param string $capability the capability to check
     * @return object the coursesetpage contexts for capability
     */
    public static function get_contexts($capability) {
        if (!isset(self::$contexts[$capability])) {
            global $USER;
            self::$contexts[$capability] = get_contexts_by_capability_for_user('courseset', $capability, $USER->id);
        }
        return self::$contexts[$capability];
    }

    /**
     * Check the cached capabilities for the current user.
     * @param string $capability the capability to check
     * @param int $id the courseset id
     * @return object courseset contexts user has capability
     */
    public static function check_cached($capability, $id) {
        if (isset(self::$contexts[$capability])) {
            // we've already cached which contexts the user has delete
            // capabilities in
            $contexts = self::$contexts[$capability];
            return $contexts->context_allowed($id, 'courseset');
        }
        return null;
    }

    /**
     * Check if the user has the given capability for the requested courseset
     * @param string $capability the capability to check
     * @param int $id the courseset id
     * @return bool true if user has capability on courseset context
     */
    public function _has_capability($capability, $id = null) {
        if (empty($id)) {
            $id = (isset($this) && method_exists($this, 'required_param'))
                  ? $this->required_param('id', PARAM_INT)
                  : required_param('id', PARAM_INT);
        }
        $cached = self::check_cached($capability, $id);
        if ($cached !== null) {
            return $cached;
        }
        $context = \local_elisprogram\context\courseset::instance($id);
        return has_capability($capability, $context);
    }

    /**
     * Method to get page params
     * @return array the page parameters
     */
    public function _get_page_params() {
        return parent::_get_page_params();
    }

    /**
     * Courseset constructor
     * @param array $params optional params to init page class.
     */
    public function __construct(array $params = null) {
        parent::__construct($params);

        $this->tabs = array(
        array('tab_id' => 'view', 'page' => get_class($this), 'params' => array('action' => 'view'), 'name' => get_string('detail', 'local_elisprogram'), 'showtab' => true),
        array('tab_id' => 'edit', 'page' => get_class($this), 'params' => array('action' => 'edit'), 'name' => get_string('edit', 'local_elisprogram'),
            'showtab' => true, 'showbutton' => true, 'image' => 'edit'),
        array('tab_id' => 'crssetprogrampage', 'page' => 'crssetprogrampage', 'name' => get_string('curricula', 'local_elisprogram'),
            'showtab' => true, 'showbutton' => true, 'image' => 'curriculum'),
        array('tab_id' => 'crssetcoursepage', 'page' => 'crssetcoursepage', 'name' => get_string('courses', 'local_elisprogram'),
            'showtab' => true, 'showbutton' => true, 'image' => 'course'),
        array('tab_id' => 'courseset_rolepage', 'page' => 'courseset_rolepage', 'name' => get_string('roles', 'role'), 'showtab' => true, 'showbutton' => false, 'image' => 'tag'),
        array('tab_id' => 'delete', 'page' => get_class($this), 'params' => array('action' => 'delete'), 'name' => get_string('delete', 'local_elisprogram'),
            'showbutton' => true, 'image' => 'delete'),
        );
    }

    /**
     * is_active method
     * @param int $courseid optionally specify course id to check, 0 means all
     * @param int $crssetid optionally specify courseset id to check, 0 means use page 'id' param
     * @return bool true if courseset has active enrolments, false otherwise
     */
    public function is_active($courseid = 0, $crssetid = 0) {
        $id = $crssetid ? $crssetid : $this->required_param('id', PARAM_INT);
        $isactive = false;
        $crsset = new courseset($id);
        foreach ($crsset->programs as $prgcrsset) {
            if ($prgcrsset->is_active($courseid)) {
                $isactive = true;
                break;
            }
        }
        return $isactive;
    }

    /**
     * can_do_view method
     * @return bool true if user has courseset_view capability on courseset
     */
    public function can_do_view() {
        return $this->_has_capability('local/elisprogram:courseset_view');
    }

    /**
     * can_do_edit method
     * @return bool true if user has appropriate courseset_edit or courseset_edit_active capability on courseset
     */
    public function can_do_edit() {
        return $this->_has_capability($this->is_active() ? 'local/elisprogram:courseset_edit_active' : 'local/elisprogram:courseset_edit');
    }

    /**
     * can_do_delete method
     * @return bool true if user has appropriate courseset_delete or courseset_delete_active capability on courseset
     */
    public function can_do_delete() {
        return $this->_has_capability($this->is_active() ? 'local/elisprogram:courseset_delete_active' : 'local/elisprogram:courseset_delete');
    }

    /**
     * can_do_confirm method
     * @return bool true if user has courseset_delete capability on courseset
     */
    public function can_do_confirm() {
        return $this->can_do_delete();
    }

    /**
     * can_do_add method
     * @return bool true if user has courseset_create capability at system level
     */
    public function can_do_add() {
        $context = context_system::instance();
        return has_capability('local/elisprogram:courseset_create', $context);
    }

    /**
     * can_do_default method
     * @return bool true if user has courseset_view capability on courseset
     */
    public function can_do_default() {
        $contexts = self::get_contexts('local/elisprogram:courseset_view');
        return !$contexts->is_empty();
    }

    /**
     * display_default method for main courseset listing
     */
    public function display_default() {
        // Get parameters
        $sort       = optional_param('sort', 'name', PARAM_ALPHA);
        $dir        = optional_param('dir', 'ASC', PARAM_ALPHA);
        $page       = optional_param('page', 0, PARAM_INT);
        $perpage    = optional_param('perpage', 30, PARAM_INT); // how many per page
        $namesearch = trim(optional_param('search', '', PARAM_TEXT));
        $alpha      = optional_param('alpha', '', PARAM_ALPHA);

        // Define columns
        $columns = array(
            'idnumber'    => array('header' => 'Idnumber'),
            'name'        => array('header' => get_string('courseset_name', 'local_elisprogram')),
            'description' => array('header' => get_string('description', 'local_elisprogram')),
            'programs'    => array('header' => get_string('curricula', 'local_elisprogram')),
            'courses'     => array('header' => get_string('courses', 'local_elisprogram')),
            'priority'    => array('header' => get_string('priority', 'local_elisprogram'))
        );

        if ($dir !== 'DESC') {
            $dir = 'ASC';
        }
        if (isset($columns[$sort])) {
            $columns[$sort]['sortable'] = $dir;
        }

        // Get list of coursesets
        $items = courseset_get_listing($sort, $dir, $page*$perpage, $perpage, $namesearch, $alpha, self::get_contexts('local/elisprogram:courseset_view'));
        $numitems = courseset_count_records($namesearch, $alpha, self::get_contexts('local/elisprogram:courseset_view'));

        self::get_contexts('local/elisprogram:courseset_edit');
        self::get_contexts('local/elisprogram:courseset_edit_active');
        self::get_contexts('local/elisprogram:courseset_delete');
        self::get_contexts('local/elisprogram:courseset_delete_active');

        $this->print_list_view($items, $numitems, $columns, null, true, true);
    }

    /**
     * Hook that gets called after a CM entity is added through this page
     * (Note: this function should only use the id field from the supplied cm entity
     *  as the rest of the data is not guaranteed to be there)
     *
     * @param object $cmentity The CM entity added
     * @uses $DB
     * @uses $USER
     */
    public function after_cm_entity_add($cmentity) {
    }

    /**
     * Specifies a unique shortname for the entity represented by
     * a page of this type, transforming the supplied value if necessary
     *
     * @param string $parent_path Path of all parent elements, or the empty string if none
     * @param string $name Initial name provided for the element
     *
     * @return string|null A valid name to identify the item with, or NULL if not applicable
     */
    public static function get_entity_name($parent_path, $name) {
        $parts = explode('_', $name);

        // Try to find the entity type and id, and combine them
        if (count($parts) == 2) {
            if ($parts[0] == 'courseset') {
                return $parts[0].'-'.$parts[1];
            }
        }

        return null;
    }

    /**
     * Prints a deletion confirmation form - overloads managementpage for active coursesets
     * @param $obj record whose deletion is being confirmed
     */
    public function print_delete_form($obj) {
        global $OUTPUT;

        $obj->load(); // force load, so that the confirmation notice has something to display
        $message = get_string(($this->is_active() ? 'confirm_delete_active_' : 'confirm_delete_').get_class($obj), 'local_elisprogram', $obj->to_object());

        $target_page = $this->get_new_page(array('action' => 'view', 'id' => $obj->id, 'sesskey' => sesskey()), true);
        $no_url = $target_page->url;
        $no = new single_button($no_url, get_string('no'), 'get');

        $optionsyes = array('action' => 'delete', 'id' => $obj->id, 'confirm' => 1);
        $yes_url = clone($no_url);
        $yes_url->params($optionsyes);
        $yes = new single_button($yes_url, get_string('yes'), 'get');

        echo $OUTPUT->confirm($message, $yes, $no);
    }
}
