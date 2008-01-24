<?php

require_once("locallib.php");

////////////////////////////////////////////////////////////////
//Functions that are called by the Moodle System
////////////////////////////////////////////////////////////////

/**
* @desc Called when the module is installed into the Moodle System
*/
function wwassignment_install() {
}

/**
* @desc Creates a new Moodle assignment <-> Webwork problem set tie.
* @param $wwassignment object The data of the record to be entered in the DB.
* @return integer The ID of the new record.
*/
function wwassignment_add_instance($wwassignment) {
    global $COURSE;
        
    //Get data about the set from WebWorK
    $wwclient = new wwassignment_client();
    $wwcoursename = _wwassignment_mapped_course($COURSE->id,false);
    $wwsetname = $wwassignment->webwork_set;
    $wwsetdata = $wwclient->get_assignment_data($wwcoursename,$wwsetname,false);
        
    //Attaching Moodle Set to WeBWorK Set
    $returnid = insert_record('wwassignment',$wwassignment);
    
    //Creating events
    _wwassignment_create_events($wwsetname,$wwassignment,$wwsetdata['open_date'],$wwsetdata['due_date']);
    
    return $returnid;
}

/**
* @desc Updates and resynchronizes all information related to the a moodle assignment <-> webwork problem set tie.
* @param $wwassignment object The data of the record to be updated in the DB.
* @return integer The result of the update_record function.
*/
function wwassignment_update_instance($wwassignment) {
    //error_log("updating instance".$wwassignment->id);
    //checking mappings
    $wwclient = new wwassignment_client();
    $wwcoursename = _wwassignment_mapped_course($wwassignment->course,false);
    $wwsetname = $wwassignment->webwork_set;
    
    //get data from WeBWorK
    $wwsetdata = $wwclient->get_assignment_data($wwcoursename,$wwsetname,false);
    $wwassignment->id = $wwassignment->instance;
    
    $returnid = update_record('wwassignment',$wwassignment);
    
    _wwassignment_delete_events($wwassignment);
    _wwassignment_create_events($wwsetname,$wwassignment,$wwsetdata['open_date'],$wwsetdata['due_date']);
    
    return $returnid;
}

/**
* @desc Deletes a tie in Moodle. Deletes nothing in webwork.
* @param integer $wwassignmentid The id of the assignment to delete.
* @return bool Delete was successful or not.
*/
function wwassignment_delete_instance($wwassignmentid) {
    
    $result = true;

    #delete DB record
    if (!delete_records('wwassignment', 'id',$wwassignmentid)) {
        $result = false;
    }
    
    #delete events
    _wwassignment_delete_events($wwassignmentid);
    
    // Get the cm id to properly clean up the grade_items for this assignment
    // bug 4976
    if (! $cm = get_record('modules', 'name', 'wwassignment')) {
        $result = false;
    } else {
        if (! delete_records('grade_item', 'modid', $cm->id, 'cminstance', $wwassignment->id)) {
            $result = false;
        }
    }
    return $result;
}

/**
* @desc Contacts webwork to find out the completion status of a problem set for all users in a course.
* @param integer $wwassignmentid The problem set
* @return object The student grades indexed by student ID.
*/
function wwassignment_grades($wwassignmentid) {
    global $COURSE;
    $wwclient = new wwassignment_client();
    
    $studentgrades = new stdClass;
    $studentgrades->grades = array();
    $studentgrades->maxgrade = 0;
    
    $gradeformula = '$finalgrade += ($problem->status > 0) ? 1 : 0;';
    
    $wwcoursename = _wwassignment_mapped_course($COURSE->id,false);
    $wwsetname = _wwassignment_mapped_set($wwassignmentid,false);
    
    // enumerate over the students in the course:
    $students = get_course_students($COURSE->id);
    
    $usernamearray = array();
    foreach($students as $student) {
        array_push($usernamearray,$student->username);
    }
    $gradearray  = $wwclient->grade_users_sets($wwcoursename,$usernamearray,$wwsetname);
    $i = 0;
    foreach($students as $student) {
        $studentgrades->grades[$student->id] = $gradearray[$i];
        $i++;
    }
    $studentgrades->maxgrade = $wwclient->get_max_grade($wwcoursename,$wwsetname); 
    return $studentgrades;
}


/**
* @desc Returns a small object with summary information about a wwassignment instance. Used for user activity repots.
* @param string $course The ID of the course.
* @param string $user The ID of the user.
* @param string $wwassignment The ID of the wwassignment instance.
* @return array Representing time, info pairing.
*/
function wwassignment_user_outline($course, $user, $mod, $wwassignment) {
    $aLogs = get_logs("l.userid=$user AND l.course=$course AND l.cmid={$wwassignment->id}");
    if( count($aLogs) > 0 ) {
        $return->time = $aLogs[0]->time;
        $return->info = $aLogs[0]->info;
    }
    return $return;
}

/**
* @desc Prints a detailed representation of what a user has done with a instance of this module.
* @param string $course The ID of the course.
* @param string $user The ID of the user.
* @param string $wwassignment The ID of the wwassignment instance.
* @return array Representing time, info pairing.
*/
function wwassignment_user_complete($course, $user, $mod, $wwassignment) {    
    return true;
}



function wwassignment_delete_course() {
}

function wwassignment_process_options() {
}

function wwassignment_reset_course_form() {
}

function wwassignment_delete_userdata() {
    
}

/**
* @desc Finds recent activity that has occured in wwassignment activities.
*/
function wwassignment_print_recent_activity($course, $isteacher, $timestart) {
        global $CFG;
        return false;  //  True if anything was printed, otherwise false 
}

/**
* @desc Function that is run by the cron job. This makes sure that all data is pushed to webwork.
*/
function wwassignment_cron() {
    // refresh wwassignment events in all courses.
    wwassignment_refresh_events();
    //error_log("cron:  update instance   has been called");
    //FIXME: Add a call that updates all events with dates (in case people forgot to push)
    return true;
}
/**
 * Make sure up-to-date events are created for all assignment instances
 *
 * This standard function will check all instances of this module
 * and make sure there are up-to-date events created for each of them.
 * If courseid = 0, then every assignment event in the site is checked, else
 * only assignment events belonging to the course specified are checked.
 * This function is used, in its new format, by restore_refresh_events()
 *
 * @param $courseid int optional If zero then all assignments for all courses are covered
 * @return boolean Always returns true
 */

function wwassignment_refresh_events($courseid = 0) {

    if ($courseid == 0) {
        if (! $wwassignments = get_records("wwassignment")) {
            return true;
        }
    } else {
        if (! $wwassignments = get_records("wwassignment", "course", $courseid)) {
            return true;
        }
    }
    $moduleid = get_field('modules', 'id', 'name', 'wwassignment');
    //error_log("assignments ". count($wwassignments));
    foreach($wwassignments as $key => $wwassignment) {
       //error_log("updating events for ".$wwassignment->id);
       wwassignment_update_instance($wwassignment);
        

    }
    return true;
}


/**
* @desc Finds all the participants in the course
* @param string $wwassignmentid The Moodle wwassignment ID.
* @return array An array of course users (IDs).
*/
/*
function wwassignment_get_participants($wwassignmentid) {
    $wwassignment = get_record('wwassignment', 'id', $wwassignmentid);
    if(!isset($wwassignment)) {
        return array();
    }
    return get_course_users($wwassignment->course);
}
*/

?>