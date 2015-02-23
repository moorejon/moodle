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

// Libs.
require_once(elis::plugin_file('eliscore_etl', 'health.php'));
require_once(elis::plugin_file('eliscore_etl', 'etl.php'));

/**
 * Test health checks.
 * @group local_elisprogram
 */
class user_activity_health_testcase extends elis_database_test {

    /**
     * Test the user_activity_health_check
     */
    public function test_etlbehindmorethanweek() {
        global $DB;

        $dataset = $this->createCsvDataSet(array(
            'log' => elis::file('elisprogram/tests/fixtures/log_data.csv')
        ));
        $this->loadDataSet($dataset);

        elis::$config->eliscore_etl->last_run = time() - DAYSECS;
        elis::$config->eliscore_etl->state = serialize(array(
            'sessiontimeout' => 300,
            'sessiontail' => 300,
            'starttime' => 1326308749,
            'startrec' => 1
        ));

        $problem = new user_activity_health_empty();
        $this->assertTrue($problem->exists());

        elis::$config->eliscore_etl->state = serialize(array(
            'sessiontimeout' => 300,
            'sessiontail' => 300,
            'starttime' => time() - (6 * DAYSECS),
            'startrec' => 1
        ));
        $this->assertFalse($problem->exists());
    }

    /**
     * Test ETL bugs fixed with ELIS-7815 & ELIS-7845
     */
    public function test_noetlerrorswithproblemlogdata() {
        global $DB;

        $dataset = $this->createCsvDataSet(array(
            'log' => elis::file('elisprogram/tests/fixtures/mdl_log_elis7845_1500.csv')
        ));
        $this->loadDataSet($dataset);

        elis::$config->eliscore_etl->last_run = 0;
        elis::$config->eliscore_etl->state = '';

        // Create existing record (NOT first)!
        $DB->insert_record('eliscore_etl_modactivity', (object)array(
            'userid' => 409,
            'courseid' => 382,
            'cmid' => 12127,
            'hour' => 1319659200,
            'duration' => 1
        ));

        // Run until complete.
        $prevdone = 0;
        $prevtogo = 1501;
        $prevstart = 0;
        $etlobj = new eliscore_etl_useractivity(0, false);
        do {
            $realtime = time();
            ob_start();
            $etlobj->cron();
            ob_end_clean();
            $lasttime = (int)$etlobj->state['starttime'];
            $recordsdone = $DB->count_records_select('log', "time < $lasttime");
            $recordstogo = $DB->count_records_select('log', "time >= $lasttime");
            /*
             * Uncomment to track progress.
             * echo "\n Done = {$recordsdone} ({$prevdone}), Togo = {$recordstogo} ({$prev_togo}),
             * starttime = {$lasttime} ({$prev_start})\n";
             */
            if (!$lasttime || !$recordstogo) {
                break;
            }
            $this->assertTrue($recordsdone >= $prevdone);
            $this->assertTrue($recordstogo <= $prevtogo);
            $this->assertTrue($lasttime > $prevstart);
            $prevdone = $recordsdone;
            $prevtogo = $recordstogo;
            $prevstart = $lasttime;
        } while (true);
        $etluacnt = $DB->count_records('eliscore_etl_useractivity');
        $etlumacnt = $DB->count_records('eliscore_etl_modactivity');

        $this->assertEquals(342, $etluacnt);
        $this->assertEquals(225, $etlumacnt);
    }

    /**
     * Test for duplicate profile fields.
     */
    public function test_duplicate_profile_data() {
        global $DB;

        // Drop the table index so that we can insert a duplicate record.
        $table = new xmldb_table('user_info_data');
        $index = new xmldb_index('userid_fieldid_ix', XMLDB_INDEX_UNIQUE, array('userid', 'fieldid'));
        $dbman = $DB->get_manager();
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Set up data.
        $record = new stdClass;
        $record->fieldid = 1;
        $record->userid = 1;
        $record->data = 'test';
        $DB->insert_record('user_info_data', $record);
        $DB->insert_record('user_info_data', $record);
        $DB->insert_record('user_info_data', $record);
        $DB->insert_record('user_info_data', $record);

        $duplicateprofilecheck = new \local_elisprogram\lib\health\duplicatemoodleprofile();
        $this->assertEquals(get_string('health_dupmoodleprofiledesc', 'local_elisprogram', 3), $duplicateprofilecheck->description());

        // Put the index we removed back
        unset($record->id);
        unset($record->data);
        $DB->delete_records('user_info_data', (array)$record);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }
    }

    /**
     * Test for duplicate usertrack records.
     */
    public function test_duplicateusertrackdata() {
        global $CFG, $DB;
        require_once(elispm::lib('data/usertrack.class.php'));

        $dataset = $this->createCsvDataSet(array(
            usertrack::TABLE => elis::component_file('elisprogram', 'tests/fixtures/usertrack_trackassignment_listing.csv')
        ));
        $this->loadDataSet($dataset);

        // Drop the table index so that we can insert a duplicate record.
        $table = new xmldb_table(usertrack::TABLE);
        $index = new xmldb_index('userid_trackid_ix', XMLDB_INDEX_UNIQUE, array('userid', 'trackid'));
        $dbman = $DB->get_manager();
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Should not report any duplicates.
        $duplicatecheck = new \local_elisprogram\lib\health\duplicateusertracks();
        $this->assertEquals(0, $duplicatecheck->get_amount());

        // Insert a duplicate record.
        $record = new stdClass();
        $record->userid = 100;
        $record->trackid = 1;
        $DB->insert_record(usertrack::TABLE, $record);

        // Should report duplicates.
        $duplicatecheck = new \local_elisprogram\lib\health\duplicateusertracks();
        $this->assertGreaterThan(0, $duplicatecheck->get_amount());

        // Remove duplicate records.
        pm_fix_duplicate_usertrack_records();

        // Should not report any duplicates.
        $duplicatecheck = new \local_elisprogram\lib\health\duplicateusertracks();
        $this->assertEquals(0, $duplicatecheck->get_amount());

        // Put the index back.
        $dbman->add_index($table, $index);
    }
}
