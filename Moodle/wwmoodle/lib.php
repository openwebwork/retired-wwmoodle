<?php 
// $Id: lib.php,v 1.2 2006-06-24 01:46:57 sh002i Exp $

//MEG
#$path = ini_get('include_path');
#ini_set('include_path', $path . ':/usr/local/lib/php/pear/DB');

#require_once("PEAR.php");
//require_once("DB.php");
//endMEG



/// Library of functions and constants for module NEWMODULE
/// (replace NEWMODULE with the name of your module and delete this line)

/**
 * These defines exist simply for easy of referencing them in functions.
 */
define('WWMOODLE_WEBWORK_DB', $CFG->wwmoodle_webwork_db);
/**
 * The location of the webwork courses directory.
 * The user that this script executes as must have write access to this directory.
 * This must end in a /!
 */
define('WWMOODLE_WEBWORK_COURSES', $CFG->wwmoodle_webwork_courses);
/**
 * The URL of the WeBWorK install.
 */
define('WWMOODLE_WEBWORK_URL', $CFG->wwmoodle_set_webwork_url);

/**
 * Maps a course ID number to it's (sanatized) shortname.
 * @param int $iCourseID The ID of the course.
 * @return string The shortname of the course, with unsafe characters removed. If the courseID is not found, null is returned.
 */
function wwmoodle_courseIdToShortName($iCourseId) {
	$c = get_record('course', 'id', $iCourseId);
	if( ! $c ) {
		return null;
	}
	$shortname = preg_replace("/[^a-zA-Z0-9]/", "", $c->shortname);
	return $shortname;
}

/**
 * Creates the needed course directory structure.
 * @param string $sCourseName The name of the course being added. (This is the shortname of the course).
 * @return mixed In the event of an error, a human readable string is returned. Otherwise true is returned.
 * @pre The course directories have not yet been setup.
 * @post The course directories exist.
 */
function wwmoodle_createCourseDirs($sCourseName) {
	if( !mkdir(WWMOODLE_WEBWORK_COURSES.$sCourseName) ) {
		return "Failed to create the course directory in WebWorK!";
	}
	if( !mkdir(WWMOODLE_WEBWORK_COURSES.$sCourseName."/DATA") ) {
		return "Failed to create the DATA subdirectory!";
	}
	if( !mkdir(WWMOODLE_WEBWORK_COURSES.$sCourseName."/DATA/.auth") ) {
		return "Failed to create the DATA/.auth subdirectory!";
	}
	if( !mkdir(WWMOODLE_WEBWORK_COURSES.$sCourseName."/html") ) {
		return "Failed to create the html subdirectory!";
	}
	if( !mkdir(WWMOODLE_WEBWORK_COURSES.$sCourseName."/html/images") ) {
		return "Failed to create the html/images subdirectory!";
	}
	if( !mkdir(WWMOODLE_WEBWORK_COURSES.$sCourseName."/html/tmp") ) {
		return "Failed to create the html/tmp subdirectory!";
	}
	if( !mkdir(WWMOODLE_WEBWORK_COURSES.$sCourseName."/logs") ) {
		return "Failed to create the logs subdirectory!";
	}
	if( !mkdir(WWMOODLE_WEBWORK_COURSES.$sCourseName."/scoring") ) {
		return "Failed to create the scoring subdirectory!";
	}
	if( !mkdir(WWMOODLE_WEBWORK_COURSES.$sCourseName."/templates") ) {
		return "Failed to create the templates subdirectory!";
	}
	if( !mkdir(WWMOODLE_WEBWORK_COURSES.$sCourseName."/templates/email") ) {
		return "Failed to create the templates/email subdirectory!";
	}
	if( !mkdir(WWMOODLE_WEBWORK_COURSES.$sCourseName."/templates/macros") ) {
		return "Failed to create the templates/macros subdirectory!";
	}
	return true;
}

/**
 * Generates and writes the course configuration file for the WeBWorK course.
 * @param string $sCourseName The shortname of the course being configured.
 * @param string $sAlloweRecipients The list of recipients allowed.
 * @return mixed In the event of an error a human readable string is returned, otherwise TRUE is returned.
 * @post The WeBWorK course configuration has been written.
 * @pre The WeBWorK course directory structure has been set up.
 */
