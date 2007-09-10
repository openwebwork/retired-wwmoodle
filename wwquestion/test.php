<?php
//base configuration file for moodle
require_once('../../../config.php');

echo "<b>Testing WeBWorK Question Type Setup</b><br>";
echo "------------------------------------------<br>";

//Tests configuration file existance
echo "1) Configuration File<br>";
require_once("$CFG->dirroot/question/type/webwork/config.php");
echo "WSDL Path: '" . WWQUESTION_WSDL . "'<br>";
echo "WeBWorK Question Root Directory: '" . $CFG->dataroot . WWQUESTION_ROOTDIR . "'<br>";
echo "Success<br><br>";

//Tests communication to server
echo "2) Connection to WeBWorK Question Server<br>";
require_once('locallib.php');
$client = new webwork_client();
echo "Initalized...<br>";
$response = $client->handler('hello');
echo "Connected....<br>";
echo "Response: $response<br>";
echo "Success<br><br>";

echo "All tests completed successfully.<br>";
echo "WeBWorK Question Type is setup.<br>";







?>
