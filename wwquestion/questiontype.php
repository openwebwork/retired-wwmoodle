<?php

require_once("$CFG->libdir/soap/nusoap.php");

require_once("htmlparser.php");

//Path to the WSDL file on the Webwork Server
define('PROBLEMSERVER_WSDL','http://128.151.231.20/WSDL.wsdl');
define('PROBLEMSERVER_DISPLAYMODE','images');


/**
 * The question type class for the webwork question type.
 *
 * @copyright &copy; 2007 Matthew Leventi
 * @author mleventi@gmail.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package webwork_qtype
 * 
**/

/**
 * The webwork question class
 *
 * Allows webwork questions to be used in Moodle through a new question type.
 */
class webwork_qtype extends default_questiontype {

    function name() {
        return 'webwork';
    }

    /**
     * @desc Retrieves the seed and decoded code out of the question_webwork table.
     * @return boolean to indicate success of failure.
     */
    function get_question_options(&$question) {
        // TODO code to retrieve the extra data you stored in the database into
        // $question->options.
        if (!$record = get_record('question_webwork', 'question', $question->id)) {
            notify('Error: Missing question options!');
            return false;
        }
        $question->seed = $record->seed;
        $question->code = base64_decode($record->code);
        return true;
    }

    /**
     * @desc Saves the webwork question code and default seed setting into question_webwork
     * @return boolean to indicate success of failure.
     */
    function save_question_options($question) {
        // TODO code to save the extra data to your database tables from the
        // $question object, which has all the post data from editquestion.html
        // Save question options in question_webwork table
        if ($record = get_record("question_webwork", "question", $question->id)) {
            //get rid of all the derived questions based on this one
            $this->delete_derived_questions($question->id);
            
            $record->code = base64_encode(stripslashes($question->code));
            $record->seed = $question->seed;
            $record->trials = $question->trials;
            $result = update_record("question_webwork", $record);
            if (!$result) {
                $result->error = "Could not update quiz webwork options! (id=$record->id)";
                return $result;
            }
            
        } else {
            unset($record);
            $record->question    = $question->id;
            $record->code = base64_encode(stripslashes($question->code));
            $record->seed = $question->seed;
            $record->trials = $question->trials;
            $result = insert_record("question_webwork", $record);
            if (!$result) {
                $result->error = "Could not insert quiz webwork options!";
                return $result;
            }
            $record->id = $result;
        }
        
        return $this->create_derived_questions($record);
    }
    function delete_derived_questions($questionid) {
        delete_records("question_webwork_derived", "question_webwork", $questionid);
        return true;
    }
    function create_derived_questions($question) {
        $code = $question->code;
        $seed = $question->seed;
        $trials = $question->trials;
        $parentid = $question->id;
        
        $params = array('code' => $code, 'seed' => $seed, 'trials' => $trials);
        $client = new problemserver_client();
        $response = $client->handler('generateProblems',$params);
        
        $record->question_webwork = $parentid;
        foreach($response as $problem) {
            $record->html = $problem['html'];
            $record->seed = $problem['seed'];
            $result = insert_record("question_webwork_derived",$record);
            if (!$result) {
                $result->error = "Could not insert quiz webwork derived options!";
                return $result;
            }
        }
        return true;
    }

    /**
     * @desc Deletes question from the question_webwork table
     * @param integer $questionid The question being deleted
     * @return boolean to indicate success of failure.
     */
    function delete_question($questionid) {
        $this->delete_derived_questions($questionid);
        delete_records("question_webwork", "question", $questionid);  
        return true;
    }
    
