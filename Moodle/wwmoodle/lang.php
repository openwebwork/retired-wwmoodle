<?php
// $Id: lang.php,v 1.3 2006-06-24 16:00:36 gage Exp $
// language strings for wwmoodle
// TODO: merge with proper language stuff

$string['modulename'] = "WeBWorK/Moodle Bridge";
$string['modulenameplural'] = "WeBWorK/Moodle Bridges";

$string['createWWc'] = "Create WeBWorK Course";
$string['helpCreateWWc'] = "Create WeBWorK Course?";
$string['allowedRecipients'] = "Allowed Recipients";
$string['helpAllowedRecipients'] = "Allowed Recipients?";

$string['alreadyHasBridge'] = "This course already has a WeBWorK/Moodle Bridge. Adding another one would only confuse things.";

$string['goToWeBWorK'] = "Administer the WeBWorK portion of this course.";
$string['WeBWorKSets'] = "Assigned WeBWorK Sets";
$string['setName'] = "Set Name";
$string['setId'] = "Set ID";
$string['noSets'] = "No sets were found for this course.";

$string['webwork_db'] = "This is the connection string for the WeBWorK database. It should be of the form type://username:password@host/database-name, where type is usually mysql. For more information see the PEAR::DB documentation: <a href='http://pear.php.net/DB'>pear.php.net/DB</a>";
$string['webwork_courses'] = "This is the directory where WeBWorK stores information about courses. Generally it should be wherever-you-installed-webwork/courses (eg: /opt/webwork2/courses/). NOTE: This <b>MUST</b> end with a '/'!";
$string['webwork_delete'] = "If this option is set, when a bridge is removed from a course, the coresponding WeBWorK course will also be deleted. Use this option with care, deletion is not undoable!";

$string['on'] = "On";
$string['off'] = "Off";

?>