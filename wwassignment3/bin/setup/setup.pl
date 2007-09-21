#!/usr/bin/env perl

use Cwd;

sub promptUser {

   #-------------------------------------------------------------------#
   #  two possible input arguments - $promptString, and $defaultValue  #
   #  make the input arguments local variables.                        #
   #-------------------------------------------------------------------#

   local($promptString,$defaultValue) = @_;

   #-------------------------------------------------------------------#
   #  if there is a default value, use the first print statement; if   #
   #  no default is provided, print the second string.                 #
   #-------------------------------------------------------------------#

   if ($defaultValue) {
      print $promptString, "[", $defaultValue, "]: ";
   } else {
      print $promptString, ": ";
   }

   $| = 1;               # force a flush after our print
   $_ = <STDIN>;         # get the input from STDIN (presumably the keyboard)


   #------------------------------------------------------------------#
   # remove the newline character from the end of the input the user  #
   # gave us.                                                         #
   #------------------------------------------------------------------#

   chomp;

   #-----------------------------------------------------------------#
   #  if we had a $default value, and the user gave us input, then   #
   #  return the input; if we had a default, and they gave us no     #
   #  no input, return the $defaultValue.                            #
   #                                                                 #
   #  if we did not have a default value, then just return whatever  #
   #  the user gave us.  if they just hit the <enter> key,           #
   #  the calling routine will have to deal with that.               #
   #-----------------------------------------------------------------#

   if ("$defaultValue") {
      return $_ ? $_ : $defaultValue;    # return $_ if it has a value
   } else {
      return $_;
   }
}

print "###################################\n";
print "#WeBWorK Assignment in Moodle     #\n";
print "###################################\n";

#Continue?
print "This script will setup the WeBWorK Assignment Type.\n";
$continue = promptUser('Continue','y');
if($continue ne "y") {
    exit;
}

#Program Root
print "Please enter the root directory where wwassignment3 module is located. \n";
print "Example: /tmp/wwmoodle/wwassignment3\n";
$wwassignmentRoot = promptUser('');

#Moodle Root
print "Please enter the root directory where Moodle is installed. \n";
print "Example: /var/www/moodle \n";
$moodleRoot = promptUser('');

#File Moving/Linking
$files = promptUser('Would you like me to place the files into proper directories (y,n)','y');
if($files eq 'y') {
   $doWhat = promptUser('Would you like me to copy the help files or soft link them.(copy,link)','link');
   if($doWhat eq 'link') {
      $action = 'ln -sf ';
   } elsif ($doWhat eq 'copy') {
      print "Remember to rerun setup when/if you update from the CVS\n";
      $action = 'cp -R ';
   } else {
      exit;
   }
   #wipe existing directories
   system("rm -rf $moodleRoot/mod/wwassignment");
   system("rm -rf $moodleRoot/blocks/wwlink");
   system("rm -rf $moodleRoot/lang/en_utf8/help/wwassignment");

   #copy php code directories
   system("cp -R $wwassignmentRoot/moodle/mod/wwassignment " . $moodleRoot . '/mod/');
   system("cp -R $wwassignmentRoot/moodle/blocks/wwlink " . $moodleRoot . '/blocks/');

   #help files
   system($action . "$wwassignmentRoot/moodle/lang/en_utf8/wwassignment.php " . $moodleRoot . '/lang/en_utf8/wwassignment.php');
   system($action . "$wwassignmentRoot/moodle/lang/en_utf8/block_wwlink.php " . $moodleRoot . '/lang/en_utf8/block_wwlink.php');
   system($action . "$wwassignmentRoot/moodle/lang/en_utf8/help/wwassignment " . $moodleRoot . '/lang/en_utf8/help/');

   print "Setup Successful!\n";


}

1;
