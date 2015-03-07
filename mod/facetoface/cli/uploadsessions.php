<?php
// Import facetoface sessions from a CSV file

define('CLI_SCRIPT', true);

require(__DIR__.'/../../../config.php');
//require_once("$CFG->libdir/clilib.php");
//require_once('$CFG->libdir/lib/lib.php');
require_once($CFG->dirroot.'/mod/facetoface/lib.php');

// Ensure errors are well explained
set_debugging(DEBUG_DEVELOPER, true);

$options = getopt("f:");
if(isset($options['f'])){
    $csv_file = $options['f'];
    echo("Import file $csv_file\n");
} else{
    exit("uploadsessions.php -f <filename>\n");
}


// Column mappings
// todo improve column handling so it's not hard coded
$csv_cols = [
    'course' => 2,
    'capacity' => 4,
    'instructor' => 5,
    'date' => 6,
    'starttime' => 7,
    'endtime' => 8,
    'custom_location' => 9,
    'custom_room' => 10,
    'custom_address' => 11,
    'custom_map' => 12,
    'details' => 13,
    'dummy' => 14
];
global $TESTING, $TESTING_POSTFIX, $DB;
//$TESTING = 1;
$TESTING_POSTFIX = "----THISISATESTENTRY-qweoirfdoisiuhwi0nvs-";
if ($TESTING){
    if (($h = fopen("test-count.txt", "r")) !== FALSE){
        $test_cnt = fgets($h);
        chop($test_cnt);
        echo("Test count: $test_cnt\n");
        fclose($h);
        if (($h = fopen("test-count.txt", "w")) !== FALSE){
            $TESTING_POSTFIX .= "$test_cnt----";
            echo("Testing postfix: $TESTING_POSTFIX\n");
            $test_cnt += 1;
            echo("New test count: $test_cnt\n");
            fwrite($h, $test_cnt);
            fclose($h);
        }
        else echo("Couldn't write test tracking file\n");
    }
    else echo("Couldn't open test tracking file\n");
}
//exit("Test completed\n");

$row = 1;
if (($handle = fopen($csv_file, "r")) !== FALSE) {
    // Get header row and discard;
    fgetcsv($handle, 1000, ",");
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $num = count($data);
        //echo "$num fields in line $row:\n";
        $row++;
        //for ($c=0; $c < $num; $c++) {
        //    echo "$csv_cols['']$session[$c]\n";
        //}

        // Setup session array
        $session = array();
        foreach($csv_cols as $name => $col) {
            if (isset($data[$col])) {
                $session[$name] = trim($data[$col]);
                if($TESTING){
                    if($name == 'details'){
                        $session[$name] = $session[$name] . $TESTING_POSTFIX;
 //                       echo("Desc: ". $session[$name]."\n");
                    }
         //           print("$name: $data[$col]\n");
                }


        //        echo("$session[$name]\n");
            }
        }// foreach
        //echo($session['course']."\n");
        if($facetoface = facetoface_get_from_course_shortname($session['course'])){
            if( facetoface_csv_add_session($session, $facetoface) ){
                print("Session " . $session['course'] ."-". $session['date'] . "-" . $session['starttime'] . "-" . $session['custom_location'] . "-"
                    . $session['custom_room'] ." added\n");
            }
        } else {
            print("Course not found:" . $session['course']."\n");
        }

        /*if($row > 25){
            exit("Exiting after 25 rows\n");
        }*/
    }
    fclose($handle);
}
/**
 * Takes an array of input and creates a new facetoface session
 * including associated table data, based on code from sessions.php form submission
 * @param $session
 * @param $facetoface
 * @return bool
 */
