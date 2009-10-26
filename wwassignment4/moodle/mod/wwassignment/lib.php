<?php
global $CFG;
require_once("locallib.php");

// debug switch defined in locallib.php  define('WWASSIGNMENT_DEBUG',0);

////////////////////////////////////////////////////////////////
// External grade triggers
//      wwassignment_update_grades(wwassignment,userid=0) is called from
//           grade_update_mod_grades in gradlib.php and also from wwassignment/upgrade.php file
//           grade_update_mod_grades is called by $grade_item->refresh_grades
//      
//      wwassignment_grade_item_update(wwassignment)
//           is called from grade_update_mod_grades (before update_grades(wwassignment,userid=0))) 
//
//      wwassignment_get_user_grades 
//   	      could be called from  wwassignment/index.php pages (legacy??)
//
// High level grade calls in gradelib.php  (see end of file)
//
//
//
// Internal grade calling structure
//
//   wwassignment_update_grades($wwassignment=null, $userid=0, $nullifnone=true) -- updates grades for assignment instance or all instances
//                 wwassignment_get_user_grades($wwassignment,$userid=0)  -- fetches homework grades from WeBWorK
//						_wwassignment_get_course_students($courseid) -- collects users from moodle database
//                      $wwclient->grade_users_sets($webworkcourse,$webworkusers,$webworkset) -- fetches grades from a given course, set and user collection
//                 wwassignment_grade_item_update(wwassignment, grades)
//                      grade_update(...) -- fills record in grade_item table and possibly in grade_grades table as well
//
//   wwassignment_update_grade_item(wwassignment) -- just updates grade_item table
//   wwassignment_update_grade_item(wwassignment, grades) updates grade_item table and grade_grades table
////////////////////////////////////////////////////////////////



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

    debugLog("Begin wwassignment_add_instance");
    debugLog("input wwassignment ");
    //debugLog( print_r($wwassignment, true) );
    
    //Get data about the set from WebWorK
    $wwclient = new wwassignment_client();
    $wwassignment->webwork_course = _wwassignment_mapped_course($COURSE->id,false);
    $wwsetdata = $wwclient->get_assignment_data($wwassignment->webwork_course,$wwassignment->webwork_set,false);
    

    
    //Attaching Moodle Set to WeBWorK Set
    debugLog("saving wwassignment ");
    debugLog( print_r($wwassignment,true));
    
     $wwassignment->timemodified = time();   
    if ($returnid = insert_record("wwassignment", $wwassignment)) {
    	$wwassignment->id = $returnid;

		//Creating events
		_wwassignment_create_events($wwassignment,$wwsetdata);
    debugLog("notify gradebook");
		//notify gradebook
		 wwassignment_grade_item_update($wwassignment);
	}
    debugLog("End wwassignment_add_instance");
    return $returnid;
}

/**
* @desc Updates and resynchronizes all information related to the a moodle assignment <-> webwork problem set tie.
*       except for grades
* @param $wwassignment object The data of the record to be updated in the DB.
* @return integer The result of the update_record function.
*/
function wwassignment_update_instance($wwassignment) {
    global $COURSE;
    require_once("locallib.php");
    debugLog("Begin wwassignment_update_instance");
    

    //checking mappings
    $wwclient = new wwassignment_client();
    $wwcoursename = _wwassignment_mapped_course($COURSE->id,false);
    $wwassignment->webwork_course = $wwcoursename;
    $wwsetname = $wwassignment->webwork_set;
    $wwsetdata = $wwclient->get_assignment_data($wwcoursename,$wwsetname,false);
    
    
    //get data from WeBWorK

    $wwsetdata = $wwclient->get_assignment_data($wwcoursename,$wwsetname,false);
    $wwassignment->id = $wwassignment->instance;
    $wwassignment->grade = $wwclient->get_max_grade($wwcoursename,$wwsetname,false);
    $wwassignment->timemodified = time();
    $returnid = update_record('wwassignment',$wwassignment);
    
    _wwassignment_delete_events($wwassignment->id);
    _wwassignment_create_events($wwassignment,$wwsetdata);
    
     //notify gradebook -- update  grades for this wwassignment only
     wwassignment_grade_item_update($wwassignment);
     wwassignment_update_grades($wwassignment);     
	 debugLog("End wwassignment_update_instance");
    return $returnid;
}

