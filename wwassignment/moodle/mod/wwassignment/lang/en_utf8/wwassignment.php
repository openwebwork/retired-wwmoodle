<?php
// $Id: wwassignment.php,v 1.2 2009-09-20 22:02:04 gage Exp $
// language strings for wwmoodle_set

$string['modulename'] = "WeBWorK Problem Set";
$string['modulenameplural'] = "WeBWorK Problem Sets";

$string['set_name'] = $string['modulename'];

//index list
$string['open_date'] = "Opened";
$string['due_date'] = "Due";
$string['total_points'] = "Total points";

//viewing page
$string['go_to_webwork'] = "Administer the WeBWorK portion of this course.";
$string['iframeNoShow-1'] = "If you are unable to view this problem set, please ";
$string['iframeNoShow-2'] = "click here";

//Defining New Module without Block
$string['mainpage_link_desc'] = 'You need to create the webwork course mapping by creating a WeBWorK link block.';
$string['mainpage_link_name'] = 'Course Page';

//Course Link form
$string['course_initialization'] = 'Linking to WeBWorK';
$string['webwork_course'] = 'WeBWorK Course';

//Set Link form
$string['set_initialization'] = 'Linking to WeBWorK Problem Set';
$string['wwassignmentname'] = 'Name';
$string['webwork_set'] = 'WeBWorK Set';
$string['instructor_page_link_desc'] = 'Link to the Instructor Section of WeBWorK';
$string['instructor_page_link_name'] = 'Go To WeBWorK Instructor Tools';

$string['edit_set_link_desc'] = 'Link to Set Modification Section of WeBWorK';
$string['edit_set_link_name'] = 'Modify Set';

//CONFIG
$string['webwork_url']      = "WeBWorK server URL:";
$string['webwork_url_desc'] = "Enter the url of the webwork installation containing the webwork course: e.g.  http://www.server.edu/webwork2";
$string['rpc_wsdl']         = "URL for WeBWorK server WSDL file";
$string['rpc_wsdl_desc']    = "Enter the url of the WSDL at the installation containing the webwork course: e.g. http://www.server.edu/webwork2_wsdl";
$string['rpc_key']          = "Key identifying Moodle to WeBWorK.";
$string['rpc_key_desc']     = "Enter the key for obtaining access to the server. e.g. ABC321 ";

$string['iframe_width']        = " iframe width as per cent:";
$string['iframe_width_desc']   = "Enter the width of the frame containing the webwork pages.e.g. 95&#37; ";//&#37 is per cent sign--can't use it in string??
$string['iframe_height']       = "iframe height in pixels";
$string['iframe_height_desc']  = "Enter the height of the frame containing the webwork pages. e.g. 500px ";

// 
// 
// $string['testchanges'] = "Test WeBWorK Connection.";
// 



// $string['webwork_url'] = "The URL location of webwork on the server. ex: http://www.example.org/webwork2";
// $string['iframe_width'] = "The width in percentage of the page of the iframe displaying WeBWorK problem sets.";
// $string['iframe_height'] = "The height in pixels of the iframe displaying WeBWorK problem sets.";
// $string['rpc_wsdl'] = "Path to the WSDL file on the host running WeBWorK.";
// $string['rpc_key'] = "Key identifying Moodle to WeBWorK.";
// $string['testchanges'] = "Test WeBWorK Connection.";
//ERRORS
$string['construction_error'] = 'An error occured in establishing the connection to WeBWorK.<br>The most likely cause of this problem is that your WSDL path is incorrect.';

$string['event_creation_error'] = 'wwassignment event could not be created.';
$string['webwork_course_list_map_failure'] = 'Could not retrieve a list of courses on the current WeBWorK server.';

$string['webwork_course_map_failure'] = 'No WeBWorK course is linked to this course.<br>Use the WWLink block to create a connection between this Moodle course and a WeBWorK course.';

$string['webwork_set_map_failure'] = 'No WeBWorK problem set is mapped to the current Moodle problem set.';
$string['webwork_user_map_failure'] = 'No WeBWorK user is mapped to the current Moodle user.';
$string['webwork_user_set_map_failure'] = 'The WeBWorK user does not have the current Moodle set assigned.';
$string['rpc_error'] = 'Communication error between the Moodle client and WeBWorK server.';



?>