<?php
// $Id: lang.php,v 1.3 2007-06-25 21:03:30 mleventi Exp $
// language strings for wwmoodle_set
// TODO: merge with proper language stuff

$string['modulename'] = "WeBWorK Problem Set";
$string['modulenameplural'] = "WeBWorK Problem Sets";


//RPC
$string['rpc_wsdl'] = "Path to the WSDL file on the host running WeBWorK.";
$string['rpc_key'] = "Key identifying Moodle to WeBWorK.";
$string['rpc_fault'] = "WeBWorK Failure:";


$string['set_id'] = "Problem Set";
$string['helpSet_id'] = "Problem Set?";
$string['gradeMethod'] = "Grading Method";
$string['helpGradeMethod'] = "Grading Method?";

$string['gradeSimple'] = "Simple";
$string['gradeDeductRetry'] = "Deduct Retries";

$string['openDate'] = "Opened";
$string['dueDate'] = "Due";

$string['iframeNoShow-1'] = "If you are unable to view this problem set, please ";
$string['iframeNoShow-2'] = "click here";

$string['hasNoBridge'] = "There is no WeBWorK/Moodle bridge for this course. You must add one before you can add a problem set.";



$string['webwork_url'] = "This is the URL where WeBWorK is available. It is used to send students off to their assignments. Generally it is something like /webwork2";

$string['iframe_width'] = " This is the width of the iframe used to show students their homework.";

$string['iframe_height'] = "This is the height of the iframe used to show students their homework.";



$string['alreadyHasBridge'] = "This course already has a WeBWorK/Moodle Bridge. Adding another one would only confuse things.";

$string['goToWeBWorK'] = "Administer the WeBWorK portion of this course.";
$string['WeBWorKSets'] = "Assigned WeBWorK Sets";
$string['setName'] = "Set Name";
$string['setId'] = "Set ID";
$string['noSets'] = "No sets were found for this course.";



$string['webwork_courses'] = "This is the directory where WeBWorK stores information about courses. Generally it should be wherever-you-installed-webwork/courses (eg: /opt/webwork2/courses/). NOTE: This <b>MUST</b> end with a '/'!";




$string['on'] = "On";

$string['off'] = "Off";

?>