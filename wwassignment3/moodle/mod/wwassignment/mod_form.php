<?php
require_once ('moodleform_mod.php');
require_once ('locallib.php');

class mod_wwassignment_mod_form extends moodleform_mod {
    
    function definition() {
        global $COURSE,$USER;
        $mform =& $this->_form;
        
        //Is this particular course mapped to a course in WeBWorK   
        $wwclient = new wwassignment_client();
        $wwcoursename = _wwassignment_mapped_course($COURSE->id,false);
        $wwsetname = _wwassignment_mapped_set($this->_instance);
        $wwusername = $USER->username;
        
        //create the instructor if necessary
        $wwusername = _wwassignment_mapcreate_user($wwcoursename,$wwusername,'10');
        
        //login the instructor
        $wwkey = _wwassignment_login_user($wwcoursename,$wwusername);
        
        $wwinstructorlink = _wwassignment_link_to_instructor_auto_login($wwcoursename,$wwusername,$wwkey);
        
        $mform->addElement('link','instructor_page_link',get_string('instructor_page_link_desc','wwassignment'),$wwinstructorlink,get_string('instructor_page_link_name','wwassignment'));
        
        
        if($wwsetname != -1) {
            //we are doing an update, since an id exists in moodle db
            $wwsetlink = _wwassignment_link_to_edit_set_auto_login($wwcoursename,$wwsetname,$wwusername,$wwkey);
            $mform->addElement('link','edit_set',get_string('edit_set_link_desc','wwassignment'),$wwsetlink,get_string('edit_set_link_name','wwassignment'));
            $wwsetdata = $wwclient->get_assignment_data($wwcoursename,$wwsetname,false);
            
            $opendate = strftime("%c", $wwsetdata['open_date']);
            $duedate = strftime("%c", $wwsetdata['due_date']);
            $mform->addElement('static','opendate','WeBWorK Set Open Date',$opendate);
            $mform->addElement('static','duedate','WeBWorK Set Due Date',$duedate);
        }
        
        //define the mapping
        $mform->addElement('header','set_initialization',get_string('set_initialization','wwassignment'));
        
        //name
        $mform->addElement('text', 'name', get_string('wwassignmentname', 'wwassignment'), array('size'=>'64'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        
        //set select
        $options = $wwclient->options_set($wwcoursename,false);
        $mform->addElement('select','webwork_set',get_string('webwork_set','wwassignment'),$options);
        $mform->setHelpButton('webwork_set', array('webwork_set', get_string('webwork_set', 'wwassignment'), 'wwassignment'));
        
        //description
        $mform->addElement('htmleditor', 'description', get_string('description', 'assignment'));
        $mform->setType('description', PARAM_RAW);
        $mform->setHelpButton('description', array('writing', 'questions', 'richtext'), false, 'editorhelpbutton');
            
        $this->add_action_buttons();
        $this->standard_hidden_coursemodule_elements();
        return;
    }
}

?>