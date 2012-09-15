#!/usr/bin/env php
<?php
###################################################################
# AGI for allowing a user to record/play various voicemail greetings
#
# Adapted from Copyright (C) 2008 Ethan Schroeder
# ethan.schroeder@schmoozecom.com
# Copyright (C) 2009 Aastra Technologies
#
# All rights reserved.  THIS COPYRIGHT NOTICE MUST NOT BE REMOVED
# UNDER ANY CIRCUMSTANCES.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE
#
###################################################################

###################################################################
# Includes
###################################################################
require_once('/var/lib/asterisk/agi-bin/phpagi.php');

###################################################################
# Private functions
###################################################################
function agi_get_variable($variable)  
{
global $agi;
$tmp = $agi->get_variable($variable);
return $tmp['data'];
}

function play_sound($sound_file)  
{
global $agi;
$agi->exec('Playback',$sound_file);
}

function check_for_voicemail_box($exten,$silent=false,$self=true,$context='default') 
{
global $agi;
$cmd = $agi->exec("MailboxExists",$exten."@".$context);
$vmexists = agi_get_variable("VMBOXEXISTSSTATUS");
if($vmexists == "SUCCESS") return True;
else if(!$silent) 
	{
      	if($self == True) play_sound("custom/vm-you-no-box");
      	else play_sound("custom/vm-no-box");
    	}
return False;
}

function get_action() 
{
global $argv;
return strtolower(trim($argv['1']));
}

function get_type() 
{
global $argv;
return strtolower(trim($argv['2']));
}

###################################################################
# Main code
###################################################################
$agi=new AGI();  
$exten=agi_get_variable("USER");
$action=get_action();
$type=get_type();

# VM exists?
if(check_for_voicemail_box($exten))  
	{
	# Retrieve Spooler directory
      	$spool_dir=agi_get_variable('ASTSPOOLDIR');

	# Depending on message type
	switch($type)
		{
		case 'temp':
			$play='vm-rec-temp';
	      		$file=$spool_dir.'/voicemail/default/'.$exten.'/temp';
			break;
		case 'name':
			$play='vm-rec-name';
	      		$file=$spool_dir.'/voicemail/default/'.$exten.'/greet';
			break;
		case 'busy':
			$play='vm-rec-busy';
	      		$file=$spool_dir.'/voicemail/default/'.$exten.'/busy';
			break;
		case 'unavail':
			$play='vm-rec-unv';
	      		$file=$spool_dir.'/voicemail/default/'.$exten.'/unavail';
			break;
		default:
			play_sound('sorry-cant-let-you-do-that');	
			exit;
			break;
		}

	# Depending on action
	switch($action)
		{
		case 'play':
			# Play message
			play_sound($file);
			break;

		case 'record':
			# Play intro
			play_sound($play);

			# Record message
			$agi->record_file($file, 'wav', '#', -1, '', TRUE, 2);

			# Convert file
		      	$create_wav49 = `/usr/bin/sox $file.wav -c 1 -r 8000 -g -t wav $file.WAV`;

			# Message saved
		      	play_sound('auth-thankyou&vm-msgsaved');
			break;

		default:
			play_sound('sorry-cant-let-you-do-that');	
			exit;
			break;
		}
    	}
else 
	{
	# Play error message
	play_sound('sorry-cant-let-you-do-that');
	}
?>
