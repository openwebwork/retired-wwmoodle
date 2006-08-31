<?php
// $Id: lib.php,v 1.8 2006-08-31 01:30:13 gage Exp $
//require_once("DB.php");
function debug_log($obj) {
	$fh = fopen("/home/gage/moodle_debug", "w");
	//fwrite($fh,  "wwmoodle\n");
	$struct = print_r($obj, true);
	fwrite($fh, $struct);
	fwrite($fh, "\n");
	fclose($fh);
	//MEG
}
//MEG
$path = ini_get('include_path');
//debug_log($path);
//ini_set('include_path', $path . ':/usr/local/lib/php/pear/DB');
//ini_set('include_path', $path . 'lib/adodb');
//require_once("PEAR.php");
//require_once("DB.php");
//require_once($CFG->libdir.'/adodb/adodb-pear.inc.php');
//endMEG



//MEG  from old wwmoodle/lib.php
/**
 * These defines exist simply for easy of referencing them in functions.
 */
/**
 * The location of the webwork courses directory.
 * The user that this script executes as must have write access to this directory.
 * This must end in a /!
 */
define('WWASSIGNMENT_WEBWORK_COURSES', $CFG->wwassignment_webworkcourses);
/**
 * The URL of the WeBWorK install.
 */
define('WWASSIGNMENT_WEBWORK_URL', $CFG->wwassignment_webworkurl);






/// Library of functions and constants for module wwassignment

/**
 * The URL of your WeBWorK installation.
 */
define('WW_TABLE_PREFIX', 'webwork');
define('WWASSIGNMENT_WEBWORK_URL', $CFG->wwassignment_webwork_url);
/**
 * The PEAR::DB connection string to connect to the WeBWorK database.
 * This is in the form:
 * type://username:password@host/dbname
 * Where type is usually either 'mysql', 'mysqli', or 'pgsql'
 */
define('WWASSIGNMENT_WEBWORK_DB', $CFG->wwmoodle_webwork_db);


function wwassignment_gradeMethods() {

	return array(
		0 => array('name' => get_string("gradeSimple", "wwassignment"), 'formula' => '$fGrade += ($p[\'num_correct\'] > 0) ? 1 : 0;'),
		1 => array('name' => get_string("gradeDeductRetry", "wwassignment"), 'formula' => '$fGrade += $p[\'num_correct\']/$p[\'attempted\'];'),
	);

}

/**
 * Prints out a select box allowing a teacher to select how WeBWorK grades will be calculated.
 * @param int $iGradeMethod The current grade method.
 * @return void
 */
function wwassignment_printGradeMethodSelect($iGradeMethod='-1') {
    // debug_log("printGrademethodSelect called");
	$wwassignment_gradeMethods = wwassignment_gradeMethods();
	print("<select id='gradingmethod' name='gradingmethod'>\n");
	foreach( $wwassignment_gradeMethods as $k=>$g ) {
		print("\t<option value='$k'");
		if( $k == $iGradeMethod ) {
			print(" selected='selected'");
		}
		print(">$g[name]</option>\n");
	}
}




/**
 * Maps a course ID number to it's (sanatized) shortname.
 * @param int $iCourseID The ID of the course.
 * @return string The shortname of the course, with unsafe characters removed. If the courseID is not found, null is returned.
 */
function wwassignment_courseIdToShortName($iCourseId) {
	$c = get_record('course', 'id', $iCourseId);
	if( ! $c ) {
		return null;
	}
	$shortname = preg_replace("/[^a-zA-Z0-9]/", "", $c->shortname);
	return $shortname;
}



