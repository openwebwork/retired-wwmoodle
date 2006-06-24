<?php
// $Id: lib.php,v 1.3 2006-06-24 13:57:05 gage Exp $
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
require_once($CFG->libdir.'/adodb/adodb-pear.inc.php');
//endMEG

/// Library of functions and constants for module wwmoodleset

/**
 * The URL of your WeBWorK installation.
 */
define('WW_TABLE_PREFIX', 'webwork');
define('WWMOODLE_SET_WEBWORK_URL', $CFG->wwmoodleset_webwork_url);
/**
 * The PEAR::DB connection string to connect to the WeBWorK database.
 * This is in the form:
 * type://username:password@host/dbname
 * Where type is usually either 'mysql', 'mysqli', or 'pgsql'
 */
define('WWMOODLE_SET_WEBWORK_DB', $CFG->wwmoodle_webwork_db);


function wwmoodleset_gradeMethods() {

	return array(
		0 => array('name' => get_string("gradeSimple", "wwmoodleset"), 'formula' => '$fGrade += ($p[\'num_correct\'] > 0) ? 1 : 0;'),
		1 => array('name' => get_string("gradeDeductRetry", "wwmoodleset"), 'formula' => '$fGrade += $p[\'num_correct\']/$p[\'attempted\'];'),
	);

}

/**
 * Prints out a select box allowing a teacher to select how WeBWorK grades will be calculated.
 * @param int $iGradeMethod The current grade method.
 * @return void
 */
function wwmoodleset_printGradeMethodSelect($iGradeMethod='-1') {
    debug_log("printGrademethodSelect called");
	$wwmoodleset_gradeMethods = wwmoodleset_gradeMethods();
	print("<select id='gradeMethod' name='gradeMethod'>\n");
	foreach( $wwmoodleset_gradeMethods as $k=>$g ) {
		print("\t<option value='$k'");
		if( $k == $iGradeMethod ) {
			print(" selected='selected'");
		}
		print(">$g[name]</option>\n");
	}
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
    if (!$res = $db->Execute($sql)) {
        if (isset($CFG->debug) and $CFG->debug > 7) {
            notify($db->ErrorMsg() .'<br /><br />'. $sql);
        }
        if (!empty($CFG->dblogerror)) {
            $debug=array_shift(debug_backtrace());
            error_log("SQL ".$db->ErrorMsg()." in {$debug['file']} on line {$debug['line']}. STATEMENT:  $sql");
        }
        return false;
    }

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
	debug_log("start getProblemsForUser");
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
 	$row = $res->fetchRow();
 	!$row ? $row = array() : $row = $row;
 	$res->free();
	return $row;
}

/**
 * Returns a URL to the specified set.
 * @param int $iSetId The set ID to link to.
 * @param string $sCourseName The name of this course.
 * @return string The URL to the specified set. This might be absolute, or relative. However, it is assured of working.
 */
function wwmoodleset_linkToSet($iSetId, $sCourseName) {
	// TODO: Verify me.
	return WWMOODLE_SET_WEBWORK_URL."/$sCourseName/$iSetId";
}

/**
 * Maps a course ID number to it's (sanatized) shortname.
 * @param int $iCourseID The ID of the course.
 * @return string The shortname of the course, with unsafe characters removed. If the courseID is not found, null is returned.
 */
function wwmoodleset_courseIdToShortName($iCourseId) {
	$c = get_record('course', 'id', $iCourseId);
	if( ! $c ) {
		return null;
	}
	$shortname = preg_replace("/[^a-zA-Z0-9]/", "", $c->shortname);
	return $shortname;
}

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
 * Prints an HTML select widget allowing for selection of any of the sets defined
 * for this course in WeBWorK.
 * @pre There is a WeBWorK course for this course.
 * @param int $iCourseId The id of this course.
 * @param int $iSetId The set id to have selected.
 * @return void
 */
