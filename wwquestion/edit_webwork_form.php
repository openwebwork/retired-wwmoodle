<?php
/**
 * The editing form code for this question type.
 *
 * @copyright &copy; 2007 Matthew Leventi
 * @author mleventi@gmail.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package webwork_qtype
 *//** */

require_once($CFG->dirroot.'/question/type/edit_question_form.php');

/**
 * webwork editing form definition.
 * 
 * See http://docs.moodle.org/en/Development:lib/formslib.php for information
 * about the Moodle forms library, which is based on the HTML Quickform PEAR library.
 */
class question_edit_webwork_form extends question_edit_form {
    function definition_inner(&$mform) {
        
        //HEADER
        $mform->addElement('header', 'generalheader', get_string("edit_header", 'qtype_webwork'));
        
        //CODE
        $mform->addElement('textarea', 'code', get_string('edit_code', 'qtype_webwork'),
                array('rows' => 10,'cols' => 60));
        $mform->setType('code', PARAM_RAW);
        $mform->setHelpButton('code', array('webwork', get_string('edit_code', 'qtype_webwork'), 'webwork'));
        $mform->addRule('code', null, 'required', null, 'client');
        
        //SEED
        $mform->addElement('text', 'seed', get_string('edit_seed','qtype_webwork'),
            array('size' => 3));
        $mform->setType('seed', PARAM_INT);
        $mform->setHelpButton('seed', array('webwork', get_string('edit_seed', 'qtype_webwork'), 'webwork'));
        $mform->setDefault('seed', 0);
        $mform->addRule('seed', null, 'required', null, 'client');
    }

    function set_data($question) {
        parent::set_data($question);
    }

    function validation($data) {
        $errors = array();
        if ($errors) {
            return $errors;
        } else {
            return true;
        }
    }

    function qtype() {
        return 'webwork';
    }
}
?>