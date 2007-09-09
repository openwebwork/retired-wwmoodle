<?php

require_once("$CFG->dirroot/question/type/webwork/config.php");
require_once("$CFG->libdir/soap/nusoap.php");
require_once("$CFG->libdir/filelib.php");
require_once("$CFG->libdir/setuplib.php");
require_once("$CFG->libdir/formslib.php");

/**
* @desc Gets derived questions from a webworkquestion record object by calling the SOAP server.
* @param $webworkquestion The record to create from
* @return $response The recordset from the WeBWorK Question Server.
*/
function webwork_get_derivations($wwquestion) {
    //parameters needed from the webworkquestion object
    $code = $wwquestion->code;
    $seed = $wwquestion->seed;
    $trials = $wwquestion->trials;
    $files = $wwquestion->files;
    
    //problem to be generated
    $problem = array();
    $problem['code'] = $code;
    $problem['seed'] = $seed;
    $problem['files'] = $files;
    
    //requested # times for generation
    $request = array();
    $request['trials'] = $trials;
    $request['problem'] = $problem;
    
    //SOAP CALL
    $params = array($request);
    $client = new webwork_client();
    $response = $client->handler('generateProblem',$params);
    return $response;
}

/**
* @desc Deletes a wwquestion directory
* @param integer $wwquestionid The id of the wwquestion.
* @return bool true
*/
function webwork_delete_wwquestion_dir($wwquestionid) {
    global $CFG;
    fulldelete($CFG->dataroot . WWQUESTION_ROOTDIR . '/' . $wwquestionid . '/');
    return true;
}

/**
* @desc Deletes derivation db records for a wwquestion
* @param integer $wwquestionid The id of the wwquestion.
* @return bool true
*/
function webwork_delete_derivations_db($wwquestionid) {
    delete_records("question_webwork_derived", "question_webwork", $wwquestionid);
    return true;
}

/**
* @desc Deletes all derivations of a wwquestion.
* @param integer $wwquestionid The id of the wwquestion.
* @return bool true
*/
function webwork_delete_derivations($wwquestionid) {
    $recordset = get_records('question_webwork_derived','question_webwork',$wwquestionid);
    foreach($recordset as $record) {
        $derivationid = $record->id;
        webwork_delete_derivation_dir($wwquestionid,$derivationid);
    }
    delete_records("question_webwork_derived", "question_webwork", $wwquestionid);
    return true;
}

/**
* @desc Deletes a derivation db record
* @param integer $derivationid The derivation id.
* @return bool true
*/
function webwork_delete_derivation_db($derivationid) {
    delete_records('question_webwork_derived','id',$derivationid);
    return true;
}

/**
* @desc Deletes a derivation's directory.
* @param integer $wwquestionid The wwquestion id.
* @param integer $derivationid The derivation's id.
* @return bool true.
*/
function webwork_delete_derivation_dir($wwquestionid, $derivationid) {
    global $CFG;
    $dirpath = $CFG->dataroot . webwork_get_derivation_path($wwquestionid, $derivationid);
    fulldelete($dirpath);
    return true;
}

/**
* @desc Parses an answer's preview html to change the image src path. Copies images into user data on a derivation.
* @param string $html The preview answer html.
* @param string $replacer The new src url path.
* @param string $copyto The place to copy the image to. 
*/
function webwork_parse_change_ans($html,$replacer,$copyto) {
    $parsedhtml = "";
    $parser = new HtmlParser($html);
    while($parser->parse()) {
        if($parser->iNodeType == NODE_TYPE_ELEMENT) {
            $name = $parser->iNodeName;
            if(strcasecmp($name,'img') == 0) {
                $src = $parser->iNodeAttributes['src'];
                //an equation, need to copy
                $srccapture = strrchr($src,'/');
                webwork_copy_file($src,$copyto . $srccapture);
                $parser->iNodeAttributes['src'] = $replacer . $srccapture;
            }
        }
        $parsedhtml .= $parser->printTag();
    }
    return $parsedhtml;
}