/**
* @desc Deletes a tie in Moodle. Deletes nothing in webwork.
* @param integer $wwassignmentid The id of the assignment to delete.
* @return bool Delete was successful or not.
*/
function wwassignment_delete_instance($wwassignmentid) {
    debugLog("Begin wwassignment_delete_instance -- input wwassignmentid:");
    debugLog(print_r($wwassignmentid,true));
    $result = true;

    #delete DB record
    if ( ! $wwassignment = get_record('wwassignment', 'id',$wwassignmentid)) {
        $result = false;
    }
    
    $wwassignment->courseid = $wwassignment->course;

    #delete events
    _wwassignment_delete_events($wwassignmentid);
    
    
    // Get the cm id to properly clean up the grade_items for this assignment
    // bug 4976
//     if (! $cm = get_record('modules', 'name', 'wwassignment')) {
//         $result = false;
//     } else {
//         if (! delete_records('grade_item', 'modid', $cm->id, 'cminstance', $wwassignment->id)) {
//             $result = false;
//         }
//     }
     
      if (! delete_records('wwassignment', 'id', $wwassignment->id)) {
            $result = false;
      }     
     
     //notify gradebook
     wwassignment_grade_item_delete($wwassignment);
    debugLog("End wwassignment_delete_instance -- input wwassignmentid:");

    return $result;
}

    /** gradebook upgrades
    * add xxx_update_grades() function into mod/xxx/lib.php
Ê Ê * add xxx_grade_item_update() function into mod/xxx/lib.php
Ê Ê * patch xxx_update_instance(),  xxx_insert_instance()? xxx_add_instance() and xxx_delete_instance() to call xxx_grade_item_update()
Ê Ê * patch all places of code that change grade values to call xxx_update_grades()
Ê Ê * patch code that displays grades to students to use final grades from the gradebookÊ
    **/
    

/**
 * Return grade for given user or all users.
 *
 * @param int $assignmentid id of assignment
 * @param int $userid optional user id, 0 means all users
 * @return array array of grades, false if none
 */


function wwassignment_get_user_grades($wwassignment,$userid=0) {
	debugLog("Begin wwassignment_get_user_grades");
	//debugLog("inputs -- wwassignment" . print_r($wwassignment,true));
	//debugLog("userid = $userid");
	
	require_once("locallib.php");
	
	//checking mappings
	$courseid = $wwassignment->course;
	$wwclient = new wwassignment_client();
	$wwcoursename = _wwassignment_mapped_course($courseid,false);
	$wwsetname = $wwassignment->webwork_set;
	$usernamearray = array();
	$students      = array();
	$studentgrades = array();
	if ($userid) {
		$user = get_complete_user_data('id',$userid);
		$username = $user->username;
		array_push($usernamearray, $username);
		array_push($students, $user);
	} else {  // get all student names
		$students = _wwassignment_get_course_students( $courseid);
		foreach($students as $student) {
			array_push($usernamearray,$student->username);
		}
	}
	
	$gradearray = $wwclient->grade_users_sets($wwcoursename,$usernamearray,$wwsetname); // FIXME? return key/value pairs instead?
	// returns an array of grades -- the number of questions answered correctly?
	// debugLog("usernamearray " . print_r($usernamearray, true));
	// debugLog("grades($wwcoursename,usernamearray,$wwsetname) = " . print_r($gradearray, true));
	// model for output of grades
	
	$i =0;
	foreach($students as $student) {
		$studentid = $student->id;
		$grade = new stdClass();
			$grade->userid = $studentid;
			$grade->rawgrade =$gradearray[$i];
			$grade->feedback = "some text";
			$grade->feedbackformat = 0;
			$grade->usermodified = 0;
			$grade->dategraded = 0;
			$grade->datesubmitted = 0;
			$grade->id = $studentid;
		$studentgrades[$studentid] = $grade;
		$i++;
	}

	
			
	// end model
	//debugLog("output student grades:" . print_r($studentgrades,true) );
	debugLog("End wwassignment_get_user_grades");
	return $studentgrades;
}

/**
 * Update grades by firing grade_updated event
 *
  * @param object $wwassignment object with extra cmidnumber  ??
 * @param object $wwassignment null means all wwassignments
 * @param int $userid specific user only, 0 mean all
 */
