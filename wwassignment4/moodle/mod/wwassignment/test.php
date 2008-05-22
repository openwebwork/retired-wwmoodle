<?php 
 require_once("/Library/WebServer/Documents/moodle//config.php");
error_log("begin test");

global $COURSE;
$COURSE->id=7;
require_once("lib.php");
require_once("locallib.php");
error_log("test update_grades for assignment44 and user 41 mabernat");
$wwassignment = get_record('wwassignment','id',44); //get a sample assignment id=44
$wwassignment->cmidnumber =1;  //How would we usually get this?
wwassignment_update_grades($wwassignment, 0);  //
error_log("end test update_grades");
// error_log("test ww_get_user_grades course 44 user 41");
// $wwassignment = get_record('wwassignment','id',44); //get a sample assignment id=44
// $userid =0;   //user mabernat 41   user gage 2  user richman 20
// wwassignment_get_user_grades($wwassignment,$userid); 
// error_log("end test ww_get_user_grades");
error_log("test completed");


?>