function facetoface_csv_add_session($session, $facetoface){
global $DB,$TESTING;

    $session['details'] = "<p>".$session['details']."</p>"; // wrap details in HTML
    // setup array for facetoface_add_session
    $todb = new stdClass();
    $todb->facetoface = $facetoface->id;
    $todb->datetimeknown = 1; // hardcoded assuming date is known
    $todb->capacity = $session['capacity'];
    $todb->allowoverbook = 0; // hardcoded off for now, need to review with SLHS to see if they want to use
    $todb->duration = 2; // todo add duration field to the class import file. Hard coding 2 hours duration for now.
    $todb->normalcost = 0; // hardcoded 0 no cost
    $todb->discountcost = 0; // hardcoded 0 no cost

    $sessionid = null; // todo is this needed?
    $returnurl = "view.php?f=$facetoface->id";
    $transaction = $DB->start_delegated_transaction();

    // todo created session data array
    $sessiondates = array();
    $date = new stdClass();
    $date->timestart = strtotime($session['date'] . " " . $session['starttime']);
    $date->timefinish = strtotime($session['date'] . " " . $session['endtime']);
    $sessiondates[] = $date;

    if($TESTING){
//        print("Time start: $date->timestart\n");
//        print("Time finish: $date->timefinish\n");
        //exit("Exiting before inserting data");
    }

    if (!$sessionid = facetoface_add_session($todb, $sessiondates)) {
        $transaction->force_transaction_rollback();

        // todo Logging and events trigger.
  /*      $params = array(
            'context'  => $modulecontext,
            'objectid' => $facetoface->id
        );
        $event = \mod_facetoface\event\add_session_failed::create($params);
        $event->add_record_snapshot('facetoface', $facetoface);
        $event->trigger();
        print_error('error:couldnotaddsession', 'facetoface', $returnurl);*/
    }

    $customfields = facetoface_get_session_customfields(); // todo is there a more efficient location?
    foreach ($customfields as $field) {
        $fieldname = "custom_$field->shortname";
        if (!isset($session[$fieldname])) {
            $session[$fieldname] = ''; // Need to be able to clear fields.
        }

        if (!facetoface_save_customfield_value($field->id, $session[$fieldname], $sessionid, 'session')) {
            $transaction->force_transaction_rollback();
            print_error('error:couldnotsavecustomfield', 'facetoface', $returnurl);
        }
    }

    // todo Save trainer roles.
    // todo assumes trainer is assigned valid role in course, gives error and crashes
    if($session['instructor']){
        $cnt = 0;
        $trainers = explode(',', $session['instructor']);
        $trainerrole[4] = array();
        foreach( $trainers as $trainer){
            $trainer = trim($trainer);
            if($results = $DB->get_records('user' ,array('idnumber'=>$trainer))){
                foreach( $results as $result){
                    $trainerrole[4][$cnt] = $result->id;
                } // take the first matching result
            }
            $cnt++;
        }
        if (isset($trainerrole)) {
            facetoface_update_trainers($sessionid, $trainerrole);
        }
    }

    // Retrieve record that was just inserted/updated.
    if (!$session_rec = facetoface_get_session($sessionid)) {
        $transaction->force_transaction_rollback();
        print_error('error:couldnotfindsession', 'facetoface', $returnurl);
    }

    // Update calendar entries.
    //facetoface_update_calendar_entries($session, $facetoface);

    // Logging and events trigger.
  /*  $params = array(
        'context'  => $modulecontext,
        'objectid' => $session->id
    );
    $event = \mod_facetoface\event\add_session::create($params);
    $event->add_record_snapshot('facetoface_sessions', $session);
    $event->add_record_snapshot('facetoface', $facetoface);
    $event->trigger();*/

    // Update session details in database
    if (!$DB->set_field('facetoface_sessions', 'details', $session['details'], array('id' => $session_rec->id))){
        print_error('error:couldnotupdatedetails', 'facetoface', $returnurl);
    }
    $transaction->allow_commit();
    return true;
}

/**
 * Get the f2f id number based on course shortname. Assumes there is only one F2F session
 * for courses with multiple F2F activities would need to change to using F2F idnumber matching
 * @param $class
 * @return int
 */
function facetoface_get_from_course_shortname($course_shortname){
    global $DB;
    // todo $DB get error check
    if(!$results = $DB->get_records('course' ,array('shortname'=>$course_shortname))){
        return 0;
    }
    // todo $DB get error check
    foreach( $results as $result ){
        if(!$f2f = $DB->get_record('facetoface', array('course'=>$result->id))){
            return 0;
        }
    }
    return $f2f;
}
?>
