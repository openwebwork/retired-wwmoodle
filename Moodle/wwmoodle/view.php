<?php
// $Id: view.php,v 1.1.1.1 2006-06-17 21:45:25 sh002i Exp $

/// This page prints a particular instance of NEWMODULE
/// (Replace NEWMODULE with the name of your module)

require_once("../../config.php");
require_once("lib.php");

optional_variable($id);    // Course Module ID, or
optional_variable($a);     // NEWMODULE ID

if ($id) {
	if (! $cm = get_record("course_modules", "id", $id)) {
		error("Course Module ID was incorrect");
	}
	if (! $course = get_record("course", "id", $cm->course)) {
		error("Course is misconfigured");
	}
	if (! $wwmoodle = get_record("wwmoodle", "id", $cm->instance)) {
		error("Course module is incorrect");
	}
}
else {
	if (! $wwmoodle = get_record("wwmoodle", "id", $a)) {
		error("Course module is incorrect");
	}
	if (! $course = get_record("course", "id", $wwmoodle->course)) {
		error("Course is misconfigured");
	}
	if (! $cm = get_coursemodule_from_instance("wwmoodle", $wwmoodle->id, $course->id)) {
		error("Course Module ID was incorrect");
	}
}

require_login($course->id);

add_to_log($course->id, "wwmoodle", "view", "view.php?id=$cm->id", "$wwmoodle->id");

/// Print the page header

if ($course->category) {
	$navigation = "<a href=\"../../course/view.php?id=$course->id\">$course->shortname</a> ->";
}

$strwwmoodles = get_string("modulenameplural", "wwmoodle");
$strwwmoodle  = get_string("modulename", "wwmoodle");

print_header("$course->shortname: $wwmoodle->name", "$course->fullname", "$navigation <a href='index.php?id=$course->id'>$strwwmoodles</a> -> $wwmoodle->name", "", "", true, update_module_button($cm->id, $course->id, $strwwmoodle), navmenu($course, $cm));

/// Print the main part of the page
if( isteacher($course->id) ) {
	print("<p style='font-size: smaller; color: #aaa; text-align: center;'><a style='color: #aaa;' href='".WWMOODLE_WEBWORK_URL."/$course->shortname'>".get_string("goToWeBWorK", "wwmoodle")."</a></p>");
}
// TODO: Ensure table is printed with style consistant with rest of moodle
print("<table id='setList'>\n");
print("\t<caption>".get_string("WeBWorKSets", "wwmoodle")."</caption>");
print("\t<tr>\n");
print("\t\t<th>".get_string("setName", "wwmoodle")."</th><th>".get_string("setId", "wwmoodle")."</th>\n");
print("\t</tr>\n");

$aSets = get_records("wwmoodle_set", "course", $course->id);
if( is_array($aSets) ) {
	foreach( $aSets as $s ) {
		print("\t<tr>\n");
		print("\t\t<td>$s->name</td><td><a href='".WWMOODLE_WEBWORK_URL."/$course->shortname/$s->set_id'>$s->set_id</a></td>");
		print("\t</tr>\n");
	}
}
else {
	print("\t<tr>\n");
	print("\t\t<td colspan='2'>".get_string("noSets", "wwmoodle")."</td>\n");
	print("\t</tr>\n");
}
print("</table>\n");

/// Finish the page
print_footer($course);

?>