function wwassignment_update_grades($wwassignment=null, $userid=0, $nullifnone=true) {
    debugLog("Begin wwassignment_update_grades");
    //debugLog("inputs wwassignment = " . print_r($wwassignment,true));
    debugLog("userid = $userid");
    global $CFG;
    if (!function_exists('grade_update')) { //workaround for buggy PHP versions
        require_once($CFG->libdir.'/gradelib.php');
    }

    if ($wwassignment != null) {
        // Make sure wwassignment has cmid defined isnce wwassignment_grade_item_update requires it
        if (!$wwassignment->cmidnumber) { // is this ever needed?
        	$wwassignment->cmidnumber =_wwassignment_cmid() ;
        	//error_log("adding cmidnumber to wwassignment".$wwassignment->cmidnumber);
        }

        if ($grades = wwassignment_get_user_grades($wwassignment, $userid)) { # fetches all students if userid=0
            foreach($grades as $k=>$v) {
                if ($v->rawgrade == -1) {
                    $grades[$k]->rawgrade = null;
                }
            }
             wwassignment_grade_item_update($wwassignment, $grades);
        } else {
            wwassignment_grade_item_update($wwassignment);
        }

    } else {  // find all the assignments
        debugLog("import grades for all wwassignments for all courses");
        $sql = "SELECT a.*, cm.idnumber as cmidnumber, a.course as courseid
                  FROM {$CFG->prefix}wwassignment a, {$CFG->prefix}course_modules cm, {$CFG->prefix}modules m
                 WHERE m.name='wwassignment' AND m.id=cm.module AND cm.instance=a.id";

        //$sql = " SELECT a.*  FROM {$CFG->prefix}wwassignment a";
        //debugLog ("sql string = $sql");
        //$tmp = get_recordset_sql($sql);
        //error_log("result is ".print_r($tmp,true) );
       if ($rs = get_recordset_sql($sql)) {
            debugLog("record set found");
            while ($wwassignment = rs_fetch_next_record($rs)) {
                if (!$wwassignment->cmidnumber) { // is this ever needed?
					$wwassignment->cmidnumber =_wwassignment_cmid() ;
				}
 
                //debugLog("processing next grade wwassignment is ".print_r($wwassignment,true) );
                if ($wwassignment->grade != 0) {
                    wwassignment_update_grades($wwassignment);
                } else {
                   wwassignment_grade_item_update($wwassignment);
                }
            }
            rs_close($rs);
        }
    }

	debugLog("End wwassignment_update_grades");

}
/**
 * Create grade item for given assignment
 *
 * @param object $wwassignment object with extra cmidnumber
 * @param mixed optional array/object of grade(s); 'reset' means reset grades in gradebook
 * @return int 0 if ok, error code otherwise
 */
function wwassignment_grade_item_update ($wwassignment, $grades=NULL) {
    $msg = "Begin wwassignment_grade_item_update";
    $msg = ($grades)? $msg . " with grades (updates grade_grades table)" :$msg;
	debugLog($msg);
	// debugLog("inputs wwassignment " . print_r($wwassignment, true));
	// debugLog("grades " . print_r($grades, true) );
    global $CFG;
    if (!function_exists('grade_update')) { //workaround for buggy PHP versions
        require_once($CFG->libdir.'/gradelib.php');
    }

    if (!isset($wwassignment->courseid)) {
        $wwassignment->courseid = $wwassignment->course;
    }
    if (!isset($wwassignment->grade) ) {  // this case occurs when the set link is edited from moodle activity editor
    	$wwclient = new wwassignment_client();
    	$wwcoursename = _wwassignment_mapped_course($wwassignment->courseid,false); //last 'false' means report errors
        $wwsetname    = _wwassignment_mapped_set($wwassignment->id,false);
    	$wwassignment->grade = $wwclient->get_max_grade($wwcoursename,$wwsetname,false);
    }
	// set grade in wwassignment 
	
    $params = array('itemname'=>$wwassignment->name, 'idnumber'=>$wwassignment->cmidnumber);

    if ($wwassignment->grade > 0) {
        $params['gradetype'] = GRADE_TYPE_VALUE;
        $params['grademax']  = $wwassignment->grade;
        $params['grademin']  = 0;

    } else if ($wwassignment->grade < 0) {
        $params['gradetype'] = GRADE_TYPE_SCALE;
        $params['scaleid']   = -$wwassignment->grade;

    } else {
        $params['gradetype'] = GRADE_TYPE_TEXT; // allow text comments only
    }

    if ($grades  === 'reset') {
        $params['reset'] = true;
        $grades = NULL;
    }
    # grade_update() defined in gradelib.php 
    # $grades=NULL means update grade_item table only, otherwise post grades in grade_grades
    debugLog("End wwassignment_grade_item_update");
    error_log("update grades for courseid: ". $wwassignment->courseid . " assignment id: ".$wwassignment->id." time modified ".$wwassignment->timemodified);
    return grade_update('mod/wwassignment', $wwassignment->courseid, 'mod', 'wwassignment', $wwassignment->id, 0, $grades, $params);
}
/**
 * Delete grade item for given assignment
 *
 * @param object $wwassignment object
 * @return object wwassignment ????
 */