/**
* @desc Parses a derivation record. This changes all paths of external entities. Copies all external entities to local data folder.
* @param object $derivation The derivation object.
* @return bool true. 
*/
function webwork_parse_change_derivation(&$derivation) {
    //webwork question id
    $wwquestionid = $derivation->question_webwork;
    //assign paths
    $wwquestioncopyto = webwork_get_wwquestion_path_full($wwquestionid);
    $wwquestionreplacer = webwork_get_wwquestion_url($wwquestionid);
    $derivationcopyto = webwork_get_derivation_path_full($wwquestionid,$derivation->id);
    $derivationreplacer = webwork_get_derivation_url($wwquestionid,$derivation->id);
    $parsedhtml = "";
    $html = base64_decode($derivation->html);
    $parser = new HtmlParser($html); 
    while($parser->parse()) {
        if($parser->iNodeType == NODE_TYPE_ELEMENT) {
            $nodename = $parser->iNodeName;
            if(strcasecmp($nodename,'img') == 0) {
                //image, path fixing
                if(isset($parser->iNodeAttributes['src'])) {
                    $src = $parser->iNodeAttributes['src'];
                    if(!(strstr($src,'/tmp/') == false)) {
                        //image produced by the server
                        $srccapture = strrchr($src,'/');
                        webwork_copy_file($src,$derivationcopyto . $srccapture);
                        $parser->iNodeAttributes['src'] = $derivationreplacer . $srccapture;
                    }
                }
            } else if(strcasecmp($nodename,'a') == 0) {
                //hyperlink, path fixing
                if(isset($parser->iNodeAttributes['href'])) {
                    $href = $parser->iNodeAttributes['href'];
                    $hrefcapture = strrchr($href,'/');
                    webwork_copy_file($href,$derivationcopyto . $hrefcapture);
                    $parser->iNodeAttributes['href'] = $derivationreplacer . $hrefcapture;
                }
            }
        }
        $parsedhtml .= $parser->printTag();
    }
    $derivation->html = base64_encode($parsedhtml);
    return true;
}

//////////////////////////////////////////////////////////////////////////////////
//WEBWORK FILE RESOLVERS
//////////////////////////////////////////////////////////////////////////////////

//These functions return paths to different resources in the filesystem
    
    
    function webwork_get_derivation_user_url($wwquestionid,$derivationid,$userid) {
        return webwork_get_filehandler_path() . webwork_get_derivation_user_path($wwquestionid,$derivationid,$userid);
    }
    
    function webwork_get_derivation_user_path_full($wwquestionid,$derivationid,$userid) {
        global $CFG;
        return $CFG->dataroot . webwork_get_derivation_user_path($wwquestionid,$derivationid,$userid);
    }
    
    function webwork_get_derivation_user_path($wwquestionid,$derivationid,$userid) {
        return webwork_get_derivation_path($wwquestionid,$derivationid) . '/users/' . $userid;
    } 
        
    function webwork_get_derivation_url($wwquestionid, $derivationid) {
        return webwork_get_filehandler_path() . webwork_get_derivation_path($wwquestionid,$derivationid);
    }
    
    function webwork_get_derivation_path_full($wwquestionid, $derivationid) {
        global $CFG;
        return $CFG->dataroot . webwork_get_derivation_path($wwquestionid, $derivationid);
    }
    
    function webwork_get_derivation_path($wwquestionid, $derivationid) {
        return WWQUESTION_ROOTDIR . '/' . $wwquestionid . '/derivations/' . $derivationid;
    }
    
    function webwork_get_wwquestion_url($wwquestionid) {
        return webwork_get_filehandler_path() . webwork_get_wwquestion_path($wwquestionid);
    }
    
    function webwork_get_wwquestion_path_full($wwquestionid) {
        global $CFG;
        return $CFG->dataroot . webwork_get_wwquestion_path($wwquestionid);
    }
    
    function webwork_get_wwquestion_path($wwquestionid) {
        return WWQUESTION_ROOTDIR . '/' . $wwquestionid;
    }
    
    function webwork_get_filemanager_url($wwquestionid) {
        global $CFG;
        return $CFG->wwwroot . "/question/type/webwork/files.php?id=".SITEID.'&qid='.$wwquestionid;
    }
    
    function webwork_get_tmp_path_full() {
        global $CFG;
        return $CFG->dataroot . webwork_get_tmp_path();
    }
    
    function webwork_get_tmp_path() {
        global $USER;
        return WWQUESTION_ROOTDIR . '/tmp' . $USER->id; 
    }
    
    function webwork_get_filehandler_path() {
        global $CFG;
        return $CFG->wwwroot . "/question/type/webwork/file.php";
    }
    
    function webwork_make_tmp_dir() {
        return make_upload_directory(webwork_get_tmp_path());
    }
    
    /**
    * @desc Copies a file if needed from $srcpath to $dstpath.
    * @param $srcpath string The source.
    * @param $dstpath string The destination.
    * @return bool true
    */
    function webwork_copy_file($srcpath,$dstpath) {
        if(!file_exists($dstpath)) {
            $err = copy($srcpath,$dstpath);
            if($err == false) {
                print_error("Copy Failed for: '".$srcpath."' to '".$dstpath."'");
                return false;
            }
        }
        return true;
    }
    
    /**
    * @desc Makes a directory for a derivation.
    * @param $wwquestionid integer The id of a webwork_question.
    * @param $derivationid integer The id of a derivation.
    * @return bool true.
    */
    function webwork_make_derivation_dir($wwquestionid,$derivationid) {
        return make_upload_directory(webwork_get_derivation_path($wwquestionid,$derivationid));
    }
    
    /**
    * @desc Makes a directory for a derivation for a specific user.
    * @param $wwquestionid integer The id of a webwork_question.
    * @param $derivationid integer The id of a derivation.
    * @param $userid integer The id of a user.
    * @return bool true.
    */
    function webwork_make_derivation_user_dir($wwquestionid,$derivationid,$userid) {
        return make_upload_directory(webwork_get_derivation_user_path($wwquestionid,$derivationid,$userid));
    }
    
    /**
    * @desc Makes a directory for a wwquestion.
    * @param integer $wwquestionid The wwquestion id.
    * @return bool directory creation.
    */
    function webwork_make_wwquestion_dir($wwquestionid) {
        return make_upload_directory(webwork_get_wwquestion_path($wwquestionid));
    }

