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
 * @copyright  (C) 2008-2013 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

require_once(dirname(__FILE__).'/../../eliscore/test_config.php');
global $CFG;
require_once($CFG->dirroot.'/local/elisprogram/lib/setup.php');

// Data object.
require_once(elispm::lib('data/pmclass.class.php'));
require_once(elispm::lib('data/student.class.php'));
require_once(elispm::lib('data/user.class.php'));

/**
 * Test healthpage functions.
 * @group local_elisprogram
 */
class healthpage_testcase extends elis_database_test {

    /**
     * Load initial data from CSVs.
     */
    protected function load_csv_data() {
        $dataset = $this->createCsvDataSet(array(
            course::TABLE => elispm::file('tests/fixtures/pmcourse.csv'),
            pmclass::TABLE => elispm::file('tests/fixtures/pmclass.csv'),
            user::TABLE => elispm::file('tests/fixtures/pmusers.csv'),
        ));
        $this->loadDataSet($dataset);
    }

    /**
     * Dataprovider for test_duplicateenrolmentdetection
     * @return array An array of test parameters.
     */
    public function dataprovider_duplicateenrolment() {
        return array(
                // Case 0: No duplicates, all users enrolled in both classes.
                array(
                        array(
                                array(
                                    'classid' => 100,
                                    'userid'  => 1
                                ),
                                array(
                                    'classid' => 100,
                                    'userid'  => 2
                                ),
                                array(
                                    'classid' => 100,
                                    'userid'  => 3
                                ),
                                array(
                                    'classid' => 101,
                                    'userid'  => 1
                                ),
                                array(
                                    'classid' => 101,
                                    'userid'  => 2
                                ),
                                array(
                                    'classid' => 101,
                                    'userid'  => 3
                                )
                        ),
                        0
                ),
                // Case 1: One duplicate, one user enrolled multiple times in one class.
                array(
                        array(
                                array(
                                    'classid' => 100,
                                    'userid' => 1
                                ),
                                array(
                                    'classid' => 100,
                                    'userid' => 1
                                ),
                                array(
                                    'classid' => 100,
                                    'userid' => 1
                                )
                        ),
                        1
                ),
                // Case 2: Six duplicates, each user enrolled twice in both courses.
                array(
                        array(
                                array(
                                    'classid' => 100,
                                    'userid' => 1
                                ),
                                array(
                                    'classid' => 100,
                                    'userid' => 1
                                ),
                                array(
                                    'classid' => 100,
                                    'userid' => 2
                                ),
                                array(
                                    'classid' => 100,
                                    'userid' => 2
                                ),
                                array(
                                    'classid' => 100,
                                    'userid' => 3
                                ),
                                array(
                                    'classid' => 100,
                                    'userid' => 3
                                ),
                                array(
                                    'classid' => 101,
                                    'userid' => 1
                                ),
                                array(
                                    'classid' => 101,
                                    'userid' => 1
                                ),
                                array(
                                    'classid' => 101,
                                    'userid' => 2
                                ),
                                array(
                                    'classid' => 101,
                                    'userid' => 2
                                ),
                                array(
                                    'classid' => 101,
                                    'userid' => 3
                                ),
                                array(
                                    'classid' => 101,
                                    'userid' => 3
                                )
                        ),
                        6
                )
        );
    }

    /**
     * Validate that the health page duplicate enrolment check and correctly detect the number of duplicate enrolments
     * in the system.
     *
     * @dataProvider dataprovider_duplicateenrolment
     * @param array $enrolments An array of enrolment data to insert into student::TABLE
     * @param int $count The number of duplicate enrolments that should be detected
     */
    public function test_duplicateenrolmentdetection($enrolments, $count) {
        global $DB;

        $this->load_csv_data();

        // Pre-load enrolment data.
        foreach ($enrolments as $enrolment) {
            $DB->insert_record(student::TABLE, $enrolment);
        }

        $healthcheck = new \local_elisprogram\lib\health\duplicateenrolments();
        $this->assertEquals($count, $healthcheck->get_amount());
    }
}