function wwmoodleset_printSetSelect($iCourseId, $iSetId=-1) {
	global $db, $CFG;
	debug_log("starting printSetSelect");
	$sCourseName = wwmoodleset_courseIdToShortName($iCourseId);
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
	debug_log("got sets");
 	while( $row = $res->fetchRow() ) {
 		$aSets[] = $row['set_id'];
 	}
	$res->free();
	
	
	// now print the option box, if we have any to print:
	if( count($aSets) < 1 ) {
		print("<b>No sets exist for this course. Please create one via <a href='" . WWMOODLE_SET_WEBWORK_URL . "/$sCourseName'>WeBWorK</a>.</b>\n");
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

function wwmoodleset_add_instance($wwmoodleset) {
/// Given an object containing all the necessary data, 
/// (defined by the form in mod.html) this function 
/// will create a new instance and return the id number 
/// of the new instance.


    $aSetInfo = _wwrpc_getSetInfo($wwmoodleset->set_id, wwmoodleset_courseIdToShortName($wwmoodleset->course));
    
	$wwmoodleset->timemodified = time();
	$wwmoodleset->id = $wwmoodleset->instance;
	$wwmoodleset->timedue = $aSetInfo['due_date'];
	$wwmoodleset->timeavailable = $aSetInfo['open_date'];
    error_log("description".$wwmoodleset->description);
	if ($returnid = insert_record("wwmoodleset", $wwmoodleset)) {
		if ($wwmoodleset->timedue) {
			$event = NULL;
			$event->name        = $wwmoodleset->name;
			$event->description = $wwmoodleset->description;
			$event->courseid    = $wwmoodleset->course;
			$event->groupid     = 0;
			$event->userid      = 0;
			$event->modulename  = 'wwmoodleset';
			$event->instance    = $returnid;
			$event->eventtype   = 'due';
			$event->timestart   = $wwmoodleset->timedue;
			$event->timeduration = 0;
			if (! add_event($event) ){ 
			 	error_log("wwmoodleset event not created when adding instance!");
			}
		}
	}

	return $returnid;
}


function wwmoodleset_update_instance($wwmoodleset) {
/// Given an object containing all the necessary data, 
/// (defined by the form in mod.html) this function 
/// will update an existing instance with new data.
    $aSetInfo = _wwrpc_getSetInfo($wwmoodleset->set_id, wwmoodleset_courseIdToShortName($wwmoodleset->course));

	$wwmoodleset->timemodified = time();
	$wwmoodleset->id = $wwmoodleset->instance;
	$wwmoodleset->timedue = $aSetInfo['due_date'];
	$wwmoodleset->timeavailable = $aSetInfo['open_date'];

	if ($returnid = insert_record("wwmoodleset", $wwmoodleset)) {
		if ($wwmoodleset->timedue) {
			$event = NULL;
	
			if ($event->id = get_field('event', 'id', 'modulename', 'wwmoodleset', 'instance', $wwmoodleset->id)) {
	
				$event->name        = $wwmoodleset->name;
				$event->description = $wwmoodleset->description || "";
				$event->timestart   = $wwmoodleset->timedue;
				
				$rs = update_event($event) ;
				error_log("updating the event".$rs);
	
			} else {
				$event = NULL;
				$event->name        = $wwmoodleset->name;
				$event->description = $wwmoodleset->description;
				$event->courseid    = $wwmoodleset->course;
				$event->groupid     = 0;
				$event->userid      = 0;
				$event->modulename  = 'wwmoodleset';
				$event->instance    = $wwmoodleset->id;
				$event->eventtype   = 'due';
				$event->timestart   = $wwmoodleset->timedue;
				$event->timeduration = 0;
				add_event($event);
			}
		} else {
			delete_records('event', 'modulename', 'wwmoodleset', 'instance', $wwmoodleset->id);
		}
		return $returnid;
	}

}
function wwmoodleset_delete_instance($id) {
/// Given an ID of an instance of this module, 
/// this function will permanently delete the instance 
/// and any data that depends on it.  
    $result = true;
	if (! $wwmoodleset = get_record("wwmoodleset", "id", "$id")) {
		return false;
	}

	# Delete any dependent records here #

	if (! delete_records("wwmoodleset", "id", $wwmoodleset->id)) {
		$result = false;
	}

	if (! delete_records('event', 'modulename', 'wwmoodleset', 'instance', $wwmoodleset->id)) {
		$result = false;
	}
	
	// Get the cm id to properly clean up the grade_items for this assignment
	// bug 4976
	if (! $cm = get_record('modules', 'name', 'wwmoodleset')) {
		$result = false;
	} else {
		if (! delete_records('grade_item', 'modid', $cm->id, 'cminstance', $wwmoodleset->id)) {
			$result = false;
		}
	}

	return $result;
}

function wwmoodleset_user_outline($course, $user, $mod, $wwmoodleset) {
/// Return a small object with summary information about what a 
/// user has done with a given particular instance of this module
/// Used for user activity reports.
/// $return->time = the time they did it
/// $return->info = a short text description
	$aLogs = get_logs("l.userid=$user AND l.course=$course AND l.cmid={$wwmoodleset->id}");
	if( count($aLogs) > 0 ) {
		$return->time = $aLogs[0]->time;
		$return->info = $aLogs[0]->info;
	}
	return $return;
}

function wwmoodleset_user_complete($course, $user, $mod, $wwmoodleset) {
/// Print a detailed representation of what a  user has done with 
/// a given particular instance of this module, for user activity reports.
	
	return true;
}

function wwmoodleset_print_recent_activity($course, $isteacher, $timestart) {
/// Given a course and a time, this module should find recent activity 
/// that has occurred in wwmoodleset activities and print it out. 
/// Return true if there was output, or false is there was none.

		global $CFG;

		return false;  //  True if anything was printed, otherwise false 
}

function wwmoodleset_cron () {
/// Function to be run periodically according to the moodle cron
/// This function searches for things that need to be done, such 
/// as sending out mail, toggling flags etc ... 

		global $CFG;

		return true;
}

function wwmoodleset_grades($wwmoodlesetid) {
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
    debug_log("start grades");
	$wwmoodleset_gradeMethods = wwmoodleset_gradeMethods();
	
	$oGrades->grades = array();
	$aGrades->maxgrade = 0;
	
	$oMod = get_record("wwmoodleset", "id", $wwmoodlesetid);
	if( ! $oMod ) {
		return NULL;
	}
	$gradeFormula = $wwmoodleset_gradeMethods[$oMod->grade_method]['formula'];
	if( empty($gradeFormula) ) {
		return NULL;
	}
	
	$sCourseName = wwmoodleset_courseIdToShortName($oMod->course);
	
	// enumerate over the students in the course:
	$aStudents = get_course_students($oMod->course);
 	foreach( $aStudents as $s ) {
 		$aProblems = _wwrpc_getProblemsForUser($s->username, $oMod->set_id, $sCourseName);
 		$fGrade = 0.0;
		foreach( $aProblems as $p ) {
			eval($gradeFormula);
		}
		$oGrades->grades[$s->id] = $fGrade;
 	}
	$oGrades->maxgrade = _wwrpc_getMaxSetGrade($oMod->set_id, $sCourseName);
	return $oGrades;
}

function wwmoodleset_get_participants($wwmoodlesetid) {
//Must return an array of user records (all data) who are participants
//for a given instance of wwmoodleset. Must include every user involved
//in the instance, independient of his role (student, teacher, admin...)
//See other modules as example.
	$oMod = get_record("wwmoodleset", "id", $wwmoodlesetid);
	if( ! $oMod ) {
		return array();
	}
	return get_course_users($oMod->course);
}

function wwmoodleset_scale_used ($wwmoodlesetid,$scaleid) {
//This function returns if a scale is being used by one wwmoodleset
//it it has support for grading and scales. Commented code should be
//modified if necessary. See forum, glossary or journal modules
//as reference.
	 
	$return = false;

	$rec = get_record("wwmoodleset","id","$wwmoodlesetid","scale","-$scaleid");
	
	if (!empty($rec)  && !empty($scaleid)) {
		$return = true;
	}
	return $return;
}

//////////////////////////////////////////////////////////////////////////////////////
/// Any other wwmoodleset functions go here.  Each of them must have a name that 
/// starts with wwmoodleset_

function wwmoodleset_refresh_events($courseid = 0) {
    error_log("wwmoodleset_refresh_events called");
    if ($courseid == 0) {
        if (! $wwmoodleset = get_records("wwmoodleset")) {
            return true;
        }
    } else {
        if (! $wwmoodleset = get_records("wwmoodleset", "course", $courseid)) {
            return true;
        }
    }
    $moduleid = get_field('modules', 'id', 'name', 'wwmoodleset');

    foreach ($wwmoodleset as $wwmoodleset) {
        $event = NULL;
        $event->name        = addslashes($wwmoodleset->name);
        $event->description = addslashes($wwmoodleset->description);
        $event->timestart   = $wwmoodleset->timedue;

        if ($event->id = get_field('event', 'id', 'modulename', 'wwmoodleset', 'instance', $wwmoodleset->id)) {
            update_event($event);

        } else {
            $event->courseid    = $wwmoodleset->course;
            $event->groupid     = 0;
            $event->userid      = 0;
            $event->modulename  = 'wwmoodleset';
            $event->instance    = $wwmoodleset->id;
            $event->eventtype   = 'due';
            $event->timeduration = 0;
            $event->visible     = get_field('course_modules', 'visible', 'module', $moduleid, 'instance', $wwmoodleset->id);
            add_event($event);
        }

    }
    return true;
}

?>
