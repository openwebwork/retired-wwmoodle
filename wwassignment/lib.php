<?php
// $Id: lib.php,v 1.15 2007-06-29 19:04:41 mleventi Exp $

require_once("$CFG->libdir/soap/nusoap.php");

define('WWASSIGNMENT_WEBWORK_URL', $CFG->wwassignment_webworkurl);
define('WWASSIGNMENT_WEBWORK_WSDL', $CFG->wwassignment_rpc_wsdl);
define('WWASSIGNMENT_WEBWORK_KEY',$CFG->wwassignment_rpc_key);

/**
* @desc Creates an auto_login link to the URL of the webwork problem set pointed to by wwassignmentid for the current user. (will force set creation if necessary)
* @param string $wwassignmentid The Moodle ID of the wwassignment.
* @return URL to the assignment.
*/
function wwassignment_view_link($wwassignmentid) {
    global $COURSE,$USER;
    
    $webworkclient =& new webwork_client();
    $webworkcourse = _wwassignment_mapped_course($COURSE->id,false);
    $webworkset = _wwassignment_mapped_set($wwassignmentid,false);
    $webworkuser = $webworkclient->mapped_user($webworkcourse,$USER->username);
    if($webworkuser == -1) {
        error_log(get_string("user_not_mapped","wwassignment"));
        $tempuser = $USER;
        $newuser = $webworkclient->create_user($webworkcourse,$tempuser);
        $webworkuser = $webworkclient->mapped_user($webworkcourse,$USER->username,false); 
    }
    
    $webworksetuser = $webworkclient->mapped_user_set($webworkcourse,$webworkuser,$webworkset);
    if($webworksetuser == -1) {
        error_log(get_string("set_user_not_mapped","wwassignment"));
        //try and do it now
        $newsetuser = $webworkclient->create_user_set($webworkcourse,$webworkuser,$webworkset);
        //AGAIN FOR GOOD MEASURE
        $webworksetuser = $webworkclient->mapped_user_set($webworkcourse,$webworkuser,$webworkset,false);
    }
    
    $key = $webworkclient->login_user($webworkcourse,$webworkuser,false);
    
    return _wwassignment_link_to_set_auto_login($webworkcourse,$webworkset,$webworkuser,$key);   
}

/**
* @desc Creates an auto_login link to the URL for editing the problem set associated with $wwassignmentid.
* @param string $wwassignmentid The assignment id.
* @return URL The URL.
*/
function wwassignment_edit_set_link($wwassignmentid) {
    global $COURSE,$USER;
    $webworkclient =& new webwork_client();
    
    //IS THE COURSE MAPPED?
    $webworkcourse = _wwassignment_mapped_course($COURSE->id,false);
    //IS THE SET MAPPED?
    $webworkset = _wwassignment_mapped_set($wwassignmentid,false);
    //IS THE USER MAPPED?
    $webworkuser = $webworkclient->mapped_user($webworkcourse,$USER->username);
    if($webworkuser == -1) {
        //USER WAS NOT FOUND, wasnt mapped
        error_log(get_string("user_not_mapped","wwassignment"));
        //try and create a teacher
        $tempuser = $USER;
        $newuser = $webworkclient->create_user($webworkcourse,$tempuser,"10");
        //AGAIN FOR GOOD MEASURE
        $webworkuser = $webworkclient->mapped_user($webworkcourse,$USER->username,false);
    }
    $key = $webworkclient->login_user($webworkcourse,$webworkuser,false);
    return _wwassignment_link_to_edit_set_auto_login($webworkcourse,$webworkset,$webworkuser,$key);
}

