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

require_once(dirname(__FILE__).'/../../eliscore/test_config.php');
global $CFG;
require_once($CFG->dirroot.'/local/elisprogram/lib/setup.php');

// Data objects.
require_once(elispm::lib('data/courseset.class.php'));
require_once(elispm::lib('data/programcrsset.class.php'));
require_once(elispm::lib('data/crssetcourse.class.php'));
require_once(elispm::lib('data/curriculumcourse.class.php'));
require_once(elispm::lib('data/user.class.php'));

/**
 * Test courseset data object.
 * @group local_elisprogram
 */
class courseset_testcase extends elis_database_test {

    /**
     * Courseset creation data provider
     */
    public function courseset_create_dataprovider() {
        return array(
                array(array( // valid
                    'idnumber' => '12345678',
                    'name' => 'Courseset Name',
                    'description' => 'Courseset Description',
                    'priority' => '1'), true
                ),
                array(array( // invalid - empty idnumber
                    'idnumber' => '',
                    'name' => 'Courseset Name',
                    'description' => 'Courseset Description',
                    'priority' => '1'), false
                ),
                array(array( // invalid - idnumber over max. length
                    'idnumber' => '123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890',
                    'name' => 'Courseset Name',
                    'description' => 'Courseset Description',
                    'priority' => '1'), false
                ),
        );
    }

    /**
     * Test courseset creation
     * @param array $inputdata the input data for courseset creation
     * @param bool $expected true if courseset should exist, false otherwise
     * @dataProvider courseset_create_dataprovider
     */
    public function test_courseset_create($inputdata, $expected) {
        $courseset = new courseset($inputdata);
        $exists = !empty($courseset);
        if ($exists) {
            try {
                $courseset->save();
            } catch (Exception $e) {
                $exists = false;
            }
        }
        $this->assertEquals($expected, $exists);
    }

    /**
     * Test courseset cannot have duplicate idnumber
     */
    public function test_courseset_duplicate_idnumber() {
        $crsset1 = array(
            'idnumber' => '123456789',
            'name' => 'Courseset1 Name',
            'description' => 'Courseset1 Description',
            'priority' => '1'
        );
        $crsset2 = array(
            'idnumber' => '123456789',
            'name' => 'Courseset2 Name',
            'description' => 'Courseset2 Description',
            'priority' => '2'
        );
        $courseset1 = new courseset($crsset1);
        $courseset1->save();
        $courseset2 = new courseset($crsset2);
        $failed = false;
        try {
            $courseset2->save();
        } catch (Exception $e) {
            $failed = true;
        }
        $this->assertTrue($failed);
    }

