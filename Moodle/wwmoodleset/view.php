<?php
// $Id: view.php,v 1.2 2006-06-24 01:47:01 sh002i Exp $

/// This page prints a particular instance of wwmoodleset
/// (Replace wwmoodleset with the name of your module)

require_once("../../config.php");
require_once("lib.php");

    $id = optional_param('id', 0, PARAM_INT); // Course Module ID, or
    $a  = optional_param('a', 0, PARAM_INT);  // NEWMODULE ID

if ($id) {
	if (! $cm = get_record("course_modules", "id", $id)) {
		error("Course Module ID was incorrect");
	}
	
	if (! $course = get_record("course", "id", $cm->course)) {
		error("Course is misconfigured");
	}
	
	if (! $wwmoodleset = get_record("wwmoodleset", "id", $cm->instance)) {
		error("Course module is incorrect");
	}
} else {
	if (! $wwmoodleset = get_record("wwmoodleset", "id", $a)) {
		error("Course module is incorrect");
	}
	if (! $course = get_record("course", "id", $wwmoodleset->course)) {
		error("Course is misconfigured");
	}
	if (! $cm = get_coursemodule_from_instance("wwmoodleset", $wwmoodleset->id, $course->id)) {
		error("Course Module ID was incorrect");
	}
}

require_login($course->id);

add_to_log($course->id, "wwmoodleset", "view", "view.php?id=$cm->id", "$wwmoodleset->id");

/// Print the page header

if ($course->category) {
	$navigation = "<a href=\"../../course/view.php?id=$course->id\">$course->shortname</a> ->";
}

$strwwmoodlesets = get_string("modulenameplural", "wwmoodleset");
$strwwmoodleset  = get_string("modulename", "wwmoodleset");

print_header("$course->shortname: $wwmoodleset->name", "$course->fullname", "$navigation <a href='index.php?id=$course->id'>$strwwmoodlesets</a> -> $wwmoodleset->name", "", "", true, update_module_button($cm->id, $course->id, $strwwmoodleset), navmenu($course, $cm));

/// Print the main part of the page

$sSetLink = wwmoodleset_linkToSet($wwmoodleset->set_id, wwmoodleset_courseIdToShortName($wwmoodleset->course));
print("<p style='font-size: smaller; color: #aaa;'>" . get_string("iframeNoShow-1", "wwmoodleset") . "<a href='$sSetLink'>" . get_string("iframeNoShow-2", "wwmoodleset") . "</a>.</p>\n");
print("<iframe src='$sSetLink' align='center' width='".$CFG->wwmoodleset_iframe_width."' height='".$CFG->wwmoodleset_iframe_height."'>" . get_string("iframeNoShow-1", "wwmoodleset") . "<a href='$sSetLink'>" . get_string("iframeNoShow-2", "wwmoodleset") . "</a>.</iframe>\n");

/// Finish the page
print_footer($course);

?>