function wwmoodle_configureCourse($sCourseName, $sAllowedRecipients) {
	// split up the list of allowed Recipients into an array so we can ensure it won't
	// screw up the WeBWorK configuration too badly
	$aRecipients = split("/\s*,\s*/", $sAllowedRecipients); // 0+ whitespace comma 0+ whitespace
	$wwmoodle_allowedRecipients = "";
	foreach( $aRecipients as $r ) {
		// clean out any characters that shouldn't be in an email address:
		$r = preg_replace("/[^a-zA-Z0-9@.]/", "", $r);
		$wwmoodle_allowedRecipients .= "\"$r\", ";
	}
	// perl literal array declarations can have trailing ,s
	ob_start();
	include("course.conf.template");
	$sConf = ob_get_contents();
	ob_end_clean();
	
	// now write the config file:
	if( FALSE === ($fh = fopen(WWMOODLE_WEBWORK_COURSES.$sCourseName."/course.conf", 'w')) ) {
		return "Unable to open course.conf for writing!";
	}
	if( FALSE === fwrite($fh, $sConf) ) {
		fclose($fh);
		return "Unable to write course.conf!";
	}
	if( FALSE === fclose($fh) ) {
		return "Unable to close course.conf!";
	}
	return true;
}

/**
 * Creates the database tables that webwork needs for the new course.
 * @param string $sCourseName The name of the course to create.
 * @return mixed In the event of an error, a human readable string is returned. Otherwise true is returned.
 */
function wwmoodle_createCourseTables($sCourseName) {
    global $db;
	//$db =& DB::connect(WWMOODLE_WEBWORK_DB);
// 	if( DB::isError($db) ) {
// 		return "Failed to connect to database.";
// 	}
	$sKey = "CREATE TABLE `{$sCourseName}_key` (`user_id` text, `key_not_a_keyword` text, `timestamp` text, KEY `user_id` (`user_id`(16)))";
	$sProblem = "CREATE TABLE `{$sCourseName}_problem` (`set_id` text, `problem_id` text, `source_file` text, `value` text, `max_attempts` text, KEY `set_id` (`set_id`(16),`problem_id`(16)), KEY `problem_id` (`problem_id`(16)))";
	$sProblemUser = "CREATE TABLE `{$sCourseName}_problem_user` (`user_id` text, `set_id` text, `problem_id` text, `source_file` text, `value` text, `max_attempts` text, `problem_seed` text, `status` text, `attempted` text, `last_answer` text, `num_correct` text, `num_incorrect` text, KEY `user_id` (`user_id`(16),`set_id`(16),`problem_id`(16)), KEY `set_id` (`set_id`(16),`problem_id`(16)), KEY `problem_id` (`problem_id`(16)))";
	//$sSet = "CREATE TABLE `{$sCourseName}_set` (`set_id` text, `set_header` text, `hardcopy_header` text, `open_date` text, `due_date` text, `answer_date` text, `published` text, KEY `set_id` (`set_id`(16)))";
	$sSet = "CREATE TABLE `{$sCourseName}_set` (`set_id` text, `set_header` text, `hardcopy_header` text, `open_date` text, `due_date` text, `answer_date` text, `published` text, `assignment_type` text, `attempts_per_version` int(11), `time_interval` int(11), `versions_per_interval` int(11), `version_time_limit` int(11), `version_creation_time` bigint(20), `problem_randorder` int(11), `version_last_attempt_time` bigint(20), KEY `set_id` (`set_id`(16)))";
	
	$sSetUser = "CREATE TABLE `{$sCourseName}_set_user` (`user_id` text, `set_id` text, `psvn` int(11) NOT NULL auto_increment, `set_header` text, `hardcopy_header` text, `open_date` text, `due_date` text, `answer_date` text, `published` text, `assignment_type` text, `attempts_per_version` int(11), `time_interval` int(11), `versions_per_interval` int(11), `version_time_limit` int(11), `version_creation_time` bigint(20), `problem_randorder` int(11), `version_last_attempt_time` bigint(20), PRIMARY KEY  (`psvn`), KEY `user_id` (`user_id`(16),`set_id`(16)), KEY `set_id` (`set_id`(16)))";
	if( $db->ErrorNo($db->query($sKey)) ) {
		return $db->ErrorMsg()." Failed to create table {$sCourseName}_key!";
	}
	if( $db->ErrorNo($db->query($sProblem)) ) {
		return $db->ErrorMsg()." Failed to create table {$sCourseName}_problem!";
	}
	if( $db->ErrorNo($db->query($sProblemUser)) ) {
		return $db->ErrorMsg()." Failed to create table {$sCourseName}_problem_user!";
	}
	if( $db->ErrorNo($db->query($sSet)) ) {
		return $db->ErrorMsg()." Failed to create table {$sCourseName}_set!";
	}
	if( $db->ErrorNo($db->query($sSetUser)) ) {
		return $db->ErrorMsg()." Failed to create table {$sCourseName}_set_user!";
	}

	//$db->disconnect();
	return true;
}

