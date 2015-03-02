<?php
// $Id: version.php,v 1.3 2008/10/03 13:20:51 gage Exp $
/////////////////////////////////////////////////////////////////////////////////
///  Code fragment to define the version of wwassignment
///  This fragment is called by moodle_needs_upgrading() and /admin/index.php
/////////////////////////////////////////////////////////////////////////////////

$module->version  = 2010102600;  // The current module version (Date: YYYYMMDDXX)
$module->requires = 2010102600;  // Requires this Moodle version
$module->cron     = 300;         // Period for cron to check this module (secs) -- every 5 minutes
?>
