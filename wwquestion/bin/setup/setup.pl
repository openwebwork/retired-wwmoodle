#!/usr/bin/env perl

use CPAN;
use File::Which;  die "You do not have File::Which installed.\n Run perl -MCPAN -e 'install File::Which' to install. Then rerun this." if $@;

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
print "#WeBWorK Question Type in Moodle  #\n";
print "###################################\n";

#Continue?
print "This script will setup the configuration of WeBWorK Question Server.\n";
$continue = promptUser('Continue','y');
if($continue ne "y") {
    exit;
}

print "Please enter the root directory where Moodle is installed. \n";
print "Example: /var/www/moodle \n";
$moodleRoot = promptUser('');

print "Please enter the WSDL Path given in the server setup. \n";
print "Example: http://myserver/problemserver_files/WSDL.wsdl\n";
$wsdlPath = promptUser('');
