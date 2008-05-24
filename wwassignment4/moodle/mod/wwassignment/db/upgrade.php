<?php  //$Id: upgrade.php,v 1.2 2008-05-24 02:29:18 gage Exp $

// This file keeps track of upgrades to
// the assignment module
//
// Sometimes, changes between versions involve
// alterations to database structures and other
// major things that may break installations.
//
// The upgrade function in this file will attempt
// to perform all the necessary actions to upgrade
// your older installtion to the current version.
//
// If there's something it cannot do itself, it
// will tell you what you need to do.
//
// The commands in here will all be database-neutral,
// using the functions defined in lib/ddllib.php

function xmldb_wwassignment_upgrade($oldversion=0) {

    global $CFG, $THEME, $db;

    $result = true;

/// And upgrade begins here. For each one, you'll need one 
/// block of code similar to the next one. Please, delete 
/// this comment lines once this file start handling proper
/// upgrade code.

/// if ($result && $oldversion < YYYYMMDD00) { //New version in version.php
///     $result = result of "/lib/ddllib.php" function calls
/// }

//===== 1.9.0 upgrade line ======//
    notify("running 1.9 upgrade");
    if ($result && $oldversion < 2008042072) {
    
    	/// Define field grade to be added to wwassignment
        //$table = new XMLDBTable('wwassignment');
        //$field = new XMLDBField('grade');
        //$field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'webwork_set');
        
    	/// Launch add field grade
        //$result = $result && add_field($table, $field);
        
    	/// Define field timemodified to be added to wwassignment
    	$table = new XMLDBTable('wwassignment');
        $field = new XMLDBField('timemodified');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'webwork_set');
        
        /// Launch add field timemodified
        $result = $result && add_field($table, $field);
        
        notify('Processing assignment grades, this may take a while if there are many assignments...', 'notifysuccess');
        // change grade typo to text if no grades MDL-13920
        require_once $CFG->dirroot.'/mod/wwassignment/lib.php';
        // too much debug output
        $db->debug = false;
        wwassignment_update_grades();
        $db->debug = true;
    }

    return $result;
}
?>

