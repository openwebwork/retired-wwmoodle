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
require_once($CFG->dirroot.'/question/type/webwork/questiontype.php');

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
        
        //CODECHECK
        $codecheckoptions = array(
            0 => get_string('edit_codecheck0','qtype_webwork'),
            1 => get_string('edit_codecheck1','qtype_webwork'),
            2 => get_string('edit_codecheck2','qtype_webwork'));
        $mform->addElement('select','codecheck', get_string('edit_codecheck', 'qtype_webwork'),$codecheckoptions);
        $mform->setType('codecheck',PARAM_INT);
        $mform->setHelpButton('codecheck', array('webwork', get_string('edit_codecheck', 'qtype_webwork'), 'webwork'));
        $mform->setDefault('codecheck',2);
        
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
        //check that the code is valid
        $err = $this->codecheck($data);
        if($err != false) {
            return $err;
        }
    }

    function codecheck($data) {
        //codechecklevel
        $codechecklevel = $data['codecheck'];
        //here we construct a temp question object
        $question = new stdClass;
        $question->code = base64_encode(stripslashes($data['code']));
        $question->seed = $data['seed'];
        $question->trials = $data['trials'];
        
        //one call to the server will return response for this code and keep it static in the function
        $results = webwork_qtype::get_derived_questions($question);
        //no code check
        if($codechecklevel == 0) {
            webwork_qtype::_derivedquestions($results);
            return false;
        }
        
        //init error array
        $errorresults = array();
        $goodresults = array();
        
        //see if we got errors (split)
        foreach($results as $record) {
            if((isset($record['errors'])) && ($record['errors'] != '') && ($record['errors'] != null)) {
                array_push($errorresults,$record);
            } else {
                array_push($goodresults,$record);
            }
        }
        
        //if there are good seeds we use those
        if((count($goodresults) > 0) && ($codechecklevel == 1)) {
            webwork_qtype::_derivedquestions($goodresults);
            return false;
        }
        
        //if code check is strict
        if(count($goodresults) == count($results)) {
            webwork_qtype::_derivedquestions($results);
            return false;
        }
        
        $errormsgs = array();
        //at this point we are going to be invalid
        
        //this correlates seeds with certain error messages for better output
        
        foreach($errorresults as $record) {
            $found = 0;
            $candidate = $record['errors'];
            $candidateseed = $record['seed'];
            for($i=0;$i<count($errormsgs);$i++) {
                if($candidate == $errormsgs[$i]['errors']) {
                    $found = 1;
                    $errormsgs[$i]['seeds'][] = $candidateseed;
                }
            }
            if($found == 0) {
                //new error message
                $msg = array();
                $msg['errors'] = $candidate;
                $msg['seeds'] = array();
                $msg['seeds'][] = $candidateseed;
                $errormsgs[] = $msg;
            }
        }
        $output = "Errors in PG Code on: " . count($errorresults) . " out of " . count($results) . " seeds tried:<br>";
        //construct error statement
        $counter = 1;
        foreach($errormsgs as $msg) {
            $output .= "$counter) ";
            $output .= "Seeds (";
            foreach ($msg['seeds'] as $seed) {
                $output .= $seed . " ";
            }
            $output .= ") gave Error:" . $msg['errors'] . "<br><br>";
            $counter++;
        }
        $returner =array();
        $returner['code'] = $output;
        return $returner;
    }

    function qtype() {
        return 'webwork';
    }
}
?>