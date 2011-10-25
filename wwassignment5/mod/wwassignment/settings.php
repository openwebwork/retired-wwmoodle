<?php  //$Id: settings.php,v 1.1 2008/08/21 17:59:52 gage Exp $


require_once($CFG->dirroot.'/mod/wwassignment/lib.php');

   

$settings->add(new admin_setting_configtext('wwassignment_webworkurl', 
                           get_string('webwork_url','wwassignment'),
                           get_string('webwork_url_desc','wwassignment'),
                           $CFG->wwassignment_webworkurl,
                           PARAM_RAW, //param type -- default is PARAM_RAW
                           50  // size of blank -- default is 30

));

$settings->add(new admin_setting_configtext('wwassignment_rpc_wsdl', 
                           get_string('rpc_wsdl','wwassignment'),
                           get_string('rpc_wsdl_desc','wwassignment'),
                           $CFG->wwassignment_rpc_wsdl,
                           PARAM_RAW, //param type -- default is PARAM_RAW
                           50  // size of blank -- default is 30
));
$settings->add(new admin_setting_configtext('wwassignment_rpc_key', 
                           get_string('rpc_key','wwassignment'),
                           get_string('rpc_key_desc','wwassignment'),
                           $CFG->wwassignment_rpc_key,
                           PARAM_RAW, //param type -- default is PARAM_RAW
                           50  // size of blank -- default is 30
));

$settings->add(new admin_setting_configtext('wwassignment_iframewidth', 
                           get_string('iframe_width','wwassignment'),
                           get_string('iframe_width_desc','wwassignment'),
                           $CFG->wwassignment_iframewidth,
                           PARAM_RAW, //param type -- default is PARAM_RAW
                           30  // size of blank -- default is 30
));
$settings->add(new admin_setting_configtext('wwassignment_iframeheight', 
                           get_string('iframe_height','wwassignment'),
                           get_string('iframe_height_desc','wwassignment'),
                           $CFG->wwassignment_iframeheight,
                           PARAM_RAW, //param type -- default is PARAM_RAW
                           30  // size of blank -- default is 30        
));



?>
