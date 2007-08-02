<?php

////////////////////////////////////////////////////////////////////////////
/// Blackboard 6.x Format
///
/// This Moodle class provides all functions necessary to import and export
///
///
////////////////////////////////////////////////////////////////////////////

// Based on default.php, included by ../import.php

require_once ("$CFG->libdir/xmlize.php");

class qformat_webwork extends qformat_default {
    function provide_import() {
        return true;
    }
    
    
    //Function to check and create the needed dir to unzip file to
    function check_and_create_import_dir($unique_code) {

        global $CFG; 

        $status = $this->check_dir_exists($CFG->dataroot."/temp",true);
        if ($status) {
            $status = $this->check_dir_exists($CFG->dataroot."/temp/webworkquiz_import",true);
        }
        if ($status) {
            $status = $this->check_dir_exists($CFG->dataroot."/temp/webworkquiz_import/".$unique_code,true);
        }
        
        return $status;
    }
    
    function clean_temp_dir($dir='') {
        // for now we will just say everything happened okay note 
        // that a mess may be piling up in $CFG->dataroot/temp/webworkquiz_import
        return true;
        
        if ($dir == '') {
            $dir = $this->temp_dir;   
        }
        $slash = "/";

        // Create arrays to store files and directories
        $dir_files      = array();
        $dir_subdirs    = array();

        // Make sure we can delete it
        chmod($dir, 0777);

        if ((($handle = opendir($dir))) == FALSE) {
            // The directory could not be opened
            return false;
        }

        // Loop through all directory entries, and construct two temporary arrays containing files and sub directories
        while($entry = readdir($handle)) {
            if (is_dir($dir. $slash .$entry) && $entry != ".." && $entry != ".") {
                $dir_subdirs[] = $dir. $slash .$entry;
            }
            else if ($entry != ".." && $entry != ".") {
                $dir_files[] = $dir. $slash .$entry;
            }
        }

        // Delete all files in the curent directory return false and halt if a file cannot be removed
        for($i=0; $i<count($dir_files); $i++) {
            chmod($dir_files[$i], 0777);
            if (((unlink($dir_files[$i]))) == FALSE) {
                return false;
            }
        }

        // Empty sub directories and then remove the directory
        for($i=0; $i<count($dir_subdirs); $i++) {
            chmod($dir_subdirs[$i], 0777);
            if ($this->clean_temp_dir($dir_subdirs[$i]) == FALSE) {
                return false;
            }
            else {
                if (rmdir($dir_subdirs[$i]) == FALSE) {
                return false;
                }
            }
        }

        // Close directory
        closedir($handle);
        if (rmdir($this->temp_dir) == FALSE) {
            return false;    
        }
        // Success, every thing is gone return true
        return true;
    }
    
    //Function to check if a directory exists and, optionally, create it
    function check_dir_exists($dir,$create=false) {

        global $CFG; 

        $status = true;
        if(!is_dir($dir)) {
            if (!$create) {
                $status = false;
            } else {
                umask(0000);
                $status = mkdir ($dir,$CFG->directorypermissions);
            }
        }
        return $status;
    }

    function importpostprocess() {
    /// Does any post-processing that may be desired
    /// Argument is a simple array of question ids that 
    /// have just been added.
    
        // need to clean up temporary directory
        return $this->clean_temp_dir();
    }

    function copy_file_to_course($filename) {
        global $CFG, $COURSE;
        $filename = str_replace('\\','/',$filename);
        $fullpath = $this->temp_dir.'/res00001/'.$filename;
        $basename = basename($filename);
    
        $copy_to = $CFG->dataroot.'/'.$COURSE->id.'/bb_import';
        
        if ($this->check_dir_exists($copy_to,true)) {
            if(is_readable($fullpath)) {
                $copy_to.= '/'.$basename;
                if (!copy($fullpath, $copy_to)) {
                    return false;
                }
                else {
                    return $copy_to;
                }
            }
        }
        else {
            return false;   
        }
    }

