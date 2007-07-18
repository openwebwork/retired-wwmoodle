<?php

function wwassignment_backup_mods($bf,$preferences) {
    $status = true;
    
    //Iterate over wwassignments
    if($wwassignments = get_records("wwassignment","course",$preferences->backup_course,"id"))  {
        foreach ($wwassignments as $wwassignment) {
            if (backup_mod_selected($preferences,'wwassignment',$wwassignment->id)) {
                $status = wwassignment_backup_one_mod($bf,$preferences,$wwassignment);
            }
        }
    }
    return $status;
}

function wwassignment_backup_one_mod($bf,$preferences,$wwassignment) {
    $status = true;
    
    if (is_numeric($wwassignment)) {
        $wwassignment = get_record('wwassignment','id',$wwassignment);
    }
    
    //Start mod
    fwrite ($bf,start_tag("MOD",3,true));
    //Print wwassignment data
    fwrite ($bf,full_tag("ID",4,false,$wwassignment->id));
    fwrite ($bf,full_tag("MODTYPE",4,false,"wwassignment"));
    fwrite ($bf,full_tag("NAME",4,false,$wwassignment->name));
    fwrite ($bf,full_tag("DESCRIPTION",4,false,$wwassignment->description));
    fwrite ($bf,full_tag("WEBWORK_SET",4,false,$wwassignment->webwork_set));
    
    //End mod
    $status = fwrite ($bf,end_tag("MOD",3,true));
        
    return $status;
}

function wwassignment_check_backup_mods($course,$user_data=false,$backup_unique_code,$instances=null) {
    if (!empty($instances) && is_array($instances) && count($instances)) {
        $info = array();
        foreach ($instances as $id => $instance) {
            $info += wwassignment_check_backup_mods_instances($instance,$backup_unique_code);
        }
        return $info;
    }
        
    //First the course data
    $info[0][0] = get_string("modulenameplural","wwassignment");
    $info[0][1] = count_records("wwassignment", "course", "$course");
    return $info;
} 

////Return an array of info (name,value)
function wwassignment_check_backup_mods_instances($instance,$backup_unique_code) {
    //First the course data
    $info[$instance->id.'0'][0] = '<b>'.$instance->name.'</b>';
    $info[$instance->id.'0'][1] = '';
    return $info;
}
?>
