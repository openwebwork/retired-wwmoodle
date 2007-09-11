<?php

require_once("$CFG->dirroot/question/type/webwork/config.php");
require_once("$CFG->dirroot/question/type/webwork/locallib.php");
require_once("$CFG->dirroot/question/type/questiontype.php");
require_once("$CFG->dirroot/backup/lib.php");

/**
 * The question type class for the webwork question type.
 *
 * @copyright &copy; 2007 Matthew Leventi
 * @author mleventi@gmail.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package webwork_qtype
**/

/**
 * The webwork question class
 *
 * Allows webwork questions to be used in Moodle through a new question type.
 */
class webwork_qtype extends default_questiontype {
    
    //////////////////////////////////////////////////////////////////
    // Functions overriding default_questiontype functions
    //////////////////////////////////////////////////////////////////
    
    /**
    * @desc Required function that names the question type.
    * @return string webwork.
    */
    function name() {
        return 'webwork';
    }
    
    /**
    * @desc Gives the label in the Create Question dropdown.
    * @return string WeBWorK
    */
    function menu_name() {
        return 'WeBWorK';
    }
    
    /**
     * @desc Retrieves information out of question_webwork table and puts it into question object.
     * @return boolean to indicate success of failure.
     */
    function get_question_options(&$question) {
        if (!$record = get_record('question_webwork', 'question', $question->id)) {
            notify('Error: Missing question options!');
            return false;
        }
        $question->trials = $record->trials;
        $question->seed = $record->seed;
        $question->code = base64_decode($record->code);
        $question->codecheck = $record->codecheck;
        $question->grading = $record->grading;
        //hold onto the ID of the question_webwork record
        $question->webworkid = $record->id;
        return true;
    }
    
    /**
     * @desc Saves the webwork question code and default seed setting into question_webwork. Will recreate all corresponding derived questions.
     * @param $question object The question object holding new data.
     * @return boolean to indicate success of failure.
     */
    function save_question_options($question) {
        //determing update or insert
        $oldrecord = get_record('question_webwork','question',$question->id);
        if(!$oldrecord) {
            $isupdate = false;
        } else {
            $isupdate = true;
        }
        //set new variables for DB entry
        $record = new stdClass;
        $record->question = $question->id;
        $record->codecheck = $question->codecheck;
        $record->code = base64_encode(stripslashes($question->code));
        $record->seed = $question->seed;
        $record->trials = $question->trials;
        
        $results = webwork_qtype::_derivations();
        if(count($results) > 0) {
            $record->grading = $results[0]['grading'];
        }
        
        //insert or update question in DB
        if($isupdate) {
            //update
            $record->id = $oldrecord->id;
            $errresult = update_record("question_webwork", $record);
            if (!$errresult) {
                $errresult->error = "Could not update question_webwork record! (id=$record->id)";
                return $errresult;
            } 
        } else {
            //insert
            $errresult = insert_record("question_webwork", $record);
            if (!$errresult) {
                $errresult->error = "Could not insert question_webwork record!";
                return $errresult;
            }
            //set the new record id
            $record->id = $errresult; 
        }
        $wwquestionid = $record->id;
        //copy the tmp directory to the question one
        if($isupdate == false) {
            rename(webwork_get_tmp_path_full(),webwork_get_wwquestion_path_full($wwquestionid));
        }
        
        
        //update the derivations
        $this->_update_derivations($wwquestionid);
        return true;
    }
    
    /**
    * @desc Creates an empty response before a student answers a question. This contains the possibly randomized seed for that particular student. Sticky seeds are created here.
    * @param $question object The question object.
    * @param $state object The state object.
    * @param $cmoptions object The cmoptions containing the course ID
    * @param $attempt id The attempt ID.
    * @return bool true. 
    */
    function create_session_and_responses(&$question, &$state, $cmoptions, $attempt) {
        global $CFG,$USER;
        //here we get the derived results for this question
        $derivations = get_records("question_webwork_derived","question_webwork",$question->webworkid,'','id');
        if(!$derivations) {
            print_error(get_string('error_db_webwork_derived','qtype_webwork'));
            return false;
        }
        
        //make sure its not 0
        if(count($derivations) == 0) {
            print_error(get_string('error_no_webwork_derived','qtype_webwork'));
            return false;
        }
        
        //pick a random question based on time
        srand(time());
        $random = rand(0,count($derivations)-1);
        $values = array_values($derivations);
        $derivationid = $values[$random]->id;
        
        //get the actual data
        $derivation = get_record('question_webwork_derived','id',$derivationid);
        //build state
        $state->responses['seed'] = $derivation->seed;
        $state->responses['derivationid'] = $derivation->id;
        return true;
    }
    