    function readdata($filename) {
    /// Returns complete file with an array, one item per line
        global $CFG;
        
        $unique_code = time();
        $temp_dir = $CFG->dataroot."/temp/webworkquiz_import/".$unique_code;
        $this->temp_dir = $temp_dir;
        //failures we cannot handle
        if (!$this->check_and_create_import_dir($unique_code)) {
            error("Could not create temporary directory");
            return;
        }
        if(!is_readable($filename)) {
            error ("Could not read uploaded file");
            return;
        }
        if (!copy($filename, "$temp_dir/webwork.zip")) {
            error("Could not copy backup file");
            return;
        }
        if(!unzip_file("$temp_dir/webwork.zip", '', false)) {
            print "filename: $filename<br />tempdir: $temp_dir <br />";
            error("Could not unzip file.");
            return;   
        }
        $data = array();
        //open the directory
        $this->process_directory($data,$temp_dir);
        return $data;
    }
    function process_directory(&$data,$dir) {
        //add trailing slash
        if ($dir[strlen($dir)-1] != '/') {
            $dir .= '/';
        }
        //is this a directory
        if (!is_dir($dir)) {
            return;
        }
        $children = array();
        
        //open it and process files
        $dir_handle = opendir($dir);
        while ($object = readdir($dir_handle)) {
            if (!in_array($object, array('.','..'))) {
                $filename = $dir . $object;
                $type = filetype($filename);
                if($type == 'dir') {
                    array_push($children,$object);
                } else if ($type == 'file') {
                    if ((is_file($filename)) && (is_readable($filename))) {
                        //we have a config file
                        if($object == 'webwork.conf') {
                            $this->process_config($data,file($filename));
                        } else if ($this->is_problem_file($object)) {
                            $problemtxt = file_get_contents($filename);
                            $problemname = substr($object,0,strlen($object)-3);
                            $data['problems'][$problemname] = $problemtxt;
                        } else {
                            echo "Not working with file " . $filename . "<br />";
                        }
                    }
                }
            }
        }
        //recursive down the directory tree
        foreach($children as $child) {
            $childdata = array();
            $this->process_directory($childdata,$dir . $child);
            $data['children'][$child] = $childdata;
        }        
    }
    function process_config(&$data,$config) {
        if (ereg("\r", $config[0]) AND !ereg("\n", $config[0])) {
            $temp = explode("\r", $config[0]);
        } else {
            $temp = $config;
        }
        $finalconfig = array();
        foreach($temp as $configline) {
            if($configline != "") {
                $linearray = explode('=',$configline);
                if(count($linearray) == 2) {
                    $key = trim($linearray[0]);
                    $value = trim($linearray[1]);
                    $finalconfig[$key] = $value;
                }
            }
        }
        $data['config'] = $finalconfig;
    }
    
    function is_problem_file($filename) {
        $len = strlen($filename);
        if(($len > 3) && ($filename[$len-1] == 'g') && ($filename[$len-2] == 'p') && ($filename[$len-3] == '.')) {
            return true;
        }
        return false;
    }
        
    function save_question_options($question) {
        return true; 
    }
    function format_question_text($question) {
        return $question->name . '<br />Seed: ' . $question->seed . '<br />Trials:' . $question->trials;
    }
    
    
  function process_categories(&$questions,$category,&$data,&$parentconfig) {
      
      //proper category
      $qo = new stdClass;
      $qo->qtype = 'category';
      $qo->category = $category;
      $questions[] = $qo;
      
      //set defaults on config from parent
      $questiongrade = $parentconfig['QuestionGrade'];
      $penaltyfactor = $parentconfig['PenaltyFactor'];
      $feedback = $parentconfig['GeneralFeedback'];
      $seed = $parentconfig['Seed'];
      $trials = $parentconfig['Trials'];
      
      //override as necessary
      if(isset($data['config'])) {
          if(isset($data['config']['QuestionGrade'])) {
              $questiongrade = $data['config']['QuestionGrade'];
          }
          if(isset($data['config']['PenaltyFactor'])) {
              $penaltyfactor = $data['config']['PenaltyFactor'];
          }
          if(isset($data['config']['GeneralFeedback'])) {
              $feedback = $data['config']['GeneralFeedback'];
          }
          if(isset($data['config']['Seed'])) {
              $seed = $data['config']['Seed'];
          }
          if(isset($data['config']['Trials'])) {
              $trials = $data['config']['Trials'];
          } 
      }
      
      //create problems
      foreach($data['problems']as $key => $value) {
          $question = $this->defaultquestion();
          $question->qtype = 'webwork';
          $question->name = $key;
          //we add slashes here because they are taken off later... question type is expecting stuff out of textarea
          $question->code = addslashes($value);
          $question->seed = $seed;
          $question->trials = $trials;
          $question->penalty = $penaltyfactor;
          $question->defaultgrade = $questiongrade;
          $question->generalfeedback = $feedback;
          //add one
          $questions[] = $question;
          echo $trials;
      }
      
      //handle children config
      $childconfig = array();
      $childconfig['QuestionGrade'] =  $questiongrade;
      $childconfig['PenaltyFactor'] = $penaltyfactor;
      $childconfig['GeneralFeedback'] =  $feedback;
      $childconfig['Seed'] = $seed;
      $childconfig['Trials'] = $trials;
      foreach($data['children'] as $name => $child) {
          $this->process_categories($questions,$category."/".$name,$child,$childconfig);
      }
  }
    
  function readquestions ($lines) {
      $questions = array();
      $category = "Default";
      $this->process_categories($questions,$category,$lines,$config);
      return $questions;
  }


} // close object
?>
