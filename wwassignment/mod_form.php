<?php
require_once ('moodleform_mod.php');
require_once ('lib.php');

class mod_wwassignment_mod_form extends moodleform_mod {
    
    function definition() {
        global $COURSE;
        $mform =& $this->_form;
        //Is this particular course mapped to a course in WeBWorK   
        $webworkclient = new webwork_client();
        $coursemapped = _wwassignment_mapped_course($COURSE->id);
        if($coursemapped == -1) {
            
            $mform->addElement('link','mainpage',get_string('mainpage_link_desc','wwassignment'),"BUGGER",get_string('mainpage_link_name','wwassignment'));
            return;
        }
        
        //links
        $mform->addElement('link','instructor_page_link',get_string('instructor_page_link_desc','wwassignment'),wwassignment_instructor_page_link(),get_string('instructor_page_link_name','wwassignment'));
        
        if($this->instance != "") {
            $mform->addElement('link','edit_set',get_string('edit_set_link_desc','wwassignment'),wwassignment_edit_set_link($this->_instance),get_string('edit_set_link_name','wwassignment'));
        }
        
        //define the mapping
        $mform->addElement('header','set_initialization',get_string('set_initialization','wwassignment'));
        
        //name
        $mform->addElement('text', 'name', get_string('wwassignmentname', 'wwassignment'), array('size'=>'64'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        
        //set select
        $options = $webworkclient->options_set($coursemapped,false);
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