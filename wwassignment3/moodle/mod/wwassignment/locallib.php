<?php

require_once("$CFG->libdir/soap/nusoap.php");

DEFINE('WWASSIGNMENT_DEBUG',0);

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
function _wwassignment_create_events($wwsetname,$wwassignment,$opendate,$duedate) {
    global $COURSE;
    unset($event);
    $event->name = $wwsetname;
    $event->description = 'WeBWorK Set Event';
    $event->courseid = $wwassignment->course;
    $event->groupid = 0;
    $event->userid = 0;
    $event->format = 1;
    $event->modulename = 'wwassignment';
    $event->instance = $wwassignment->id;
    $event->visible  = 1;
    
    // FIXME -- this doesn't create a new object  we'll just handle the due date for now
    // what you want is $openevent = clone $event  this makes a shallow copy of the object
    //$openevent = $event;
    // FIXME -- it's likely that only the due date needs to be posted. once that is sure, clean this up.
    $dueevent = $event;
    
    //$openevent->name .= ' is Open.';
    //$openevent->eventtype = 'open';
    //$openevent->timestart = $opendate;
    //$openevent->timeduration = $duedate - $opendate;
    
    $dueevent->name .= ' is Due.';
    $dueevent->eventtype = 'due';
    $dueevent->timestart = $duedate;
    $dueevent->timeduration = 1;
    // error_log("adding a due event");
    $result = 0;
    //if(!add_event($openevent)) {
    //    $result = -1;
    //}
    if(!add_event($dueevent)) {
        $result = -1;
    }
    return $result;
}


/**
* @desc Deletes all events relating to the wwassignment passed in.
* @param $wwassignmentid integer The wwassignment ID.
* @return integer 0 on success
*/
function _wwassignment_delete_events($wwassignment) {
    $wwassignmentid = $wwassignment->id;
    if ($events = get_records_select('event', "modulename = 'wwassignment' and instance = '$wwassignmentid'")) {
        foreach($events as $event) {
               // error_log("deleting  event ".$event->id);
            delete_event($event->id);
        }
    }
    return 0;
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
        print_error('webwork_course_map_failure','wwassignment');
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
* @desc This singleton class acts as the gateway for all communication from the Moodle Client to the WeBWorK SOAP Server. It encapsulates an instance of a SoapClient.
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
                $this->client = new soap_client($CFG->wwassignment_rpc_wsdl,'wsdl');
                $err = $this->client->getError();
                if ($err) {
                    print_error('construction_error','wwassignment');
                }
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
                $result = $this->client->call($functioncall,$params);
                
                //$result = call_user_func_array(array(&$this->client,$functioncall),$params);
                if($err = $this->client->getError()) {
                        //print_error(get_string("rpc_fault","wwassignment') . " " . $functioncall. " ". $err);
                        print_error('rpc_error','wwassignment');  
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
            $record = $this->handler('get_user',array('courseName' => $webworkcourse,'userID' => $webworkuser));
            if($record != -1) {
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
            if($record != -1) {
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
                $setinfo['open_date'] = $record['open_date'];
                $setinfo['due_date'] = $record['due_date'];
                $setinfo['set_id'] = $record['set_id'];
                $setinfo['name'] = $record['set_id']; 
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
        function get_max_grade($webworkcourse,$webworkset,$silent = true) {
            $record = $this->handler('list_global_problems',array('courseName' => $webworkcourse,'setID' => $webworkset));
            if(isset($record)) {
                return count($record);
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
        * @return Returns 1 on success.
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
            return 1;
        }
       /**  NOT yet ready!!!!!!!!!
        * @desc Updates data for a user in the WeBWorK course.
        * @param string $webworkcourse The webwork course name.
        * @param array $userdata The user data to use in creation.
        * @param string $permission The permissions of the new user, defaults to 0.
        * @return Returns 1 on success.
        */
        function update_user($webworkcourse,&$userdata,$permission='0') {
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
