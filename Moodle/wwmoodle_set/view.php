<?php
// $Id: view.php,v 1.1.1.1 2006-06-17 21:45:25 sh002i Exp $

/// This page prints a particular instance of wwmoodle_set
/// (Replace wwmoodle_set with the name of your module)

require_once("../../config.php");
require_once("lib.php");

optional_variable($id);    // Course Module ID, or
optional_variable($a);     // wwmoodle_set ID

if ($id) {
	if (! $cm = get_record("course_modules", "id", $id)) {
		error("Course Module ID was incorrect");
	}
	
	if (! $course = get_record("course", "id", $cm->course)) {
		error("Course is misconfigured");
	}
	
	if (! $wwmoodle_set = get_record("wwmoodle_set", "id", $cm->instance)) {
		error("Course module is incorrect");
	}
} else {
	if (! $wwmoodle_set = get_record("wwmoodle_set", "id", $a)) {
		error("Course module is incorrect");
	}
	if (! $course = get_record("course", "id", $wwmoodle_set->course)) {
		error("Course is misconfigured");
	}
	if (! $cm = get_coursemodule_from_instance("wwmoodle_set", $wwmoodle_set->id, $course->id)) {
		error("Course Module ID was incorrect");
	}
}

require_login($course->id);

add_to_log($course->id, "wwmoodle_set", "view", "view.php?id=$cm->id", "$wwmoodle_set->id");

/// Print the page header

if ($course->category) {
	$navigation = "<a href=\"../../course/view.php?id=$course->id\">$course->shortname</a> ->";
}

$strwwmoodle_sets = get_string("modulenameplural", "wwmoodle_set");
$strwwmoodle_set  = get_string("modulename", "wwmoodle_set");

print_header("$course->shortname: $wwmoodle_set->name", "$course->fullname", "$navigation <a href='index.php?id=$course->id'>$strwwmoodle_sets</a> -> $wwmoodle_set->name", "", "", true, update_module_button($cm->id, $course->id, $strwwmoodle_set), navmenu($course, $cm));

/// Print the main part of the page

$sSetLink = wwmoodle_set_linkToSet($wwmoodle_set->set_id, wwmoodle_set_courseIdToShortName($wwmoodle_set->course));
print("<p style='font-size: smaller; color: #aaa;'>" . get_string("iframeNoShow-1", "wwmoodle_set") . "<a href='$sSetLink'>" . get_string("iframeNoShow-2", "wwmoodle_set") . "</a>.</p>\n");
print("<iframe src='$sSetLink' align='center' width='".$CFG->wwmoodle_set_iframe_width."' height='".$CFG->wwmoodle_set_iframe_height."'>" . get_string("iframeNoShow-1", "wwmoodle_set") . "<a href='$sSetLink'>" . get_string("iframeNoShow-2", "wwmoodle_set") . "</a>.</iframe>\n");

/// Finish the page
print_footer($course);

?>
