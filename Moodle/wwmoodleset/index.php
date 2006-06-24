<?php
// $Id: index.php,v 1.3 2006-06-24 13:57:05 gage Exp $


/// This page lists all the instances of wwmoodleset in a particular course
/// Replace wwmoodleset with the name of your module

    require_once("../../config.php");
    require_once("lib.php");
    
        $id = required_param('id', PARAM_INT);   // course
    
    if (! $course = get_record("course", "id", $id)) {
        error("Course ID is incorrect");
    }
    
    require_login($course->id);
    
    add_to_log($course->id, "wwmoodleset", "view all", "index.php?id=$course->id", "");

     
/// Get all required strings

    $strwwmoodlesets = get_string("modulenameplural", "wwmoodleset");
    $strwwmoodleset  = get_string("modulename", "wwmoodleset");


/// Print the header

    if ($course->category) {
        $navigation = "<a href=\"../../course/view.php?id=$course->id\">$course->shortname</a> »";
    }

print_header("$course->shortname: $strwwmoodlesets", "$course->fullname", "$navigation $strwwmoodlesets", "", "", true, "", navmenu($course));

/// Get all the appropriate data

    if (! $wwmoodlesets = get_all_instances_in_course("wwmoodleset", $course)) {
        notice("There are no $strwwmoodlesets", "../../course/view.php?id=$course->id");
        die;
    }
    
/// Print the list of instances (your module will probably extend this)
    
    $timenow = time();
    $strname  = get_string("name");
    $strweek  = get_string("week");
    $strtopic  = get_string("topic");
    
    $strOpenDate = get_string("openDate", "wwmoodleset");
    $strDueDate = get_string("dueDate", "wwmoodleset");
    
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
    
    foreach ($wwmoodlesets as $wwmoodleset) {
        // grab specific info for this set:
        $aSetInfo = _wwrpc_getSetInfo($wwmoodleset->set_id, wwmoodleset_courseIdToShortName($course->id));
        if (!$wwmoodleset->visible) {
            //Show dimmed if the mod is hidden
            $link = "<a class=\"dimmed\" href=\"view.php?id=$wwmoodleset->coursemodule\">$wwmoodleset->name</a>";
        } else {
            //Show normal if the mod is visible
            $link = "<a href=\"view.php?id=$wwmoodleset->coursemodule\">$wwmoodleset->name</a>";
        }
    
        if ($course->format == "weeks" or $course->format == "topics") {
            $table->data[] = array ($wwmoodleset->section, $link, strftime("%c", $aSetInfo['open_date']), strftime("%c", $aSetInfo['due_date']));
        } else {
            $table->data[] = array ($link, strftime("%c", $aSetInfo['open_date']), strftime("%c", $aSetInfo['due_date']));
        }
    }
    
    echo "<br />";
    
    print_table($table);
    if( isteacher($course->id) ) {
		print("<p style='font-size: smaller; color: #aaa; text-align: center;'><a style='color: #666;text-decoration:underline' href='".WWMOODLE_SET_WEBWORK_URL."/$course->shortname/instructor' target='_webwork_edit'>".get_string("goToWeBWorK", "wwmoodle")."</a></p>");
	}
/// Finish the page

    print_footer($course);

?>
