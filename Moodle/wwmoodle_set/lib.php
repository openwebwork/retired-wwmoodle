<?php
// $Id: lib.php,v 1.1.1.1 2006-06-17 21:45:25 sh002i Exp $
require_once("DB.php");
/// Library of functions and constants for module wwmoodle_set

/**
 * The URL of your WeBWorK installation.
 */
define('WWMOODLE_SET_WEBWORK_URL', $CFG->wwmoodle_set_webwork_url);
/**
 * The PEAR::DB connection string to connect to the WeBWorK database.
 * This is in the form:
 * type://username:password@host/dbname
 * Where type is usually either 'mysql', 'mysqli', or 'pgsql'
 */
define('WWMOODLE_SET_WEBWORK_DB', $CFG->wwmoodle_webwork_db);

function wwmoodle_set_gradeMethods() {
	return array(
		1 => array('name' => get_string("gradeSimple", "wwmoodle_set"), 'formula' => '$fGrade += ($p[\'num_correct\'] > 0) ? 1 : 0;'),
		2 => array('name' => get_string("gradeDeductRetry", "wwmoodle_set"), 'formula' => '$fGrade += $p[\'num_correct\']/$p[\'attempted\'];'),
	);
}

/**
 * Prints out a select box allowing a teacher to select how WeBWorK grades will be calculated.
 * @param int $iGradeMethod The current grade method.
 * @return void
 */
