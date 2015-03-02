<?php
/**
* @desc This page lists all the instances of wwassignments within a particular course.
*/

require_once("../../config.php");
require_once("locallib.php");

//get the course ID from GET line
$id = required_param('id', PARAM_INT);

//check this course exists
if (!$course = get_record("course", "id", $id)) {
    error("Course ID is incorrect");
}
 
//force login   
require_login($course->id);
add_to_log($course->id, "wwassignment", "view all", "index.php?id=$course->id", "");

//Get all required strings
$strwwassignments = get_string("modulenameplural", "wwassignment");
$strwwassignment  = get_string("modulename", "wwassignment");

//Print the header
if ($course->category) {
    $navigation = "<a href=\"../../course/view.php?id=$course->id\">$course->shortname</a> »";
}
print_header("$course->shortname: $strwwassignments", "$course->fullname", "$navigation $strwwassignments", "", "", true, "", navmenu($course));

//Get all the appropriate data
if (!$wwassignments = get_all_instances_in_course("wwassignment", $course)) {
    notice("There are no $strwwassignments", "../../course/view.php?id=$course->id");
    die;
}

//Print the list of instances (your module will probably extend this)
$timenow = time();
$strname  = get_string("name");
$strweek  = get_string("week");
$strtopic  = get_string("topic");
$strdescription = get_string('description');
$stropendate = get_string("open_date", "wwassignment");
$strduedate = get_string("due_date", "wwassignment");
$strtotalpoints = get_string("total_points","wwassignment");



if ($course->format == "weeks") {
    $table->head  = array ($strweek, $strname,$strdescription, $stropendate, $strduedate, $strtotalpoints);
    $table->align = array ("center", "left", "left", "left", "left");
} else if ($course->format == "topics") {
    $table->head  = array ($strtopic, $strname,$strdescription, $stropendate, $strduedate);
    $table->align = array ("center", "left", "left", "left", "left", "left", "left");
} else {
    $table->head  = array ($strname,$strdescription, $stropendate, $strduedate);
    $table->align = array ("left", "left", "left", "left", "left", "left");
}

$wwclient = new wwassignment_client();
$wwcoursename = _wwassignment_mapped_course($COURSE->id,false);

foreach ($wwassignments as $wwassignment) {
    // grab specific info for this set:
    if(isset($wwassignment)) {
        $wwsetname = $wwassignment->webwork_set;
        $wwsetinfo = $wwclient->get_assignment_data($wwcoursename,$wwsetname,false);
        
        if (!$wwassignment->visible) {
            //Show dimmed if the mod is hidden
            $link = "<a class=\"dimmed\" href=\"view.php?id=$wwassignment->coursemodule\">$wwassignment->name</a>";
        } else {
            //Show normal if the mod is visible
            $link = "<a href=\"view.php?id=$wwassignment->coursemodule\">$wwassignment->name</a>";
        }
        if ($course->format == "weeks" or $course->format == "topics") {
            $totalpoints = $wwclient->get_max_grade($wwcoursename, $wwsetname,false);
            $table->data[] = array ($wwassignment->section,  $link, $wwassignment->description, strftime("%c", $wwsetinfo['open_date']), strftime("%c", $wwsetinfo['due_date']), $totalpoints);
        } else {
            $table->data[] = array ($link, $wwassignment->description, strftime("%c", $wwsetinfo['open_date']), strftime("%c", $wwsetinfo['due_date']));
        }
    }
}

echo "<br />";
    
print_table($table);

/*if( isteacher($course->id) ) {
    $wwusername = $USER->username;
    
    $wwlink = _wwassignment_link_to_instructor_auto_login($wwcoursename,$wwusername,)
    print("<p style='font-size: smaller; color: #aaa; text-align: center;'><a style='color: #666;text-decoration:underline' href='".WWASSIGNMENT_WEBWORK_URL."/$course->shortname/instructor' target='_webwork_edit'>".get_string("go_to_webwork", "wwassignment")."</a></p>");
}*/

/// Finish the page
print_footer($course);

?>
