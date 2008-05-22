<?php
// $Id: version.php,v 1.1 2008-05-22 14:36:24 gage Exp $
/////////////////////////////////////////////////////////////////////////////////
///  Code fragment to define the version of wwassignment
///  This fragment is called by moodle_needs_upgrading() and /admin/index.php
/////////////////////////////////////////////////////////////////////////////////

$module->version  = 2008042072;  // The current module version (Date: YYYYMMDDXX)
$module->requires = 2007101509;  // Requires this Moodle version
$module->cron     = 0;           // Period for cron to check this module (secs)

?>