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
 * Test \eliswidget_enrolment\datatable\nonprogramcourse.
 * @group eliswidget_enrolment
 */
class datatable_nonprogramcourse_testcase extends \elis_database_test {
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

        // Create user.
        $mockuser = $datagen->create_user();

        // Course 1: SHOWN. User assigned, course not part of any program.
        $test1 = [];
        $test1['course'] = $datagen->create_course(['name' => 'Test1']);
        $test1['class'] = $datagen->create_pmclass(array('courseid' => $test1['course']->id));
        $test1['student'] = $datagen->assign_user_to_class($mockuser->id, $test1['class']->id);

        // Course 2: SHOWN. User assigned, course part of a program user is not assigned to.
        $test2 = [];
        $test2['course'] = $datagen->create_course(['name' => 'Test2']);
        $test2['class'] = $datagen->create_pmclass(array('courseid' => $test2['course']->id));
        $test2['student'] = $datagen->assign_user_to_class($mockuser->id, $test2['class']->id);
        $test2['program'] = $datagen->create_program();
        $test2['pgmcrs'] = $datagen->assign_course_to_program($test2['course']->id, $test2['program']->id);

        // Course 3: NOT SHOWN. No classes.
        $test3['course'] = $datagen->create_course(['name' => 'Test3']);

        // Course 4: NOT SHOWN. User not assigned.
        $test4['course'] = $datagen->create_course(['name' => 'Test4']);
        $test4['class'] = $datagen->create_pmclass(array('courseid' => $test4['course']->id));

        // Course 5: NOT SHOWN. User assigned, course part of assigned program.
        $test5 = [];
        $test5['course'] = $datagen->create_course(['name' => 'Test5']);
        $test5['class'] = $datagen->create_pmclass(array('courseid' => $test5['course']->id));
        $test5['student'] = $datagen->assign_user_to_class($mockuser->id, $test5['class']->id);
        $test5['program'] = $datagen->create_program();
        $test5['pgmcrs'] = $datagen->assign_course_to_program($test5['course']->id, $test5['program']->id);
        $test5['pgmstu'] = $datagen->assign_user_to_program($mockuser->id, $test5['program']->id);

        // Course 6: SHOWN. User assigned, course is part of a courseset that is part of program that the user is not assigned to.
        $test6 = [];
        $test6['course'] = $datagen->create_course(['name' => 'Test6']);
        $test6['class'] = $datagen->create_pmclass(array('courseid' => $test6['course']->id));
        $test6['student'] = $datagen->assign_user_to_class($mockuser->id, $test6['class']->id);
        $test6['program'] = $datagen->create_program();
        $test6['crsset'] = $datagen->create_courseset();
        $test6['crssetcrs'] = $datagen->assign_course_to_courseset($test6['course']->id, $test6['crsset']->id);
        $test6['pgmcrsset'] = $datagen->assign_courseset_to_program($test6['crsset']->id, $test6['program']->id);

        // Course 7: NOT SHOWN. User assigned, course part of courseset that is part program that user is assigned to.
        $test7 = [];
        $test7['course'] = $datagen->create_course(['name' => 'Test7']);
        $test7['class'] = $datagen->create_pmclass(array('courseid' => $test7['course']->id));
        $test7['student'] = $datagen->assign_user_to_class($mockuser->id, $test7['class']->id);
        $test7['program'] = $datagen->create_program();
        $test7['crsset'] = $datagen->create_courseset();
        $test7['crssetcrs'] = $datagen->assign_course_to_courseset($test7['course']->id, $test7['crsset']->id);
        $test7['pgmcrsset'] = $datagen->assign_courseset_to_program($test7['crsset']->id, $test7['program']->id);
        $test7['pgmstu'] = $datagen->assign_user_to_program($mockuser->id, $test7['program']->id);

        // Run method.
        $datatable = new \eliswidget_enrolment\datatable\nonprogramcourse($DB, 'http://example.com');
        $datatable->set_userid($mockuser->id);
        list($courses, $totalresults) = $datatable->get_search_results();

        // Convert recordset to array.
        $coursesar = [];
        foreach ($courses as $course) {
            $coursesar[$course->id] = $course;
        }

        // Validate.
        $this->assertTrue((isset($coursesar[$test1['course']->id])));
        $this->assertTrue((isset($coursesar[$test2['course']->id])));
        $this->assertFalse((isset($coursesar[$test3['course']->id])));
        $this->assertFalse((isset($coursesar[$test4['course']->id])));
        $this->assertFalse((isset($coursesar[$test5['course']->id])));
        $this->assertTrue((isset($coursesar[$test6['course']->id])));
        $this->assertFalse((isset($coursesar[$test7['course']->id])));
        $this->assertEquals(3, count($coursesar));
    }
}
