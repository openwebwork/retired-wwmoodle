<?php
require_once(dirname(__FILE__) . '/../../../../config.php');

global $CFG;
require_once($CFG->libdir . '/simpletestlib.php');
require_once($CFG->dirroot . '/question/type/webwork/questiontype.php');
require_once($CFG->dirroot . '/question/type/webwork/locallib.php');
require_once($CFG->dirroot . '/question/type/webwork/config.php');


class webwork_qtype_test extends UnitTestCase {      
    var $qtype;
    var $questioncode;
    
    function setUp() {
        global $CFG;
        $this->qtype = new webwork_qtype();
        $filepath = $CFG->dirroot . '/question/type/webwork/simpletest/sampleProblem.pg';
        if(!file_exists($filepath)) {
            die('Test PG file doesnt exist.');
        }
        $this->questioncode = file_get_contents($filepath);
    }
    
    function tearDown() {
        $this->qtype = null;   
    }
    
    /**
    * @desc Test Module Name.
    */
    function test_name() {
        $this->assertEqual($this->qtype->name(),'webwork','Module Test');
    }
    
    /**
    * @desc Test WSDL Path is set.
    */
    function test_wsdl_path() {
        $this->assertNotEqual(WWQUESTION_WSDL,'ENTER YOUR WSDL PATH HERE','WSDL Path Test');
    }
    
    /**
    * @desc Test Menu Name
    */
    function test_menu_name() {
        $this->assertEqual($this->qtype->menu_name(),'WeBWorK','Menu Test');
    }
    
    /**
    * @desc Test Basic Communication
    */
    function test_basic_communication() {
        $client = new webwork_client();
        $response = $client->handler('hello');
        $this->assertEqual('hello world!',$response,'Webwork Server Communication Test.');
    }
    
    /**
    * @desc Test that problem is rendered
    */
    function test_render_communication() {
        $question->code = base64_encode(stripslashes($this->questioncode));
        $question->trials = 10;
        $question->seed = 0;
        $results = webwork_get_derivations($question);
        $numresults = count($results);
        $warning = false;
        $error = false;
        $ret = $this->assertEqual('10',$numresults,'PG Count Test');
        if($ret) {
            foreach($results as $record) {
                if((isset($record['errors'])) && ($record['errors'] != '') && ($record['errors'] != null)) {
                    $error = true;
                    $theerror = $record['errors'];
                    //$this->assertEqual(false,true);
                }
                if((isset($record['warnings'])) && ($record['warnings'] != '') && ($record['warnings'] != null)) {
                    $warning = true;
                    //$this->assertEqual(false,true);
                }
            }       
        }
        $this->assertEqual(false,$error,'Sample PG Error Test');
        $this->assertEqual(false,$warning,'Sample PG Warning Test');
    }
    
    /**
    * @desc Test creation of a wwquestion directory
    */
    function test_create_wwquestion_directory() {
        webwork_make_wwquestion_dir('SIMPLETEST');
        $path = webwork_get_wwquestion_path_full('SIMPLETEST');
        $this->assertEqual(is_dir($path),true,'Webwork Question Directory Creation Test.');
        webwork_delete_wwquestion_dir('SIMPLETEST');
    }
    
    /**
    * @desc Test creation of a derivation directory
    */
    function test_create_derivation_directory() {
        webwork_make_derivation_dir('SIMPLETEST','SIMPLETEST');
        $path = webwork_get_derivation_path_full('SIMPLETEST','SIMPLETEST');
        $this->assertEqual(is_dir($path),true,'Webwork Derivation Directory Test.');
        webwork_delete_derivation_dir('SIMPLETEST','SIMPLETEST');
    }

//    function test_get_question_options() {
//    }
//
//    function test_get_numerical_units() {
//    }
//
//    function test_get_default_numerical_unit() {
//    }
//
//    function test_save_question_options() {
//    }
//
//    function test_save_numerical_units() {
//    }
//
//    function test_delete_question() {
//    }
//
//    function test_compare_responses() {
//    }
//
//    function test_test_response() {
//    }
//
//    function test_check_response(){
//    }
//
//    function test_grade_responses() {
//    }
//
//    function test_get_correct_responses() {
//    }
//
//    function test_get_all_responses() {
//    }


//    function test_backup() {
//    }
//
//    function test_restore() {
//    }
}

?>

