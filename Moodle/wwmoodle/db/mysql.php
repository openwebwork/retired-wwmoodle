<?PHP

function wwmoodle_upgrade($oldversion) {
/// This function does anything necessary to upgrade 
/// older versions to match current functionality 

	global $CFG;

	if ($oldversion < 2006042700) {
		// fix to handle course shortnames with spaces properly.
		table_column('wwmoodle', null, 'coursename', 'varchar', 15, '', '', 'not null', 'course');
		// now properly update the table to have modified shortName
		$aRecords = get_records('wwmoodle');
		foreach( $aRecords as $oRecord ) {
			// get the short name:
			$oCourse = get_record('course', 'id', $oRecord->course);
			$oRecord->coursename = preg_replace("/[^a-zA-Z0-9]/", "", $oCourse->shortname);
			update_record('wwmoodle', $oRecord);
		}
	}

	return true;
}

?>