function wwassignment_add_instance($wwassignment) {
/// Given an object containing all the necessary data, 
/// (defined by the form in mod.html) this function 
/// will create a new instance and return the id number 
/// of the new instance.
	# create set
	$aSetInfo = _wwrpc_getSetInfo($wwassignment->set_id, wwassignment_courseIdToShortName($wwassignment->course));
	
	$wwassignment->timemodified = time();
	$wwassignment->id = $wwassignment->instance;
	$wwassignment->timedue = $aSetInfo['due_date'];
	$wwassignment->timeavailable = $aSetInfo['open_date'];
	error_log("add wwassignment record");
//	error_log(print_r($wwassignment, true));
	if ($returnid = insert_record("wwassignment", $wwassignment)) {
		if ($wwassignment->timedue) {
			$event = NULL;
			$event->name        = $wwassignment->name;
			$event->description = $wwassignment->description;
			$event->courseid    = $wwassignment->course;
			$event->groupid     = 0;
			$event->userid      = 0;
			$event->modulename  = 'wwassignment';
			$event->instance    = $returnid;
			$event->eventtype   = 'due';
			$event->timestart   = $wwassignment->timedue;
			$event->timeduration = 0;
			if (! add_event($event) ){ 
				error_log("wwassignment event not created when adding instance!");
			}
		}
	}
	
	return $returnid;
}


