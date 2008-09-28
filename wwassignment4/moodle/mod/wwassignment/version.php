<?php
// $Id: version.php,v 1.2 2008-09-28 21:07:19 gage Exp $
/////////////////////////////////////////////////////////////////////////////////
///  Code fragment to define the version of wwassignment
///  This fragment is called by moodle_needs_upgrading() and /admin/index.php
/////////////////////////////////////////////////////////////////////////////////

$module->version  = 2008092818;  // The current module version (Date: YYYYMMDDXX)
$module->requires = 2007101509;  // Requires this Moodle version
$module->cron     = 300;           // Period for cron to check this module (secs) -- every 5 minutes

?>