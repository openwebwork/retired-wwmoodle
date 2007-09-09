Webwork Question Type
----------------------
Version: 0.3 (stable) Released 9/9/2007
Maintainer: Matthew Leventi  <mleventi@gmail.com>
CVS: cvs.webwork.rochester.edu:/webwork/cvs/system wwmoodle/wwquestion

This is a moodle questiontype module that will allow webwork questions to be asked in Moodle Quizzes and Lessons. Currently it supports many of the features found in the webwork2 system.

* If you are using this send me an email. Feedback is appreciated. *

Current Release:
* Derivation mismatch bugs fixed.
* New Test file
* External File support! (applet,images,graphs,etc)
* Code Refactoring
* New levels of Code Checking including warnings

PG Language: What is supported?
Simple and complex pg problems including those which use applets, images, graph generation problems, etc. 

PG Language: What is not supported?
Currently the only PG files that don't fully work are those with custom graders. Hence problems will reveal which answers the student has correct despite the showCorrectAnswer flag being off.

Upgrading (from 0.1):
A database column was added called codecheck. If you have data that you don't want to lose your going to have to add the new column manually to the db
mysql command: ALTER TABLE mdl_question_webwork ADD COLUMN codecheck int(10) not null default 0;
Then you can delete the webwork directory and recreate it from the CVS
** I am not really sure if this is backward compatible to 0.1. If you are having problems with questions edit them and submit to regenerate derived copies.


Setup:
1) Make a new folder named 'webwork' in the question/type directory.
2) Copy all the files from this directory into .
3) Point your browser to http://yourmoodle/admin to setup the question_webwork database table.

Configuration:
1) Change the WSDL path variable in the webwork/config.php file to point to your Webwork Problem Server's WSDL file.

Use:
Go into the question bank and create a new WeBWorK question. 
A webwork question only has three special fields. 
    -code: Paste the perl code that renders the webwork question here.
    -seed: The starting seed to use to generate random problems
    -trials: The number of attempts the generator should make to generate and cache problems.
    
Finding webwork questions:
http://cvs.webwork.rochester.edu/viewcvs.cgi/rochester_problib/?cvsroot=UR+Problem+Library (for now)    

Note:
Previewing the question will use random seeds.

Example Problem:

##DESCRIPTION

# Singularities, Determine Type (Poles), Residues

##ENDDESCRIPTION


DOCUMENT();        # This should be the first executable line in the problem.

loadMacros(
   "PGstandard.pl",     # Standard macros for PG language
   "MathObjects.pl",
   "PGunion.pl",        # Union College macros
   #"PGcourse.pl",      # Customization file for the course
);

TEXT(beginproblem());
$showPartialCorrectAnswers = 1;

##############################################################
#
#  Setup
#
Context("Point");

$a = non_zero_random(-10,10,1);

$formula_string = Formula( "sin(1/($a*z))"  );

@singularities = ("(0, 1/$a)" );
$f = Compute("(0,1/$a)");
##############################################################
#
#  Text
#
#
Context()->texStrings;
BEGIN_TEXT

Find the singularities and their residues for $PAR

\[ $formula_string \]

$PAR
\{ans_rule(60)\}
$PAR
List the singularities and their residues as ordered pairs. (for example,
(3, pi), (-1,2) means that there is a singularity at \(3\) with residue \(\pi\)
and a singularity at \(-1\) with residue \(2\).

$PA
END_TEXT
Context()->normalStrings;
##############################################################
#
#  Answers
#
#

ANS(List(@singularities)->cmp(correct_ans=>$f->{correct_ans}) );



ENDDOCUMENT();        # This should be the last executable line in the problem.





