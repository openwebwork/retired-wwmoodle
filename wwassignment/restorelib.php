<?php

function wwassignment_restore_mods($mod,$restore) {

    global $CFG;

    $status = true;
    //Get record from backup_ids
    $data = backup_getid($restore->backup_unique_code,$mod->modtype,$mod->id);

    if ($data) {
        //Now get completed xmlized object
        $info = $data->info;
                   
            //traverse_xmlize($info);                                                                     //Debug
            //print_object ($GLOBALS['traverse_array']);                                                  //Debug
            //$GLOBALS['traverse_array']="";                                                              //Debug

            //Now, build the QUIZ record structure
        $wwassignment = new stdClass;
        $wwassignment->course = $restore->course_id;
        $wwassignment->name = backup_todb($info['MOD']['#']['NAME']['0']['#']);
        $wwassignment->description = backup_todb($info['MOD']['#']['DESCRIPTION']['0']['#']); 
        $wwassignment->webwork_set = backup_todb($info['MOD']['#']['WEBWORK_SET']['0']['#']);
        
        //The structure is equal to the db, so insert the quiz
        $newid = insert_record("wwassignment",$wwassignment);

        //Do some output
        if (!defined('RESTORE_SILENTLY')) {
            echo "<li>".get_string("modulename","wwassignment")." \"".format_string(stripslashes($wwassignment->name),true)."\"</li>";
        }
        backup_flush(300);
        if ($newid) {
            //We have the newid, update backup_ids
            backup_putid($restore->backup_unique_code,$mod->modtype,$mod->id, $newid);
        } else {
            $status = false;
        }
    } else {
        $status = false;
    }
    return $status;
}
?>