    /**
     * Test program-courseset associations
     */
    public function test_program_courseset_associations() {
        require_once(elispm::lib('data/programcrsset.class.php'));
        // Course Description
        $cddata = array(
            'name' => 'Course Description 1',
            'code' => 'CD1',
            'idnumber' => 'CD1',
            'syllabus' => 'CD1:Syllabus',
            'credits' => 2.0,
            'completion_grade' => 55
        );
        $cd = new course($cddata);
        $cd->save();

        // CourseSet
        $crsset1 = array(
            'idnumber' => '123456789',
            'name' => 'Courseset1 Name',
            'description' => 'Courseset1 Description',
            'priority' => '1'
        );
        $courseset = new courseset($crsset1);
        $courseset->save();

        $crssetcrs = new crssetcourse(array('courseid' => $cd->id, 'crssetid' => $courseset->id));
        $crssetcrs->save();

        // Program
        $program1 = array(
            'idnumber' => '123456789',
            'name' => 'Program1 Name',
            'description' => 'Program1 Description',
            'priority' => '1',
            'reqcredits' => '2.5'
        );
        $program = new curriculum($program1);
        $program->save();

        $prgcrssetdata = array(
            'reqcredits' => 2.0,
            'reqcourses' => 1,
            'andor' => 1,
            'prgid' => $program->id,
            'crssetid' => $courseset->id
        );
        $prgcrsset = new programcrsset($prgcrssetdata);
        $prgcrsset->save();
        $this->assertNotEmpty($prgcrsset->id);

        // Attempt duplicate - should fail validation
        $prgcrsset2 = new programcrsset($prgcrssetdata);
        $failed = false;
        try {
            $prgcrsset2->save();
        } catch (data_object_validation_exception $e) {
            $failed = true;
        }
        $this->assertTrue($failed);

        // Verify cannot safely remove only course from courseset
        $this->assertEquals(crssetcourse::CAN_SAFELY_DELETE_ERROR_COURSES, $crssetcrs->can_safely_delete());

        // Attempt requiring more courses than in courseset
        $prgcrssetdata = array(
            'reqcredits' => 2.0,
            'reqcourses' => 2,
            'andor' => 1,
            'prgid' => $program->id,
            'crssetid' => $courseset->id
        );
        $prgcrsset3 = new programcrsset($prgcrssetdata);
        $failed = false;
        try {
            $prgcrsset3->save();
        } catch (data_object_validation_exception $e) {
            $failed = true;
        }
        $this->assertTrue($failed);

        // Attempt requiring more credits than in courseset
        $prgcrssetdata = array(
            'reqcredits' => 2.5,
            'reqcourses' => 1,
            'andor' => 1,
            'prgid' => $program->id,
            'crssetid' => $courseset->id
        );
        $prgcrsset4 = new programcrsset($prgcrssetdata);
        $failed = false;
        try {
            $prgcrsset4->save();
        } catch (data_object_validation_exception $e) {
            $failed = true;
        }
        $this->assertTrue($failed);

        // 2nd Course Description
        $cd2data = array(
            'name' => 'Course Description 2',
            'code' => 'CD2',
            'idnumber' => 'CD2',
            'syllabus' => 'CD2:Syllabus',
            'credits' => 1.1,
            'completion_grade' => 55
        );
        $cd2 = new course($cd2data);
        $cd2->save();
        // Add 2nd course to courseset
        $crssetcrs2 = new crssetcourse(array('courseid' => $cd2->id, 'crssetid' => $courseset->id));
        $crssetcrs2->save();

        $this->assertEquals(2, $courseset->total_courses());
        $this->assertEquals(3.1, $courseset->total_credits());

        // Verify cannot safely remove 1st course from courseset
        $this->assertEquals(crssetcourse::CAN_SAFELY_DELETE_ERROR_CREDITS, $crssetcrs->can_safely_delete());

        // Enrol an ELIS user into Class Instance of 2nd course
        $elisuser = new user(array(
            'idnumber' => 'testuserid',
            'username' => 'testuser',
            'firstname' => 'testuser',
            'lastname' => 'testuser',
            'email' => 'testuser@testuserdomain.com',
            'country' => 'CA'
        ));
        $elisuser->save();

        $pmclass = new pmclass(array('idnumber' => 'CI222'));
        $pmclass->auto_create_class(array('courseid' => $cd2->id));
        $student = new student(array('userid' => $elisuser->id, 'classid' => $pmclass->id));
        $student->save();

        // Verify can safely remove 2nd course from courseset
        $this->assertEquals(crssetcourse::CAN_SAFELY_DELETE_SUCCESS, $crssetcrs2->can_safely_delete());

        // Enrol elisuser into program
        $prgstu = new curriculumstudent(array('userid' => $elisuser->id, 'curriculumid' => $program->id));
        $prgstu->save();

        // Verify cannot safely remove 2nd course from courseset
        $this->assertEquals(crssetcourse::CAN_SAFELY_DELETE_ERROR_ACTIVE, $crssetcrs2->can_safely_delete());

        // Enrol user in other Class Instance of 1st course
        $pmclass = new pmclass(array('idnumber' => 'CI1A'));
        $pmclass->auto_create_class(array('courseid' => $cd->id));
        $student2 = new student(array('userid' => $elisuser->id, 'classid' => $pmclass->id));
        $student2->save();
        $this->assertFalse($prgcrsset->is_complete($elisuser->id));

        // Complete both classes for ELIS user
        $student->complete(STUSTATUS_PASSED, time(), 60.2, 1.0, true);
        $student2->complete(STUSTATUS_PASSED, time(), 55.1, 2.0, true);
        $this->assertTrue($prgcrsset->is_complete($elisuser->id));

        // Enrol user in a non-Program course
        $cd3data = array(
            'name' => 'Course Description 3',
            'code' => 'CD3',
            'idnumber' => 'CD3',
            'syllabus' => 'CD3:Syllabus',
            'credits' => 1.5,
            'completion_grade' => 65
        );
        $cd3 = new course($cd3data);
        $cd3->save();
        $pmclass = new pmclass(array('idnumber' => 'CI3'));
        $pmclass->auto_create_class(array('courseid' => $cd3->id));
        $student = new student(array('userid' => $elisuser->id, 'classid' => $pmclass->id));
        $student->save();

        $this->assertEquals(0, curriculumcourse_count_records($program->id));
        $this->assertEquals(2, curriculumcourse_count_records($program->id, '', '', array('coursesets' => true)));
        $cnt = -1;
        $rs = user::get_non_curriculum_classes($program->id, $cnt);
        $this->assertEquals(1, $cnt);

        // Mark one program class as incomplete
        $student2->completestatusid = STUSTATUS_NOTCOMPLETE;
        $student2->locked = false;
        $student2->save();
        $rs = user::get_current_classes_in_curriculum($elisuser->id, $program->id);
        $cnt = 0;
        if ($rs && $rs->valid()) {
            foreach ($rs as $el) {
                $cnt++;
            }
        }
        $this->assertEquals(1, $cnt);
    }
}
