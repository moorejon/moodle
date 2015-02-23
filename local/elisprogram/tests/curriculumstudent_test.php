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
require_once(elispm::lib('data/curriculumstudent.class.php'));
require_once(elispm::lib('data/curriculum.class.php'));
require_once(elispm::lib('data/user.class.php'));

/**
 * Test curriculumstudent data object.
 * @group local_elisprogram
 */
class curriculumstudent_testcase extends elis_database_test {

    /**
     * Load initial data from CSVs.
     */
    protected function load_csv_data() {
        $dataset = $this->createCsvDataSet(array(
            curriculumstudent::TABLE => elispm::file('tests/fixtures/curriculum_student.csv')
        ));
        $this->loadDataSet($dataset);
    }

    /**
     * Test validation of duplicates.
     * @expectedException data_object_validation_exception
     */
    public function test_curriculumstudent_validationpreventsduplicates() {
        $this->load_csv_data();
        $curriculumstudent = new curriculumstudent(array('curriculumid' => 1, 'userid' => 1));
        $curriculumstudent->save();
    }

    /**
     * Test complete function.
     */
    public function test_complete() {
        $dataset = $this->createCsvDataSet(array(
            user::TABLE => elispm::file('tests/fixtures/pmuser.csv'),
            curriculum::TABLE => elispm::file('tests/fixtures/curriculum.csv'),
            curriculumstudent::TABLE => elispm::file('tests/fixtures/curriculum_student.csv'),
        ));
        $this->loadDataSet($dataset);

        $cs = new curriculumstudent(2);
        $cs->load();
        $cs->complete(time(), 5);

        // Verify.
        $completed = curriculumstudent::get_completed_for_user(103);
        $count = 0;
        foreach ($completed as $cstu) {
            $this->assertTrue(($cstu instanceof curriculumstudent));
            $this->assertEquals(103, $cstu->userid);
            $count++;
        }
        $this->assertEquals(1, $count);
    }

    /**
     * Test check_for_completed_nags function with completion time in the past.
     */
    public function test_checkforcompletednagsdate() {
        global $DB;
        $dataset = $this->createCsvDataSet(array(
            user::TABLE => elispm::file('tests/fixtures/pmuser.csv'),
            curriculum::TABLE => elispm::file('tests/fixtures/curriculum.csv'),
            curriculumstudent::TABLE => elispm::file('tests/fixtures/curriculum_student.csv'),
            course::TABLE => elispm::file('tests/fixtures/pmcourse.csv'),
            curriculumcourse::TABLE => elispm::file('tests/fixtures/curriculum_course.csv'),
            pmclass::TABLE => elispm::file('tests/fixtures/pmclass.csv'),
            student::TABLE => elispm::file('tests/fixtures/student.csv'),
        ));
        $this->loadDataSet($dataset);

        // Set the course to be required in the program.
        $sql = "UPDATE {".curriculumcourse::TABLE."} SET required = 1 WHERE curriculumid = 1 AND courseid = 100";
        $DB->execute($sql);

        // Set the completion time to a month ago and status to completed on the class enrolment.
        $completetime = time() - 2592000;
        $sql = 'UPDATE {'.student::TABLE.'} SET completetime = '.$completetime.', completestatusid = 2 WHERE userid = 103 AND classid = 100';
        $DB->execute($sql);

        // Execute check_for_completed_nags.
        $curriculum = new curriculum(1);
        $curriculum->load();
        $result = $curriculum->check_for_completed_nags();

        // Verify completion time in program assignment table.
        $recordset = curriculumstudent::get_curricula(103);
        foreach ($recordset as $record) {
            $this->assertEquals(1, $record->curid);
            $this->assertEquals($completetime, $record->timecompleted);
        }
    }

