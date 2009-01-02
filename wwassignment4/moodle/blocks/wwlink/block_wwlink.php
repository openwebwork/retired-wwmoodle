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
        $this->version = 2007092100;
    }
    /**
    * @desc Allows each instance of the block to have its own configuration settings.
    */
    function instance_allow_config() {
        return true;
    }
    
    /**
    * @desc Saves the form data from configuration into the wwassignment_bridge table.
    */
//    function instance_config_save($data) {
        
//         $webworkcourse = $data->webwork_link_id;
//         $moodlecourse = $data->courseid;
//         
//         $wwassignmentbridge = new stdClass;
//         $wwassignmentbridge->course = $moodlecourse;
//         $wwassignmentbridge->webwork_course = $webworkcourse;
//         
//         //has this mapping been defined
//         $record = get_record('wwassignment_bridge','course',$moodlecourse);
//         if(!$record) {
//             //new one
//             insert_record('wwassignment_bridge',$wwassignmentbridge);
//         } else {
//             //update
//             $wwassignmentbridge->id = $record->id;
//             update_record('wwassignment_bridge',$wwassignmentbridge);
//         }
        
//        return parent::instance_config_save($data);        
//    }
    
    /**
    * @desc Makes sure that the only place this block can be added is on course-view page. This insures one block per course.
    */
    function applicable_formats() {
        return array('all' => false, 'course-view' => true);
    }
    
    /**
    * @desc Prints the content of the block. Whether or not the course is connected to a moodle course.
    */
//     function get_content() {
//         global $COURSE;
//         print_r($this->config  );
//         $courseid = $COURSE->id;
//         $record = get_record('wwassignment_bridge','course',$courseid);
//         print_r($record);
//         if(!$record) {
//             $wwlinkdata = $this->config;
//             if (!$wwlinkdata) {
// 				$this->content->text = get_string('not_connected','block_wwlink');
// 			} else {
// 				$this->content->text = "connecting to ". $this->config->webwork_link_id;
// 				$wwassignmentbridge->course = $courseid;
// 				$wwassignmentbridge->webwork_course = $wwlinkdata->webwork_link_id;
// 				insert_record('wwassignment_bridge',$wwassignmentbridge);
// 			}
//         	
//         } else {
//             $this->content->text = get_string('connected','block_wwlink') . ' ' . $record->webwork_course; 
//         }
//         $this->content->footer = '';
//         return $this->content;
//     }

// new method for storing state:
// we can store it in the configuration data for the block! we don't need the wwassignment_bridge table to store state.
    function get_content() {
        global $COURSE;
        //print_r($this->config  );
		$wwlinkdata = $this->config;
		if (!isset($wwlinkdata) || !$wwlinkdata->webwork_link_id ) {	 // need webwork_link_id to exist and be non-zero  
			$this->content->text = get_string('not_connected','block_wwlink');
		} else {	
			$this->content->text = get_string('connected','block_wwlink') . ' ' . $wwlinkdata->webwork_link_id;
		}
       
        $this->content->footer = '';
        return $this->content;
    }
 
}
?>