/**
* @desc Creates an auto_login link to the URL of the instructor page for the webwork course associated with the current course.
* @return URL The URL.
*/
function wwassignment_instructor_page_link() {
    global $COURSE,$USER;
    $webworkclient =& new webwork_client();
    
    $webworkcourse = _wwassignment_mapped_course($COURSE->id,false);
    //IS THE USER MAPPED?
    $webworkuser = $webworkclient->mapped_user($webworkcourse,$USER->username);
    if($webworkuser == -1) {
        //USER WAS NOT FOUND, wasnt mapped
        error_log(get_string("user_not_mapped","wwassignment"));
        //try and create a teacher
        $tempuser = $USER;
        $newuser = $webworkclient->create_user($webworkcourse,$tempuser,"10");
        //AGAIN FOR GOOD MEASURE
        $webworkuser = $webworkclient->mapped_user($webworkcourse,$USER->username,false); 
    }
    $key = $webworkclient->login_user($webworkcourse,$webworkuser,false);
    
    return _wwassignment_link_to_instructor_auto_login($webworkcourse,$webworkuser,$key);
}

/**
* @desc On the first instance in a course this will add the Moodle/WeBWorK tie. Otherwise it will create a new Moodle assignment <-> Webwork problem set tie.
*/
function wwassignment_add_instance($wwassignment) {
    global $COURSE,$SESSION,$USER;
    
    
    if(isset($wwassignment->webwork_course)) {
        //Attaching Moodle Course to WeBWorK Course
        if($wwassignment->webwork_course =="create") {
            print_error("Creation is not yet supported.");
        }
        error_log("adding wwassignment_bridge record");
        $wwassignment->course = $COURSE->id;
        $returnid = insert_record("wwassignment_bridge",$wwassignment);
        $wwassignment->webwork_set = "undefined";
        $returnid = insert_record("wwassignment",$wwassignment);
        $webworkclient =& new webwork_client();
        if($webworkclient->mapped_user($wwassignment->webwork_course,$USER->username) == -1) {
            $webworkclient->create_user($wwassignment->webwork_course,$USER,"10");
        }
        return $returnid;
    }
    if(isset($wwassignment->webwork_set)) {
        $webworkclient =& new webwork_client();
        if($wwassignment->webwork_set =="create") {
            print_error("Creation is not yet supported.");
        }
        $webworkcourse = _wwassignment_mapped_course($COURSE->id);
        $webworksetdata = $webworkclient->get_assignment_data($webworkcourse,$wwassignment->webwork_set,false);
        //Attaching Moodle Set to WeBWorK Set
        error_log("adding wwassignment record");
        $wwassignment->course = $COURSE->id;
        $wwassignment->name = get_string("set_name","wwassignment") . " " . $webworksetdata['set_id'];
        $returnid = insert_record("wwassignment",$wwassignment);
        //GET TIMEDUE INFO AND ADD AN EVENT
       
        if(isset($webworksetdata)) {
            $event = NULL;
            $event->name = get_string("set_name","wwassignment") . " " . $webworksetdata['set_id'];
            $event->description = "";
            $event->courseid = $COURSE->id;
            $event->groupid = 0;
            $event->userod = 0;
            $event->modulename = 'wwassignment';
            $event->instance = $returnid;
            $event->eventtype = 'due';
            $event->timestart = $webworksetdata['due_date'];
            $event->timeduration = 0;
            if(!add_event($event)) {
                error_log(get_string("event_creation_error","wwassignment"));
            }
        }
    }
    return $returnid;
}

/**
* @desc Updates and resynchronizes all information related to the a moodle assignment <-> webwork problem set tie.
* @param The ID of the wwassignment to update.
*/
function wwassignment_update_instance($wwassignment) {
    global $COURSE;
    $webworkclient =& new webwork_client();
    $webworkcourse = _wwassignment_mapped_course($COURSE->id);
    $webworkset = _wwassignment_mapped_set($wwassignment->instance,false);
    $webworksetdata = $webworkclient->get_assignment_data($webworkcourse,$webworkset,false);
    if(isset($webworksetdata)) {
        $wwassignment->name = get_string("set_name","wwassignment") . " " . $webworksetdata['set_id'];
        if($returnid = update_record("wwassignment",$wwassignment)) {
            $event = NULL;
            if($event->id = get_field('event','id','modulename','wwassignment','instance',$wwassignment->id)) {
                $event->name = get_string("set_name","wwassignment") . " " . $webworksetdata['set_id'];
                $event->timestart = $webworksetdata['due_date'];
                $rs = update_event($event);
                error_log("updating the event".$rs);
            } else {
                $event = NULL;
                $event->name        = get_string("set_name","wwassignment") . " " . $webworksetdata['set_id'];
                $event->description = "";
                $event->courseid    = $COURSE->id;
                $event->groupid     = 0;
                $event->userid      = 0;
                $event->modulename  = 'wwassignment';
                $event->instance    = $wwassignment->id;
                $event->eventtype   = 'due';
                $event->timestart   = $webworksetdata['due_date'];
                $event->timeduration = 0;
                add_event($event);
            }
        }
    
    }
    return $returnid;
}