/**
 * Deletes the specified WeBWorK course.
 * @pre The course exists.
 * @post The course, and all associated files and database tables, does not exist
 * @param string $sCourseName The name of the course to delete.
 */
function wwmoodle_deleteCourse($sCourseName) {
	global $db;

	$sKey = "DROP TABLE `${sCourseName}_key`";
	$sProblem = "DROP TABLE `${sCourseName}_problem`";
	$sProblemUser = "DROP TABLE `${sCourseName}_problem_user`";
	$sSet = "DROP TABLE `${sCourseName}_set`";
	$sSetUser = "DROP TABLE `${sSetUser}_set_user`";
	if( $db->ErrorNo($db->query($sKey)) ) {
		return "Failed to drop table ${sCourseName}_key";
	}
	if( $db->ErrorNo($db->query($sProblem)) ) {
		return $db->ErrorMsg()." Failed to drop table ${sCourseName}_problem";
	}
	if($db->ErrorNo($db->query($sProblemUser)) ) {
		return $db->ErrorMsg()." Failed to drop table ${sCourseName}_problem_user";
	}
	if( $db->ErrorNo($db->query($sSet)) ) {
		return $db->ErrorMsg()." Failed to drop table ${sCourseName}_set";
	}
	if( $db->ErrorNo($db->query($sSetUser)) ) {
		return $db->ErrorMsg()." Failed to drop table ${sCourseName}_set_user";
	}
	//$db->disconnect();
	// now kill the directory:
	wwmoodle_rmdirr(WWMOODLE_WEBWORK_COURSES.$sCourseName);
	return true;
}

/**
 * Recursively removes $sDir.
 * @param $sDir The directory to remove.
 * @pre $sDir exists and is a directory.
 * @post $sDir does not.
 * Note: When a symlink is encountered the link is removed, never traversed.
 */
function wwmoodle_rmdirr($sDir) {
	if( !file_exists($sDir) || !is_dir($sDir) ) {
		return;
	}
	// process each file in the directory:
	$d = dir($sDir);
	while( FALSE !== ($e = $d->read()) ) {
		// skip '.' and '..'
		if( '.' == $e || '..' == $e ) {
			continue;
		}
		$sPath = realpath($sDir."/$e");
		// if it's a directory and not symlink, recurse:
		if( is_dir($sPath) && !is_link($sPath) ) {
			wwmoodle_rmdirr($sPath);
		}
		else {
			// just try to unlink it:
			unlink($sPath);
		}
	}
	$d->close();
	// remove the directory itself:
	rmdir($sDir);
}

function wwmoodle_add_instance($wwmoodle) {
/// Given an object containing all the necessary data, 
/// (defined by the form in mod.html) this function 
/// will create a new instance and return the id number 
/// of the new instance.

	// ensure there is no other bridge for this course.
	$oCourse = get_record("course", "id", $wwmoodle->course);
	$aMods = get_all_instances_in_course("wwmoodle", $oCourse);
	if( count($aMods) > 0 ) {
		error(get_string("alreadyHasBridge", "wwmoodle"));
	}
	
	$wwmoodle->timemodified = time();
	//FIXME
	// If the subroutine wwmoodle_createCourseTables is run
	// then the call to insert_record results in an error
	// the routine _add... in adodb-lib.inc.php fails to find
	// the number of columns in the table. 
	// also the moodle course can't be found
	// if the subroutine is not called then insert_record works
	// what is it about wwmoodle_createCourseTables that appears to interfere
	// with insert_record
	//MEG
	
	$wwmoodle->coursename = wwmoodle_courseIdToShortName($wwmoodle->course);
	if (! isset($wwmoodle->coursename) ){
	     $wwmoodle->coursename = "foo";
	}
	$result = insert_record("wwmoodle",$wwmoodle);
	$fh = fopen("/home/gage/moodle_debug", "w");
	fwrite($fh,  "wwmoodle structure1\n");
	$struct = print_r($wwmoodle, true);
	fwrite($fh, $struct);
	fwrite($fh, "\nresult is ");
	fwrite($fh, $result);
	fwrite($fh, "\n");
	fclose($fh);
	//MEG
		
	// create the course in WeBWorK if desired:
	if( $wwmoodle->createWWc ) {
		$courseName = wwmoodle_courseIdToShortName($wwmoodle->course);

		$mRes = wwmoodle_createCourseDirs($courseName);
		if( is_string($mRes) ) {
			error($mRes);
		}
		$mRes = wwmoodle_configureCourse($courseName, $wwmoodle->allowedRecipients);
		if( is_string($mRes) ) {
			error($mRes);
		}
		$mRes = wwmoodle_createCourseTables($courseName);
		if( is_string($mRes) ) {
			error($mRes);
		}

	}

	return $result;
}


