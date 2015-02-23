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

require_once(elispm::file('form/cmform.class.php'));
require_once(elispm::lib('data/courseset.class.php'));
require_once(elispm::lib('lib.php'));

/**
 * The form class for coursesets
 */
class cmCoursesetForm extends cmform {
    /**
     * defines items in the form
     */
    public function definition() {
        if ($this->_customdata['obj']) {
            // FIXME: This is probably not be the right place for set_data - move it
            $this->set_data($this->_customdata['obj']);
        }

        $mform =& $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);

        $mform->addElement('text', 'idnumber', get_string('courseset_idnumber', 'local_elisprogram').':');
        $mform->setType('idnumber', PARAM_TEXT);
        $mform->addRule('idnumber', null, 'required', null, 'client');
        $mform->addRule('idnumber', null, 'maxlength', 100);
        $mform->addHelpButton('idnumber', 'coursesetform:courseset_idnumber', 'local_elisprogram');

        $mform->addElement('text', 'name', get_string('courseset_name', 'local_elisprogram').':');
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', null, 'maxlength', 64);
        $mform->addHelpButton('name', 'coursesetform:courseset_name', 'local_elisprogram');

        $attributes = array('rows' => '2', 'cols' => '40');
        $mform->addElement('textarea', 'description', get_string('description', 'local_elisprogram').':', $attributes);
        $mform->setType('description', PARAM_CLEAN);
        $mform->addHelpButton('description', 'coursesetform:courseset_description', 'local_elisprogram');

        $choices = range(0, 10);
        $mform->addElement('select', 'priority', get_string('priority', 'local_elisprogram').':', $choices);
        $mform->addHelpButton('priority', 'coursesetform:priority', 'local_elisprogram');

        // custom fields
        $this->add_custom_fields('courseset', 'local/elisprogram:courseset_edit', 'local/elisprogram:courseset_view');

        $this->add_action_buttons();
    }

    /**
     * Validation helper method to verify table field unique value
     *
     * @param string $table the DB table name.
     * @param string $field the field name.
     * @param mixed $value the value to test for uniqueness.
     * @param int $id the id of the current record.
     * @return bool true if value unqiue, false otherwise.
     */
    protected function check_unique($table, $field, $value, $id) {
        global $DB;
        return !$DB->record_exists_select($table, "$field = ? AND id <> ?", array($value, $id));
    }

    /**
     * Validation helper method to verify table field unique value
     *
     * @param array $data the form data
     * @param array $files any files in form data
     * @return array associate array of errors with key as form element
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (!empty($data['idnumber'])) {
            if (!$this->check_unique(courseset::TABLE, 'idnumber', $data['idnumber'], $data['id'])) {
                $errors['idnumber'] = get_string('badidnumber', 'local_elisprogram');
            }
        }

        $errors += parent::validate_custom_fields($data, 'courseset');

        return $errors;
    }
}