function wwassignment_grade_item_delete($wwassignment) {
	debugLog("Begin wwassignment_grade_item_delete");
	debugLog("inputs wwassignment " . print_r($wwassignment, true) );

    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    if (!isset($wwassignment->courseid)) {
        $wwassignment->courseid = $wwassignment->course;
    }
	debugLog("End wwassignment_grade_item_delete");
    return grade_update('mod/wwassignment', $wwassignment->courseid, 'mod', 'wwassignment', $wwassignment->id, 0, NULL, array('deleted'=>1));


}
/**
 * Updates an assignment instance
 *
 * This is done by calling the update_instance() method of the assignment type class
 */
function wwassignment_item_update($wwassignment) {
	error_log("Begin wwassignment_item_update -- not yet defined!!!!");
	error_log("input wwassignment " . print_r($wwassignment,true) );
	error_log("End wwassignment_item_update -- not yet defined!!!!");	
}
/**
* @desc Contacts webwork to find out the completion status of a problem set for all users in a course.
* @param integer $wwassignmentid The problem set
* @return object The student grades indexed by student ID.
*/
function wwassignment_grades($wwassignmentid) {
	error_log("Begin wwassignment_grades -- legacy function?");
    global $COURSE;
    $wwclient = new wwassignment_client();
    $wwassignment = get_record('wwassignment', 'id',$wwassignmentid);
    $courseid     = $wwassignment->course;
    
    $studentgrades = new stdClass;
    $studentgrades->grades = array();
    $studentgrades->maxgrade = 0;
    
    $gradeformula = '$finalgrade += ($problem->status > 0) ? 1 : 0;';
    
    $wwcoursename = _wwassignment_mapped_course($courseid,false);
    $wwsetname    = _wwassignment_mapped_set($wwassignmentid,false);
    
    // enumerate over the students in the course:
    $students = get_course_students( $courseid);
    
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
    error_log("End wwassignment_grades -- legacy function?");
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
    error_log("Begin wwassignment_delete_course --not used yet");
}

function wwassignment_process_options() {
    error_log("Begin wwassignment_process_options --not used yet");

}

function wwassignment_reset_course_form() {
    error_log("Begin wwassignment_reset_course_form --not used yet");
}

function wwassignment_delete_userdata() {
     error_log("Begin wwassignment_delete_userdata --not used yet");
   
}

/**
* @desc Finds recent activity that has occured in wwassignment activities.
*/
function wwassignment_print_recent_activity($course, $isteacher, $timestart) {
        global $CFG;
        error_log("Begin wwassignment_print_recent_activity --not used yet");

        return false;  //  True if anything was printed, otherwise false 
}

/**
* @desc Function that is run by the cron job. This makes sure that 
* the grades and all other data are pulled from webwork.
* returns true if successful
*/
function wwassignment_cron() {	
	error_log("Begin wwassignment_cron");

    //FIXME: Add a call that updates all events with dates (in case people forgot to push)
    //wwassignment_refresh_events();
    //FIXME: Add a call that updates all grades in all courses
    //wwassignment_update_grades(null,0); 
   //try {    // try didn't work on some php systems -- leave it out.
    	 wwassignment_update_dirty_sets();
    error_log("End wwassignment_cron");
    return true;
}


// reference material for improving update dirty sets:
//  from wiki/lib /print_recent_activity 
//  $sql = "SELECT l.*, cm.instance FROM {$CFG->prefix}log l 
//                 INNER JOIN {$CFG->prefix}course_modules cm ON l.cmid = cm.id 
//             WHERE l.time > '$timestart' AND l.course = {$course->id} 
//                 AND l.module = 'wiki' AND action LIKE 'edit%'
//             ORDER BY l.time ASC";
//             
//     if (!$logs = get_records_sql($sql)){
//         return false;
//     }
// 
//     $modinfo = get_fast_modinfo($course);
//     
    
