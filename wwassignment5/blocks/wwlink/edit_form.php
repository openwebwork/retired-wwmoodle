<?php



class block_wwlink_edit_form extends block_edit_form {
    
    protected function specific_definition($mform) {
	
	// include CFG object
	global $CFG;
	
	// library for wwassign funcsitons
	$lib = $CFG->dirroot . '/mod/wwassignment/locallib.php';
	require_once($lib);
	
	// form header
	$mform->addElement('header','configheader', get_string('blocksettings','block'));
	
	// get courses from WeBWorK server
	$client = new wwassignment_client();
	$options = $client->options_course(true);
	
	// add the menu
	$mform->addElement('select','config_wwlink_id',get_string('select_course','block_wwlink'),$options);
	$mform->setType('config_wwlink_id',PARAM_RAW);
	
    }
    
}
	
	
	
	
