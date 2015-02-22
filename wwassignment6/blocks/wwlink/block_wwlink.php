<?php

/**
* @desc This block creates the tie between a Moodle course and a WeBWorK course
*/
class block_wwlink extends block_base {
    
    /**
    * @desc Sets the title of the block and the current version.
    */
    function init() {
        $this->title = get_string('blockname','block_wwlink');
    }
    /**
    * @desc Allows each instance of the block to have its own configuration settings.
    */
    function instance_allow_config() {
        return true;
    }
    
 
    
    /**
    * @desc Makes sure that the only place this block can be added is on course-view page. This insures one block per course.
    */
    function applicable_formats() {
        return array('all' => false, 'course-view' => true);
    }
    

    function user_can_edit() {
	$test = has_capability('moodle/site:manageblocks',context_system::instance());
	return (parent::user_can_edit() && $test);
    } 

    // new method for storing state:
    // we can store it in the configuration data for the block! we don't need the wwassignment_bridge table to store state.

    function get_content() {
        global $COURSE;
        if ($this->content !== null) {
		  return $this->content;
		}
        //print_r(get_string('pluginname','block_wwlink'));
		$wwlinkdata = $this->config;
		$this->content = new stdClass;
		if (!isset($wwlinkdata) || !$wwlinkdata->wwlink_id ) {	 // need webwork_link_id to exist and be non-zero
			$this->content->text = get_string('not_connected','block_wwlink');
		} else {	
			$this->content->text = get_string('connected','block_wwlink') . ' ' . $wwlinkdata->wwlink_id;
		}
       
        $this->content->footer = '';
        return $this->content;
    }
 
}
?>