    /**
     * @desc Deletes question from the question_webwork table
     * @param integer $questionid The question being deleted
     * @return boolean to indicate success of failure.
     */
    function delete_question($questionid) {
        //Get wwquestion from DB
        $record = get_record('question_webwork','question',$questionid);
        $wwquestionid = $record->id;
        
        //delete DB and Files
        webwork_delete_wwquestion_dir($wwquestionid);
        delete_records('question_webwork', 'id' , $wwquestionid);
        
        //delete derivations
        webwork_delete_derivations_db($wwquestionid);
        return true;
    }
    
    /**
    * @desc Decodes and unserializes a students response into the response array carried by state
    * @param $question object The question object.
    * @param $state object The state that needs to be restored.
    * @return bool true.
    */
    function restore_session_and_responses(&$question, &$state) {
        $serializedresponse = $state->responses[''];
        $serializedresponse = base64_decode($serializedresponse);
        $responses = unserialize($serializedresponse);
        $state->responses = $responses;
        return true;
    }
    
    /**
    * @desc Serialize, encodes and inserts a students response into the question_states table.
    * @param $question object The question object for the session.
    * @param $state object The state to save.
    * @return true, or error on db change.
    */
    function save_session_and_responses(&$question, &$state) {
        $responses = $state->responses;
        $serialized = serialize($responses);
        $serialized = base64_encode($serialized);
        return set_field('question_states', 'answer', $serialized, 'id', $state->id);
    }
    
    /**
    * @desc Prints the question. Calls question_webwork_derived, and prints out the html associated with derivedid.
    * @param $question object The question object to print.
    * @param $state object The state of the responses for the question.
    * @param $cmoptions object Options containing course ID.
    * @param $options object
    */
    function print_question_formulation_and_controls(&$question, &$state, $cmoptions, $options) {
        global $CFG,$USER;
        $readonly = empty($options->readonly) ? '' : 'disabled="disabled"';
        //Formulate question image and text
        $questiontext = $this->format_text($question->questiontext,
                $question->questiontextformat, $cmoptions);
        $image = get_question_image($question, $cmoptions->course);
        
        $derivationid = $state->responses['derivationid'];
        $derivation = get_record('question_webwork_derived','id',$derivationid);
        
        $unparsedhtml = base64_decode($derivation->html);
        
        //partial answers
        $showPartiallyCorrectAnswers = $question->grading;
        
        //new array keyed by field
        $fieldhash = $state->responses['answers'];
        $answerfields = array();
        $parser = new HtmlParser($unparsedhtml);
        $currentselect = "";
        while($parser->parse()) {
            //change some attributes of html tags for moodle compliance
            if ($parser->iNodeType == NODE_TYPE_ELEMENT) {
                $nodename = $parser->iNodeName;
                $name = $parser->iNodeAttributes['name'];
                //handle generic change of node's attribute name
                if(($nodename == "INPUT") || ($nodename == "SELECT") || ($nodename == "TEXTAREA")) {
                    $parser->iNodeAttributes['name'] = 'resp' . $question->id . '_' . $name;
                    if(($state->event == QUESTION_EVENTGRADE) && (isset($fieldhash[$name]))) {
                        if($showPartiallyCorrectAnswers) {
                            $parser->iNodeAttributes['class'] = $parser->iNodeAttributes['class'] . ' ' . question_get_feedback_class($fieldhash[$name]['score']);
                        }
                    }
                    if(!strstr($name,'previous')) {
                        $answerfields[$name] = $fieldhash[$name];
                    }
                }
                //handle specific change
                if($nodename == "INPUT") {
                    //put submitted value into field
                    if(isset($fieldhash[$name])) {
                        $parser->iNodeAttributes['value'] = $fieldhash[$name]['answer'];
                    }
                } else if($nodename == "SELECT") {
                    $currentselect = $name;    
                } else if($nodename == "OPTION") {
                    if($parser->iNodeAttributes['value'] == $fieldhash[$currentselect]['answer'])
                        $parser->iNodeAttributes['selected'] = '1';
                } else if($nodename == "TEXTAREA") {
                }
            }
            $problemhtml .= $parser->printTag();
        }
        
        //for the seed form field
        $qid = $question->id;
        $seed = $state->responses['seed'];
        
        
        
        //if the student has answered
        include("$CFG->dirroot/question/type/webwork/display.html");
    }
    