function wwassignment_update_dirty_sets() {  // update grades for all instances which have been modified since last cronjob
    global $CFG;
	$timenow = time();
	$lastcron = get_field("modules","lastcron","name","wwassignment");
	error_log ("lastcron is $lastcron and time now is $timenow");
	
	//error_log ("sql string = $sql");
	// Could we speed this up by getting all of the log records pertaining to webwork in one go?
	// Or perhaps just the log records which have occured after the lastcron date
	// Then create a hash with wwassignment->id  => timemodified
	// means just one database lookup

	$logRecords = get_logs("l.module LIKE \"wwassignment\" AND l.time >$lastcron ", "l.time ASC");
	// possible actions generating a log entry include view,  update and 'view all'
	$wwmodificationtime=array();
	foreach ($logRecords as $record) { 
	    $wwid =$record->info;
	    if ($wwid > 0) { // the $wwid must not be 0 or blank -- blank id's occur for view all.
			$wwmodtimes[$wwid] = $record->time;
		}
	}

	// Create an array with the wwid values
	$idValues= "( ".implode(",", array_keys($wwmodtimes) ). " )";
    
	error_log("values string $idValues");
	//error_log("last modification times".print_r($wwmodtimes,true));

	$sql = "SELECT a.*, cm.idnumber as cmidnumber, a.course as courseid, cm.id as wwinstanceid
			  FROM {$CFG->prefix}wwassignment a, {$CFG->prefix}course_modules cm, {$CFG->prefix}modules m
			 WHERE m.name='wwassignment' AND m.id=cm.module AND cm.instance=a.id AND a.id IN $idValues";
    error_log("sql $sql");
	//$sql3 = "SELECT a.* FROM {$CFG->prefix}wwassignment a WHERE a.id IN $idValues";
	
	//error_log("last modification times".print_r($wwmodificationtime,true));
	$rs = get_recordset_sql($sql);
	if ($rs) {
		while ($wwassignment = rs_fetch_next_record($rs)) {
			if (!$wwassignment->cmidnumber) { // is this ever needed?
				$wwassignment->cmidnumber =_wwassignment_cmid() ;
			}
             $wwassignment->timemodified  = $wwmodtimes[$wwassignment->id];
             if ($wwassignment->timemodified > $lastcron) {
             	error_log("instance needs update.  timemodified ".$wwassignment->timemodified.
             	     ", lastcron $lastcron, course id ".$wwassignment->course.", wwassignment id ".$wwassignment->id.
             	     ", set name ".$wwassignment->name.", cm.id ".$wwassignment->wwinstanceid);
             	if ($wwassignment->grade != 0) {
					wwassignment_update_grades($wwassignment);
				} else {
				   wwassignment_grade_item_update($wwassignment);
				}
				// refresh events for this assignment
				_wwassignment_refresh_event($wwassignment);
				
             } else {
             	error_log("no update needed.  timemodified ".$wwassignment->timemodified.
             	 ", lastcron $lastcron, course id ".$wwassignment->course.", wwassignment id ".$wwassignment->id.
             	", set name ".$wwassignment->name.", cm.id ".$wwassignment->wwinstanceid);
             }

		}
		rs_close($rs);
	}
	error_log("done with updating dirty sets");
	return(true);
}


/**
* @desc Finds all the participants in the course
* @param string $wwassignmentid The Moodle wwassignment ID.
* @return array An array of course users (IDs).
*/

function wwassignment_get_participants($wwassignmentid) {
    $wwassignment = get_record('wwassignment', 'id', $wwassignmentid);
    if(!isset($wwassignment)) {
        return array();
    }
    return _wwassignment_get_course_students( $wwassignment->course );
}