/**
* @desc Deletes a tie in Moodle. Deletes nothing in webwork.
* @param integer $id The id of the assignment to delete.
*/
function wwassignment_delete_instance($id) {
    
    $result = true;
    if (! $wwassignment = get_record("wwassignment", "id", "$id")) {
        return false;
    }

    # Delete any dependent records here #

    if (! delete_records("wwassignment", "id", $wwassignment->id)) {
        $result = false;
    }

    if (! delete_records('event', 'modulename', 'wwassignment', 'instance', $wwassignment->id)) {
        $result = false;
    }
    
    // Get the cm id to properly clean up the grade_items for this assignment
    // bug 4976
    if (! $cm = get_record('modules', 'name', 'wwassignment')) {
        $result = false;
    } else {
        if (! delete_records('grade_item', 'modid', $cm->id, 'cminstance', $wwassignment->id)) {
            $result = false;
        }
    }

    return $result;
}

function wwassignment_user_outline($course, $user, $mod, $wwassignment) {
/// Return a small object with summary information about what a 
/// user has done with a given particular instance of this module
/// Used for user activity reports.
/// $return->time = the time they did it
/// $return->info = a short text description
    $aLogs = get_logs("l.userid=$user AND l.course=$course AND l.cmid={$wwassignment->id}");
    if( count($aLogs) > 0 ) {
        $return->time = $aLogs[0]->time;
        $return->info = $aLogs[0]->info;
    }
    return $return;
}

function wwassignment_user_complete($course, $user, $mod, $wwassignment) {
/// Print a detailed representation of what a  user has done with 
/// a given particular instance of this module, for user activity reports.
    
    return true;
}

function wwassignment_print_recent_activity($course, $isteacher, $timestart) {
/// Given a course and a time, this module should find recent activity 
/// that has occurred in wwassignment activities and print it out. 
/// Return true if there was output, or false is there was none.

        global $CFG;

        return false;  //  True if anything was printed, otherwise false 
}

function wwassignment_cron () {
/// Function to be run periodically according to the moodle cron
/// This function searches for things that need to be done, such 
/// as sending out mail, toggling flags etc ... 

        global $CFG;

        return true;
}
/**
* @desc Contacts webwork to find out the completion status of a problem set for all users in a course.
* @param integer $wwassignmentid The problem set
*/
function wwassignment_grades($wwassignmentid) {
    global $COURSE;
    $webworkclient =& new webwork_client();
    $studentgrades->grades = array();
    $studentgrades->maxgrade = 0;
        //debug_log("record ".print_r($oMod,true));
    $gradeformula = '$finalgrade += ($problem->status > 0) ? 1 : 0;';
    //debug_log("formula ".print_r($gradeFormula, true));
    $webworkcourse = _wwassignment_mapped_course($COURSE->id,false);
    $webworkset = _wwassignment_mapped_set($wwassignmentid,false);
    
    // enumerate over the students in the course:
    $students = get_course_students($COURSE->id);
    
     foreach( $students as $student ) {
        $webworkuser = $webworkclient->mapped_user($webworkcourse,$student->username);
        if($webworkuser != -1) {
            $webworkuserset = $webworkclient->mapped_user_set($webworkcourse,$webworkuser,$webworkset);
            if($webworkuserset != -1)
            {
                $studentproblems = $webworkclient->get_user_problems($webworkcourse,$webworkuser,$webworkset);
                $finalgrade = 0.0;
                foreach( $studentproblems as $problem ) {
                    $finalgrade += $problem->status;
                }
            } else {
                $finalgrade = 0.0;
            }
        } else {
            $finalgrade = 0.0;
        }       
        $studentgrades->grades[$student->id] = $finalgrade;
     }
    
    $studentgrades->maxgrade = $webworkclient->get_max_grade($webworkcourse,$webworkset);
    //error_log("all grades".print_r($studentgrades, true));
    
    return $studentgrades;
}