    /**
    * @desc Assigns a grade for a student response. Currently a percentage right/total questions. Calls the Webwork Server to evaluate answers.
    * @param $question object The question to grade.
    * @param $state object The response to the question.
    * @param $cmoptions object ...
    * @return boolean true.
    */
    function grade_responses(&$question, &$state, $cmoptions) {
        global $CFG,$USER;
        
        //get code and seed of the students problem
        $code = base64_encode($question->code);
        $seed = $state->responses['seed'];
        $derivationid = $state->responses['derivationid'];
        $wwquestionid = $question->webworkid;
        
        //get answers & build answer request
        $answerarray = array();
        foreach($state->responses as $key => $value) {
            array_push($answerarray, array('field' => $key, 'answer'=> $value));
        }
        
        //build problem request
        $problem = array();
        $problem['code'] = $code;
        $problem['seed'] = $seed;
        $problem['files']= array();
        
        //SOAP request
        $params = array();
        $params['request'] = $problem;
        $params['answers'] = $answerarray;
        
        //SOAP Call
        $client = new webwork_client();
        $response = $client->handler('checkAnswers',$params);
        
        //process output from soap & calculate score
        $answers = $response;
        $state->raw_grade = 0;
        $total = 0;
        $num = 0;
        foreach($answers as $answer) {
            $total += $answer['score'];
            $num++;
        }
        if($num != 0) {
            $state->raw_grade = $total / $num;
        }
        
        //create the directory for this user
        webwork_make_derivation_user_dir($wwquestionid,$derivationid,$USER->id);
        
        // Apply the penalty for this attempt
        $state->penalty = $question->penalty * $question->maxgrade;

        // mark the state as graded
        $state->event = ($state->event ==  QUESTION_EVENTCLOSE) ? QUESTION_EVENTCLOSEANDGRADE : QUESTION_EVENTGRADE;
        
        //put the responses into the state to remember
        $state->responses['answers'] = array();
        
        foreach ($answers as $answer) {
            $html = base64_decode($answer['preview']);
            $copyto = webwork_get_derivation_user_path_full($wwquestionid,$derivationid,$USER->id);
            $replacer = webwork_get_derivation_user_url($wwquestionid,$derivationid,$USER->id);
            $html = webwork_parse_change_ans($html,$replacer,$copyto);
            $answer['preview'] = $html;
            $state->responses['answers'][$answer['field']] = $answer;
        }
        return true;
    }
    
    /**
    * @desc Comparison of two student responses for the same question. Checks based on seed equality, and response equality.
    * Perhaps we could add check on evaluated answer (depends on whether the server is called before this function)
    * @param $question object The question object to compare.
    * @param $state object The first response.
    * @param $teststate object The second response.
    * @return boolean, Returns true if the state are equal | false if not.
    */
    function compare_responses($question, $state, $teststate) {        
        if(sizeof($state->responses) != sizeof($teststate->responses)) {
            return false;
        }
        //check values are equal
        foreach($state->responses as $key => $value) {
            if($value != $teststate->responses[$key]) {
                return false;
            }
        }
        return true;
    }
    
    /**
    * @desc Gets the correct answers from the SOAP server for the seed in state. Places them into the state->responses array.
    * @param $question object The question object.
    * @param $state object The state object.
    * @return object Object containing the seed,derivedid, and answers.
    */
    function get_correct_responses(&$question, &$state) {
        //get code and seed of response
        $code = base64_encode($question->code);
        $seed = $state->responses['seed'];
        $derivationid = $state->responses['derivationid'];
        
        //get empty answers & build answer request
        $answerarray = array();
        foreach($state->responses as $key => $value) {
            array_push($answerarray, array('field' => $key, 'answer'=> $value));
        }
        
        //build problem request
        $problem = array();
        $problem['code'] = $code;
        $problem['seed'] = $seed;
        
        //SOAP request
        $params = array();
        $params['request'] = $problem;
        $params['answers'] = $answerarray;
        
        //SOAP Call
        $client = new webwork_client();
        $response = $client->handler('checkAnswers',$params);
        
        //make the state perfect graded
        $state->raw_grade = 1;
        $state->event = ($state->event ==  QUESTION_EVENTCLOSE) ? QUESTION_EVENTCLOSEANDGRADE : QUESTION_EVENTGRADE;
        $state->penalty = 0;
        
        //process correct answers into fields
        $answers = $response;
        $ret = array();
        $ret['answers'] = array();
        
        foreach ($answers as $answer) {
            $ret['answers'][$answer['field']] = $answer;
            $ret['answers'][$answer['field']]['answer'] = $answer['correct'];
            $ret['answers'][$answer['field']]['score'] = 1;
            $ret['answers'][$answer['field']]['evaluated'] = "";
            $ret['answers'][$answer['field']]['preview'] = "";         
        }
        
        //push the seed onto the answer array, keep track of what seed these are for.
        $ret['seed'] = $seed;
        $ret['derivationid'] = $derivationid;
        return $ret;
    }
    
