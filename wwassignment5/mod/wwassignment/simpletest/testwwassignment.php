<?php
require_once(dirname(__FILE__) . '/../../config.php');

global $CFG;

require_once($CFG->libdir . '/simpletestlib.php');
require_once($CFG->dirroot . '/mod/wwassignment/locallib.php');

class wwassignment_test extends UnitTestCase {      

    function setUp() {
    }
    
    function tearDown() {  
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
    * @desc Test Webwork Path
    */
    function test_webwork_path() {
    }

    /**
    * @desc Test Basic Communication
    */
    function test_basic_communication() {
        $client = new wwassignment_client();
        $response = $client->handler('hello');
        $this->assertEqual('hello world!',$response,'Webwork Server Communication Test.');
    }
}

?>

