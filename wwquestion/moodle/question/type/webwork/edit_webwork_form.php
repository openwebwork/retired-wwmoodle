<?php
/**
 * The editing form code for this question type.
 *
 * @copyright &copy; 2007 Matthew Leventi
 * @author mleventi@gmail.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package webwork_qtype
 *//** */

require_once("$CFG->dirroot/question/type/edit_question_form.php");
require_once("$CFG->dirroot/question/type/webwork/locallib.php");
require_once("$CFG->dirroot/question/type/webwork/questiontype.php");
require_once("$CFG->dirroot/backup/lib.php");

/**
 * webwork editing form definition.
 * 
 * See http://docs.moodle.org/en/Development:lib/formslib.php for information
 * about the Moodle forms library, which is based on the HTML Quickform PEAR library.
 */
class question_edit_webwork_form extends question_edit_form {
    
    function definition_inner(&$mform) {
        
        //CODE HEADER
        $mform->addElement('header', 'codeheader', get_string("edit_codeheader", 'qtype_webwork'));
        $mform->setHelpButton('codeheader',array('codeheader', get_string('edit_codeheader','qtype_webwork'),'webwork'));
        
        //CODE
        $mform->addElement('textarea', 'code', get_string('edit_code', 'qtype_webwork'),
                array('rows' => 10,'cols' => 60));
        $mform->setType('code', PARAM_RAW);
        $mform->setHelpButton('code', array('code', get_string('edit_code', 'qtype_webwork'), 'webwork'));
        
        //FILES HEADER
        $mform->addElement('header', 'fileheader', get_string("edit_fileheader", 'qtype_webwork'));
        $mform->setHelpButton('fileheader',array('fileheader', get_string('edit_fileheader','qtype_webwork'),'webwork'));

        //FILES
        if(!isset($this->question->webworkid)) {
            $tempid = -1;
        } else {
            $tempid = $this->question->webworkid;
        }
        $src = webwork_get_filemanager_url($tempid);
        $html = "<iframe src='".$src."' width='100%' height='300px' style='border:0px;'></iframe>";
        $mform->addElement('html',$html);
                        
        //OPTIONS HEADER
        $mform->addElement('header', 'optionheader', get_string("edit_optionheader","qtype_webwork"));
        
        //CODECHECK
        $codecheckoptions = array(
            0 => get_string('edit_codecheck0','qtype_webwork'),
            1 => get_string('edit_codecheck1','qtype_webwork'),
            2 => get_string('edit_codecheck2','qtype_webwork'),
            3 => get_string('edit_codecheck3','qtype_webwork'),
            4 => get_string('edit_codecheck4','qtype_webwork'));
        $mform->addElement('select','codecheck', get_string('edit_codecheck', 'qtype_webwork'),$codecheckoptions);
        $mform->setType('codecheck',PARAM_INT);
        $mform->setHelpButton('codecheck', array('codecheck', get_string('edit_codecheck', 'qtype_webwork'), 'webwork'));
        $mform->setDefault('codecheck',4);
        
        //SEED
        $mform->addElement('text', 'seed', get_string('edit_seed','qtype_webwork'),
            array('size' => 3));
        $mform->setType('seed', PARAM_INT);
        $mform->setHelpButton('seed', array('webwork', get_string('edit_seed', 'qtype_webwork'), 'webwork'));
        $mform->setDefault('seed', 0);
        $mform->addRule('seed', null, 'required', null, 'client');
        
        //TRIALS
        $mform->addElement('text', 'trials', get_string('edit_trials','qtype_webwork'),
            array('size' => 3));
        $mform->setType('trials', PARAM_INT);
        $mform->setHelpButton('trials', array('webwork', get_string('edit_trials', 'qtype_webwork'), 'webwork'));
        $mform->setDefault('trials', 10);
        $mform->addRule('trials', null, 'required', null, 'client');
    } 

    function set_data($question) {
        parent::set_data($question);
    }
    
    function validation($data) {
        global $CFG;
        
        //is this a copy of a current question
        if(isset($this->_form->_submitValues['makecopy'])) {
            $questioncopy = true;
        } else {
            $questioncopy = false;
        }
        //webwork_question id
        $wwquestionid = $this->question->webworkid;
        
        //codecheck
        $result = webwork_codecheck($data,$wwquestionid,$questioncopy);
        if(is_array($result)) {
            return $result;
        }
        return true;
    }

    

    function qtype() {
        return 'webwork';
    }
}
?>