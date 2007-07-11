Webwork Question Type
----------------------
Version: 0.1 (stable)
Maintainer: Matthew Leventi  <mleventi@gmail.com>
CVS: cvs.webwork.rochester.edu:/webwork/cvs/system wwmoodle/wwquestion

Setup:
1) Make a new folder named 'webwork' in the question/type directory.
2) Copy all the files from either the zip or the cvs checkout into the new webwork directory.
3) Point your browser to http://yourmoodle/admin to setup the question_webwork database table.

Configuration:
1) Change the WSDL path variable in the webwork/questiontype.php file to point to your Webwork Problem Server's WSDL file.
2) Modify the displayMode to your preferences (images,jsMath,plainText)

Use:
A webwork question only has two special fields. 
    -code: Paste the perl code that renders the webwork question here.
    -seed: A value of 0 will randomize the values in a webwork problem for each student. Any other value will be the seed for all students. Hence all students will get the exact same problem

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





