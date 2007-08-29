<?php

require_once("$CFG->libdir/soap/nusoap.php");

require_once("htmlparser.php");

//Path to the WSDL file on the Webwork Server
define('PROBLEMSERVER_WSDL','YOUR WSDL PATH HERE');


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
    
    function _derivedquestions($derivedquestions = null) {
        static $temp = null;
        if($derivedquestions == null) {
            return $temp;
        }
        $temp = $derivedquestions;
        return true;
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
        $question->trials = $record->trials;
        $question->seed = $record->seed;
        $question->code = base64_decode($record->code);
        $question->codecheck = $record->codecheck;
        $question->webworkid = $record->id;
        return true;
    }

    /**
     * @desc Saves the webwork question code and default seed setting into question_webwork. Will recreate all corresponding derived questions.
     * @return boolean to indicate success of failure.
     */
    function save_question_options($question) {
                
        //UPDATE OR INSERTION
        if ($record = get_record("question_webwork", "question", $question->id)) {
            $isupdate = true;
        } else {
            $isupdate = false;
            unset($record);
        }
        //set new variables for update or insert
        $record->question = $question->id;
        $record->codecheck = $question->codecheck;
        $record->code = base64_encode(stripslashes($question->code));
        $record->seed = $question->seed;
        $record->trials = $question->trials;
        
        //create the derived questions and check for errors
        //$results = $question->derivedquestions;
        
        //do the database action on question_webwork
        if($isupdate) {
            $errresult = update_record("question_webwork", $record);
            if (!$errresult) {
                $errresult->error = "Could not update quiz webwork options! (id=$record->id)";
                return $errresult;
            } 
        } else {
            $errresult = insert_record("question_webwork", $record);
            if (!$errresult) {
                $errresult->error = "Could not insert quiz webwork options!";
                return $errresult;
            }
            $record->id = $errresult; 
        }
        
        //delete the derived questions
        $this->delete_derived_questions($record->id);
        
        //do the database action on question_webwork_derived
        $err = $this->insert_derived_questions($record->id,$this->_derivedquestions());
        if($err != 0) {
            return $err;
        }
        return true;
    }
    
    /**
    * @desc Deletes all derived questions that are children of the ID passed in.
    * @param $webworkquestionid integer The ID of the parent question
    */
    function delete_derived_questions($webworkquestionid) {
        delete_records("question_webwork_derived", "question_webwork", $webworkquestionid);
        return true;
    }
    
    /**
    * @desc Gets derived questions from a webworkquestion record object by calling the SOAP object.
    * @param $webworkquestion The record to create from
    * 
    */
    function get_derived_questions($webworkquestion) {
        //parameters needed from the webworkquestion object
        $code = $webworkquestion->code;
        $seed = $webworkquestion->seed;
        $trials = $webworkquestion->trials;
        
        
        //problem to be generated
        $problem = array();
        $problem['code'] = $code;
        $problem['seed'] = $seed;
        
        //requested # times for generation
        $request = array();
        $request['trials'] = $trials;
        $request['problem'] = $problem;
        
        //SOAP CALL
        $params = array($request);
        $client = new problemserver_client();
        $response = $client->handler('generateProblem',$params);
        return $response;
    }
    
    
    /**
    * @desc Inserts the derived questions into the DB.
    * @param $parentid The parent ID of the derived questions.
    * @param $derivedrecordset The recordset to create from.
    */
    function insert_derived_questions($parentid,$derivedrecordset) {
        
        foreach($derivedrecordset as $problem) {
            unset($record);
            //set the parent id for the derived questions
            $record->question_webwork = $parentid;
            $record->html = $problem['output'];
            $record->seed = $problem['seed'];
            //initial insert
            $result = insert_record("question_webwork_derived",$record);
            if (!$result) {
                $result->error = "Could not insert quiz webwork derived options!";
                return $result;
            }
            $record->id = $result;
            
            //brings image files to local drive
            //THIS SHOULD ALSO DO APPLETS SOON
            $err = $this->copy_derived_question_data($record);
            if($err != 0) {
                return $err;
            }
            
            $result = update_record("question_webwork_derived",$record);
            if(!$result) {
                $result->error = "Could not update quiz webwork derived options! (id=$record->id)";
                return $result;
            }
            
        }
        return false;
    }
    
    function copy_derived_question_data(&$derivedrecord) {
        global $CFG;
        //make the base directory if needed
        $dir = $CFG->dataroot . '/wwquestions';
        mkdir($dir);
        //make the directory for this question
        $dir = $CFG->dataroot . '/wwquestions/'.$derivedrecord->id;
        mkdir($dir);
        
        //first we need to find the image paths
        $imagestocopy = array();
        $problemhtml = "";
        $unparsedhtml = base64_decode($derivedrecord->html);
        $parser = new HtmlParser($unparsedhtml);
        while($parser->parse()) {
             if ($parser->iNodeType == NODE_TYPE_ELEMENT) {
                 $nodename = $parser->iNodeName;
                 //rewrite the images
                 if(($nodename == "IMG") || ($nodename == "img")) {
                     //found one
                     $srcpath = $parser->iNodeAttributes['src'];
                     $srcfilename = strrchr($srcpath,'/');
                     $parser->iNodeAttributes['src'] = $CFG->wwwroot . "/question/type/webwork/file.php/wwquestions/" . $derivedrecord->id . '/' . $srcfilename;
                     //NOTE explore the possibility of having an existence check here, filenames hashed?
                     //copy it
                     $err = copy($srcpath,$CFG->dataroot.'/wwquestions/'.$derivedrecord->id.$srcfilename);
                     if($err == false) {
                         $err->error = 'Copy Failed for: '.$srcpath;
                         return $err;
                     }
                     
                     
                 }
             }
             $problemhtml .= $parser->printTag();
        }
        $html = base64_encode($problemhtml);
        $derivedrecord->html = $html;
        return false;
    }
    

    /**
     * @desc Deletes question from the question_webwork table
     * @param integer $questionid The question being deleted
     * @return boolean to indicate success of failure.
     */
    function delete_question($questionid) {
        //Deleting the webwork derived questions
        $records = get_records('question_webwork','question',$questionid,'','id');
        foreach($records as $record) {
            $this->delete_derived_questions($record->id);
        }
        //Deleting the webwork questions
        delete_records("question_webwork", "question", $questionid);  
        return true;
    }
    
    /**
    * @desc Creates an empty response before student answers a question. This contains the possibly randomized seed for that particular student. Sticky seeds are created here.
    */
    function create_session_and_responses(&$question, &$state, $cmoptions, $attempt) {
        global $CFG,$USER;
        
        //directory housekeeping (insure directories are setup)
        mkdir($CFG->dataroot.'/wwquestions/users');
        mkdir($CFG->dataroot.'/wwquestions/users/'.$USER->id);

        
                
        //here we get the derived results for this question
        $results = get_records("question_webwork_derived","question_webwork",$question->webworkid,'','id');
        if(!$results) {
            print_error(get_string('error_db_webwork_derived','qtype_webwork'));
            return false;
        }
        //make sure its not 0
        if(count($results) == 0) {
            print_error(get_string('error_no_webwork_derived','qtype_webwork'));
            return false;
        }
        //pick a random question based on time
        srand(time());
        $random = rand(0,count($results)-1);
        $values = array_values($results);
        $derivedid = $values[$random]->id;
        
        //more directory housekeeping
        mkdir($CFG->dataroot.'/wwquestions/users/'.$USER->id.'/'.$derivedid);
        
        //get the actual data
        $results = get_record('question_webwork_derived','id',$derivedid);
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
        global $CFG,$USER;
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
                        $parser->iNodeAttributes['class'] = $parser->iNodeAttributes['class'] . question_get_feedback_class($fieldhash[$name]['score']);
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
    * @desc Assigns a grade for a student response. Currently a percentage right/total questions. Calls the Webwork Server to evaluate answers
    */
    function grade_responses(&$question, &$state, $cmoptions) {
        global $CFG,$USER;
        //get code and seed of the students problem
        $code = base64_encode($question->code);
        $seed = $state->responses['seed'];
        $derivedid = $state->responses['derivedid'];
        
        //get answers & build answer request
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
        $client = new problemserver_client();
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
        
        // Apply the penalty for this attempt
        $state->penalty = $question->penalty * $question->maxgrade;

        // mark the state as graded
        $state->event = ($state->event ==  QUESTION_EVENTCLOSE) ? QUESTION_EVENTCLOSEANDGRADE : QUESTION_EVENTGRADE;
        
        //put the responses into the state to remember
        $state->responses['answers'] = array();
        foreach ($answers as $answer) {
            //parse and change the preview paths
            $unparsedhtml = base64_decode($answer['preview']);
            $ansparser = new HtmlParser($unparsedhtml);
            $parsedhtml = "";
            while($ansparser->parse()) {
                if($ansparser->iNodeType == NODE_TYPE_ELEMENT) {
                    $nodename = $ansparser->iNodeName;
                    if(($nodename == 'img') || ($nodename == 'IMG')) {
                        $srcpath = $ansparser->iNodeAttributes['src'];
                        $srcfilename = strrchr($srcpath,'/');
                        $newpath = "/wwquestions/users/" . $USER->id . '/' . $derivedid . '' . $srcfilename;
                        $ansparser->iNodeAttributes['src'] = $CFG->wwwroot . "/question/type/webwork/file.php$newpath";
                        //copy it
                        $err = copy($srcpath,$CFG->dataroot . $newpath);
                        if($err == false) {
                            print_error("copy operation failed src:'$srcpath' dest:'$newpath'");
                        }
                    }
                }
                $parsedhtml .= $ansparser->printTag();
                $answer['preview'] = $parsedhtml;
            }
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
        //get code and seed of response
        $code = base64_encode($question->code);
        $seed = $state->responses['seed'];
        
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
        $client = new problemserver_client();
        $response = $client->handler('checkAnswers',$params);
        
        //process correct answers into fields
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