/**
* @desc Finds all the participants in the course
* @param string $wwassignmentid The Moodle wwassignment ID.
* @return array An array of course users (IDs).
*/
function wwassignment_get_participants($wwassignmentid) {
    $wwassignment = get_record("wwassignment", "id", $wwassignmentid);
    if(!isset($wwassignment)) {
        return array();
    }
    return get_course_users($wwassignment->course);
}

function wwassignment_refresh_events($courseid = 0) {
    error_log("wwassignment_refresh_events called");
    if ($courseid == 0) {
        if (! $wwassignment = get_records("wwassignment")) {
            return true;
        }
    } else {
        if (! $wwassignment = get_records("wwassignment", "course", $courseid)) {
            return true;
        }
    }
    $moduleid = get_field('modules', 'id', 'name', 'wwassignment');

    foreach ($wwassignment as $wwassignment) {
        $event = NULL;
        $event->name        = addslashes($wwassignment->name);
        $event->description = addslashes($wwassignment->description);
        $event->timestart   = $wwassignment->timedue;

        if ($event->id = get_field('event', 'id', 'modulename', 'wwassignment', 'instance', $wwassignment->id)) {
            update_event($event);

        } else {
            $event->courseid    = $wwassignment->course;
            $event->groupid     = 0;
            $event->userid      = 0;
            $event->modulename  = 'wwassignment';
            $event->instance    = $wwassignment->id;
            $event->eventtype   = 'due';
            $event->timeduration = 0;
            $event->visible     = get_field('course_modules', 'visible', 'module', $moduleid, 'instance', $wwassignment->id);
            add_event($event);
        }

    }
    return true;
}


////////////////////////////////////////////////////////////////////////////////////
// internal functions start with _wwassignment
///////////////////////////////////////////////////////////////////////////////////


////////////////////////////////////////////////////////////////
//functions that check mapping existance in the local db
////////////////////////////////////////////////////////////////

/**
* @desc Finds the webwork course name from a moodle course id.
* @param integer $courseid Moodle Course ID.
* @param integer $silent whether to trigger an error message.
* @return string the name of the webwork course on success and -1 on failure.
*/
function _wwassignment_mapped_course($courseid,$silent = true) {
    $wwassignmentbridge = get_record('wwassignment_bridge','course', $courseid);
    if((isset($wwassignmentbridge)) && (isset($wwassignmentbridge->webwork_course))) {
        return $wwassignmentbridge->webwork_course;
    }
    if(!$silent) {
        print_error(get_string("webwork_map_failure","wwassignment") . " No WeBWorK course is mapped for this course.");
    }
    return -1;
}

/**
* @desc Finds the webwork set name from a wwassignment id.
* @param integer $wwassignmentid Moodle wwassignment ID.
* @param integer $silent whether to trigger an error message.
* @return string the name of the webwork set on success and -1 on failure.
*/
function _wwassignment_mapped_set($wwassignmentid,$silent = true) {
    $wwassignment = get_record('wwassignment','id',$wwassignmentid);
    if((isset($wwassignment)) && (isset($wwassignment->webwork_set))) {
        return $wwassignment->webwork_set;
    }
    if(!$silent) {
        print_error(get_string("webwork_map_failure","wwassignment") . " No WeBWorK set is mapped for this set.");
    }
    return -1;
}

////////////////////////////////////////////////////////////////
//functions that create links to the webwork site.
////////////////////////////////////////////////////////////////