    public function dataprovider_get_percent_complete() {
        return [
                [
                        'Required credits only.',
                        [
                            'reqcredits' => 10,
                            'courses' => [
                                [
                                    'required' => 0,
                                    'completestatusid' => STUSTATUS_PASSED,
                                    'credits' => 2,
                                ],
                            ],
                        ],
                        20,
                ],
                [
                        'Single required course (passed).',
                        [
                            'reqcredits' => 0,
                            'courses' => [
                                [
                                    'required' => 1,
                                    'completestatusid' => STUSTATUS_PASSED,
                                    'credits' => 2,
                                ],
                            ],
                        ],
                        100,
                ],
                [
                        'Single required course (failed).',
                        [
                            'reqcredits' => 0,
                            'courses' => [
                                [
                                    'required' => 1,
                                    'completestatusid' => STUSTATUS_FAILED,
                                    'credits' => 2,
                                ],
                            ],
                        ],
                        0,
                ],
                [
                        'Single required course (not complete).',
                        [
                            'reqcredits' => 0,
                            'courses' => [
                                [
                                    'required' => 1,
                                    'completestatusid' => STUSTATUS_NOTCOMPLETE,
                                    'credits' => 2,
                                ],
                            ],
                        ],
                        0,
                ],
                [
                        'Multiple passed required courses.',
                        [
                            'reqcredits' => 0,
                            'courses' => [
                                [
                                    'required' => 1,
                                    'completestatusid' => STUSTATUS_PASSED,
                                    'credits' => 2,
                                ],
                                [
                                    'required' => 1,
                                    'completestatusid' => STUSTATUS_PASSED,
                                    'credits' => 2,
                                ],
                                [
                                    'required' => 1,
                                    'completestatusid' => STUSTATUS_NOTCOMPLETE,
                                    'credits' => 2,
                                ],
                            ],
                        ],
                        67,
                ],
                [
                        'Multiple required courses with different states.',
                        [
                            'reqcredits' => 0,
                            'courses' => [
                                [
                                    'required' => 1,
                                    'completestatusid' => STUSTATUS_PASSED,
                                    'credits' => 2,
                                ],
                                [
                                    'required' => 1,
                                    'completestatusid' => STUSTATUS_FAILED,
                                    'credits' => 2,
                                ],
                                [
                                    'required' => 1,
                                    'completestatusid' => STUSTATUS_NOTCOMPLETE,
                                    'credits' => 2,
                                ],
                            ],
                        ],
                        33,
                ],
                [
                        'Single Courseset',
                        [
                            'reqcredits' => 0,
                            'coursesets' => [
                                [
                                    'reqcredits' => 10,
                                    'reqcourses' => 2,
                                    'courses' => [
                                        [
                                            'completestatusid' => STUSTATUS_PASSED,
                                            'credits' => 5,
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        50,
                ],
        ];
    }

    /**
     * @dataProvider dataprovider_get_percent_complete
     */
    public function test_get_percent_complete($testdesc, $setupdata, $expectedpercentcomplete) {
        global $DB;
        require_once(\elispm::file('tests/other/datagenerator.php'));
        $datagen = new \elis_program_datagenerator($DB);

        // Create user.
        $mockuser = $datagen->create_user();

        $program = $datagen->create_program(['reqcredits' => $setupdata['reqcredits']]);
        $pgmstu = $datagen->assign_user_to_program($mockuser->id, $program->id);

        if (!empty($setupdata['courses'])) {
            foreach ($setupdata['courses'] as $coursesetupdata) {
                $course = $datagen->create_course();
                $pgmcrs = $datagen->assign_course_to_program($course->id, $program->id, ['required' => $coursesetupdata['required']]);
                $pmclass = $datagen->create_pmclass(array('courseid' => $course->id));
                $studentparams = ['completestatusid' => $coursesetupdata['completestatusid'], 'credits' => $coursesetupdata['credits']];
                $student = $datagen->assign_user_to_class($mockuser->id, $pmclass->id, $studentparams);
            }
        }

        if (!empty($setupdata['coursesets'])) {
            foreach ($setupdata['coursesets'] as $coursesetsetupdata) {
                $courseset = $datagen->create_courseset();
                $pgmcrssetparams = ['reqcredits' => $coursesetsetupdata['reqcredits'], 'reqcourses' => $coursesetsetupdata['reqcourses']];
                $pgmcrsset = $datagen->assign_courseset_to_program($courseset->id, $program->id, $pgmcrssetparams);
                foreach ($coursesetsetupdata['courses'] as $coursesetupdata) {
                    $course = $datagen->create_course();
                    $crssetcrs = $datagen->assign_course_to_courseset($course->id, $courseset->id);
                    $pmclass = $datagen->create_pmclass(array('courseid' => $course->id));
                    $studentparams = ['completestatusid' => $coursesetupdata['completestatusid'], 'credits' => $coursesetupdata['credits']];
                    $student = $datagen->assign_user_to_class($mockuser->id, $pmclass->id, $studentparams);
                }
            }
        }

        $currstu = new curriculumstudent($pgmstu);
        $actual = $currstu->get_percent_complete();
        $this->assertEquals($expectedpercentcomplete, $actual);
    }
}