function wwassignment_refresh_events($courseid = 0) {
 //   error_log('wwassignment_refresh_events called --not yet defined');

// This standard function will check all instances of this module
// and make sure there are up-to-date events created for each of them.
// If courseid = 0, then every wwassignment event in the site is checked, else
// only wwassignment events belonging to the course specified are checked.
// This function is used, in its new format, by restore_refresh_events() and by the cron function
// 
// find wwassignment instances associated with this course or all wwassignment modules
     $courses = array();  # create array of courses
    if ($courseid) {
        if (! $wwassignments = get_records("wwassignment", "course", $courseid)) {
            return true;
        } else {
        	$courses[$courseid]= array();      // collect wwassignments for this course
        	array_push( $courses[$courseid],   $wwassignments );  
        }
    } else {
        if (! $wwassignments = get_records("wwassignment")) {
            return true;
        } else {
        	foreach ($wwassignments as $ww ) {
        		// collect wwassignments for each course
        		error_log("course id ".$ww->course);
        		if (! ($courses[$ww->course] ) ) {
        			$courses[$ww->course] = array();
        		}
        		array_push($courses[$ww->course], $ww) ;  // push wwassignment onto an exisiting one
        	}
        }
        	
    }

 
    // $courses now holds a list of course wwassignments

    if ( $wwclient = new wwassignment_client() ) {
        $moduleid = _wwassignment_cmid();
		$cids = array_keys($courses);   # collect course ids
		error_log("course ids  ".print_r($cids, true));
		foreach ($cids as $cid) {
		    error_log("processing cid $cid");
			// connect to WeBWorK
			$wwcoursename = _wwassignment_mapped_course($cid,false); 
			$wwassignment->webwork_course = $wwcoursename;
			if ( $wwcoursename== -1) {
				error_log("Can't connect course $cid to webwork");
				break;
			}
	
		    // retrieve wwassignments associated with this course
			foreach($courses[$cid] as $wwassignment ) {
			   if (!isset($wwassignment)) {
			   		error_log("no wwassignments yet");
			   		break;
			   	}
			   //checking mappings
			    $wwsetname = $wwassignment->webwork_set;
			    error_log(" wwassignment_refresh_events updating events for course |$wwcoursename| set |$wwsetname| "    );
				if ( isset($wwsetname) ) {
					
					//get data from WeBWorK
					$wwsetdata                  = $wwclient->get_assignment_data($wwcoursename,$wwsetname,false);
					$wwassignment->grade        = $wwclient->get_max_grade($wwcoursename,$wwsetname,false);	
					$wwassignment->timemodified = time();
					$returnid = update_record('wwassignment',$wwassignment);
					// update event
					//this part won't work because these items implicitly require the course.
					if (  isset( $wwsetdata )  ) {
						_wwassignment_delete_events($wwassignment->id);
						_wwassignment_create_events($wwassignment, $wwsetdata);
					} else {
						error_log("Can't update events for course |$wwcoursename| set |$wwsetname|");
					}
					error_log("Done updating course |$wwcoursename| set |$wwsetname|");
				} else {
					error_log ("no wwsetname |$wwsetname|"  );
				}
			 }  
		} 
		error_log("done with cids");
   	} else {
		error_log("Can't connect to webwork course");
	}
    error_log("done with wwassignment_refresh_events() ");
    return(true);
}


// High level grade calls ins gradelib.php

/**
 * Returns grading information for given activity - optionally with users grades
 * Manual, course or category items can not be queried.
 * @public
 * @param int $courseid id of course
 * @param string $itemtype 'mod', 'block'
 * @param string $itemmodule 'forum, 'quiz', etc.
 * @param int $iteminstance id of the item module
 * @param int $userid_or_ids optional id of the graded user or array of ids; if userid not used, returns only information about grade_item
 * @return array of grade information objects (scaleid, name, grade and locked status, etc.) indexed with itemnumbers
 */
 
// function grade_get_grades($courseid, $itemtype, $itemmodule, $iteminstance, $userid_or_ids=null) {
 
 
 /**
 * Submit new or update grade; update/create grade_item definition. Grade must have userid specified,
 * rawgrade and feedback with format are optional. rawgrade NULL means 'Not graded', missing property
 * or key means do not change existing.
 *
 * Only following grade item properties can be changed 'itemname', 'idnumber', 'gradetype', 'grademax',
 * 'grademin', 'scaleid', 'multfactor', 'plusfactor', 'deleted' and 'hidden'. 'reset' means delete all current grades including locked ones.
 *
 * Manual, course or category items can not be updated by this function.
 * @public
 * @param string $source source of the grade such as 'mod/assignment'
 * @param int $courseid id of course
 * @param string $itemtype type of grade item - mod, block
 * @param string $itemmodule more specific then $itemtype - assignment, forum, etc.; maybe NULL for some item types
 * @param int $iteminstance instance it of graded subject
 * @param int $itemnumber most probably 0, modules can use other numbers when having more than one grades for each user
 * @param mixed $grades grade (object, array) or several grades (arrays of arrays or objects), NULL if updating grade_item definition only
 * @param mixed $itemdetails object or array describing the grading item, NULL if no change
 */
// function grade_update($source, $courseid, $itemtype, $itemmodule, $iteminstance, $itemnumber, $grades=NULL, $itemdetails=NULL) {

/**
 * Refetches data from all course activities
 * @param int $courseid
 * @param string $modname
 * @return success
 */
// function grade_grab_course_grades($courseid, $modname=null) {

?>

