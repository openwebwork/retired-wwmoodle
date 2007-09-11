<?php
    //This script fetches files from wwquestions directory, and only that directory.
    //It is used to display the cached equation images that are copied to moodledata when derived questions are created

    require_once('../../../config.php');
    require_once($CFG->libdir . '/filelib.php');

    // disable moodle specific debug messages
    disable_debugging();

    $relativepath = get_file_argument('file.php');
    $forcedownload = optional_param('forcedownload', 0, PARAM_BOOL);
    
    // relative path must start with '/', because of backup/restore!!!
    if (!$relativepath) {
        error('No valid arguments supplied or incorrect server configuration');
    } else if ($relativepath{0} != '/') {
        error('No valid arguments supplied, path does not start with slash!');
    }

    $pathname = $CFG->dataroot.$relativepath;

    // extract relative path components
    $args = explode('/', trim($relativepath, '/'));
    
    //security this should only be used for wwquestions
    if ((count($args) < 3) || ($args[0] != '1') || ($args[1] != 'wwquestions')) {
        error('No valid arguments supplied');
    }
    
    //extra security for users
    /*if((count($args) > 6) && ($args[5] == 'users')) {
        //trying to get a user equation image
        if($args[6] != $USER->id) {
            require_capability('moodle/mod/quiz:viewreports');
        }
    }*/
    
    

    if (is_dir($pathname)) {
        if (file_exists($pathname.'/index.html')) {
            $pathname = rtrim($pathname, '/').'/index.html';
            $args[] = 'index.html';
        } else if (file_exists($pathname.'/index.htm')) {
            $pathname = rtrim($pathname, '/').'/index.htm';
            $args[] = 'index.htm';
        } else if (file_exists($pathname.'/Default.htm')) {
            $pathname = rtrim($pathname, '/').'/Default.htm';
            $args[] = 'Default.htm';
        } else {
            // security: do not return directory node!
            not_found($course->id);
        }
    }

    // check that file exists
    if (!file_exists($pathname)) {
        not_found($course->id);
    }

    // ========================================
    // finally send the file
    // ========================================
    session_write_close(); // unlock session during fileserving
    $filename = $args[count($args)-1];
    send_file($pathname, $filename);

    function not_found($courseid) {
        global $CFG;
        header('HTTP/1.0 404 not found');
        error(get_string('filenotfound', 'error'), $CFG->wwwroot.'/course/view.php?id='.$courseid); //this is not displayed on IIS??
    }
?>
