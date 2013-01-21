#!/usr/bin/env php
<?php
###################################################################
# AGI for allowing a user to record/play various voicemail 
# greetings and messages
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

function play_message($sound_file)  
{
global $agi;

# No consistency between 1.4 and various 1.6...
$agi->exec('ControlPlayback',$sound_file.'|2000|6|4|0|5|1');
$agi->exec('ControlPlayback',$sound_file.',2000,6,4,0,5,1');
}

function check_for_voicemail_box($exten,$silent=false,$self=true,$context='default') 
{
global $agi;
$cmd = $agi->exec('MailboxExists',$exten.'@'.$context);
$vmexists = agi_get_variable('VMBOXEXISTSSTATUS');
if($vmexists == 'SUCCESS') return True;
else if(!$silent) 
	{
      	if($self == True) play_sound('custom/vm-you-no-box');
      	else play_sound('custom/vm-no-box');
    	}
return False;
}

function readINIfile($filename,$commentchar,$delim) 
{
# Get file content with a shared lock to avoid race conditions
$array1 = array();
$handle = @fopen($filename, 'r');
if ($handle)
	{
	if (flock($handle, LOCK_SH))
		{
		while (!feof($handle)) $array1[] = fgets($handle);
		flock($handle, LOCK_UN);
		}   
	fclose($handle);
	}
$section='';
$array2=NULL;
foreach($array1 as $filedata) 
	{
   	$dataline=trim($filedata);
   	$firstchar=substr($dataline, 0, 1);
   	if ($firstchar!=$commentchar && $dataline!='') 
		{
     		#It's an entry (not a comment and not a blank line)
     		if ($firstchar == '[' && substr($dataline, -1, 1) == ']') 
			{
       		#It's a section
			$section = substr($dataline, 1, -1);
     			}
		else
			{
		       #It's a key...
       		$delimiter = strpos($dataline, $delim);
       		if ($delimiter > 0) 
				{
         			#...with a value
         			$key = strtolower(trim(substr($dataline, 0, $delimiter)));
         			$value = trim(substr($dataline, $delimiter + 1));
         			if (substr($value, 1, 1) == '"' && substr($value, -1, 1) == '"') { $value = substr($value, 1, -1); }
         			$array2[$section][$key] = stripcslashes($value);
       			}
			else
				{
         			#...without a value
         			$array2[$section][strtolower(trim($dataline))]='';
       			}
     			}
   		}
	else
		{
     		#It's a comment or blank line.  Ignore.
   		}
  	}

# Return array with data
return $array2;
}

function read_session($appli,$filename)
{
$array=@parse_ini_file(AASTRA_PATH_CACHE.$filename.'.session',true);
if($array==NULL) $array=array();
else
	{
	if(($appli!=$array['appli']) || (time()>$array['exp'])) $array=array();
	}
return($array);
}

###################################################################
# Main code
###################################################################

# Retrieve cache location
$array_config_server=readINIfile('/etc/aastra-xml.conf','#','=');
if($array_config_server['General']['cache']!='') define('AASTRA_PATH_CACHE',$array_config_server['General']['cache'].'/');
else define('AASTRA_PATH_CACHE','/var/cache/aastra/');

# New AGI
$agi=new AGI();  

# Retrieve calling extension
$exten=$agi->request['agi_callerid'];

# Retrieve parameters
$array=read_session('vmail',$exten);
$action=$array['action'];
$type=$array['type'];
$user=$array['user'];
if($type=='message')
	{
	$action='play';
	$folder=$array['folder'];
	$msgid=$array['msgid'];
	}

# VM exists?
if(check_for_voicemail_box($user))  
	{
	# Retrieve Spooler directory
      	$spool_dir=agi_get_variable('ASTSPOOLDIR');

	# Depending on message type
	switch($type)
		{
		case 'temp':
			$play='vm-rec-temp';
	      		$file=$spool_dir.'/voicemail/default/'.$user.'/temp';
			break;
		case 'name':
			$play='vm-rec-name';
	      		$file=$spool_dir.'/voicemail/default/'.$user.'/greet';
			break;
		case 'busy':
			$play='vm-rec-busy';
	      		$file=$spool_dir.'/voicemail/default/'.$user.'/busy';
			break;
		case 'unavail':
			$play='vm-rec-unv';
	      		$file=$spool_dir.'/voicemail/default/'.$user.'/unavail';
			break;
		case 'message':
	      		$file=$spool_dir.'/voicemail/default/'.$user.'/'.$folder.'/msg'.$msgid;
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
			if($type!='message') play_sound($file);
			else play_message($file);
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