    /**
    * @desc Creates an empty response before student answers a question. This contains the possibly randomized seed for that particular student. Sticky seeds are created here.
    */
    function create_session_and_responses(&$question, &$state, $cmoptions, $attempt) {        
        //here we get the derived results for this question
        $results = get_records("question_webwork_derived","question_webwork",$question->id,'','id');
        if(!$results) {
            print_error(get_string('error_db_webwork_derived','qtype_webwork'));
            return false;
        }
        //make sure its not 0
        if(count($results) == 0) {
            print_error(get_string('error_no_webwork_derived','qtype_webwork'));
            return false;
        }
        srand(time());
        $random = rand(0,count($results)-1);
        $values = array_values($results);
        $derivedid = $values[$random]->id;
        
        //get the actual data
        $results = get_record('question_webwork_derived',"id",$derivedid);
        $state->responses['seed'] = $results->seed;
        $state->responses['derivedid'] = $derivedid;
        
        $unparsedhtml = base64_decode($results->html);
        //parse source to figure out the fields
        $parser = new HtmlParser($unparsedhtml);
        $currentselect = "";
        while($parser->parse()) {
            if ($parser->iNodeType == NODE_TYPE_ELEMENT) {
                $nodename = $parser->iNodeName;
                $name = $parser->iNodeAttributes['name'];
                if(($nodename == "INPUT") || ($nodename == "SELECT") || ($nodename == "TEXTAREA")) {
                    //THIS IS A FIELD WE NEED TO KNOW ABOUT
                    $state->responses['answers'][$name] = "";
                }
            }   
        }
        return true;
    }
    
    /**
    * @desc Decodes and unserializes a students response into the response array carried by state
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
    */
    function save_session_and_responses(&$question, &$state) {
        // TODO package up the students response from the $state->responses
        // array into a string and save it in the question_states.answer field.
        $responses = $state->responses;
        $serialized = serialize($responses);
        $serialized = base64_encode($serialized);
        return set_field('question_states', 'answer', $serialized, 'id', $state->id);
    }
    
    /**
    * @desc Prints the question. Calls the Webwork Server for appropriate HTML output and image paths.
    */
    function print_question_formulation_and_controls(&$question, &$state, $cmoptions, $options) {
        global $CFG;
        $readonly = empty($options->readonly) ? '' : 'disabled="disabled"';
        //Formulate question image and text
        $questiontext = $this->format_text($question->questiontext,
                $question->questiontextformat, $cmoptions);
        $image = get_question_image($question, $cmoptions->course);
        
        $derivedid = $state->responses['derivedid'];
        $result = get_record('question_webwork_derived','id',$derivedid);
        $unparsedhtml = base64_decode($result->html);
        
        //new array keyed by field
        $fieldhash = $state->responses['answers'];
        $answerfields = $fieldhash;
        
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
                        $parser->iNodeAttributes['class'] = $parser->iNodeAttributes['class'] . question_get_feedback_class($fieldhash[$name]['score']);
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
        
        include("$CFG->dirroot/question/type/webwork/display.html");
    }
    
    /**
    * @desc Assigns a grade for a student response. Currently a percentage right/total questions. Calls the Webwork Server to evaluate answers
    */
    function grade_responses(&$question, &$state, $cmoptions) {
        // TODO assign a grade to the response in state.
        //get code
        //echo "GRADE";
        //var_dump($state);
        $code = base64_encode($question->code);
        $seed = $state->responses['seed'];
        echo  $seed;
        //get answers
        $answerarray = array();
        foreach($state->responses as $key => $value) {
            array_push($answerarray, array('field' => $key, 'answer'=> $value));
        }
        
        $params = array('code'=>$code, 'seed'=>$seed, 'answers'=>$answerarray);
        $client = new problemserver_client();
        $response = $client->handler('checkAnswers',$params);
        
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
        
        // Apply the penalty for this attempt
        $state->penalty = $question->penalty * $question->maxgrade;

        // mark the state as graded
        $state->event = ($state->event ==  QUESTION_EVENTCLOSE) ? QUESTION_EVENTCLOSEANDGRADE : QUESTION_EVENTGRADE;
        
        $state->responses['answers'] = array();
        foreach ($answers as $answer) {
            $state->responses['answers'][$answer['field']] = $answer;
        }
        
        return true;
        //var_dump($state);
    }
    
