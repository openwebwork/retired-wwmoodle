<?php
global $CFG,$DB;
#require_once("$CFG->libdir/soap/nusoap.php");
require_once("$CFG->libdir/soaplib.php");

define('WWASSIGNMENT_DEBUG',0);


//////////////////////////////////////////////////////////////////
//Utility functions
//////////////////////////////////////////////////////////////////

/** 
 * @desc  prints message to the apache log
 * @param string  message
**/

function debugLog($message) {
	if (WWASSIGNMENT_DEBUG) {
		error_log($message);
		var_dump($message);
	}
}

/** (reference from accesslib.php )
 * @desc  gets all the users assigned this role in this context or higher
 * @param int roleid (can also be an array of ints!)
 * @param int contextid
 * @param bool parent if true, get list of users assigned in higher context too
 * @param string fields - fields from user (u.) , role assignment (ra) or role (r.)
 * @param string sort  - sort from user (u.) , role assignment (ra) or role (r.)
 * @param bool gethidden - whether to fetch hidden enrolments too
 * @return array()
 */
//function get_role_users($roleid, $context, $parent=false, $fields='', $sort='u.lastname ASC', $gethidden=true, $group='', $limitfrom='', $limitnum='') {


/**
* @desc Finds all of the users in the course
* @param $courseid   -- the course id
* @return record containing user information ( username, userid)
*/

function _wwassignment_get_course_students($courseid) {
    debugLog("Begin get_course_students($courseid )");
    debugLog("courseID is ". print_r($courseid, true));
	$context = get_context_instance(CONTEXT_COURSE, $courseid);
	debugLog("context is ". print_r($context, true));
	
	$users = array();
	$roles_used_in_context = get_roles_used_in_context($context);
	//debugLog("roles used ". print_r($roles_used_in_context, true));
	foreach($roles_used_in_context as $role) {
		$roleid = $role->id;
 		debugLog( "roleid should be 5 for a student $roleid");
 		//debugLog(get_role_users($roleid, $context, true) );
 		if ($new_users = get_role_users($roleid, $context, true) ) {
			$users = array_merge($users, $new_users );//FIXME a user could be liseted twice
		}
		debugLog("display users ".print_r($users,true));
	}
 	debugLog("display users in course--on");
	debugLog("users again".print_r($users, true));
	
    debugLog("End get_course_students($courseid )");
	return $users;


}
   
//////////////////////////////////////////////////////////////////
//EVENT CREATION AND DELETION
//////////////////////////////////////////////////////////////////

/**
* @desc Creates the corresponding events for a wwassignment.
* @param $wwsetname string The name of the set.
* @param $wwassignmentid string The ID of the wwassignment record.
* @param $opendate integer The UNIX timestamp of the open date.
* @param $duedate integer The UNIX timestamp of the due date.
* @return integer 0 on success. -1 on error.
*/

function _wwassignment_create_events($wwassignment,$wwsetdata ) {
//    error_log("enter create_events for set ".$wwassignment->name." id ".$wwassignment->id." date ".$wwsetdata['open_date']." ".$wwsetdata['due_date'] );
    global $COURSE;
    //error_log("set data".print_r($wwsetdata,true));
   if (! $opendate = $wwsetdata['open_date'] ) {
//    	error_log(" undefined open date ");
    }
    if (! $duedate = $wwsetdata["due_date"] ){
//    	error_log(" undefined due date ");
    }
    if (! $wwassignmentid = $wwassignment->id ) {
//       	error_log(" undefined ww id ");
    }
    if (! $name = $wwassignment->name ) {
//       	error_log(" undefined set name ");
    }
    $courseid = $wwassignment->course;

    if (!courseid) {
    	$courseid =$COURSE->id;
    }
     

    unset($event);
    $event->name = $name;
    $event->description = 'WeBWorK Set Event';
    $event->courseid = $courseid;
    $event->groupid = 0;
    $event->userid = 0;
    $event->format = 1;
    $event->modulename = 'wwassignment';
    $event->instance = $wwassignmentid;
    $event->visible  = 1;    
    $event->eventtype = 'due';
    $event->timestart = $duedate;
    $event->timeduration = 1;

    // error_log("adding a due event");
    $result = 0;
    $calendareventid = add_event($event);
//    error_log("calendareventid $calendareventid created");
    if(!$calendareventid) {
//        error_log("can't create calendarevent for set $wwsetname wwid $wwassignmentid date $opendate $duedate course $courseid");
        $result = -1;
    }
    return $result;
}