function wwassignment_update_instance($wwassignment) {
/// Given an object containing all the necessary data, 
/// (defined by the form in mod.html) this function 
/// will update an existing instance with new data.
    $aSetInfo = _wwrpc_getSetInfo($wwassignment->set_id, wwassignment_courseIdToShortName($wwassignment->course));

	$wwassignment->timemodified = time();
	$wwassignment->id = $wwassignment->instance;
	$wwassignment->timedue = $aSetInfo['due_date'];
	$wwassignment->timeavailable = $aSetInfo['open_date'];

    error_log("updating wwassignment record");
    error_log(print_r($wwassignment, true));
	if ($returnid = update_record("wwassignment", $wwassignment)) {
		if ($wwassignment->timedue) {
			$event = NULL;
	
			if ($event->id = get_field('event', 'id', 'modulename', 'wwassignment', 'instance', $wwassignment->id)) {
	
				$event->name        = $wwassignment->name;
				$event->description = $wwassignment->description;
				$event->timestart   = $wwassignment->timedue;
				
				$rs = update_event($event) ;
				
				error_log("updating the event".$rs);
				error_log(print_r($event,true));
	
			} else {
				$event = NULL;
				$event->name        = $wwassignment->name;
				$event->description = $wwassignment->description;
				$event->courseid    = $wwassignment->course;
				$event->groupid     = 0;
				$event->userid      = 0;
				$event->modulename  = 'wwassignment';
				$event->instance    = $wwassignment->id;
				$event->eventtype   = 'due';
				$event->timestart   = $wwassignment->timedue;
				$event->timeduration = 0;
				add_event($event);
			}
		} else {
			delete_records('event', 'modulename', 'wwassignment', 'instance', $wwassignment->id);
		}
		
	}
	return $returnid;

}
function wwassignment_delete_instance($id) {
/// Given an ID of an instance of this module, 
/// this function will permanently delete the instance 
/// and any data that depends on it.  
    $result = true;
	if (! $wwassignment = get_record("wwassignment", "id", "$id")) {
		return false;
	}

	# Delete any dependent records here #

	if (! delete_records("wwassignment", "id", $wwassignment->id)) {
		$result = false;
	}

	if (! delete_records('event', 'modulename', 'wwassignment', 'instance', $wwassignment->id)) {
		$result = false;
	}
	
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

function wwassignment_user_outline($course, $user, $mod, $wwassignment) {
/// Return a small object with summary information about what a 
/// user has done with a given particular instance of this module
/// Used for user activity reports.
/// $return->time = the time they did it
/// $return->info = a short text description
	$aLogs = get_logs("l.userid=$user AND l.course=$course AND l.cmid={$wwassignment->id}");
	if( count($aLogs) > 0 ) {
		$return->time = $aLogs[0]->time;
		$return->info = $aLogs[0]->info;
	}
	return $return;
}

function wwassignment_user_complete($course, $user, $mod, $wwassignment) {
/// Print a detailed representation of what a  user has done with 
/// a given particular instance of this module, for user activity reports.
	
	return true;
}

function wwassignment_print_recent_activity($course, $isteacher, $timestart) {
/// Given a course and a time, this module should find recent activity 
/// that has occurred in wwassignment activities and print it out. 
/// Return true if there was output, or false is there was none.

		global $CFG;

		return false;  //  True if anything was printed, otherwise false 
}

function wwassignment_cron () {
/// Function to be run periodically according to the moodle cron
/// This function searches for things that need to be done, such 
/// as sending out mail, toggling flags etc ... 

		global $CFG;

		return true;
}

function wwassignment_grades($wwassignmentid) {
/// Must return an array of grades for a given instance of this module, 
/// indexed by user.  It also returns a maximum allowed grade.
///
///    $return->grades = array of grades;
///    $return->maxgrade = maximum allowed grade;
///
///    return $return;
	// here's how we compute the grade:
	// NOTE: each set has P problems in it.
	// NOTE: each problem may be attempted M times.
	// NOTE: each problem has been attempted A times (by a given user).
	// NOTE: each problem was gotten correct C times (by a given user).
	// NOTE: each problem was gotten incorrect I times (by a given user).
	// Thus, a users grade is: sigma(over P) C/A
	// And the max score is P
	// Alternately, code is provided for:
	// sigma(over P) { if( C > 0) 1 else 0 }
	// again with a max of P
	
	// redefine it here, 'cause for some reason we can't global it...
    //debug_log("start grades ".$wwassignmentid);
	$wwassignment_gradeMethods = wwassignment_gradeMethods();
	
	$oGrades->grades = array();
	$aGrades->maxgrade = 0;
	
	$oMod = get_record("wwassignment", "id", $wwassignmentid);
	if( ! $oMod ) {
		return NULL;
	}
	//debug_log("record ".print_r($oMod,true));
	$gradeFormula = $wwassignment_gradeMethods[$oMod->gradingmethod]['formula'];
	//debug_log("formula ".print_r($gradeFormula, true));
	if( empty($gradeFormula) ) {
		return NULL;
	}
	
	$sCourseName = wwassignment_courseIdToShortName($oMod->course);
	
	// enumerate over the students in the course:
	$aStudents = get_course_students($oMod->course);
 	foreach( $aStudents as $s ) {
 		$aProblems = _wwrpc_getProblemsForUser($s->username, $oMod->set_id, $sCourseName);
 		$fGrade = 0.0;
		foreach( $aProblems as $p ) {
		    //debug_log("student".$s->username." set ".$oMod->set_id." score ". $p['num_correct']);
			eval($gradeFormula);
		}
		$oGrades->grades[$s->id] = $fGrade;
 	}
	$oGrades->maxgrade = _wwrpc_getMaxSetGrade($oMod->set_id, $sCourseName);
	debug_log("all grades".print_r($oGrades, true));
	return $oGrades;
}

function wwassignment_get_participants($wwassignmentid) {
//Must return an array of user records (all data) who are participants
//for a given instance of wwassignment. Must include every user involved
//in the instance, independient of his role (student, teacher, admin...)
//See other modules as example.
	$oMod = get_record("wwassignment", "id", $wwassignmentid);
	if( ! $oMod ) {
		return array();
	}
	return get_course_users($oMod->course);
}

function wwassignment_scale_used ($wwassignmentid,$scaleid) {
//This function returns if a scale is being used by one wwassignment
//it it has support for grading and scales. Commented code should be
//modified if necessary. See forum, glossary or journal modules
//as reference.
	 
	$return = false;

	$rec = get_record("wwassignment","id","$wwassignmentid","scale","-$scaleid");
	
	if (!empty($rec)  && !empty($scaleid)) {
		$return = true;
	}
	return $return;
}
function wwassignment_refresh_events($courseid = 0) {
    error_log("wwassignment_refresh_events called");
    if ($courseid == 0) {
        if (! $wwassignment = get_records("wwassignment")) {
            return true;
        }
    } else {
        if (! $wwassignment = get_records("wwassignment", "course", $courseid)) {
            return true;
        }
    }
    $moduleid = get_field('modules', 'id', 'name', 'wwassignment');

    foreach ($wwassignment as $wwassignment) {
        $event = NULL;
        $event->name        = addslashes($wwassignment->name);
        $event->description = addslashes($wwassignment->description);
        $event->timestart   = $wwassignment->timedue;

        if ($event->id = get_field('event', 'id', 'modulename', 'wwassignment', 'instance', $wwassignment->id)) {
            update_event($event);

        } else {
            $event->courseid    = $wwassignment->course;
            $event->groupid     = 0;
            $event->userid      = 0;
            $event->modulename  = 'wwassignment';
            $event->instance    = $wwassignment->id;
            $event->eventtype   = 'due';
            $event->timeduration = 0;
            $event->visible     = get_field('course_modules', 'visible', 'module', $moduleid, 'instance', $wwassignment->id);
            add_event($event);
        }

    }
    return true;
}


////////////////////////////////////////////////////////////////////////////////////
// calls to WeBWorK database start with _wwrpc_
///////////////////////////////////////////////////////////////////////////////////

/**
 * Gets information about the specified set.
 * @param int $iSetId The id of the set.
 * @param $sCourseName The name of this course
 * @return array Information about the set.
 */
function _wwrpc_getSetInfo($iSetId, $sCourseName) {
	global $db, $CFG;
	$qry = "SELECT * FROM ". WW_TABLE_PREFIX.".{$sCourseName}_set WHERE set_id=?";
    //error_log("get info for set $iSetID and $sCourseName");
	if (!$res = $db->query($qry, array($iSetId))) {
        if (isset($CFG->debug) and $CFG->debug > 7) {
            notify($db->ErrorMsg() .'<br /><br />'. $sql);
        }
        if (!empty($CFG->dblogerror)) {
            $debug=array_shift(debug_backtrace());
            error_log("SQL ".$db->ErrorMsg()." in {$debug['file']} on line {$debug['line']}. STATEMENT:  $sql");
        }
        return false;
    }
	$row = $res->fetchRow();
	if( ! $row ) {
		return array('set_id' => $iSetId, 'set_header' => "Unable to get information for this set.", 'hardcopy_header' => "Unable to get information for this set.", 'open_date'=>time(), 'due_date'=>time(), 'answer_date'=>time(), 'published'=>time());
	}
	$res->free();
	//error_log("result from getSetInfo");
	return $row;
}

/**
 * Gets the max grade for a given problem set.
 * Essentially, this is just the number of problems in the set.
 * @param int $iSetId The id of the set.
 * @param string $sCourseName The name of this course.
 * @return int
 */
function _wwrpc_getMaxSetGrade($iSetId, $sCourseName) {
	global $db, $CFG;


	$qry = "SELECT COUNT(*) FROM ". WW_TABLE_PREFIX.".{$sCourseName}_problem WHERE set_id=?";
	if (!$res = $db->query($qry, array($iSetId))) {
        if (isset($CFG->debug) and $CFG->debug > 7) {
            notify($db->ErrorMsg() .'<br /><br />'. $sql);
        }
        if (!empty($CFG->dblogerror)) {
            $debug=array_shift(debug_backtrace());
            error_log("SQL ".$db->ErrorMsg()." in {$debug['file']} on line {$debug['line']}. STATEMENT:  $sql");
        }
        return false;
    }
	$row = $res->fetchRow();
	$res->free();
	return $row ? $row['COUNT(*)'] : -1;
}

/**
 * Gets the results of all the problems in the given set for the given user.
 * @param string $sUserName The name of the user to check for.
 * @param int $iSetId The id of the set to check for.
 * @param string $sCourseName The name of this course.
 * @return array An array of the results of all the problems for this user.
 */
function _wwrpc_getProblemsForUser($sUserName, $iSetId, $sCourseName) {
	// debug_log("start getProblemsForUser");
    global $db, $CFG;
 	$qry = "SELECT * FROM ". WW_TABLE_PREFIX.".{$sCourseName}_problem_user WHERE user_id=? AND set_id=? ORDER BY problem_id";
	if (!$res = $db->query($qry, array($sUserName, $iSetId))) {
        if (isset($CFG->debug) and $CFG->debug > 7) {
            notify($db->ErrorMsg() .'<br /><br />'. $sql);
        }
        if (!empty($CFG->dblogerror)) {
            $debug=array_shift(debug_backtrace());
            error_log("SQL ".$db->ErrorMsg()." in {$debug['file']} on line {$debug['line']}. STATEMENT:  $sql");
        }
        return false;
    }
 	$row = $res->getArray();
 	!$row ? $row = array() : $row = $row;
 	
 	//debug_log("row for $sUserName $iSetID");
 	//debug_log(print_r($row,true));
 	
 	$res->free();
	return $row;
}

////////////////////////////////////////////////////////////////////////////////////
// internal functions start with _wwassignment
///////////////////////////////////////////////////////////////////////////////////

/**
 * Prints an HTML select widget allowing for selection of any of the sets defined
 * for this course in WeBWorK.
 * @pre There is a WeBWorK course for this course.
 * @param int $iCourseId The id of this course.
 * @param int $iSetId The set id to have selected.
 * @return void
 */
function _wwassignment_printSetSelect($iCourseId, $iSetId=-1) {
	global $db, $CFG;
	// debug_log("starting printSetSelect");
	$sCourseName = wwassignment_courseIdToShortName($iCourseId);
	if( is_null($sCourseName) ) {
		print("<b>Unable to find the name of this course.</b>\n");
		return;
	}
	
	// now get a list of all sets for this course:
	$qry = "SELECT * FROM ". WW_TABLE_PREFIX.".{$sCourseName}_set ORDER BY open_date DESC";
	if (!$res = $db->query($qry)) {
        if (isset($CFG->debug) and $CFG->debug > 7) {
            notify($db->ErrorMsg() .'<br /><br />'. $sql);
        }
        if (!empty($CFG->dblogerror)) {
            $debug=array_shift(debug_backtrace());
            error_log("SQL ".$db->ErrorMsg()." in {$debug['file']} on line {$debug['line']}. STATEMENT:  $sql");
        }
        return false;
    }
	$aSets = array();
	// debug_log("got sets");
 	while( $row = $res->fetchRow() ) {
 		$aSets[] = $row['set_id'];
 	}
	$res->free();
	
	
	// now print the option box, if we have any to print:
	if( count($aSets) < 1 ) {
		print("<b>No sets exist for this course. Please create one via <a href='" . WWASSIGNMENT_WEBWORK_URL . "/$sCourseName'>WeBWorK</a>.</b>\n");
		return;
	}
	print("<select id='set_id' name='set_id'>\n");
	foreach( $aSets as $s ) {
		print("<option value='");
		p($s);
		print("'");
		if( $s == $iSetId ) {
			print("selected='selected'");
		}
		print(">$s</option>\n");
	}
	print("</select>\n");
}

/**
 * Maps a course ID number to it's (sanatized) shortname.
 * @param int $iCourseID The ID of the course.
 * @return string The shortname of the course, with unsafe characters removed. If the courseID is not found, null is returned.
 */
function _wwassignment_courseIdToShortName($iCourseId) {
	$c = get_record('course', 'id', $iCourseId);
	if( ! $c ) {
		return null;
	}
	$shortname = preg_replace("/[^a-zA-Z0-9]/", "", $c->shortname);
	return $shortname;
}

/**
 * Returns a URL to the specified set.
 * @param int $iSetId The set ID to link to.
 * @param string $sCourseName The name of this course.
 * @return string The URL to the specified set. This might be absolute, or relative. However, it is assured of working.
 */
function _wwassignment_linkToSet($iSetId, $sCourseName) {
	// TODO: Verify me.
	return WWASSIGNMENT_WEBWORK_URL."/$sCourseName/$iSetId";
}

/**
 * Checks for a wwassignment_bridge record for the given course, creates one if it does not exist.
 * @param int $iCourseID The ID of the course.
 * @return void
 */
function _wwassignment_ensureBridgeExists($iCourseID) {
	$wwassignment_bridge = get_record("wwassignment_bridge", "course", $iCourseID);
	if (!$wwassignment_bridge) {
		$wwassignment_bridge->timemodified = time();
		$wwassignment_bridge->course = $iCourseID;
		$wwassignment_bridge->coursename = _wwassignment_courseIdToShortName($iCourseID);
		$wwassignment_bridge->name = $wwassignment_bridge->coursename;
		if (!isset($wwassignment_bridge->coursename)) {
			$wwassignment_bridge->coursename = "foo";
		}
		$returnid = insert_record("wwassignment_bridge",$wwassignment_bridge);
		error_log("inserting new entry to wwassignment_bridge id=$returnid");
	} 
}

?>