    /**
    * @desc Enumerates the pictures for a response.
    * @param $question object The question object.
    * @param $state object The state object.
    * @return array HTML code with <img> tag for each picture.
    */
    function get_actual_response($question, $state) {
        $temp = '';
        $i = 1;
        foreach($state->responses['answers'] as $key => $value) {
            $responses[] = "$i) " . $value['preview'];
            $i++;
        }
        return $responses;
    }
    
    /**
    * @desc Prints a summary of a response.
    * @param $question object The question object.
    * @param $state object The state object.
    * @return string HTML.
    */
    function response_summary($question, $state, $length=80) {
        // This should almost certainly be overridden
        $responses = $this->get_actual_response($question, $state);
        if (empty($responses) || !is_array($responses)) {
            $responses = array();
        }
        if (is_array($responses)) {
            $responses = implode('<br/><br/>', $responses);
        }
        return $responses;//substr($responses, 0, $length);
    }
    
    /**
    * Changes all states for the given attempts over to a new question
    *
    * This is used by the versioning code if the teacher requests that a question
    * gets replaced by the new version. In order for the attempts to be regraded
    * properly all data in the states referring to the old question need to be
    * changed to refer to the new version instead. In particular for question types
    * that use the answers table the answers belonging to the old question have to
    * be changed to those belonging to the new version.
    *
    * @param integer $oldquestionid  The id of the old question
    * @param object $newquestion    The new question
    * @param array  $attempts       An array of all attempt objects in whose states
    *                               replacement should take place
    */
    function replace_question_in_attempts($oldquestionid, $newquestion, $attempts) {
        echo 'Not yet implemented';
        return;
    }
        
    /**
    * @desc Updates the derivations of a wwquestion.
    * @param integer $wwquestionid The derivation to update.
    * @return boolean true
    */
    function _update_derivations($wwquestionid) {
        global $CFG;
        //retrieve the new records
        $newrecordset = webwork_qtype::_derivations();
        //retrieve the old records
        $oldrecordset = get_records('question_webwork_derived','question_webwork',$wwquestionid);
        //records that we will have
        $therecordset = array();
        
        //load in the new ones (by seed)
        foreach($newrecordset as $newrecord) {
            unset($temprecord);
            //assign parentid
            $temprecord->question_webwork = $wwquestionid;
            //copy data
            $temprecord->seed = $newrecord['seed'];
            $temprecord->html = $newrecord['output'];
            $therecordset[$temprecord->seed] = $temprecord;
        }
        //overwrite IDs with old IDs if seeds match
        if(isset($oldrecordset)) {
            //stuff that exists and might be updatable
            foreach($oldrecordset as $oldrecord) {
                //do we have an old seed that matches new seeds
                $oldseed = $oldrecord->seed;
                if(isset($therecordset[$oldseed]) && ($therecordset[$oldseed] != null)) {
                    //found a seed that already exists, make sure it goes into the old ID
                    $therecordset[$oldseed]->id = $oldrecord->id;
                } else {
                    //didnt find a seed that exists, delete the old record
                    $derivationid = $oldrecord->id;
                    webwork_delete_derivation_db($derivationid);
                    webwork_delete_derivation_dir($wwquestionid,$derivationid);
                }
            }
        }
        //update or insert into database
        foreach($therecordset as $record) {
            
            //initial insert to get the ID when necessary
            if(!isset($record->id)) {
                unset($record->id);
                $result = insert_record('question_webwork_derived',$record);
                if(!$result) {
                    print_error('DB opertaion failed');
                }
                $record->id = $result;
            }
            
            //makes the derivation directory
            webwork_make_derivation_dir($wwquestionid,$record->id);

            webwork_parse_change_derivation($record);
            //updates record
            $err = update_record('question_webwork_derived',$record);
            if(!$err) {
                print_error('DB error on updating question_webwork_derived');
            }
        }
        return true;        
    }
        
        
        
    /**
    * @desc This hold the derivation data that comes out of form validation.
    * @param array $derivations The derivation data.
    * @return object true or derivation data.
    */
    function _derivations($derivations = null) {
        static $temp = null;
        if($derivations == null) {
            return $temp;
        }
        $temp = $derivations;
        return true;
    }
    
