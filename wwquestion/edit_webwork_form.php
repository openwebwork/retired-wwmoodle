<?php
/**
 * The editing form code for this question type.
 *
 * @copyright &copy; 2006 YOURNAME
 * @author YOUREMAILADDRESS
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package YOURPACKAGENAME
 *//** */

require_once($CFG->dirroot.'/question/type/edit_question_form.php');

/**
 * QTYPENAME editing form definition.
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
        
        
        
        // TODO, add any form fields you need.
        // $mform->addElement( ... );
    }

    function set_data($question) {

       // if (isset($question->options) && isset($question->options->answers)) {
        //    var_dump($question);
         //   die();
        //    $answer = reset($question->options->answers);
        //    $question->feedback = $answer->feedback;
        //}
        //$question->penalty = 0;
        // TODO, preprocess the question definition so the data is ready to load into the form.
        // You may not need this method at all, in which case you can delete it.

        // For example:
        // if (!empty($question->options)) {
        //     $question->customfield = $question->options->customfield;
        // }
        parent::set_data($question);
    }

    function validation($data) {
        $errors = array();
        // TODO, do extra validation on the data that came back from the form. E.g.
        // if (/* Some test on $data['customfield']*/) {
        //     $errors['customfield'] = get_string( ... );
        // }

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