/**
* @desc Returns URL link to a webwork course logging the user in.
* @param string $webworkcourse The webwork course.
* @param string $webworkset The webwork set.
* @param string $webworkuser The webwork user.
* @param string $key The key used to login the user.
* @return URL.
*/
function _wwassignment_link_to_edit_set_auto_login($webworkcourse,$webworkset,$username,$key) {
    return _wwassignment_link_to_course($webworkcourse) . "instructor/sets/$webworkset/?effectiveUser=$username&user=$username&key=$key";
}


/**
* @desc Returns URL link to a webwork course logging the user in.
* @param string $webworkcourse The webwork course.
* @param string $webworkuser The webwork user.
* @param string $key The key used to login the user.
* @return URL.
*/
function _wwassignment_link_to_instructor_auto_login($webworkcourse,$username,$key) {
    return _wwassignment_link_to_course($webworkcourse) . "instructor/?effectiveUser=$username&user=$username&key=$key";
}

/**
* @desc Returns the URL link to a webwork course and a particular set logged in.
* @param string $webworkcourse The webwork course.
* @param string $webworkset The webwork set.
* @param string $webworkuser The webwork user.
* @param string $key The key used to login the user.
* @return URL.
*/
function _wwassignment_link_to_set_auto_login($webworkcourse,$webworkset,$webworkuser,$key) {
    return _wwassignment_link_to_set($webworkcourse,$webworkset) .  "?effectiveUser=$webworkuser&user=$webworkuser&key=$key";
}

/**
* @desc Returns the URL link to a webwork course and a particular set.
* @param string $webworkcourse The webwork course.
* @param string $webworkset The webwork set.
* @return URL.
*/
function _wwassignment_link_to_set($webworkcourse,$webworkset) {
    return _wwassignment_link_to_course($webworkcourse) . "$webworkset/";
}

/**
* @desc Returns the URL link to a webwork course.
* @param string $webworkcourse The webwork course.
* @return URL.
*/
function _wwassignment_link_to_course($webworkcourse) {
    return WWASSIGNMENT_WEBWORK_URL."/$webworkcourse/";
}

///////////////////////////////////////////////////////////////
//webwork client class
///////////////////////////////////////////////////////////////

