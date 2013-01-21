<?php
############################################################################
# Biorythm
#
# Aastra SIP Phones 2.2.0 or better
#
# Copyright 2008 Aastra Telecom Ltd
#
# Based on Biorhythm by Till Gerken http://www.zend.com/zend/tut/dynamic.php
#
# Supported Aastra Phones
#   All phones
#
# Usage
# 	script.php?user=XXXX
#
# Note
#      PHP-GD extension is needed for this script
#
#############################################################################

#############################################################################
# PHP customization for includes and warnings
#############################################################################
$os = strtolower(PHP_OS);
if(strpos($os, "win") === false) ini_set('include_path',ini_get('include_path').':include:../include');
else ini_set('include_path',ini_get('include_path').';include;..\include');
error_reporting(E_ERROR | E_WARNING | E_PARSE);

#############################################################################
# Includes
#############################################################################
require_once('AastraCommon.php');
require_once('AastraIPPhoneGDImage.class.php');
require_once('AastraIPPhoneImageScreen.class.php');
require_once('AastraIPPhoneInputScreen.class.php');
require_once('AastraIPPhoneTextScreen.class.php');
require_once('AastraIPPhoneFormattedTextScreen.class.php');

#############################################################################
# Private functions
#############################################################################
function drawRhythm($daysAlive, $period) 
{
global $daysToShow, $GDImage, $diagramWidth, $diagramHeight;

# get day on which to center
$centerDay = $daysAlive - ($daysToShow / 2);

# calculate diagram parameters
$plotScale = ($diagramHeight - 2) / 2;
$plotCenter = ($diagramHeight - 2) / 2;

# draw the curve
for ($x = 0; $x <= $daysToShow; $x++) 
	{
	# calculate phase of curve at this day, then Y value within diagram
	$phase = (($centerDay + $x) % $period) / $period * 2 * pi();
	$y = 1 - sin($phase) * (float)$plotScale + (float)$plotCenter;

	# draw line from last point to current point
	if ($x > 0) $GDImage->line($oldX, $oldY,	$x * $diagramWidth / $daysToShow, $y,1);

	# save current X/Y coordinates as start point for next line
	$oldX = $x * $diagramWidth / $daysToShow;
	$oldY = $y;
	}
}

function getResults()
{
global $daysGone;

$loop=array('I','E','P');
$array['period']['I']=33;
$array['period']['E']=28;
$array['period']['P']=23;
foreach($loop as $key) 
	{
	$array['day'][$key]=sin(($daysGone *2 *pi()) / $array['period'][$key]);
	$next=sin((($daysGone+1) *2 *pi()) / $array['period'][$key]);
	if($next-$array['day'][$key]>0) $array['trend'][$key]='/';
	else $array['trend'][$key]='\\';
	}
return($array);
}

#############################################################################
# Main program
#############################################################################
# Collect user input
$bdate=Aastra_getvar_safe('bdate');
$I=Aastra_getvar_safe('I','1');
$E=Aastra_getvar_safe('E','1');
$P=Aastra_getvar_safe('P','1');
$user=Aastra_getvar_safe('user');

# Retrieve phone information
$header=Aastra_decode_HTTP_header();
if($user=='') $user=$header['mac'];

# Keep return URI
$XML_SERVER.='?user='.$user;

# Trace
Aastra_trace_call('biorhytm','user='.$user);

# Get Language
$language=Aastra_get_language();

# Check compatibility
Aastra_test_phone_version('1.4.2.',0);
Aastra_test_php_function('imagecreate',Aastra_get_label('PHP-GD extension not installed.',$language));
$nb_softkeys=Aastra_number_softkeys_supported();

# Nothing entered
if ($bdate=='')
	{
	$date=Aastra_get_user_context($user,'biorhytm');
	$object=new AastraIPPhoneInputScreen();
	$object->setTitle(Aastra_get_label('Biorhythms',$language));
	$object->setPrompt(Aastra_get_label('Birth Date (MM/DD/YYYY)',$language));
	$object->setParameter('bdate');
	$object->setType('dateUS');
	$object->setURL($XML_SERVER);
	$object->setDefault($date);
	$object->setDestroyOnExit();
	if($nb_softkeys==10) $object->addSoftkey('10',Aastra_get_label('Exit',$language),"SoftKey:Exit");
	}
