<?php
require_once ('moodleform_mod.php');
require_once ('lib.php');

class mod_wwassignment_mod_form extends moodleform_mod {
    
    function definition() {
        global $COURSE;
        $mform =& $this->_form;
        //Is this particular course mapped to a course in WeBWorK
        
        $webworkclient =& new webwork_client();
        $coursemapped = _wwassignment_mapped_course($COURSE->id);
        if($coursemapped == -1) {
            
            //define the mapping
            $mform->addElement('header', 'course_initialization',get_string('course_initialization','wwassignment'));
                  
            $options = $webworkclient->options_course();
            $mform->addElement('select', 'webwork_course', get_string('webwork_course', 'wwassignment'), $options);
            $mform->setHelpButton('webwork_course', array('webwork_course', get_string('webwork_course', 'wwassignment'), 'wwassignment'));
            
            $mform->addElement('checkbox','auto_enroll_course',get_string('auto_enroll_course','wwassignment'),get_string('auto_enroll_course_desc','wwassignment'));
            $this->add_action_buttons();
            $this->standard_hidden_coursemodule_elements();
            return;
        }
        $mform->addElement('link','instructor_page_link',get_string('instructor_page_link_desc','wwassignment'),wwassignment_instructor_page_link(),get_string('instructor_page_link_name','wwassignment'));
        if($this->_instance == "") {
            //doing an addition
            $mform->addElement("header",'set_initialization',get_string('set_initialization','wwassignment'));
            $options = $webworkclient->options_set($coursemapped);
            $mform->addElement('select','webwork_set',get_string('webwork_set','wwassignment'),$options);
            $mform->setHelpButton('webwork_set', array('webwork_set', get_string('webwork_set', 'wwassignment'), 'wwassignment'));     
            $mform->addElement('checkbox','auto_enroll_set',get_string('auto_enroll_set','wwassignment'),get_string('auto_enroll_set_desc','wwassignment'));
            $this->add_action_buttons();
            $this->standard_hidden_coursemodule_elements();
            return;
        } else {
            //doing an update
            //$this->standard_hidden_coursemodule_elements();
            $mform->addElement('link','edit_set',get_string('edit_set_link_desc','wwassignment'),wwassignment_edit_set_link($this->_instance),get_string('edit_set_link_name','wwassignment'));
            //$this->add_action_buttons(false);
        }
        return;
    }
};

?>