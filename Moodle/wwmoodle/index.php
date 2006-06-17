<?php
// $Id: index.php,v 1.1.1.1 2006-06-17 21:45:25 sh002i Exp $

/// This page lists all the instances of NEWMODULE in a particular course
/// Replace NEWMODULE with the name of your module

require_once("../../config.php");
require_once("lib.php");

require_variable($id);   // course

if (! $course = get_record("course", "id", $id)) {
	error("Course ID is incorrect");
}

require_login($course->id);

add_to_log($course->id, "wwmoodle", "view all", "index.php?id=$course->id", "");


/// Get all required strings

$strwwmoodles = get_string("modulenameplural", "wwmoodle");
$strwwmoodle  = get_string("modulename", "wwmoodle");


/// Print the header

if ($course->category) {
	$navigation = "<a href=\"../../course/view.php?id=$course->id\">$course->shortname</a> »";
}

print_header("$course->shortname: $strwwmoodles", "$course->fullname", "$navigation $strwwmoodles", "", "", true, "", navmenu($course));

/// Get all the appropriate data

if (! $wwmoodles = get_all_instances_in_course("wwmoodle", $course)) {
		notice("There are no $strwwmoodles", "../../course/view.php?id=$course->id");
		die;
}

/// Print the list of instances (your module will probably extend this)

$timenow = time();
$strname  = get_string("name");
$strweek  = get_string("week");
$strtopic  = get_string("topic");

if ($course->format == "weeks") {
		$table->head  = array ($strweek, $strname);
		$table->align = array ("center", "left");
} else if ($course->format == "topics") {
		$table->head  = array ($strtopic, $strname);
		$table->align = array ("center", "left", "left", "left");
} else {
		$table->head  = array ($strname);
		$table->align = array ("left", "left", "left");
}

foreach ($wwmoodles as $wwmoodle) {
		if (!$wwmoodle->visible) {
				//Show dimmed if the mod is hidden
				$link = "<a class=\"dimmed\" href=\"view.php?id=$wwmoodle->coursemodule\">$wwmoodle->name</A>";
		} else {
				//Show normal if the mod is visible
				$link = "<a href=\"view.php?id=$wwmoodle->coursemodule\">$wwmoodle->name</A>";
		}

		if ($course->format == "weeks" or $course->format == "topics") {
				$table->data[] = array ($wwmoodle->section, $link);
		} else {
				$table->data[] = array ($link);
		}
}

echo "<br />";

print_table($table);

/// Finish the page

print_footer($course);

?>
