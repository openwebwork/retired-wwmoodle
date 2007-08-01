<?php
// $Id: view.php,v 1.6 2007-08-01 03:08:09 mleventi Exp $

/// This page prints a particular instance of wwassignment
/// (Replace wwassignment with the name of your module)


require_once("../../config.php");
require_once("lib.php");

$id = optional_param('id', 0, PARAM_INT); // Course Module ID, or
$a  = optional_param('a', 0, PARAM_INT);  // NEWMODULE ID

if($id) {
    if (! $cm = get_record("course_modules", "id", $id)) {
        error("Course Module ID was incorrect");
    }
    
    if (! $course = get_record("course", "id", $cm->course)) {
        error("Course is misconfigured");
    }
    if (! $wwassignment = get_record("wwassignment", "id", $cm->instance)) {
        error("Course module is incorrect");
    }
} else {
    
    if (! $wwassignment = get_record("wwassignment", "id", $a)) {
        error("Course module is incorrect");
    }
    if (! $course = get_record("course", "id", $wwassignment->course)) {
        error("Course is misconfigured");
    }
    if (! $cm = get_coursemodule_from_instance("wwassignment", $wwassignment->id, $course->id)) {
        error("Course Module ID was incorrect");
    }  
}
//catch the guests
global $USER;
if($USER->username == 'guest') {
    print_error('Guests cannot view WeBWorK Problem Sets');
}
$courseid = $course->id;

require_login($course->id);

$webworksetlink = wwassignment_view_link($wwassignment->id);

add_to_log($course->id, "wwassignment", "view", "view.php?id=$cm->id", "$wwassignment->id");

/// Print the page header

if ($course->category) {
    $navigation = "<a href=\"../../course/view.php?id=$course->id\">$course->shortname</a> ->";
}

$strwwassignments = get_string("modulenameplural", "wwassignment");
$strwwassignment  = get_string("modulename", "wwassignment");

print_header("$course->shortname: $wwassignment->name", "$course->fullname", "$navigation <a href='index.php?id=$course->id'>$strwwassignments</a> -> $wwassignment->name", "", "", true, update_module_button($cm->id, $course->id, $strwwassignment), navmenu($course, $cm));
/// Print the main part of the page



print("<p style='font-size: smaller; color: #aaa;'>" . get_string("iframeNoShow-1", "wwassignment")
      . "<a href='$sSetLink'>" . get_string("iframeNoShow-2", "wwassignment") . "</a>.</p>\n");
print("<p align='center'><iframe id='wwPage' src='$webworksetlink' frameborder='1' "
      . "width='".$CFG->wwassignment_iframewidth."' "
      . "height='".$CFG->wwassignment_iframeheight."'>"
      . get_string("iframeNoShow-1", "wwassignment") . "<a href='$webworksetlink'>" . get_string("iframeNoShow-2", "wwassignment")
      . "</a>.</iframe></p>\n");

print("<script>ww.Init(".isteacher($course->id).")</script>");


/// Finish the page
print_footer($course);

?>