else
	{
	# Save user context
	Aastra_save_user_context($user,'biorhytm',$bdate);

	# Extract day/month/year
	$birthMonth = substr($bdate,0,2);
	$birthDay = substr($bdate,3,2);
	$birthYear = substr($bdate,6,4);

	# check date for validity, display error message if invalid
	if (!@checkDate($birthMonth,$birthDay,$birthYear)) 
		{
		# Display error message
		$object = new AastraIPPhoneTextScreen();
		$object->setTitle(Aastra_get_label('Invalid Birth Date',$language));
		$object->setText(Aastra_get_label('Please enter a valid Birth Date.',$language));
		}
	else
		{
		# calculate the number of days this person is alive
		$daysGone = abs(gregorianToJD($birthMonth, $birthDay, $birthYear) - gregorianToJD(date("m"), date("d"), date("Y")));

		# Get the results
		$array=getResults();

		# Test if graphics are supported
		if(Aastra_is_pixmap_graphics_supported())
			{
			# create image and object
			$object=new AastraIPPhoneImageScreen();
			$GDImage=new AastraIPPhoneGDImage();

			# specify diagram parameters (these are global)
			$diagramWidth=144;
			$diagramHeight=32;
			$daysToShow=28;

			# calculate start date for diagram and start drawing
			$nrSecondsPerDay = 60 * 60 * 24;
			$diagramDate = time() - ($daysToShow / 2 * $nrSecondsPerDay) + $nrSecondsPerDay;
			for ($i = 1; $i < $daysToShow; $i++) 
				{
				$thisDate = getDate($diagramDate);
				$xCoord = ($diagramWidth / $daysToShow) * $i;
				$GDImage->line($xCoord, $diagramHeight - 2, $xCoord, $diagramHeight, 1);
				$diagramDate += $nrSecondsPerDay;
				}
	
			# draw rectangle around diagram (marks its boundaries)
			$GDImage->rectangle(0,0,$diagramWidth-1, $diagramHeight,1,False);

			# draw middle cross
			$GDImage->line(0, $diagramHeight / 2, $diagramWidth, $diagramHeight / 2,1);
			$GDImage->line($diagramWidth / 2, 0, $diagramWidth / 2, $diagramHeight,1);

			# now draw each curve with its appropriate parameters
			if($P==1) drawRhythm($daysGone,23);
			if($E==1) drawRhythm($daysGone,28);
			if($I==1) drawRhythm($daysGone,33);

			# print values
			if($I==1) $GDImage->drawtext(1,0,33,sprintf(Aastra_get_label('I=%+.3f',$language),$array['day']['I']), 1);
			if($E==1) $GDImage->drawtext(1,51,33,sprintf(Aastra_get_label('E=%+.3f',$language),$array['day']['E']), 1);
			if($P==1) $GDImage->drawtext(1,104,33,sprintf(Aastra_get_label('P=%+.3f',$language),$array['day']['P']), 1);

			# Attach GD image
			$object->setGDImage($GDImage);

			# Create Softkeys
			if($I==1) $new=0;
			else $new=1;
			$object->addSoftkey('1',Aastra_get_label('Intellect',$language),$XML_SERVER.'&bdate='.$bdate.'&I='.$new.'&E='.$E.'&P='.$P);
			if($E==1) $new=0;
			else $new=1;
			$object->addSoftkey('2',Aastra_get_label('Emotional',$language),$XML_SERVER.'&bdate='.$bdate.'&I='.$I.'&E='.$new.'&P='.$P);
			if($P==1) $new=0;
			else $new=1;
			$object->addSoftkey('3',Aastra_get_label('Physical',$language),$XML_SERVER.'&bdate='.$bdate.'&I='.$I.'&E='.$E.'&P='.$new);
			}
		else
			{
			# Display results
			if(Aastra_is_formattedtextscreen_supported())
				{
				# create object
				$object = new AastraIPPhoneFormattedTextScreen();
				if(Aastra_size_formattedtextscreen()>3)
					{
					$object->addLine(Aastra_get_label('BIORHYTHMS',$language));
					if(Aastra_size_formattedtextscreen()>4) $object->addLine('');
					$object->addLine(sprintf(Aastra_get_label('I=%+.3f %s',$language),$array['day']['I'],$array['trend']['I']));
					$object->addLine(sprintf(Aastra_get_label('E=%+.3f %s',$language),$array['day']['E'],$array['trend']['E']));
					$object->addLine(sprintf(Aastra_get_label('P=%+.3f %s',$language),$array['day']['P'],$array['trend']['P']));
					}
				else
					{
					$object->setScrollStart('2');
					$object->addLine(sprintf(Aastra_get_label('I=%+.3f %s',$language),$array['day']['I'],$array['trend']['I']));
					$object->addLine(sprintf(Aastra_get_label('E=%+.3f %s',$language),$array['day']['E'],$array['trend']['E']));
					$object->addLine(sprintf(Aastra_get_label('P=%+.3f %s',$language),$array['day']['P'],$array['trend']['P']));
					$object->setScrollEnd();
					}
				}
			else
				{
				# create object
				$object = new AastraIPPhoneTextScreen();
				$object->setTitle(Aastra_get_language('BIORHYTHMS',$language));
				$object->setText(sprintf(Aastra_get_label('I=%+.3f, E=%+.3f, P=%+.3f',$language),$array['day']['I'],$array['day']['E'],$array['day']['P']));
				}
			}
		}

	# Common parameters
	$object->setDestroyOnExit();
	if($nb_softkeys)
		{
		if($nb_softkeys==6)
			{
			$object->addSoftkey('5',Aastra_get_label('Back',$language),$XML_SERVER);
			$object->addSoftkey('6',Aastra_get_label('Exit',$language),'SoftKey:Exit');
			}
		else
			{
			$object->addSoftkey('9',Aastra_get_label('Back',$language),$XML_SERVER);
			$object->addSoftkey('10',Aastra_get_label('Exit',$language),'SoftKey:Exit');
			}
		}
	}

# Top title
if(Aastra_is_top_title_supported()) 
	{
	$object->setTopTitle(Aastra_get_label('Biorhythms'),'','1');
	$object->addIcon('1','http://'.$AA_XML_SERVER.'/'.$AA_XMLDIRECTORY.'/icons/bio.png');
	}

# Display object
$object->output();
exit;
?>