/**
* @desc Deletes all events relating to the wwassignment passed in.
* @param $wwassignmentid integer The wwassignment ID.
* @return integer 0 on success
*/
function _wwassignment_delete_events($wwassignmentid) {
    global $DB;
    if ($events = $DB->get_records_select('event', "modulename = 'wwassignment' and instance = '$wwassignmentid'")) {
        foreach($events as $event) {
               // error_log("deleting  event ".$event->id);
            delete_event($event->id);
        }
    }
    return 0;
}




function _wwassignment_refresh_event($wwassignment) {
        global $DB;
	$cid = $wwassignment->course;
	$wwcoursename = _wwassignment_mapped_course($cid,false); 
	if ( $wwcoursename== -1) {
		error_log("Can't connect course $cid to webwork");
		return false;
	}
	$wwclient = new wwassignment_client();   
	$wwsetname = $wwassignment->webwork_set;
	error_log("updating events for $wwcoursename $wwsetname");
	//get data from WeBWorK
	$wwsetdata = $wwclient->get_assignment_data($wwcoursename,$wwsetname,false);
	$wwassignment->grade = $wwclient->get_max_grade($wwcoursename,$wwsetname,false);
	$wwassignment->timemodified = time();
	$returnid = $DB->update_record('wwassignment',$wwassignment);
	// update event
	_wwassignment_delete_events($wwassignment->id);
	_wwassignment_create_events($wwassignment,$wwsetdata);
	return true;
}


//////////////////////////////////////////////////////////////////
//Functions that ensure creation of WeBWorK Data
//////////////////////////////////////////////////////////////////

/**
* @desc Checks whether a user exists in a WW course. If it doesnt creates the user using the currently logged in one.
* @param $wwcoursename string The WW course.
* @param $username string The username to check.
* @param $permission string The permission the user needs if created.
* @return string the new username.
*/
function _wwassignment_mapcreate_user($wwcoursename,$username,$permission = '0') {
    $wwclient = new wwassignment_client();
    $exists = $wwclient->mapped_user($wwcoursename,$username);
    if($exists == -1) {
        global $USER;
        $tempuser = $USER;
        $newusername = $wwclient->create_user($wwcoursename,$tempuser,$permission);
        return $newusername;
    }
    return $username;
}

/**
* @desc Checks whether a set exists for a user in a WW course. If it doesnt autocreates.
* @param $wwcoursename string The WW course.
* @param $wwusername string The WW user.
* @param $wwsetname string The WW set.
* @return integer 0.
*/
function _wwassignment_mapcreate_user_set($wwcoursename,$wwusername,$wwsetname) {
    $wwclient = new wwassignment_client();
    $exists = $wwclient->mapped_user_set($wwcoursename,$wwusername,$wwsetname);
    if($exists == -1) {
        $wwclient->create_user_set($wwcoursename,$wwusername,$wwsetname);
    }
    return 0;
}

/**
* @desc Makes sure that a user is logged in to WW.
* @param $wwcoursename string The course to login to.
* @param $wwusername string The user to login.
* @return string The users key for WW.
*/
function _wwassignment_login_user($wwcoursename,$wwusername) {
    $wwclient = new wwassignment_client();
    return $wwclient->login_user($wwcoursename,$wwusername,false);
}

////////////////////////////////////////////////////////////////
//functions that check mapping existance in the local db
////////////////////////////////////////////////////////////////

