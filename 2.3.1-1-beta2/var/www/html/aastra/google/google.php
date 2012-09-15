<?php
#############################################################################
# Google requests
#
# Aastra SIP Phones 1.4.2 or better
#
# Copyright 2008 Aastra Telecom Ltd
#
# Supported Aastra Phones
#   All phones
#
# script.php?user=XXX
#   XXX is the user ID if not provided MAC address is used instead
#############################################################################

#############################################################################
# PHP customization for includes and warnings
#############################################################################
$os = strtolower(PHP_OS);
if(strpos($os, 'win') === false) ini_set('include_path',ini_get('include_path').':include:../include');
else ini_set('include_path',ini_get('include_path').';include;..\include');
error_reporting(E_ERROR | E_PARSE);

#############################################################################
# Includes
#############################################################################
require_once('AastraIPPhoneTextScreen.class.php');
require_once('AastraIPPhoneInputScreen.class.php');
require_once('AastraCommon.php');

#############################################################################
# Private functions
#############################################################################
# filter_format function
function filter_format($string,$resultSize)
{
# Process Before
$string=preg_replace('/<br>/',' ', $string);

if($resultSize>1)
	{
	for ($i=1;$i<=$resultSize;$i++)
		{
		$search='/\('.$i.'\/'.$resultSize."\)/";
		$string=preg_replace($search,' ',$string);
		}
	}

# Remove All HTML tags
$search = array ('@<script[^>]*?>.*?</script>@si', // Strip out javascript
                 '@<[\/\!]*?[^<>]*?>@si',          // Strip out HTML tags
                 '@([\r\n])[\s]+@',                // Strip out white space
                 '@&(quot|#34);@i',                // Replace HTML entities
                 '@&(amp|#38);@i',
                 '@&(lt|#60);@i',
                 '@&(gt|#62);@i',
                 '@&(nbsp|#160);@i',
                 '@&(iexcl|#161);@i',
                 '@&(cent|#162);@i',
                 '@&(pound|#163);@i',
                 '@&(copy|#169);@i',
                 '@&#(\d+);@e');                    // evaluate as php

$replace = array ('',
                 '',
                 '\1',
                 '"',
                 '&',
                 '<',
                 '>',
                 ' ',
                 chr(161),
                 chr(162),
                 chr(163),
                 chr(169),
                 'chr(\1)');
$string=preg_replace($search, $replace, $string);
$string=preg_replace($search, $replace, $string);

return($string);
}

#############################################################################
# Main Code
#############################################################################
# Retrieve parameters
$question=Aastra_getvar_safe('question');
$user=Aastra_getvar_safe('user');
if ($user=='')
	{
	$header=Aastra_decode_HTTP_header();
	$user=$header['mac'];
	}
$XML_SERVER.='?user='.$user;

# Trace call to function
Aastra_trace_call('google','user='.$user.', question='.$question);

# Test User Agent
Aastra_test_phone_version('1.4.2.',0);

# Get Phone language
$language=Aastra_get_language();

# Global compatibility
$nb_softkeys=Aastra_number_softkeys_supported();

# Initial call test on parameter passed
if(!$question)
	{
	# Input Question
	$object = new AastraIPPhoneInputScreen();
	$object->setDestroyOnExit();
	$object->setType('string');
	$object->setTitle(Aastra_get_label('Ask Google',$language));
	$object->setPrompt(Aastra_get_label('Enter your question',$language));
	$object->setParameter('question');
	$object->setURL($XML_SERVER);
	$data=Aastra_get_user_context($user,'google');
	if($data['last']!=NULL) $default=$data['last'];
	else $default='Define SIP';
	$object->setDefault($default);

	# Softkeys
	if($nb_softkeys>0)
		{
		if($nb_softkeys<7)
			{
			$object->addSoftkey('1', Aastra_get_label('Backspace',$language), 'SoftKey:BackSpace');
			$object->addSoftkey('2', Aastra_get_label('Help',$language), $XML_SERVER.'&question=help');
			$object->addSoftkey('3', Aastra_get_label('ABC',$language), 'SoftKey:ChangeMode');
			$object->addSoftkey('4', Aastra_get_label('NextSpace',$language), 'SoftKey:NextSpace');
			$object->addSoftkey('5', Aastra_get_label('Submit',$language), 'SoftKey:Submit');
			$object->addSoftkey('6', Aastra_get_label('Exit',$language), 'SoftKey:Exit');
			}
		else $object->addSoftkey('10', Aastra_get_label('Exit',$language), 'SoftKey:Exit');
		}
	}
else
	{
	# keep track
	$data['last']=$question;
	Aastra_save_user_context($user,'google',$data);

	# Prepare Web request
	$src='http://www.google.com/sms/demo?q='.urlencode($question);

	# Extract result from Web page
	$fp = @fopen ($src, 'r');
	if($fp)
		{
		$resultSize=0;
		while (!feof ($fp))
			{
      			$line = fgets($fp, 4096);
      			if (preg_match('/var resultSize/', $line)) 
				{
				$found=1;
      				$nb_answers = preg_split('/var /', $line);
				$nb_answers[1]='$'.$nb_answers[1];
				eval($nb_answers[1]);
				}
	      		if (preg_match('/var message/', $line)) 
				{
				$found=1;
      				$nb_answers = preg_split('/var /', $line);
				$nb_answers[1]='$'.$nb_answers[1];
				eval($nb_answers[1]);
				}
			}
		fclose($fp);

		# Prepare result screen
		$object = new AastraIPPhoneTextScreen();
		$object->setDestroyOnExit();
		$object->setTitle(Aastra_get_label('Ask Google',$language));
		if ($resultSize==0)$object->setText(Aastra_get_label('Information not available at this time. Please try again later.',$language));
		else 
			{
			$message=$message1.$message2.$message3.$message4.$message5.$message6.$message7.$message8;
			$message=filter_format($message,$resultSize);
			$object->setText($message);
			}
		}
	else
		{
		# Prepare result screen
		$object = new AastraIPPhoneTextScreen();
		$object->setDestroyOnExit();
		$object->setTitle(Aastra_get_label('Ask Google',$language));
		$object->setText(Aastra_get_label('Information not available at this time. Please try again later.',$language));
		}

	# Display TextScreen
	if($nb_softkeys>0)
		{
		if($nb_softkeys<7)
			{
			$object->addSoftkey('4', Aastra_get_label('Back',$language), $XML_SERVER);
			$object->addSoftkey('6', Aastra_get_label('Exit',$language), 'SoftKey:Exit');
			}
		else
			{
			$object->addSoftkey('9', Aastra_get_label('New Question',$language), $XML_SERVER);
			$object->addSoftkey('10', Aastra_get_label('Exit',$language), 'SoftKey:Exit');
			}
		}

	# CancelAction
	$object->setCancelAction($XML_SERVER);
	}

# Display object
$object->output();
exit;
?>
