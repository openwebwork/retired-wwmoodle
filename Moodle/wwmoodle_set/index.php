<?php
// $Id: index.php,v 1.1.1.1 2006-06-17 21:45:25 sh002i Exp $

/// This page lists all the instances of wwmoodle_set in a particular course
/// Replace wwmoodle_set with the name of your module

require_once("../../config.php");
require_once("lib.php");

require_variable($id);   // course

if (! $course = get_record("course", "id", $id)) {
	error("Course ID is incorrect");
}

require_login($course->id);

add_to_log($course->id, "wwmoodle_set", "view all", "index.php?id=$course->id", "");


/// Get all required strings

$strwwmoodle_sets = get_string("modulenameplural", "wwmoodle_set");
$strwwmoodle_set  = get_string("modulename", "wwmoodle_set");


/// Print the header

if ($course->category) {
	$navigation = "<a href=\"../../course/view.php?id=$course->id\">$course->shortname</a> »";
}

print_header("$course->shortname: $strwwmoodle_sets", "$course->fullname", "$navigation $strwwmoodle_sets", "", "", true, "", navmenu($course));

/// Get all the appropriate data

if (! $wwmoodle_sets = get_all_instances_in_course("wwmoodle_set", $course)) {
	notice("There are no $strwwmoodle_sets", "../../course/view.php?id=$course->id");
	die;
}

/// Print the list of instances (your module will probably extend this)

$timenow = time();
$strname  = get_string("name");
$strweek  = get_string("week");
$strtopic  = get_string("topic");

$strOpenDate = get_string("openDate", "wwmoodle_set");
$strDueDate = get_string("dueDate", "wwmoodle_set");

if ($course->format == "weeks") {
	$table->head  = array ($strweek, $strname, $strOpenDate, $strDueDate);
	$table->align = array ("center", "left", "left", "left");
} else if ($course->format == "topics") {
	$table->head  = array ($strtopic, $strname, $strOpenDate, $strDueDate);
	$table->align = array ("center", "left", "left", "left", "left", "left");
} else {
	$table->head  = array ($strname, $strOpenDate, $strDueDate);
	$table->align = array ("left", "left", "left", "left", "left");
}

foreach ($wwmoodle_sets as $wwmoodle_set) {
	// grab specific info for this set:
	$aSetInfo = wwmoodle_set_getSetInfo($wwmoodle_set->set_id, wwmoodle_set_courseIdToShortName($course->id));
	if (!$wwmoodle_set->visible) {
		//Show dimmed if the mod is hidden
		$link = "<a class=\"dimmed\" href=\"view.php?id=$wwmoodle_set->coursemodule\">$wwmoodle_set->name</a>";
	} else {
		//Show normal if the mod is visible
		$link = "<a href=\"view.php?id=$wwmoodle_set->coursemodule\">$wwmoodle_set->name</a>";
	}

	if ($course->format == "weeks" or $course->format == "topics") {
		$table->data[] = array ($wwmoodle_set->section, $link, strftime("%c", $aSetInfo['open_date']), strftime("%c", $aSetInfo['due_date']));
	} else {
		$table->data[] = array ($link, strftime("%c", $aSetInfo['open_date']), strftime("%c", $aSetInfo['due_date']));
	}
}

echo "<br />";

print_table($table);

/// Finish the page

print_footer($course);

?>
