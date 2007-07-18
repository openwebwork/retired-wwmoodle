<?php

class block_webwork_link extends block_base {
    
    function init() {
        $this->title = get_string('blockname','block_webwork_link');
        $this->version = 2007072400;
    }
    
    function instance_allow_config() {
        return true;
    }
    
    function instance_config_save($data) {
        
        $webworkcourse = $data->webwork_link_id;
        $moodlecourse = $data->courseid;
        
        $wwassignmentbridge = new stdClass;
        $wwassignmentbridge->course = $moodlecourse;
        $wwassignmentbridge->webwork_course = $webworkcourse;
        
        //has this mapping been defined
        $record = get_record('wwassignment_bridge','course',$moodlecourse);
        if(!$record) {
            //new one
            insert_record('wwassignment_bridge',$wwassignmentbridge);
        } else {
            //update
            $wwassignmentbridge->id = $record->id;
            update_record('wwassignment_bridge',$wwassignmentbridge);
        }
        
        return parent::instance_config_save($data);        
    }
    
    function applicable_formats() {
        return array('all' => true);
    }
    
    function get_content() {
        global $COURSE;
        
        $courseid = $COURSE->id;
        
        $this->content = new stdClass;
        
        if(isset($this->config->webwork_link_id)) {
            $webworkcourse = $this->config->webwork_link_id;
            $this->content->text = 'Connected to WeBWorK Course: ' . $webworkcourse; 
        } else {
            $this->content->text = 'Not Connected to WeBWorK';
        }
        return $this->content;
    }
}
?>