    /**
    * Renders the question for printing and returns the LaTeX source produced
    *
    * This function should render the question suitable for a printed problem
    * or solution sheet in LaTeX and return the rendered output.
    * @return string          The LaTeX output.
    * @param object $question The question to be rendered. Question type
    *                         specific information is included.
    * @param object $state    The state to render the question in. The
    *                         question type specific information is also
    *                         included.
    * @param object $cmoptions
    * @param string $type     Indicates if the question or the solution is to be
    *                         rendered with the values 'question' and
    *                         'solution'.
    */
    function get_texsource(&$question, &$state, $cmoptions, $type) {
        // The default implementation simply returns a string stating that
        // the question is only available online.
        return get_string('onlineonly', 'texsheet');
    }
    
    /**
     * Backup the data in the question
     *
     * This is used in question/backuplib.php
     */
    function backup($bf,$preferences,$question,$level=6) {

        $status = true;

        $webworks = get_records('question_webwork', 'question', $question, 'id');
        //If there are webworks
        if ($webworks) {
            //Print webworks header
            //Iterate over each webwork
            foreach ($webworks as $webwork) {
                
                $status = fwrite ($bf,start_tag("WEBWORK",$level,true));
                
                fwrite ($bf,full_tag("CODE",$level+1,false,$webwork->code));
                fwrite ($bf,full_tag("SEED",$level+1,false,$webwork->seed));
                fwrite ($bf,full_tag("TRIALS",$level+1,false,$webwork->trials));
                
                $webworksderived = get_records('question_webwork_derived','question_webwork',$webwork->id);
                if($webworksderived) {
                    $status = fwrite ($bf,start_tag("WEBWORKDERIVED",$level+1,true));
                    foreach ($webworksderived as $webworkderived) {
                        fwrite ($bf,full_tag("ID",$level+2,false,$webworkderived->id));
                        fwrite ($bf,full_tag("QUESTION_WEBWORK",$level+2,false,$webworkderived->question_webwork));
                        fwrite ($bf,full_tag("HTML",$level+2,false,$webworkderived->html));
                        fwrite ($bf,full_tag("SEED",$level+2,false,$webworkderived->seed));
                    }
                    $status = fwrite ($bf,end_tag("WEBWORKDERIVED",$level+1,true));
                }
                $status = fwrite ($bf,end_tag("WEBWORK",$level,true));
            }
            //Print webworks footer
            //Now print question_webwork
            $status = question_backup_answers($bf,$preferences,$question);
        }
        return $status;
    }

    /**
     * Restores the data in the question
     *
     * This is used in question/restorelib.php
     */
     function restore($old_question_id,$new_question_id,$info,$restore) {

        $status = true;

        //Get the webworks array
        $webworks = $info['#']['WEBWORK'];

        //Iterate over webworks
        for($i = 0; $i < sizeof($webworks); $i++) {
            $webwork_info = $webworks[$i];

            //Now, build the question_webwork record structure
            $webwork = new stdClass;
            $webwork->question = $new_question_id;
            $webwork->code = backup_todb($webwork_info['#']['CODE']['0']['#']);
            $webwork->seed = backup_todb($webwork_info['#']['SEED']['0']['#']);
            $webwork->trials = backup_todb($webwork_info['#']['TRIALS']['0']['#']);

            //The structure is equal to the db, so insert the question_shortanswer
            $newid = insert_record("question_webwork",$webwork);
            
            $webworksderived = $webwork_info['#']['WEBWORKDERIVED'];
            for($j=0; $j < sizeof($webworksderived); $j++) {
                $webworkderived_info = $webworksderived[$j];
                
                $webworkderived = new stdClass;
                $webworkderived->question_webwork = $newid;
                $webworkderived->html = backup_todb($webworkderived_info['#']['HTML']['0']['#']);
                $webworkderived->seed = backup_todb($webworkderived_info['#']['SEED']['0']['#']);
                
                $newidderived = insert_record("question_webwork_derived",$webworkderived);
                if (!$newidderived) {
                    $status = false;
                }
            }

            //Do some output
            if (($i+1) % 50 == 0) {
                if (!defined('RESTORE_SILENTLY')) {
                    echo ".";
                    if (($i+1) % 1000 == 0) {
                        echo "<br />";
                    }
                }
                backup_flush(300);
            }

            if (!$newid) {
                $status = false;
            }
        }
        return $status;
    }
}

// Register this question type with the system.
question_register_questiontype(new webwork_qtype());
?>