    /**
    * @desc Comparison of two student responses for the same question. Checks based on seed equality, and response equality.
    * Perhaps we could add check on evaluated answer (depends on whether the server is called before this function)
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
    * @desc Gets the correct answers from the server for the seed in state. Places them into the state->responses array.
    */
    function get_correct_responses(&$question, &$state) {
        
        $code = base64_encode($question->code);
        
        $seed = $state->responses['seed'];
        echo $seed;
        
        //tricks checkAnswer into believing we are sending an anwer
        $answerarray = array(array('field'=>'','answer'=>''));

        
        $params = array('code'=>$code, 'seed'=>$seed, 'answers'=>$answerarray);
        $client = new problemserver_client();
        $response = $client->handler('checkAnswers',$params);
        $answers = $response;
        $ret = array();
        $ret['answers'] = array();
        foreach ($answers as $answer) {
            $ret['answers'][$answer['field']] = $answer;
            $ret['answers'][$answer['field']]['answer'] = $answer['correct'];
        }
        //push the seed onto the answer array, keep track of what seed these are for.
        $ret['seed'] = $state->responses['seed'];
        $ret['derivedid'] = $state->responses['derivedid'];
        return $ret;
    }
    
    /**
    * @desc Prints a short 40 character limited version of all the answers for a question.
    */
    function get_actual_response($question, $state) {
        // TODO
        $temp = '';
        $i = 1;
        foreach($state->responses['answers'] as $key => $value) {
            $temp .= "$i) " . $value['answer'] . " ";
            $i++;
        }
        $lmax = 40;
        $responses[] = (strlen($temp) > $lmax) ? substr($temp, 0, $lmax).'...' : $temp;
        return $responses;
    }
    
    /**
     * Backup the data in the question
     *
     * This is used in question/backuplib.php
     */
    function backup($bf,$preferences,$question,$level=6) {

        $status = true;

        $webworks = get_records('question_webwork', 'question', $question, 'id ASC');
        //If there are webworks
        if ($webworks) {
            //Iterate over each webwork
            foreach ($webworks as $webwork) {
                $status = fwrite ($bf,start_tag("WEBWORK",$level,true));
                //Print webwork contents
                fwrite ($bf,full_tag("CODE",$level+1,false,$webwork->code));
                fwrite ($bf,full_tag("SEED",$level+1,false,$webwork->seed));
                $status = fwrite ($bf,end_tag("WEBWORK",$level,true));
            }
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

        //Get the shortanswers array
        $webworks = $info['#']['WEBWORK'];

        //Iterate over shortanswers
        for($i = 0; $i < sizeof($webworks); $i++) {
            $webwork_info = $webworks[$i];

            //Now, build the question_shortanswer record structure
            $webwork = new stdClass;
            $webwork->question = $new_question_id;
            $webwork->code = backup_todb($webwork_info['#']['CODE']['0']['#']);
            $webwork->seed = backup_todb($webwork_info['#']['SEED']['0']['#']);

            //The structure is equal to the db, so insert the question_shortanswer
            $newid = insert_record("question_webwork",$webwork);

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

/**
* @desc Singleton class that contains function for communication to the server.
*/
class problemserver_client {
        var $client;
        /**
         * @desc Constructs a singleton problemserver_client.
         */
        function problemserver_client() {
            // static associative array containing the real objects, key is classname
            static $instances=array();
            // get classname
            $class = get_class($this);
            if (!array_key_exists($class, $instances)) {
                // does not yet exist, save in array
                $this->client = new soap_client(PROBLEMSERVER_WSDL,'wsdl');
                $err = $this->client->getError();
                if ($err) {
                    print_error($err . " " . get_string('error_client_construction','qtype_webwork'));
                }
                $instances[$class] = $this;
            }
            foreach (get_class_vars($class) as $var => $value) {
                $this->$var =& $instances[$class]->$var;
            }
        }
            
        /**
         *@desc Calls a SOAP function and passes (authenkey,course) automatically in the parameter list.
         *@param string $functioncall The function to call
         *@param array $params The parameters to the function.
         *@return Result of the soap function.
         */
        function handler($functioncall,$params=array()) {
            if(!is_array($params)) {
                $params = array($params);   
            }
            $result = $this->client->call($functioncall,$params);
            //$result = call_user_func_array(array(&$this->client,$functioncall),$params);
            if($err = $this->client->getError()) {
                //print_error(get_string("rpc_fault","wwassignment') . " " . $functioncall. " ". $err);
                print_error($err . " " . get_string('error_client_call','qtype_webwork'));  
            }
            return $result;
        }
}



// Register this question type with the system.
question_register_questiontype(new webwork_qtype());
?>
