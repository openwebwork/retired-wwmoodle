<?php //$Id: backuplib.php,v 1.3 2008-08-24 03:03:32 gage Exp $
    //This php script contains all the stuff to backup
    //wwassignment mods

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

    //This function executes all the backup procedure about this mod


    function wwassignment_backup_mods($bf,$preferences) {
        //error_log("wwassignment_backup_mods");
        ////error_log("preferences ".print_r($preferences,true));
        global $CFG;

        $status = true;

        //Iterate over assignment table
        $wwassignments = get_records ("wwassignment","course",$preferences->backup_course);
        if ($wwassignments) {
            foreach ($wwassignments as $wwassignment) {
                ////error_log("backing up wwassignment ".$wwassignment->id);
                if (backup_mod_selected($preferences,'wwassignment',$wwassignment->id)) {
                    $status = wwassignment_backup_one_mod($bf,$preferences,$wwassignment);
                    // backup files happens in backup_one_mod now too.
                }
            }
        }
        

        
         //error_log("end wwassignment_backup_mods");
         return $status;  
    }

    function wwassignment_backup_one_mod($bf,$preferences,$wwassignment) {
        //error_log("wwassignment_backup_one_mod");
        ////error_log("preferences ".print_r($preferences,true));
        global $CFG;
    
        if (is_numeric($wwassignment)) {
            $wwassignment = get_record('wwassignment','id',$wwassignment);
        }
    
        $status = true;
//             function full_tag($tag,$level=0,$endline=true,$content,$attributes=null) {
//         //Start mod
        fwrite ($bf,start_tag("MOD",3,true));
        //Print wwassignment data
        fwrite ($bf,full_tag("ID",4,false,$wwassignment->id));
        fwrite ($bf,full_tag("MODTYPE",4,false,"wwassignment"));      
        fwrite ($bf,full_tag("NAME",4,false,$wwassignment->name));
        fwrite ($bf,full_tag("DESCRIPTION",4,false,$wwassignment->description));
        fwrite ($bf,full_tag("WEBWORK_SET",4,false,$wwassignment->webwork_set));
        fwrite ($bf,full_tag("TIMEMODIFIED",4,false,$wwassignment->timemodified));
        //if we've selected to backup users info, then execute backup_assignment_submisions and
        //backup_assignment_files_instance

         //End mod
        $status =fwrite ($bf,end_tag("MOD",3,true));
        
        
        //error_log("end wwassignment_one_backup_mod");
        //error_log("preferences ".print_r($preferences,true));

			
        
           return $status;
     }


    //Return an array of info (name,value)
    function wwassignment_check_backup_mods($course,$user_data=false,$backup_unique_code,$instances=null) {
           ////error_log("wwassignment_check_backup_mods ");
           ////error_log("user_data ".print_r($user_data,true) );
           ////error_log("backup code ".print_r($backup_unique_code,true));
           ////error_log("instance ".print_r($instance,true));
        $info=array();
        if (!empty($instances) && is_array($instances) && count($instances)) {
            foreach ($instances as $id => $instance) {
                $info += wwassignment_check_backup_mods_instances($instance,$backup_unique_code);
            }
            return $info;
        }
        //First the course data
        $info[0][0] = get_string("modulenameplural","wwassignment");
        if ($ids = wwassignment_ids ($course)) {
            $info[0][1] = count($ids);
        } else {
            $info[0][1] = 0;
        }
         
         ////error_log("end wwassignment_check_backup_mods");

         return $info;
    }

    //Return an array of info (name,value)
    function wwassignment_check_backup_mods_instances($instance,$backup_unique_code) {
        ////error_log("wwassignment_check_backup_mods_instances ");
        ////error_log("content ".print_r($content,true));
        ////error_log("preferences ".print_r($preferences,true));

        $info[$instance->id.'0'][0] = '<b>'.$instance->name.'</b>';
        $info[$instance->id.'0'][1] = '';
//         if (!empty($instance->userdata)) {
//             $info[$instance->id.'1'][0] = get_string("submissions","assignment");
//             if ($ids = assignment_submission_ids_by_instance ($instance->id)) {
//                 $info[$instance->id.'1'][1] = count($ids);
//             } else {
//                 $info[$instance->id.'1'][1] = 0;
//             }
//         }        
         ////error_log("end wwassignment_check_backup_mods_instances");

         return $info;
     }
// 
//     //Return a content encoded to support interactivities linking. Every module
//     //should have its own. They are called automatically from the backup procedure.
//     function assignment_encode_content_links ($content,$preferences) {
// 
//         global $CFG;
// 
//         $base = preg_quote($CFG->wwwroot,"/");
// 
//         //Link to the list of assignments
//         $buscar="/(".$base."\/mod\/assignment\/index.php\?id\=)([0-9]+)/";
//         $result= preg_replace($buscar,'$@ASSIGNMENTINDEX*$2@$',$content);
// 
//         //Link to assignment view by moduleid
//         $buscar="/(".$base."\/mod\/assignment\/view.php\?id\=)([0-9]+)/";
//         $result= preg_replace($buscar,'$@ASSIGNMENTVIEWBYID*$2@$',$result);
// 
//         return $result;
//     }

      // INTERNAL FUNCTIONS. BASED IN THE MOD STRUCTURE

    //Returns an array of assignments id 
    function wwassignment_ids ($course) {
        ////error_log("wwassignment_ids ");
        ////error_log("course ".print_r($course,true));
    
        global $CFG;
        
        
        ////error_log("end wwassignment_ids");

        return get_records_sql ("SELECT a.id, a.course
                                 FROM {$CFG->prefix}wwassignment a
                                 WHERE a.course = '$course'");
    } 
    
 
?>
