<?php
// $Id: lang.php,v 1.5 2007-07-12 18:35:32 mleventi Exp $
// language strings for wwmoodle_set
// TODO: merge with proper language stuff

$string['modulename'] = "WeBWorK Problem Set";
$string['modulenameplural'] = "WeBWorK Problem Sets";

$string['set_name'] = $string['modulename'];

//index list
$string['open_date'] = "Opened";
$string['due_date'] = "Due";

//viewing page

$string['go_to_webwork'] = "Administer the WeBWorK portion of this course.";
$string['iframeNoShow-1'] = "If you are unable to view this problem set, please ";
$string['iframeNoShow-2'] = "click here";

//Course Link form
$string['course_initialization'] = 'Linking to WeBWorK';
$string['webwork_course'] = 'WeBWorK Course';

//Set Link form
$string['set_initialization'] = 'Linking to WeBWorK Problem Set';
$string['webwork_set'] = 'WeBWorK Set';
$string['instructor_page_link_desc'] = 'Link to the Instructor Section of WeBWorK';
$string['instructor_page_link_name'] = 'Go To WeBWorK Instructor Tools';

$string['edit_set_link_desc'] = 'Link to Set Modification Section of WeBWorK';
$string['edit_set_link_name'] = 'Modify Set';

//CONFIG
$string['webwork_url'] = "The URL location of webwork on the server. ex: http://www.example.org/webwork2";
$string['iframe_width'] = "The width in percentage of the page of the iframe displaying WeBWorK problem sets.";
$string['iframe_height'] = "The height in pixels of the iframe displaying WeBWorK problem sets.";
$string['rpc_wsdl'] = "Path to the WSDL file on the host running WeBWorK.";
$string['rpc_key'] = "Key identifying Moodle to WeBWorK.";
$string['testchanges'] = "Test WeBWorK Connection.";


//ERRORS
$string['construction_error'] = 'An error occured in the connection to the WeBWorK server. Perhaps you have your wsdl path set wrong?';
$string['event_creation_error'] = 'wwassignment event could not be created.';
$string['webwork_course_list_map_failure'] = 'Could not retrieve a list of courses on the current WeBWorK server.';
$string['webwork_course_map_failure'] = 'No WeBWorK course is mapped to the current Moodle course.';
$string['webwork_set_map_failure'] = 'No WeBWorK problem set is mapped to the current Moodle problem set.';
$string['webwork_user_map_failure'] = 'No WeBWorK user is mapped to the current Moodle user.';
$string['webwork_user_set_map_failure'] = 'The WeBWorK user does not have the current Moodle set assigned.';
$string['rpc_error'] = 'Communication error between the Moodle client and WeBWorK server.';



?>