function wwmoodle_set_printGradeMethodSelect($iGradeMethod='-1') {
	$wwmoodle_set_gradeMethods = wwmoodle_set_gradeMethods();
	print("<select id='gradeMethod' name='gradeMethod'>\n");
	foreach( $wwmoodle_set_gradeMethods as $k=>$g ) {
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
function wwmoodle_set_getMaxSetGrade($iSetId, $sCourseName) {
	$db =& DB::connect(WWMOODLE_SET_WEBWORK_DB);
	if( DB::isError($db) ) {
		return -1;
	}
	$qry = "SELECT COUNT(*) FROM {$sCourseName}_problem WHERE set_id=?";
	$res = $db->query($qry, array($iSetId));
	if( DB::isError($res) ) {
		$db->disconnect();
		return -1;
	}
	$row = $res->fetchRow(DB_FETCHMODE_ASSOC);
	$res->free();
	$db->disconnect();
	return $row ? $row[0] : -1;
}

/**
 * Gets the results of all the problems in the given set for the given user.
 * @param string $sUserName The name of the user to check for.
 * @param int $iSetId The id of the set to check for.
 * @param string $sCourseName The name of this course.
 * @return array An array of the results of all the problems for this user.
 */
function wwmoodle_set_getProblemsForUser($sUserName, $iSetId, $sCourseName) {
	$db =& DB::connect(WWMOODLE_SET_WEBWORK_DB);
	if( DB::isError($db) ) {
		return array();
	}
	$qry = "SELECT * FROM {$sCourseName}_problem_user WHERE user_id=? AND set_id=? ORDER BY problem_id";
	$res = $db->query($qry, array($sUserName, $iSetId));
	if( DB::isError($res) ) {
		$db->disconnect();
		return array();
	}
	$row = $res->fetchRow(DB_FETCHMODE_ASSOC);
	!$row ? $row = array() : $row = $row;
	$res->free();
	$db->disconnect();
	return $row;
}

/**
 * Returns a URL to the specified set.
 * @param int $iSetId The set ID to link to.
 * @param string $sCourseName The name of this course.
 * @return string The URL to the specified set. This might be absolute, or relative. However, it is assured of working.
 */
function wwmoodle_set_linkToSet($iSetId, $sCourseName) {
	// TODO: Verify me.
	return WWMOODLE_SET_WEBWORK_URL."/$sCourseName/$iSetId";
}

/**
 * Maps a course ID number to it's (sanatized) shortname.
 * @param int $iCourseID The ID of the course.
 * @return string The shortname of the course, with unsafe characters removed. If the courseID is not found, null is returned.
 */
function wwmoodle_set_courseIdToShortName($iCourseId) {
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
function wwmoodle_set_getSetInfo($iSetId, $sCourseName) {
	$db =& DB::connect(WWMOODLE_SET_WEBWORK_DB);
	if( DB::isError($db) ) {
		return array('set_id' => $iSetId, 'set_header' => "Unable to get information for this set.", 'hardcopy_header' => "Unable to get information for this set.", 'open_date'=>time(), 'due_date'=>time(), 'answer_date'=>time(), 'published'=>time());
	}
	$qry = "SELECT * FROM {$sCourseName}_set WHERE set_id=?";
	$res = $db->query($qry, array($iSetId));
	if( DB::isError($res) ) {
		return array('set_id' => $iSetId, 'set_header' => "Unable to get information for this set.", 'hardcopy_header' => "Unable to get information for this set.", 'open_date'=>time(), 'due_date'=>time(), 'answer_date'=>time(), 'published'=>time());
	}
	$row = $res->fetchRow(DB_FETCHMODE_ASSOC);
	if( ! $row ) {
		return array('set_id' => $iSetId, 'set_header' => "Unable to get information for this set.", 'hardcopy_header' => "Unable to get information for this set.", 'open_date'=>time(), 'due_date'=>time(), 'answer_date'=>time(), 'published'=>time());
	}
	$res->free();
	$db->disconnect();
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
function wwmoodle_set_printSetSelect($iCourseId, $iSetId=-1) {
	$sCourseName = wwmoodle_set_courseIdToShortName($iCourseId);
	if( is_null($sCourseName) ) {
		print("<b>Unable to find the name of this course.</b>\n");
		return;
	}
	
	// now get a list of all sets for this course:
	$db =& DB::connect(WWMOODLE_SET_WEBWORK_DB);
	if( DB::isError($db) ) {
		print("<b>Unable to connect to WeBWorK database.</b>\n");
		return;
	}
	$qry = "SELECT * FROM {$sCourseName}_set ORDER BY open_date DESC";
	$res = $db->query($qry);
	if( DB::isError($res) ) {
		print("<b>Unable to get a list of sets for this course.</b>\n");
		return;
	}
	$aSets = array();
	while( NULL !== ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) ) {
		$aSets[] = $row['set_id'];
	}
	$res->free();
	
	$db->disconnect();
	
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

function wwmoodle_set_add_instance($wwmoodle_set) {
/// Given an object containing all the necessary data, 
/// (defined by the form in mod.html) this function 
/// will create a new instance and return the id number 
/// of the new instance.

	$wwmoodle_set->timemodified = time();

	# May have to add extra stuff in here #

	return insert_record("wwmoodle_set", $wwmoodle_set);
}


function wwmoodle_set_update_instance($wwmoodle_set) {
/// Given an object containing all the necessary data, 
/// (defined by the form in mod.html) this function 
/// will update an existing instance with new data.

	$wwmoodle_set->timemodified = time();
	$wwmoodle_set->id = $wwmoodle_set->instance;

	# May have to add extra stuff in here #

	return update_record("wwmoodle_set", $wwmoodle_set);
}


function wwmoodle_set_delete_instance($id) {
/// Given an ID of an instance of this module, 
/// this function will permanently delete the instance 
/// and any data that depends on it.  

	if (! $wwmoodle_set = get_record("wwmoodle_set", "id", "$id")) {
		return false;
	}

	$result = true;

	# Delete any dependent records here #

	if (! delete_records("wwmoodle_set", "id", "$wwmoodle_set->id")) {
		$result = false;
	}

	return $result;
}

function wwmoodle_set_user_outline($course, $user, $mod, $wwmoodle_set) {
/// Return a small object with summary information about what a 
/// user has done with a given particular instance of this module
/// Used for user activity reports.
/// $return->time = the time they did it
/// $return->info = a short text description
	$aLogs = get_logs("l.userid=$user AND l.course=$course AND l.cmid={$wwmoodle_set->id}");
	if( count($aLogs) > 0 ) {
		$return->time = $aLogs[0]->time;
		$return->info = $aLogs[0]->info;
	}
	return $return;
}

function wwmoodle_set_user_complete($course, $user, $mod, $wwmoodle_set) {
/// Print a detailed representation of what a  user has done with 
/// a given particular instance of this module, for user activity reports.
	
	return true;
}

function wwmoodle_set_print_recent_activity($course, $isteacher, $timestart) {
/// Given a course and a time, this module should find recent activity 
/// that has occurred in wwmoodle_set activities and print it out. 
/// Return true if there was output, or false is there was none.

		global $CFG;

		return false;  //  True if anything was printed, otherwise false 
}

function wwmoodle_set_cron () {
/// Function to be run periodically according to the moodle cron
/// This function searches for things that need to be done, such 
/// as sending out mail, toggling flags etc ... 

		global $CFG;

		return true;
}

function wwmoodle_set_grades($wwmoodle_setid) {
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
	$wwmoodle_set_gradeMethods = wwmoodle_set_gradeMethods();
	
	$oGrades->grades = array();
	$aGrades->maxgrade = 0;
	$oMod = get_record("wwmoodle_set", "id", $wwmoodle_setid);
	if( ! $oMod ) {
		return NULL;
	}
	$gradeFormula = $wwmoodle_set_gradeMethods[$oMod->grade_method]['formula'];
	if( empty($gradeFormula) ) {
		return NULL;
	}
	
	$sCourseName = wwmoodle_set_courseIdToShortName($oMod->course);
	
	// enumerate over the students in the course:
	$aStudents = get_course_students($oMod->course);
	foreach( $aStudents as $s ) {
		$aProblems = wwmoodle_set_getProblemsForUser($s->username, $oMod->set_id, $sCourseName);
		$fGrade = 0.0;
		foreach( $aProblems as $p ) {
			eval($gradeFormula);
		}
		$oGrades->grades[$s->id] = $fGrade;
	}
	$oGrades->maxgrade = wwmoodle_set_getMaxSetGrade($oMod->set_id, $sCourseName);
	return $oGrades;
}

function wwmoodle_set_get_participants($wwmoodle_setid) {
//Must return an array of user records (all data) who are participants
//for a given instance of wwmoodle_set. Must include every user involved
//in the instance, independient of his role (student, teacher, admin...)
//See other modules as example.
	$oMod = get_record("wwmoodle_set", "id", $wwmoodle_setid);
	if( ! $oMod ) {
		return array();
	}
	return get_course_users($oMod->course);
}

function wwmoodle_set_scale_used ($wwmoodle_setid,$scaleid) {
//This function returns if a scale is being used by one wwmoodle_set
//it it has support for grading and scales. Commented code should be
//modified if necessary. See forum, glossary or journal modules
//as reference.
	 
	$return = false;

	$rec = get_record("wwmoodle_set","id","$wwmoodle_setid","scale","-$scaleid");
	
	if (!empty($rec)  && !empty($scaleid)) {
		$return = true;
	}
	return $return;
}

//////////////////////////////////////////////////////////////////////////////////////
/// Any other wwmoodle_set functions go here.  Each of them must have a name that 
/// starts with wwmoodle_set_


?>