//////////////////////////////////////////////////////////////////////////////////
//WEBWORK CLIENT
//////////////////////////////////////////////////////////////////////////////////

/**
* @desc Singleton class that contains function for communication to the WeBWorK Question Server.
*/
class webwork_client {
    var $client;
    
    /**
    * @desc Constructs a singleton problemserver_client. Uses nusoap libraries php(4 & 5)
    */
    function webwork_client() {
        //static associative array containing the real objects, key is classname
        static $instances=array();
        //get classname
        $class = get_class($this);
        if (!array_key_exists($class, $instances)) {
            //does not yet exist, save in array
            $this->client = new soap_client(WWQUESTION_WSDL,'wsdl',false,false,false,false,0,WWQUESTION_RESPONSE_TIMEOUT);
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
    * @desc Calls a SOAP function on the server.
    * @param string $functioncall The function to call
    * @param array $params The parameters to the function.
    * @return Result of the soap function.
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

//////////////////////////////////////////////////////////////////////////////////
//HTML PARSER
//////////////////////////////////////////////////////////////////////////////////

/*
 * Copyright (c) 2003 Jose Solorzano.  All rights reserved.
 * Redistribution of source must retain this copyright notice.
 *
 * Jose Solorzano (http://jexpert.us) is a software consultant.
 *
 * Contributions by:
 * - Leo West (performance improvements)
 */

define ("NODE_TYPE_START",0);
define ("NODE_TYPE_ELEMENT",1);
define ("NODE_TYPE_ENDELEMENT",2);
define ("NODE_TYPE_TEXT",3);
define ("NODE_TYPE_COMMENT",4);
define ("NODE_TYPE_DONE",5);

/**
 * Class HtmlParser.
 * To use, create an instance of the class passing
 * HTML text. Then invoke parse() until it's false.
 * When parse() returns true, $iNodeType, $iNodeName
 * $iNodeValue and $iNodeAttributes are updated.
 *
 * To create an HtmlParser instance you may also
 * use convenience functions HtmlParser_ForFile
 * and HtmlParser_ForURL.
 */
class HtmlParser {

    /**
     * Field iNodeType.
     * May be one of the NODE_TYPE_* constants above.
     */
    var $iNodeType;

    /**
     * Field iNodeName.
     * For elements, it's the name of the element.
     */
    var $iNodeName = "";

    /**
     * Field iNodeValue.
     * For text nodes, it's the text.
     */
    var $iNodeValue = "";

    /**
     * Field iNodeAttributes.
     * A string-indexed array containing attribute values
     * of the current node. Indexes are always lowercase.
     */
    var $iNodeAttributes;

    // The following fields should be 
    // considered private:

    var $iHtmlText;
    var $iHtmlTextLength;
    var $iHtmlTextIndex = 0;
    var $iHtmlCurrentChar;
    var $BOE_ARRAY;
    var $B_ARRAY;
    var $BOS_ARRAY;
    
    /**
     * Constructor.
     * Constructs an HtmlParser instance with
     * the HTML text given.
     */
    function HtmlParser ($aHtmlText) {
        $this->iHtmlText = $aHtmlText;
        $this->iHtmlTextLength = strlen($aHtmlText);
        $this->iNodeAttributes = array();
        $this->setTextIndex (0);

        $this->BOE_ARRAY = array (" ", "\t", "\r", "\n", "=" );
        $this->B_ARRAY = array (" ", "\t", "\r", "\n" );
        $this->BOS_ARRAY = array (" ", "\t", "\r", "\n", "/" );
    }

    /**
     * Method parse.
     * Parses the next node. Returns false only if
     * the end of the HTML text has been reached.
     * Updates values of iNode* fields.
     */
    function parse() {
        $text = $this->skipToElement();
        if ($text != "") {
            $this->iNodeType = NODE_TYPE_TEXT;
            $this->iNodeName = "Text";
            $this->iNodeValue = $text;
            return true;
        }
        return $this->readTag();
    }
    
    function printTag() {
        if($this->iNodeType == NODE_TYPE_ELEMENT) {
            $temp = "<";
            $temp .= $this->iNodeName;
            if(isset($this->iNodeAttributes['selected'])) {
                $temp .= " selected";
            }
            foreach($this->iNodeAttributes as $key => $value) {
                if($key == 'selected') {
                } else {
                    $temp .= " " . $key . "=" . '"' . $value . '"';
                }
            }
            $temp .= ">";
        } else if($this->iNodeType == NODE_TYPE_ENDELEMENT) {
            $temp = "</" . $this->iNodeName . ">";
        } else if($this->iNodeType == NODE_TYPE_TEXT) {
            $temp = $this->iNodeValue;
        } else {
        }
        return $temp;
         
        
    }
    
    function clearAttributes() {
        $this->iNodeAttributes = array();
    }

    function readTag() {
        if ($this->iCurrentChar != "<") {
            $this->iNodeType = NODE_TYPE_DONE;
            return false;
        }
        $this->clearAttributes();
        $this->skipMaxInTag ("<", 1);
        if ($this->iCurrentChar == '/') {
            $this->moveNext();
            $name = $this->skipToBlanksInTag();
            $this->iNodeType = NODE_TYPE_ENDELEMENT;
            $this->iNodeName = $name;
            $this->iNodeValue = "";            
            $this->skipEndOfTag();
            return true;
        }
        $name = $this->skipToBlanksOrSlashInTag();
        if (!$this->isValidTagIdentifier ($name)) {
                $comment = false;
                if (strpos($name, "!--") === 0) {
                    $ppos = strpos($name, "--", 3);
                    if (strpos($name, "--", 3) === (strlen($name) - 2)) {
                        $this->iNodeType = NODE_TYPE_COMMENT;
                        $this->iNodeName = "Comment";
                        $this->iNodeValue = "<" . $name . ">";
                        $comment = true;                        
                    }
                    else {
                        $rest = $this->skipToStringInTag ("-->");    
                        if ($rest != "") {
                            $this->iNodeType = NODE_TYPE_COMMENT;
                            $this->iNodeName = "Comment";
                            $this->iNodeValue = "<" . $name . $rest;
                            $comment = true;
                            // Already skipped end of tag
                            return true;
                        }
                    }
                }
                if (!$comment) {
                    $this->iNodeType = NODE_TYPE_TEXT;
                    $this->iNodeName = "Text";
                    $this->iNodeValue = "<" . $name;
                    return true;
                }
        }
        else {
                $this->iNodeType = NODE_TYPE_ELEMENT;
                $this->iNodeValue = "";
                $this->iNodeName = $name;
                while ($this->skipBlanksInTag()) {
                    $attrName = $this->skipToBlanksOrEqualsInTag();
                    if ($attrName != "" && $attrName != "/") {
                        $this->skipBlanksInTag();
                        if ($this->iCurrentChar == "=") {
                            $this->skipEqualsInTag();
                            $this->skipBlanksInTag();
                            $value = $this->readValueInTag();
                            $this->iNodeAttributes[strtolower($attrName)] = $value;
                        }
                        else {
                            $this->iNodeAttributes[strtolower($attrName)] = "";
                        }
                    }
                }
        }
        $this->skipEndOfTag();
        return true;            
    }

    function isValidTagIdentifier ($name) {
        return ereg ("^[A-Za-z0-9_\\-]+$", $name);
    }
    
    function skipBlanksInTag() {
        return "" != ($this->skipInTag ($this->B_ARRAY));
    }

    function skipToBlanksOrEqualsInTag() {
        return $this->skipToInTag ($this->BOE_ARRAY);
    }

    function skipToBlanksInTag() {
        return $this->skipToInTag ($this->B_ARRAY);
    }

    function skipToBlanksOrSlashInTag() {
        return $this->skipToInTag ($this->BOS_ARRAY);
    }

    function skipEqualsInTag() {
        return $this->skipMaxInTag ("=", 1);
    }

    function readValueInTag() {
        $ch = $this->iCurrentChar;
        $value = "";
        if ($ch == "\"") {
            $this->skipMaxInTag ("\"", 1);
            $value = $this->skipToInTag ("\"");
            $this->skipMaxInTag ("\"", 1);
        }
        else if ($ch == "'") {
            $this->skipMaxInTag ("'", 1);
            $value = $this->skipToInTag ("'");
            $this->skipMaxInTag ("'", 1);
        }                
        else {
            $value = $this->skipToBlanksInTag();
        }
        return $value;
    }

    function setTextIndex ($index) {
        $this->iHtmlTextIndex = $index;
        if ($index >= $this->iHtmlTextLength) {
            $this->iCurrentChar = -1;
        }
        else {
            $this->iCurrentChar = $this->iHtmlText{$index};
        }
    }

    function moveNext() {
        if ($this->iHtmlTextIndex < $this->iHtmlTextLength) {
            $this->setTextIndex ($this->iHtmlTextIndex + 1);
            return true;
        }
        else {
            return false;
        }
    }

    function skipEndOfTag() {
        while (($ch = $this->iCurrentChar) !== -1) {
            if ($ch == ">") {
                $this->moveNext();
                return;
            }
            $this->moveNext();
        }
    }

    function skipInTag ($chars) {
        $sb = "";
        while (($ch = $this->iCurrentChar) !== -1) {
            if ($ch == ">") {
                return $sb;
            } else {
                $match = false;
                for ($idx = 0; $idx < count($chars); $idx++) {
                    if ($ch == $chars[$idx]) {
                        $match = true;
                        break;
                    }
                }
                if (!$match) {
                    return $sb;
                }
                $sb .= $ch;
                $this->moveNext();
            }
        }
        return $sb;
    }

    function skipMaxInTag ($chars, $maxChars) {
        $sb = "";
        $count = 0;
        while (($ch = $this->iCurrentChar) !== -1 && $count++ < $maxChars) {
            if ($ch == ">") {
                return $sb;
            } else {
                $match = false;
                for ($idx = 0; $idx < count($chars); $idx++) {
                    if ($ch == $chars[$idx]) {
                        $match = true;
                        break;
                    }
                }
                if (!$match) {
                    return $sb;
                }
                $sb .= $ch;
                $this->moveNext();
            }
        }
        return $sb;
    }

    function skipToInTag ($chars) {
        $sb = "";
        while (($ch = $this->iCurrentChar) !== -1) {
            $match = $ch == ">";
            if (!$match) {
                for ($idx = 0; $idx < count($chars); $idx++) {
                    if ($ch == $chars[$idx]) {
                        $match = true;
                        break;
                    }
                }
            }
            if ($match) {
                return $sb;
            }
            $sb .= $ch;
            $this->moveNext();
        }
        return $sb;
    }

    function skipToElement() {
        $sb = "";
        while (($ch = $this->iCurrentChar) !== -1) {
            if ($ch == "<") {
                return $sb;
            }
            $sb .= $ch;
            $this->moveNext();
        }
        return $sb;             
    }

    /**
     * Returns text between current position and $needle,
     * inclusive, or "" if not found. The current index is moved to a point
     * after the location of $needle, or not moved at all
     * if nothing is found.
     */
    function skipToStringInTag ($needle) {
        $pos = strpos ($this->iHtmlText, $needle, $this->iHtmlTextIndex);
        if ($pos === false) {
            return "";
        }
        $top = $pos + strlen($needle);
        $retvalue = substr ($this->iHtmlText, $this->iHtmlTextIndex, $top - $this->iHtmlTextIndex);
        $this->setTextIndex ($top);
        return $retvalue;
    }
}

?>
