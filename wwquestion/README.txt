Webwork Question Type
----------------------
Version: 0.2 (stable)
Maintainer: Matthew Leventi  <mleventi@gmail.com>
CVS: cvs.webwork.rochester.edu:/webwork/cvs/system wwmoodle/wwquestion

**If your using this send me an email.**

Whats coming soon (sept 1):
* Question Importer (alpha at CVS: cvs.webwork.rochester.edu:/webwork/cvs/system wwmoodle/wwquestion_importer)
* Applet & External Dep. support for PG files.

Whats New:
* DB consistency issues fixed (thanks to Jean-Marc)
* New code checking, makes sure PG code is correct
* Images are now copied locally for faster problem loading
* Minor bug Fixes


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
1) Change the WSDL path variable in the webwork/questiontype.php file to point to your Webwork Problem Server's WSDL file.

Use:
A webwork question only has three special fields. 
    -code: Paste the perl code that renders the webwork question here.
    -seed: The starting seed to use to generate random problems
    -trials: The number of attempts the generator should make to generate and cache problems.
    
Finding webwork questions:
http://cvs.webwork.rochester.edu/viewcvs.cgi/rochester_problib/?cvsroot=UR+Problem+Library (for now)    

Note:
Previewing the question will use random seeds.

ex problem)

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





