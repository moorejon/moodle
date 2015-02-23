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
 * @copyright  (C) 2014 Remote-Learner.net Inc (http://www.remote-learner.net)
 * @author     James McQuillan <james.mcquillan@remote-learner.net>
 *
 */

require_once(dirname(__FILE__).'/../../eliscore/test_config.php');
global $CFG;
require_once($CFG->dirroot.'/local/elisprogram/lib/setup.php');
require_once($CFG->dirroot.'/local/elisprogram/db/uninstall.php');


/**
 * Test ELIS uninstall.
 * @group local_elisprogram
 */
class uninstall_testcase extends elis_database_test {

    /**
     * Test custom context removal.
     */
    public function test_removecustomcontexts() {
        global $DB;

        // Set known start point.
        $customcontextclasses = $DB->get_record('config', array('name' => 'custom_context_classes'));
        $this->assertNotEquals(serialize(array()), $customcontextclasses->value);

        xmldb_local_elisprogram_uninstall_removecustomcontexts();

        $customcontextclasses = $DB->get_record('config', array('name' => 'custom_context_classes'));
        $this->assertEquals(serialize(array()), $customcontextclasses->value);
    }
}