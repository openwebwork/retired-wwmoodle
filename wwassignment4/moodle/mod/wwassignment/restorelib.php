<?php //$Id: restorelib.php,v 1.2 2008-08-24 01:27:56 gage Exp $
    //This php script contains all the stuff to backup/restore
    //assignment mods

    //This is the "graphical" structure of the assignment mod:
    //
    //                     assignment
    //                    (CL,pk->id)             
    //                        |
    //                        |
    //                        |
    //                 assignment_submisions 
    //           (UL,pk->id, fk->assignment,files)
    //
    // Meaning: pk->primary key field of the table
    //          fk->foreign key to link with parent
    //          nt->nested field (recursive data)
    //          CL->course level info
    //          UL->user level info
    //          files->table may have files)
    //
    //-----------------------------------------------------------

    //This function executes all the restore procedure about this mod
    function wwassignment_restore_mods($mod,$restore) {

        global $CFG;

        $status = true;
        error_log("mod id ".$mod->id);
        if ($mod->id == "66666") {
        	$wwlinkdata  = backup_getid($restore->backup_unique_code,"wwassignment_bridge","wwassignment_bridge");
            error_log("wwlink data ".print_r($wwlinkdata, true ));
            return $status;
         }

        //Get record from backup_ids
        
        $data = backup_getid($restore->backup_unique_code,$mod->modtype,$mod->id);
        
        if ($data) {
            //Now get completed xmlized object
            $info = $data->info;
            //if necessary, write to restorelog and adjust date/time fields
            if ($restore->course_startdateoffset) {
                //restore_log_date_changes('Wwassignment', $restore, $info['MOD']['#'], array('TIMEDUE', 'TIMEAVAILABLE'));
            }
            //traverse_xmlize($info);                                                                     //Debug
            //print_object ($GLOBALS['traverse_array']);                                                  //Debug
            //$GLOBALS['traverse_array']="";                                                              //Debug

            //Now, build the ASSIGNMENT record structure
            $wwassignment->course = $restore->course_id;
            $wwassignment->name = backup_todb($info['MOD']['#']['NAME']['0']['#']);
            $wwassignment->description = backup_todb($info['MOD']['#']['DESCRIPTION']['0']['#']);
            $wwassignment->webwork_set = backup_todb($info['MOD']['#']['WEBWORK_SET']['0']['#']);
            $wwassignment->timemodified = backup_todb($info['MOD']['#']['TIMEMODIFIED']['0']['#']);



            //The structure is equal to the db, so insert the assignment
            $newid = insert_record ("wwassignment",$wwassignment);

            //Do some output     
            if (!defined('RESTORE_SILENTLY')) {
                echo "<li>".get_string("modulename","wwassignment")." \"".format_string(stripslashes($wwassignment->name),true)."\"</li>";
            }
            backup_flush(300);
 
            if ($newid) {
                //We have the newid, update backup_ids
                backup_putid($restore->backup_unique_code,$mod->modtype,
                             $mod->id, $newid);

            } else {
                $status = false;
            }
        } else {
            $status = false;
        }
         error_log("mod id is ".print_r($mod,true));
        return $status;
    }


 
    //This function returns a log record with all the necessay transformations
    //done. It's used by restore_log_module() to restore modules log.
    function wwassignment_restore_logs($restore,$log) {
                    
        $status = false;
                    
        //Depending of the action, we recode different things
        switch ($log->action) {
        case "add":
            if ($log->cmid) {
                //Get the new_id of the module (to recode the info field)
                $mod = backup_getid($restore->backup_unique_code,$log->module,$log->info);
                if ($mod) {
                    $log->url = "view.php?id=".$log->cmid;
                    $log->info = $mod->new_id;
                    $status = true;
                }
            }
            break;
        case "update":
            if ($log->cmid) {
                //Get the new_id of the module (to recode the info field)
                $mod = backup_getid($restore->backup_unique_code,$log->module,$log->info);
                if ($mod) {
                    $log->url = "view.php?id=".$log->cmid;
                    $log->info = $mod->new_id;
                    $status = true;
                }
            }
            break;
        case "view":
            if ($log->cmid) {
                //Get the new_id of the module (to recode the info field)
                $mod = backup_getid($restore->backup_unique_code,$log->module,$log->info);
                if ($mod) {
                    $log->url = "view.php?id=".$log->cmid;
                    $log->info = $mod->new_id;
                    $status = true;
                }
            }
            break;
        case "view all":
            $log->url = "index.php?id=".$log->course;
            $status = true;
            break;
        case "upload":
            if ($log->cmid) {
                //Get the new_id of the module (to recode the info field)
                $mod = backup_getid($restore->backup_unique_code,$log->module,$log->info);
                if ($mod) {
                    $log->url = "view.php?a=".$mod->new_id;
                    $log->info = $mod->new_id;
                    $status = true;
                }
            }
            break;
        case "view submission":
            if ($log->cmid) {
                //Get the new_id of the module (to recode the info field)
                $mod = backup_getid($restore->backup_unique_code,$log->module,$log->info);
                if ($mod) {
                    $log->url = "submissions.php?id=".$mod->new_id;
                    $log->info = $mod->new_id;
                    $status = true;
                }
            }
            break;
        case "update grades":
            if ($log->cmid) {
                //Extract the assignment id from the url field                             
                $assid = substr(strrchr($log->url,"="),1);
                //Get the new_id of the module (to recode the info field)
                $mod = backup_getid($restore->backup_unique_code,$log->module,$assid);
                if ($mod) {
                    $log->url = "submissions.php?id=".$mod->new_id;
                    $status = true;
                }
            }
            break;
        default:
            if (!defined('RESTORE_SILENTLY')) {
                echo "action (".$log->module."-".$log->action.") unknown. Not restored<br />";                 //Debug
            }
            break;
        }

        if ($status) {
            $status = $log;
        }
        return $status;
    }
?>