function wwmoodle_update_instance($wwmoodle) {
/// Given an object containing all the necessary data, 
/// (defined by the form in mod.html) this function 
/// will update an existing instance with new data.

		$wwmoodle->timemodified = time();
		$wwmoodle->id = $wwmoodle->instance;

		// rewrite the course configuration with the (possibly) new email addresses:
		$mRes = wwmoodle_configureCourse(wwmoodle_courseIdToShortName($wwmoodle->course), $wwmoodle->allowedRecipients);
		if( is_string($mRes) ) {
			error($mRes);
		}

		return update_record("wwmoodle", $wwmoodle);
}


function wwmoodle_delete_instance($id) {
/// Given an ID of an instance of this module, 
/// this function will permanently delete the instance 
/// and any data that depends on it.  
	global $CFG;
	if (! $wwmoodle = get_record("wwmoodle", "id", "$id")) {
		return false;
	}

	$result = true;

	# Delete any dependent records here #
	if( "on" == $CFG->wwmoodle_webwork_delete ) {
		$mRes = wwmoodle_deleteCourse(wwmmoodle_courseIdToShortName($id));
		if( is_string($mRes) ) {
			error($mRes);
		}
	}

	if (! delete_records("wwmoodle", "id", "$wwmoodle->id")) {
		$result = false;
	}

	return $result;
}

function wwmoodle_user_outline($course, $user, $mod, $wwmoodle) {
/// Return a small object with summary information about what a 
/// user has done with a given particular instance of this module
/// Used for user activity reports.
/// $return->time = the time they did it
/// $return->info = a short text description

	return null;
}

function wwmoodle_user_complete($course, $user, $mod, $wwmoodle) {
/// Print a detailed representation of what a  user has done with 
/// a given particular instance of this module, for user activity reports.

	return true;
}

function wwmoodle_print_recent_activity($course, $isteacher, $timestart) {
/// Given a course and a time, this module should find recent activity 
/// that has occurred in NEWMODULE activities and print it out. 
/// Return true if there was output, or false is there was none.

	global $CFG;

	return false;  //  True if anything was printed, otherwise false 
}

function wwmoodle_cron () {
/// Function to be run periodically according to the moodle cron
/// This function searches for things that need to be done, such 
/// as sending out mail, toggling flags etc ... 

	global $CFG;

	return true;
}

function wwmoodle_grades($wwmoodleid) {
/// Must return an array of grades for a given instance of this module, 
/// indexed by user.  It also returns a maximum allowed grade.
///
///    $return->grades = array of grades;
///    $return->maxgrade = maximum allowed grade;
///
///    return $return;
	
	return NULL;
}

function wwmoodle_get_participants($wwmoodleid) {
//Must return an array of user records (all data) who are participants
//for a given instance of NEWMODULE. Must include every user involved
//in the instance, independient of his role (student, teacher, admin...)
//See other modules as example.
	$oMod = get_record("wwmoodle", "id", $wwmoodleid);
	if( ! $oMod ) {
		return array();
	}
	return get_course_users($oMod->course);
}

function wwmoodle_scale_used ($wwmoodleid,$scaleid) {
//This function returns if a scale is being used by one NEWMODULE
//it it has support for grading and scales. Commented code should be
//modified if necessary. See forum, glossary or journal modules
//as reference.
	 
	$return = false;

	//$rec = get_record("NEWMODULE","id","$NEWMODULEid","scale","-$scaleid");
	//
	//if (!empty($rec)  && !empty($scaleid)) {
	//    $return = true;
	//}
	
	return $return;
}

//////////////////////////////////////////////////////////////////////////////////////
/// Any other NEWMODULE functions go here.  Each of them must have a name that 
/// starts with NEWMODULE_


?>
