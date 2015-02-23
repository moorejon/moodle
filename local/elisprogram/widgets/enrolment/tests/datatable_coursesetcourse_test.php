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
 * Test \eliswidget_enrolment\datatable\coursesetcourse.
 * @group eliswidget_enrolment
 */
class datatable_coursesetcourse_testcase extends \elis_database_test {
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
     * Test get_search_results function.
     */
    public function test_get_search_results() {
        global $DB;

        $datagen = $this->getelisdatagenerator();

        // Create entities.
        $mockuser = $datagen->create_user();

        $courseseta = $datagen->create_courseset();
        $coursesetb = $datagen->create_courseset();
        $pmcoursea1 = $datagen->create_course();
        $pmcoursea2 = $datagen->create_course();
        $pmcourseb1 = $datagen->create_course();
        $pmcourseb2 = $datagen->create_course();
        $datagen->assign_course_to_courseset($pmcoursea1->id, $courseseta->id);
        $datagen->assign_course_to_courseset($pmcoursea2->id, $courseseta->id);
        $datagen->assign_course_to_courseset($pmcourseb1->id, $coursesetb->id);
        $datagen->assign_course_to_courseset($pmcourseb2->id, $coursesetb->id);

        // Run method.
        $datatable = new \eliswidget_enrolment\datatable\coursesetcourse($DB, 'http://example.com');
        $datatable->set_coursesetid($courseseta->id);
        list($courses, $totalresults) = $datatable->get_search_results();

        // Convert recordset to array.
        $coursesar = [];
        foreach ($courses as $course) {
            $coursesar[$course->id] = $course;
        }

        // Validate.
        $this->assertTrue((isset($coursesar[$pmcoursea1->id])));
        $this->assertTrue((isset($coursesar[$pmcoursea2->id])));
        $this->assertEquals(2, count($coursesar));
    }
}