/**
* @desc This singleton class acts as the gateway for all communication from the Moodle Client to the WeBWorK SOAP Server. It encapsulates an instance of a SoapClient.
*/
class webwork_client {
        var $client;
        var $defaultparams;
        var $datacache;
        var $mappingcache;
        /**
         * @desc Constructs a singleton webwork_client.
         */
        function webwork_client()
        {
            // static associative array containing the real objects, key is classname
            static $instances=array();
            // get classname
            $class = get_class($this);
            if (!array_key_exists($class, $instances)) {
                // does not yet exist, save in array
                $this->client = new soap_client(WWASSIGNMENT_WEBWORK_WSDL,'wsdl');
                $err = $this->client->getError();
                if ($err) {
                    print_error("Constructor Error $err");
                }
                $this->defaultparams = array();
                $this->defaultparams['authenKey']  = WWASSIGNMENT_WEBWORK_KEY;
                $this->datacache = array(); 
                $this->mappingcache = array();
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
         *@param integer $override=false whether to override the default parameters that are passed to the soap function (authenKey).
         *@return Result of the soap function.
         */
        function handler($functioncall,$params=array(),$override=false) {
                if(!is_array($params)) {
                        $params = array($params);   
                }
                if(!$override) {
                        $params = array_merge($this->defaultparams,$params);
                }
                var_dump($params);
                $result = $this->client->call($functioncall,$params);
                var_dump($result);
                //$result = call_user_func_array(array(&$this->client,$functioncall),$params);
                if($err = $this->client->getError()) {
                        print_error(get_string("rpc_fault","wwassignment") . " " . $functioncall. " ". $err);  
                }
                return $result;
        }
        
        /**
        * @desc Checks whether a user is in a webwork course.
        * @param string $webworkcourse The webwork course name.
        * @param string $webworkuser The webwork user name.
        * @param integer $silent whether to trigger an error message
        * @return string Returns the webwork user on success and -1 on failure.
        */
        function mapped_user($webworkcourse,$webworkuser,$silent = true) {
            if(isset($this->mappingcache[$webworkcourse]['user'][$webworkuser])) {
                return $this->mappingcache[$webworkcourse]['user'][$webworkuser];
            }
            $record = $this->handler('get_user',array("courseName" => $webworkcourse,"userID" => $webworkuser));
            if($record != -1) {
                $this->mappingcache[$webworkcourse]['user'][$webworkuser] = $webworkuser;
                return $webworkuser;
            }
            if(!$silent) {
                print_error(get_string("webwork_map_failure","wwassignment") . "The user $webworkuser was not found in the webwork course $webworkcourse.");
            }
            return -1;
        }
        
        /**
        * @desc Checks whether a user has his own copy of a set built in a webwork course.
        * @param string $webworkcourse The webwork course name.
        * @param string $webworkuser The webwork user name.
        * @param string $webworkset The webwork set name.
        * @param integer $silent whether to trigger an error message
        * @return integer Returns 1 on success and -1 on failure.
        */
        function mapped_user_set($webworkcourse,$webworkuser,$webworkset,$silent = true) {
            if(isset($this->mappingcache[$webworkcourse]['user_set'][$webworkuser][$webworkset])) {
                return $this->mappingcache[$webworkcourse]['user_set'][$webworkuser][$webworkset];
            }
            $record = $this->handler("get_user_set",array("courseName" => $webworkcourse,"userID" => $webworkuser,"setID" => $webworkset));
            if($record != -1) {
                $this->mappingcache[$webworkcourse]['user_set'][$webworkuser][$webworkset] = 1;
                return 1;
            }
            
            if(!$silent) {
                print_error(get_string("webwork_map_failure","wwassignment") . "The user $webworkuser does not have copies of the set $webworkset in webwork course $webworkcourse.");
            }
            return -1;
        }
        
        /**
        * @desc Gets the record of the global set for a webwork course and set name.
        * @param string $webworkcourse The webwork course name.
        * @param string $webworkset The webwork set name.
        * @param integer $silent whether to trigger an error message
        * @return array Returns set information on success or -1 on failure.
        */
        function get_assignment_data($webworkcourse,$webworkset,$silent = true) {
            $record = $this->handler("get_global_set",array('courseName' => $webworkcourse, 'setID' => $webworkset));
            if(isset($record)) {
                $setinfo = array();
                $setinfo['open_date'] = $record->open_date;
                $setinfo['due_date'] = $record->due_date;
                $setinfo['set_id'] = $record->set_id;
                $setinfo['name'] = get_string("set_name","wwassignment") . " " . $record->set_id; 
                return $setinfo;
            }
            if(!$silent) {
                print_error(get_string("webwork_map_failure","wwassignment") . "The set $webworkset was not found  in webwork course $webworkcourse.");
            }
            return -1;
            
        }
        
        /**
        * @desc Gets all the user problems for a specfic course, user and set. 
        * @param string $webworkcourse The webwork course name.
        * @param string $webworkuser The webwork users name.
        * @param string $webworkset The webwork set name.
        * @param integer $silent whether to trigger an error message
        * @return array Returns an array of problems on success or -1 on failure.
        */
        function get_user_problems($webworkcourse,$webworkuser,$webworkset,$silent = true) {
            $record = $this->handler("get_all_user_problems",array("courseName" => $webworkcourse,"userID" => $webworkuser,"setID" => $webworkset));
            if(isset($record)) {
                return $record;
            }
            if(!$silent) {
                print_error(get_string("webwork_map_failure","wwassignment") . "The user $webworkuser does not have copies of the set $webworkset in webwork course $webworkcourse.");
            }
            return -1;
        }
        
        /**
        * @desc Calculates the max grade on a set by counting the number of problems in the set. 
        * @param string $webworkcourse The webwork course name.
        * @param string $webworkset The webwork set name.
        * @param integer $silent whether to trigger an error message
        * @return integer The max grade on success or -1 on failure.
        */
        function get_max_grade($webworkcourse,$webworkset,$silent = true) {
            $record = $this->handler('list_global_problems',array("courseName" => $webworkcourse,"setID" => $webworkset));
            if(isset($record)) {
                return count($record);
            }
            if(!$silent) {
                print_error(get_string("webwork_map_failure","wwassignment") . "The set $webworkset does not exist in webwork course $webworkcourse.");
            }
            return -1;
            
        }
        /**
        * @desc Forces a login of a user into a course.
        * @param string $webworkcourse The webwork course name.
        * @param string $webworkuser The webwork users name.
        * @param integer $silent whether to trigger an error message
        * @return string The webwork key for URL on success or -1 on failure.
        */
        function login_user($webworkcourse,$webworkuser,$silent = true) {
            $key = $this->handler("login_user",array("courseName" => $webworkcourse,"userID" => $webworkuser));
            if(isset($key)) {
                return $key;
            }
            if(!$silent) {
                print_error(get_string("webwork_map_failure","wwassignment") . "The user $webworkuser cannot login to $webworkcourse.");
            }
            return -1;
        }
        
        /**
        * @desc Retrieves a list of sets from a webwork course and converts it into form options format.
        * @param string $webworkcourse The webwork course name.
        * @param integer $silent whether to trigger an error message
        * @return array The form options.
        */
        function options_set($webworkcourse,$silent = true) {
            $setlist = $this->handler('list_global_sets',array("courseName" => $webworkcourse));
            if(isset($setlist)) {
                $setoptions = array();
                foreach($setlist as $setid) {
                    $setoptions[$setid] = $setid;
                }
                return $setoptions;
            }
            if(!$silent) {
                print_error(get_string("webwork_map_failure","wwassignment") . "Could not get sets from the course $webworkcourse.");
            }
            return -1;          
        }
        
        /**
        * @desc Retrieves a list of courses from a webwork course and converts it into form options format.
        * @param integer $silent whether to trigger an error message
        * @return array The form options.
        */
        function options_course($silent = true) {
            $courselist = $this->handler("list_courses");
            if(isset($courselist)) {
                $courseoptions = array();
                foreach($courselist as $course) {
                    $courseoptions[$course] = $course;
                }
                return $courseoptions; 
            }
            if(!$silent) {
                print_error(get_string("webwork_map_failure","wwassignment") . "Could not get list of courses.");
            }
            return -1;
   
        }
        
        /**
        * @desc Creates a user in the WeBWorK course.
        * @param string $webworkcourse The webwork course name.
        * @param array $userdata The user data to use in creation.
        * @param string $permission The permissions of the new user, defaults to 0.
        * @return Returns 1 on success.
        */
        function create_user($webworkcourse,&$userdata,$permission="0") {
            echo "Creating user" . $permission;
            $studentid = $userid;
            $this->handler("add_user",array("courseName" => $webworkcourse, "record" => array(
                "user_id" => $userdata->username,
                "first_name" => $userdata->firstname,
                "last_name" => $userdata->lastname,
                "email_address" => $userdata->emailaddress,
                "student_id" => $studentid,
                "status" => "C",
                "section" => "",
                "recitation" => "",
                "comment" => "moodle created user")));
            $this->handler("add_permission",array("courseName" => $webworkcourse,"record" => array(
                "user_id" => $userdata->username,
                "permission" => $permission)));
            $this->handler("add_password",array("courseName" => $webworkcourse,"record" => array(
                "user_id" => $userdata->username,
                "password" => $studentid)));
            return 1;
        }
        
        /**
        * @desc Creates a user set in WeBWorK
        * @param string $webworkcourse The webwork course name.
        * @param string $webworkuser The webwork user name.
        * @param string $webworkset The webwork set name.
        * @return Returns 1 on success.
        */
        function create_user_set($webworkcourse,$webworkuser,$webworkset) {
            $this->handler("assign_set_to_user",array("courseName" => $webworkcourse,"userID" => $webworkuser, "setID" => $webworkset));
            return 1;
        }   
};

?>