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

require_once(__DIR__.'/../../../../eliscore/test_config.php');
global $CFG;

require_once($CFG->dirroot.'/local/elisprogram/lib/setup.php');

/**
 * Test \eliswidget_enrolment\widget.
 * @group eliswidget_enrolment
 */
class widget_testcase extends \elis_database_test {
    /**
     * Get ELIS data generator.
     *
     * @return \elis_program_datagenerator An ELIS data generator instance.
     */
    protected function getelisdatagenerator() {
        global $DB, $CFG;
        require_once(\elispm::file('tests/other/datagenerator.php'));
        return new \elis_program_datagenerator($DB);
    }

    /**
     * Test the get_program_data function.
     */
    public function test_get_program_data() {
        global $DB;
        $widget = new \eliswidget_enrolment\widget;

        $datagen = $this->getelisdatagenerator();

        // Create user.
        $mockuser1 = $datagen->create_user();
        $mockuser2 = $datagen->create_user();

        $program1 = $datagen->create_program();
        $datagen->assign_user_to_program($mockuser1->id, $program1->id);
        $program2 = $datagen->create_program();
        $datagen->assign_user_to_program($mockuser1->id, $program2->id);
        $program3 = $datagen->create_program();
        $datagen->assign_user_to_program($mockuser2->id, $program3->id);
        $program4 = $datagen->create_program();

        $programs = $widget->get_program_data($mockuser1->id, true);

        // Convert recordset to array.
        $programsar = [];
        foreach ($programs as $program) {
            $programsar[$program->pgmid] = $program;
        }

        // Validate.
        $this->assertTrue((isset($programsar[$program1->id])));
        $this->assertTrue((isset($programsar[$program2->id])));
        $this->assertEquals(2, count($programsar));
    }
}