/**
@desc Find the id of the wwassignment module class
*@param none
*@return  id
*/
function _wwassignment_cmid() {
   global $DB;
   $wwassignment = $DB->get_record('modules', array( 'name'=>'wwassignment' ));
   return $wwassignment->id;
}

/**
* @desc Finds the webwork course name from a moodle course id.
* @param integer $courseid Moodle Course ID.
* @param integer $silent whether to trigger an error message.
* @return string the name of the webwork course on success and -1 on failure.
*/
function _wwassignment_mapped_course($courseid,$silent = true) {
    global $DB;
    $blockinstance = $DB->get_record('block_instances', array(
               'blockname'=>'wwlink',
	       'parentcontextid'=>get_context_instance(CONTEXT_COURSE,$courseid)->id, 
 	       'pagetypepattern'=>'course-view-*' ));    
    //error_log("block instance".print_r($blockinstance,true));
    $block_config = unserialize(base64_decode($blockinstance->configdata));
    //error_log("config_data ".print_r($block_config,true));
    if ( isset($block_config) &&  isset($block_config->wwlink_id)  ) {
    	return $block_config->wwlink_id;
    } else {
    	return -1;
    }
}

/**
* @desc Finds the webwork set name from a wwassignment id.
* @param integer $wwassignmentid Moodle wwassignment ID.
* @param integer $silent whether to trigger an error message.
* @return string the name of the webwork set on success and -1 on failure.
*/
function _wwassignment_mapped_set($wwassignmentid,$silent = true) {
    global $DB;
    $wwassignment = $DB->get_record('wwassignment', array('id'=>$wwassignmentid ));
    if((isset($wwassignment)) && (isset($wwassignment->webwork_set))) {
        return $wwassignment->webwork_set;
    }
    if(!$silent) {
        print_error('webwork_set_map_failure','wwassignment');
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
    global $CFG;
    return $CFG->wwassignment_webworkurl."/$webworkcourse/";
}


///////////////////////////////////////////////////////////////
//wwassignment client class
///////////////////////////////////////////////////////////////

/**
* @desc This singleton class acts as the gateway for all communication from the Moodle Client to the WeBWorK SOAP Server.
* It encapsulates an instance of a SoapClient.
*/
class wwassignment_client {
        var $client;
        var $defaultparams;
        var $datacache;
        var $mappingcache;
        
        /**
         * @desc Constructs a singleton webwork_client.
         */
        function wwassignment_client()
        {
            global $CFG;
            // static associative array containing the real objects, key is classname
            static $instances=array();
            // get classname
            $class = get_class($this);
            if (!array_key_exists($class, $instances)) {
                // does not yet exist, save in array
#                $this->client = new SoapClient($CFG->wwassignment_rpc_wsdl,'wsdl');
                $this->client = soap_connect($CFG->wwassignment_rpc_wsdl);
#                $err = $this->client->getError();
#                if ($err) {
#                    error_log($err);
#                    error_log($CFG->wwassignment_rpc_wsdl);
#                    print_error('construction_error','wwassignment');
#                }
                $this->defaultparams = array();
                $this->defaultparams['authenKey']  = $CFG->wwassignment_rpc_key;
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
                if(WWASSIGNMENT_DEBUG) {
                    echo "Called: $functioncall <br>";
                    echo "Params: ";
                    var_dump($params);
                    echo "<br>"; 
                }
                $result = $this->client->__soapCall($functioncall,$params);
                
                //$result = call_user_func_array(array(&$this->client,$functioncall),$params);
#                if($err = $this->client->getError()) {
#                        //print_error(get_string("rpc_fault","wwassignment') . " " . $functioncall. " ". $err);
#                        print_error('rpc_error','wwassignment');  
#                }
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
            $record = $this->handler('get_user',array('courseName' => $webworkcourse,'userID' => $webworkuser));
            if( is_a($record,'stdClass') ) {
                $this->mappingcache[$webworkcourse]['user'][$webworkuser] = $webworkuser;
                return $webworkuser;
            }
            if(!$silent) {
                print_error('webwork_user_map_failure',"wwassignment");
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
            $record = $this->handler('get_user_set',array('courseName' => $webworkcourse,'userID' => $webworkuser,'setID' => $webworkset));
            if(is_a($record,'stdClass')) {
                $this->mappingcache[$webworkcourse]['user_set'][$webworkuser][$webworkset] = 1;
                return 1;
            }
            
            if(!$silent) {
                print_error('webwork_user_set_map_failure','wwassignment');
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
            $record = $this->handler('get_global_set',array('courseName' => $webworkcourse, 'setID' => $webworkset));
            if(isset($record)) {
                $setinfo = array();
                $setinfo['open_date'] = $record->open_date;
                $setinfo['due_date'] = $record->due_date;
                $setinfo['set_id'] = $record->set_id;
                $setinfo['name'] = $record->set_id;
                return $setinfo;
            }
            if(!$silent) {
                print_error('webwork_set_map_failure','wwassignment');
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
            $record = $this->handler('get_all_user_problems',array('courseName' => $webworkcourse,'userID' => $webworkuser,'setID' => $webworkset));
            if(isset($record)) {
                return $record;
            }
            if(!$silent) {
                print_error('webwork_user_set_map_failure','wwassignment');
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
        //FIXME -- this assumes each problem gets 1 point which is false
//         function get_max_grade($webworkcourse,$webworkset,$silent = true) {
//             $record = $this->handler('list_global_problems',array('courseName' => $webworkcourse,'setID' => $webworkset));
//             if(isset($record)) {
//                 return count($record);
//             }
//             if(!$silent) {
//                 print_error('webwork_set_map_failure','wwassignment');
//             }
//             return -1;
//             
//         }

        function get_max_grade($webworkcourse,$webworkset,$silent = true) {
            $record = $this->handler('get_all_global_problems',array('courseName' => $webworkcourse,'setID' => $webworkset));
            $totalpoints =0;
            if(isset($record)) {
            	foreach ($record as $set) {
            		$totalpoints = $totalpoints + $set->value;
                	
                }
                return $totalpoints;
            }
            if(!$silent) {
                print_error('webwork_set_map_failure','wwassignment');
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
            $key = $this->handler('login_user',array('courseName' => $webworkcourse,'userID' => $webworkuser));
            if(isset($key)) {
                return $key;
            }
            if(!$silent) {
                print_error('webwork_user_map_failure','wwassignment');
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
            $setlist = $this->handler('list_global_sets',array('courseName' => $webworkcourse));
            if(isset($setlist)) {
                $setoptions = array();
                foreach($setlist as $setid) {

                    $setoptions[$setid] = $setid;
                }
                return $setoptions;
            }
            if(!$silent) {
                print_error('webwork_course_map_failure','wwassignment');
            }
            return -1;          
        }
        
        /**
        * @desc Retrieves a list of courses from a webwork course and converts it into form options format.
        * @param integer $silent whether to trigger an error message
        * @return array The form options.
        */
        function options_course($silent = true) {
            $courselist = $this->handler('list_courses');
            sort($courselist);
            if(isset($courselist)) {
                $courseoptions = array();
                foreach($courselist as $course) {
                    $courseoptions[$course] = $course;
                }
                return $courseoptions; 
            }
            if(!$silent) {
                print_error('webwork_course_list_map_failure','wwassignment');
            }
            return -1;
   
        }
        
        /**
        * @desc Creates a user in the WeBWorK course.
        * @param string $webworkcourse The webwork course name.
        * @param array $userdata The user data to use in creation.
        * @param string $permission The permissions of the new user, defaults to 0.
        * @return Returns username on success.
        
        */
        function create_user($webworkcourse,&$userdata,$permission='0') {
            $studentid = $userid;
            # FIXME:  find permission for this user and set permissions appropriately in webwork
            # FIXME:  find the group(s)  that this person is a member of 
            # FIXME:  I have used the following scheme:  gage_SEC  use groups ending like this to determine sections in webwork
            # FIXME:  use ordinary groups   taName    to correspond to recitation sections in WeBWorK
            #
            # FIXME:  make it so an update_user function is called whenever the user data in moodle is changed
            # FIXME:  so if a student switches groups this is reflected in WeBWorK
            $this->handler('add_user',array('courseName' => $webworkcourse, 'record' => array(
                'user_id' => $userdata->username,
                'first_name' => $userdata->firstname,
                'last_name' => $userdata->lastname,
                'email_address' => $userdata->email,
                'student_id' => $studentid,
                'status' => 'C',
                'section' => '',
                'recitation' => '',
                'comment' => 'moodle created user')));
            $this->handler('add_permission',array('courseName' => $webworkcourse,'record' => array(
                'user_id' => $userdata->username,
                'permission' => $permission)));
            $this->handler('add_password',array('courseName' => $webworkcourse,'record' => array(
                'user_id' => $userdata->username,
                'password' => $userdata->password)));
            return $userdata->username;
        }
       /**  NOT yet ready!!!!!!!!!
        * @desc Updates data for a user in the WeBWorK course.
        * @param string $webworkcourse The webwork course name.
        * @param array $userdata The user data to use in creation.
        * @param string $permission The permissions of the new user, defaults to 0.
        * @return Returns username on success.
        */
        function update_user($webworkcourse,&$userdata,$permission='0') {
            error_log("update_user called -- not yet ready");
            $studentid = $userid;
            # FIXME:  find permission for this user and set permissions appropriately in webwork
            # FIXME:  find the group(s)  that this person is a member of 
            # FIXME:  I have used the following scheme:  gage_SEC  use groups ending like this to determine sections in webwork
            # FIXME:  use ordinary groups   taName    to correspond to recitation sections in WeBWorK
            #
            # FIXME:  make it so an update_user function is called whenever the user data in moodle is changed
            # FIXME:  so if a student switches groups this is reflected in WeBWorK
            # do get_user first to get current status then update this??
            $this->handler('put_user',array('courseName' => $webworkcourse, 'record' => array(
                //'user_id' => $userdata->username,  // can't update this
                'first_name' => $userdata->firstname,
                'last_name' => $userdata->lastname,
                'email_address' => $userdata->email,
                'student_id' => $studentid,
                //'status' => 'C',  //can you update this from moodle?
                'section' => '',
                'recitation' => '',
                'comment' => 'moodle updated user')));
            $this->handler('add_permission',array('courseName' => $webworkcourse,'record' => array(
                'user_id' => $userdata->username,
                'permission' => $permission)));
            $this->handler('add_password',array('courseName' => $webworkcourse,'record' => array(
                'user_id' => $userdata->username,
                'password' => $userdata->password)));
            return $userdata->username;
        }
        /**
        * @desc Creates a user set in WeBWorK
        * @param string $webworkcourse The webwork course name.
        * @param string $webworkuser The webwork user name.
        * @param string $webworkset The webwork set name.
        * @return Returns 1 on success.
        */
        function create_user_set($webworkcourse,$webworkuser,$webworkset) {
            $this->handler('assign_set_to_user',array('courseName' => $webworkcourse,'userID' => $webworkuser, 'setID' => $webworkset));
            return 1;
        }
        
        /**
        * @desc Finds grades of many users for one set.
        * @param string $webworkcourse The webwork course name.
        * @param array $webworkusers A list of webwork users
        * @param string $webworkset The webwork set name
        * @return array Returns an array of grades   
        */
        function grade_users_sets($webworkcourse,$webworkusers,$webworkset) {
            return $this->handler('grade_users_sets',array('courseName' => $webworkcourse, 'userIDs' => $webworkusers, 'setID' => $webworkset));
        }
};
?>
