<?php
###################################################################################################
# Aastra XML API - AastraAsterisk.php
# Copyright Aastra Telecom 2006-2010
#
# This file includes libraries for the Asterisk Integration
#
# Public functions
# 	Aastra_get_callerid_Asterisk(user)
# 		This function retrieves the user callerID of a user in the Asterisk registry
#		(FreePBX 2.3)
# 	Aastra_get_username_Asterisk(user)
#		This function retrieves the username of a user in the Asterisk registry (FreePBX 2.3)
# 	Aastra_get_secret_Asterisk(user)
# 		This function retrieves the SIP password (secret) of a user in the Asterisk
#		configuration
# 	Aastra_verify_user_Asterisk(extension,password,mode)
#		This function checks the user credentials for vm or sip.
# 	Aastra_get_mailboxes_Asterisk(user)
#		This function retrieves the mailboxes from the user's voice mail
# 	Aastra_get_messages_Asterisk(user,folder)
# 		This function retrieves the Voice mail messages from a given folder
# 	Aastra_delete_message_Asterisk(user,folder,msg_id)
#		This function deletes a VM message,
# 	Aastra_move_to_folder_Asterisk(user,msg_id,msg_folder,target)
# 		This function moves a message to another folder
#      Aastra_forward_to_user_Asterisk(user1,user2,msg,srcFolder,dstFolder)
# 		This function copies a voice message to another user (forward).
# 	Aastra_is_user_registered_Asterisk(user)
# 		This function checks if a user is registered with the proxy server.
# 	Aastra_send_SIP_notify_Asterisk(event,user)
# 		This function sends a SIP Notify to a list of users.
# 	Aastra_parse_conf_Asterisk(filename)
#		This function parses an Asterisk configuration file
# 	Aastra_is_user_provisionned_Asterisk(user)
# 		This function checks if a user is provisionned in Asterisk configuration.
# 	Aastra_manage_cf_Asterisk(user,action,value)
# 		This function reads and write the CF value in Asterisk Database.
# 	Aastra_manage_dnd_Asterisk(user,action)
#		This function reads and write the DND value in Asterisk Database.
# 	Aastra_manage_daynight_Asterisk(action,index)
# 		This function reads and write the Daynight value(s) in Asterisk Database.
# 	Aastra_get_parked_calls_Asterisk()
#		This function returns the list of parked calls on the platform.
#	Aastra_check_park_Asterisk(user,number)
# 		This function returns the orbit where a call is parked after the user hangs up.
# 	Aastra_originate_Asterisk(channel,context,exten,priority,timeout,callerID,variable,account,application,data)
#		This function originates a call from the platform
# 	Aastra_get_meetme_list_Asterisk(confno)
#		This function lists the members of a meetme conference
# 	Aastra_meetme_action_Asterisk(confno,action,user-id)
#		This function sends a meetme command such as kick, mute or unmute,
# 	Aastra_get_meetme_rooms_Asterisk()
#		This function returns the list of configured meetme rooms on the platform and the
#		current status.
# 	Aastra_get_park_config_Asterisk()
#		This function returns the current parking configuration
# 	Aastra_get_intercom_config_Asterisk()
# 		This function returns the current Intercom code configuration
#	Aastra_get_version_Asterisk()
# 		This function returns the current Asterisk version.
# 	Aastra_get_registry_Asterisk()
#		This function returns the list of registered SIP phones
#      Aastra_get_userdevice_Asterisk(device)
#		This function returns the user attached to a device
#      Aastra_get_device_info_Asterisk(device)
#		This function returns all the configuration data regarding a device. This is used in
# 		the user and device freePBX mode.
#      Aastra_get_user_info_Asterisk(user)
#		This function returns all the configuration data regarding a user. This is used in
#		the user and device freePBX mode.
# 	Aastra_check_user_login_Asterisk(user,password)
#		This function tests the user credentials in the device and user mode.
# 	Aastra_get_user_directory_Asterisk()
# 		This function returns the list of configured users.
#      Aastra_is_daynight_appli_allowed_Asterisk(user)
# 		This function checks if a user is allowed to have the Day/Night application.
#      Aastra_is_daynight_notify_allowed_Asterisk(user)
#		This function checks if a user is allowed to have the Day/Night notification.
#      Aastra_check_signature_Asterisk(user)
#       	This function checks if the request is coming from the same phone. If not a message
#  		is displayed to the user.
# 	Aastra_manage_presence_Asterisk(user,action,type,value)
# 		This function manage the user presence records in the Asterisk database
# 	Aastra_format_presence_dt_Asterisk(timestamp)
#		This function properly formats the presence return date and time.
# 	Aastra_manage_followme_Asterisk(user,action)
# 		This function manages the follow-me status in the Asterisk database.
# 	Aastra_delete_temp_message_Asterisk(user)
# 		This function deletes the temporary message of any given user.
# 	Aastra_delete_name_message_Asterisk(user)
# 		This function deletes the name message of any given user.
# 	Aastra_delete_busy_message_Asterisk(user)
# 		This function deletes the busy message of any given user.
# 	Aastra_delete_unavail_message_Asterisk(user)
# 		This function deletes the unavailable message of any given user.
# 	Aastra_get_greeting_name_Asterisk(user)
# 		This function retrieve the location of the greeting name wav file.
# 	Aastra_dial_number_Asterisk(user,dial)
# 		This function launches a call from any user.
# 	Aastra_get_bulk_callerid_Asterisk(array)
# 		This function gets the callerID for a directory array
# 	Aastra_get_bulk_registry_Asterisk(array)
# 		This function gets the registry status for a directory array
# 	Aastra_queue_pause_Asterisk(agent,queue,pause)
# 		This function sets the pause status for an agent in a queue.
# 	Aastra_queue_add_Asterisk(agent,queue)
# 		This function adds a dynamic agent in a queue.
# 	Aastra_queue_remove_Asterisk(agent,queue)
# 		This function removes a dynamic agent from a queue.
# 	Aastra_send_message_Asterisk(user,long_message,short_message,uri)
#		This function immediately sends a message to a user.
# 	Aastra_add_parking_Asterisk(user)
# 		This function adds a user to the list of user being notified with a parking change.
# 	Aastra_remove_parking_Asterisk(user)
# 		This function removes a user from the list of user being notified with a parking change.
# 	Aastra_is_parking_notify_allowed_Asterisk(user)
# 		This function checks if a user is allowed to have the Parking notification.
# 	Aastra_add_vmail_Asterisk(vmbox,user,count)
# 		This function adds the monitoring of a VM box by a user.
# 	Aastra_remove_vmail_Asterisk(vmbox,user,count)
# 		This function removes the monitoring of a VM box by a user.
# 	Aastra_vm_status_Asterisk()
# 		This function returns the status of all the VM boxes on the platform.
#	Aastra_ask_language_Asterisk()
# 		This function returns information on the languages configured for the phone and if it
# 		is needed to ask the user to select a language.
# 	Aastra_get_startup_profile_Asterisk(user)
# 		This function returns the profile to use for self-configuration for any given
#		user/device based on the configuration in asterisk.conf. If user/device is not
#		configured, default profile is returned.
#
# Private functions
# 	Aastra_get_bulk_callerid_Asterisk($array)
# 		This function gets the callerID for a directory array
###################################################################################################

###################################################################################################
# INCLUDES
###################################################################################################
require_once('AastraAsterisk.class.php');
require_once('AastraCommon.php');
require_once('CustomDnd.php');
require_once('DB.php');

###################################################################################################
# GLOBAL VARIABLES
###################################################################################################
$array_config_asterisk=Aastra_readINIfile(AASTRA_CONFIG_DIRECTORY.'asterisk.conf','#','=');
$array_config_freepbx=Aastra_readINIfile('/etc/amportal.conf','#','=');
$array_config_daynight=Aastra_readINIfile(AASTRA_CONFIG_DIRECTORY.'daynight.conf','#','=');
$array_aastra=Aastra_readCFGfile(AASTRA_TFTP_DIRECTORY.'/aastra.cfg', '#', ':');

# Asterisk location
if($array_config_freepbx['']['astetcdir']!='') $ASTERISK_LOCATION=$array_config_freepbx['']['astetcdir'].'/';
else $ASTERISK_LOCATION='/etc/asterisk/';

# Asterisk SPOOL DIRECTORY
if($array_config_freepbx['']['astspooldir']!='') $ASTERISK_SPOOLDIR=$array_config_freepbx['']['astspooldir'].'/';
else $ASTERISK_SPOOLDIR='/var/spool/asterisk/';

# Asterisk Version
$AA_ASTERISK_VERSION=Aastra_get_version_Asterisk();

# FreePBX variables
$AA_FREEPBX_MODE='1';
if($array_config_freepbx['']['ampextensions']=='deviceanduser') $AA_FREEPBX_MODE='2';
if(strcasecmp($array_config_freepbx['']['usedevstate'],'true')==0) $AA_FREEPBX_USEDEVSTATE=True;

# Voice Mail variables
$AA_VM_CONTEXT='default';
$AA_VM_SPOOL=$ASTERISK_SPOOLDIR.'voicemail';
$AA_VM_BOXBASE=$AA_VM_SPOOL.'/'.$AA_VM_CONTEXT;
$AA_VM_DIRMODE=0700;
$AA_VM_MAXMSG='99';

# Asterisk Proxy Server
if($array_config_asterisk['General']['proxy']!='') $AA_PROXY_SERVER = $array_config_asterisk['General']['proxy'];
else
	{
	if(isset($_SERVER['SERVER_ADDR'])) $AA_PROXY_SERVER=$_SERVER['SERVER_ADDR'];
	else $AA_PROXY_SERVER='AA_PROXY_SERVER';
	}

# Asterisk Registrar Server
if($array_config_asterisk['General']['registrar']!='') $AA_REGISTRAR_SERVER = $array_config_asterisk['General']['registrar'];
else
	{
	if(isset($_SERVER['SERVER_ADDR'])) $AA_REGISTRAR_SERVER=$_SERVER['SERVER_ADDR'];
	else $AA_REGISTRAR_SERVER='AA_REGISTRAR_SERVER';
	}

# Phone signature
$AA_PHONE_SIGNATURE=True;
if($array_config_asterisk['General']['signature']=='0') $AA_PHONE_SIGNATURE = False;

# Asterisk email for messages
$AA_EMAIL=$array_config_asterisk['Startup']['email'];

# Asterisk sender for messages
if($array_config_asterisk['Startup']['sender']!='') $AA_SENDER=$array_config_asterisk['Startup']['sender'];
else $AA_SENDER='no-reply@'.$AA_PROXY_SERVER;

# Authentication
$AA_PW_ADMIN=$array_config_asterisk['Startup']['pw_admin'];
$AA_PW_USER=$array_config_asterisk['Startup']['pw_user'];
if($array_config_asterisk['Startup']['logout_pw_enabled']=='0') $AA_LOGOUT_PW=False;
else $AA_LOGOUT_PW=True;

# Autologout
if($array_config_asterisk['Startup']['autologout_enabled']=='0') $AA_AUTOLOGOUT=False;
else $AA_AUTOLOGOUT=True;
if($array_config_asterisk['Startup']['autologout_message']=='0') $AA_AUTOLOGOUT_MSG=False;
else $AA_AUTOLOGOUT_MSG=True;

# DND Pause ACD queues
$AA_DNDPAUSE=True;
if($array_config_asterisk['DND']['dnd_pause_queues']=='0') $AA_DNDPAUSE=False;

# Speed dial
$AA_SPEEDDIAL_STATE=True;
if($array_config_asterisk['SpeedDial']['enabled']=='0') $AA_SPEEDDIAL_STATE=False;

# Presence
$AA_PRESENCE_STATE=True;
if($array_config_asterisk['Presence']['enabled']=='0') $AA_PRESENCE_STATE=False;

# MeetMe
$AA_MEETME_STATE=True;
if($array_config_asterisk['MeetMe']['enabled']=='0') $AA_MEETME_STATE=False;

# iSymphony integration
if(file_exists(AASTRA_CONFIG_DIRECTORY.'isymphony.conf')) $AA_ISYMPHONY=True;
else $AA_ISYMPHONY=False;

# Outgoing lookup
$AA_OUTGOING_STATE=True;
if($array_config_asterisk['Outgoing']['enabled']=='0') $AA_OUTGOING_STATE=False;
$AA_OUTGOING_LOOKUP=$array_config_asterisk['Outgoing']['external'];

# Date and time format
$AA_FORMAT_DT='US';
if($array_aastra['']['time format']=='1') $AA_FORMAT_DT='INT';

# Ask TZ
$AA_ASK_TZ=False;
if($array_aastra['']['ask_tz']=='1') $AA_ASK_TZ=True;

# Presence Statuses
define('AA_PRESENCE_AVAILABLE','0');
define('AA_PRESENCE_AWAY','1');
define('AA_PRESENCE_ATLUNCH','2');
define('AA_PRESENCE_INMEETING','3');
define('AA_PRESENCE_OUTOFOFFICE','4');
define('AA_PRESENCE_ATHOME','5');
define('AA_PRESENCE_DISCONNECTED','99');

# Presence default action
define('AA_PRESENCE_ACT_NOTHING','0');
define('AA_PRESENCE_ACT_DND','1');
define('AA_PRESENCE_ACT_FM','2');
define('AA_PRESENCE_ACT_CFWD','3');

# Parking notification exclusions (ALL means everybody)
$AA_PARKING_EXCLUDE=explode(',',$array_config_asterisk['Parking']['exclude']);

# Cleanup
unset($array_config_freepbx);
unset($array_config_asterisk);
unset($array_aastra);

###################################################################################################
# Aastra_get_callerid_Asterisk(user)
#
# This function retrieves the user callerID of a user in the Asterisk registry (FreePBX 2.3)
#
# Parameters
#   @user		user ID
#
# Returns
#   CallerID as a string
###################################################################################################
function Aastra_get_callerid_Asterisk($user)
{
# Connect to AGI
$as=new AGI_AsteriskManager();
$res=$as->connect();

# Get value in the database
$callerid=$as->database_get('AMPUSER',$user.'/cidname');

# Disconnect properly
$as->disconnect();

# Return Caller ID
return($callerid);
}

###################################################################################################
# Aastra_get_username_Asterisk(user)
#
# This function retrieves the username of a user in the Asterisk registry (FreePBX 2.3)
#
# Parameters
#   @user		user ID
#
# Returns
#   username as a string
###################################################################################################
function Aastra_get_username_Asterisk($user)
{
Global $ASTERISK_LOCATION;

# No username
$username='';

# Get all the user data
$sip_array=Aastra_readINIfile($ASTERISK_LOCATION.'sip_additional.conf',';','=');

# Collect data
if($sip_array[$user]['username']!='') $username=$sip_array[$user]['username'];
else
	{
	# Connect to AGI
	$as=new AGI_AsteriskManager();
	$res=$as->connect();

	# Get value in the database
	$res=$as->database_get('DEVICE',$user.'/user');
	if($res) $username=$res;

	# Disconnect properly
	$as->disconnect();
	}

# Return answer
return($username);
}

###################################################################################################
# Aastra_get_secret_Asterisk(user)
#
# This function retrieves the SIP password (secret) of a user in the Asterisk configuration
#
# Parameters
#   @user		user ID
#
# Returns
#   secret as a string
###################################################################################################
function Aastra_get_secret_Asterisk($user)
{
Global $ASTERISK_LOCATION;

# Get all the user data
$array=Aastra_readINIfile($ASTERISK_LOCATION.'sip_additional.conf',';','=');

if(!isset($array[$user]['secret'])) {
	$array=Aastra_readINIfile($ASTERISK_LOCATION.'pjsip.auth.conf',';','=');
	$array[$user]['secret'] = $array[$user.'-auth']['password'];
}

# Return answer
return($array[$user]['secret']);
}

###################################################################################################
# Aastra_verify_user_Asterisk(extension,password,mode)
#
# This function checks the user credentials (vm or sip).
#
# Parameters
#   @extension		user extension
#   @password			password (vm or secret)
#   @mode			vm, sip or login
#
# Returns
#   Boolean
###################################################################################################
function Aastra_verify_user_Asterisk($extension,$password,$mode='vm')
{
Global $ASTERISK_LOCATION;
Global $AA_VM_CONTEXT;
Global $AA_PW_ADMIN;
Global $AA_PW_USER;

# False by default
$return=False;

# Depending on mode
switch($mode)
	{
	# Voice mail/Login
	case 'vm':
	case 'login':
		# No VM by default
		$vm=False;

		# Login mode
		if($mode=='login')
			{
			# Admin password?
			if(($AA_PW_ADMIN!='') and ($password==$AA_PW_ADMIN))
				{
				# Get all the user data
				$sip_array=Aastra_readINIfile($ASTERISK_LOCATION.'sip_additional.conf',';','=');

				# Check user and password
				if($sip_array[$extension]!=NULL) $return=True;
				}
			}

		# Check user and voice mail password
		if(!$return)
			{
			$lines=@file($ASTERISK_LOCATION.'voicemail.conf');
			$section=NULL;
			foreach($lines as $line)
				{
				$line=rtrim($line);
				if(preg_match("/^\s*\[([a-z]*)\]\s*$/i", $line, $m)) $section=$m[1];
				if(($section==$AA_VM_CONTEXT) && preg_match("/^([0-9]*)\s*=>?\s*([0-9]*)\s*,(.*)$/",$line,$m))
					{
			        	if ($m[1]==$extension)
						{
						$vm=True;
						if($m[2]==$password) $return=True;
						break;
						}
					}
				}
			}

		# Login mode
		if((!$return) and ($mode=='login') and (!$vm))
			{
			# User password?
			if(($AA_PW_USER!='') and ($password==$AA_PW_USER))
				{
				# Get all the user data
				$sip_array=Aastra_readINIfile($ASTERISK_LOCATION.'sip_additional.conf',';','=');

				# Check user and password
				if($sip_array[$extension]!=NULL) $return=True;
				}
			}
		break;

	# SIP secret
	case 'sip':
		# Get all the user data
		$sip_array=Aastra_readINIfile($ASTERISK_LOCATION.'sip_additional.conf',';','=');

		# Check user and SIP password
		if($sip_array[$extension]!=NULL)
			{
			if($sip_array[$extension]['secret']==$password) $return=True;
			}
		break;

	# Default
	default:
		Aastra_debug('Unexpected mode='.$mode);
		break;
	}

# Return checked value
return($return);
}

###################################################################################################
# Aastra_get_vmessages_Asterisk(user)
#
# This function retrieves the Voice mail messages from INBOX and Old folders. Folder are created
# if needed.
#
# Parameters
#   @user			user extension
#
# Returns
#   Array
###################################################################################################
function Aastra_get_vmessages_Asterisk($user)
{
Global $AA_VM_BOXBASE;
Global $AA_VM_DIRMODE;

# No message by default
$messages=array();

# VM root directory
$dir=$AA_VM_BOXBASE.'/'.$user;

# List of folders
$folders=array('INBOX','Old');

# Retrieve messages
foreach($folders as $folder)
	{
	# Create directories if needed
	if(!is_dir($AA_VM_BOXBASE))
		{
		umask(0000);
		mkdir($AA_VM_BOXBASE,$AA_VM_DIRMODE);
		}
	if(!is_dir($AA_VM_BOXBASE.'/'.$user))
		{
		umask(0000);
		mkdir($AA_VM_BOXBASE.'/'.$user,$AA_VM_DIRMODE);
		}
	if(!is_dir($AA_VM_BOXBASE.'/'.$user.'/'.$folder))
		{
		umask(0000);
		mkdir($AA_VM_BOXBASE.'/'.$user.'/'.$folder,$AA_VM_DIRMODE);
		}

	# Read directory
	if($handle=@opendir($dir.'/'.$folder))
		{
	    	while (false!==($file=@readdir($handle)))
			{
			if (preg_match("/^(msg(.*))\.txt$/",$file, $m))
				{
				$info=array();
				$info['txtfile']=$dir.'/'.$folder.'/'.$file;
				$info['playname']=$dir.'/'.$folder.'/'.$m[1];
				$info['id']=$m[2];
				$info['folder']=$folder;
				if ($lines=@file($info['txtfile']))
					{
	  				foreach($lines as $line)
						{
		    				if(preg_match("/^([a-z]*)=(.*)$/",rtrim($line),$m) || preg_match("/^([a-z]*) = (.*)$/",rtrim($line),$m)) $info[$m[1]]=$m[2];
	  					}
	  				$messages[]=$info;
					}
      				}
    			}
  		}
	}

# Sort messages by time starting with the latest one
Aastra_natsort2d($messages,'origtime');
$messages=array_reverse($messages);
foreach($messages as $key=>$value) $messages[$key]['number']=$key;

# Return message list
return($messages);
}

###################################################################################################
# Aastra_delete_message_Asterisk(user,folder,msg_id)
#
# This function deletes a VM message,
#
# Parameters
#   @user			user extension
#   @folder			VM folder
#   @msg_id			Message ID
#
# Returns
#   Boolean
###################################################################################################
function Aastra_delete_message_Asterisk($user,$folder,$msg_id)
{
Global $AA_VM_BOXBASE;

$dir=$AA_VM_BOXBASE.'/'.$user.'/'.$folder;
$deleted=False;
if($handle=@opendir($dir))
	{
    	while(false!==($file=@readdir($handle)))
		{
      		if (preg_match("/^msg".$msg_id.'/',$file,$m))
			{
			if(@unlink($dir.'/'.$file)) $deleted = True;
      			}
    		}
  	}
return($deleted);
}

###################################################################################################
# Aastra_move_to_folder_Asterisk(user,msg_id,msg_folder,target)
#
# This function moves a message to another folder
#
# Parameters
#   @user			user extension
#   @msg_id			VM Message ID
#   @msg_folder		Initial folder
#   @target			Target folder
#
# Returns
#   New message ID or ''
###################################################################################################
function Aastra_move_to_folder_Asterisk($user,$message,$srcFolder,$dstFolder)
{
Global $AA_VM_BOXBASE;
Global $AA_VM_MAXMSG;

# Return is false by default
$return='';

# Source and target directory
$srcPath=$AA_VM_BOXBASE.'/'.$user.'/'.$srcFolder;
$dstPath=$AA_VM_BOXBASE.'/'.$user.'/'.$dstFolder;

# Find new message ID
for($i=0;$i<=$AA_VM_MAXMSG;$i++)
	{
    	$dstMessage=sprintf('msg%04d',$i);
    	if(!@file_exists($dstPath.'/'.$dstMessage.'.txt') )
		{
		$return=sprintf('%04d-%s',$i,$dstFolder);
		break;
		}
	if($i >= $AA_VM_MAXMSG) break;
  	}

# Move if found
if($return!='')
	{
	foreach(array('wav','WAV','gsm','txt') as $extension)
		{
    		$src=$srcPath.'/msg'.$message.'.'.$extension;
	    	$dst=$dstPath.'/'.$dstMessage.'.'.$extension;
    		if(@file_exists($src))
			{
			if(!@copy($src, $dst)) return(False);
      			else @unlink($src);
  			}
		}
	}

# Return new msg-ID
return($return);
}

###################################################################################################
# Aastra_forward_to_user_Asterisk(user1,user2,msg,srcFolder,dstFolder)
#
# This function copies a voice message to another user (forward).
#
# Parameters
#   @user1			source user extension
#   @user2			target user extension
#   @msg			VM Message ID
#   @srcfolder		Initial folder
#   @dstFolder		Target folder
#
# Returns
#   Boolean
###################################################################################################
function Aastra_forward_to_user_Asterisk($user1,$user2,$msg,$srcFolder,$dstFolder)
{
Global $AA_VM_BOXBASE;
Global $AA_VM_MAXMSG;
Global $AA_VM_DIRMODE;

# Generate complete paths
$srcPath=$AA_VM_BOXBASE.'/'.$user1.'/'.$srcFolder;
$dstPath=$AA_VM_BOXBASE.'/'.$user2.'/'.$dstFolder;

# Create destination folder if needed
if(!@file_exists($dstPath))
	{
	umask(0000);
    	if(!@mkdir($dstPath,$AA_VM_DIRMODE)) return false;
  	}

# Find next message number
for($i=0;$i<=$AA_VM_MAXMSG;$i++)
	{
    	$dstMessage=sprintf('msg%04d',$i);
    	if(!@file_exists($dstPath.'/'.$dstMessage.'.txt') ) break;
	if($i >= $AA_VM_MAXMSG) return(False);
  	}

# Move files
foreach(array('wav','WAV','gsm','txt') as $extension)
	{
    	$src=$srcPath.'/msg'.$msg.'.'.$extension;
    	$dst=$dstPath.'/'.$dstMessage.'.'.$extension;
    	if(@file_exists($src))
		{
		if(!@copy($src, $dst)) return(False);
  		}
	}

# Everything is OK
return(True);
}

##################################################################################################
# Aastra_is_user_registered_Asterisk(user)
#
# This function checks if a user is registered.
#
# Parameters
#   @user		user extension
#
# Returns
#   Boolean
###################################################################################################
function Aastra_is_user_registered_Asterisk($user)
{
# Init return
$return=False;

# Connect to AGI
$as = new AGI_AsteriskManager();
$res = $as->connect();

# SIP show peer
$res = $as->Command('sip show peer '.$user.' load');
$line=split("\n", $res['data']);
foreach($line as $parameter)
	{
	if(strstr($parameter,'Addr->IP'))
		{
		$ip=split(' ', $parameter);
		if(!strstr($ip[8],'Unspecified'))
			{
			if($ip[8]!='') $return=True;
			}
		}
	}

# Disconnect properly
$as->disconnect();

# Return answer
return($return);
}

###################################################################################################
# Aastra_send_SIP_notify_Asterisk(event,user)
#
# This function sends a SIP Notify to a list of users.
#
# Parameters
#   @event			event to be sent, must be configured in sip_notify.conf
#   @user			target users as an array
#
# Returns
#   None
###################################################################################################
function Aastra_send_SIP_notify_Asterisk($event,$array_user)
{
# Max number of extensions per command
$EXT_NUM_LIMIT=30;

# Connect to AGI
$as=new AGI_AsteriskManager();
$res=$as->connect();

# Process each chunk
while(count($array_user) > 0)
	{
	# Create the chunks
	$array_user_slice=array_slice($array_user,0,$EXT_NUM_LIMIT);

  	# Prepare command
  	$command='sip notify '.$event;
  	foreach($array_user_slice as $value) $command.=' '.$value;

  	# Send command
  	$res = $as->Command($command);

	# Next chunk
  	$array_user=array_slice($array_user, $EXT_NUM_LIMIT);

	# Timer to limit CPU impact
	usleep(500000);
	}

# Disconnect properly
$as->disconnect();
}

###################################################################################################
# Aastra_parse_conf_Asterisk(filename)
#
# This function parses an Asterisk configuration file
#
# Parameters
#   @filename			Asterisk configuration file name.
#
# Returns
#   Array
###################################################################################################
function Aastra_parse_conf_Asterisk($filename)
{
$file = file($filename);
$index=0;
foreach ($file as $line)
	{
	if (preg_match("/^\s*([a-zA-Z0-9]+)\s* => \s*([^|#;]*)\s*([;#].*)?/",$line,$matches))
		{
		$value= split(",", $matches[2]);
		$conf[$value[0]]=0;
		$index++;
		}
	}
return $conf;
}

###################################################################################################
# Aastra_is_user_provisionned_Asterisk(user)
#
# This function checks if a user is provisionned in Asterisk configuration.
#
# Parameters
#   @user		user extension
#
# Returns
#   Boolean
###################################################################################################
function Aastra_is_user_provisionned_Asterisk($user)
{
Global $ASTERISK_LOCATION;
$return=True;

# Get all the user data
$sip_array=Aastra_readINIfile($ASTERISK_LOCATION.'sip_additional.conf',';','=');

# User provisionned?
if($sip_array[$user]==NULL) $return=False;

# Return answer
return($return);
}

###################################################################################################
# Aastra_manage_cf_Asterisk(user,action,value)
#
# This function reads and write the CF value in Asterisk Database.
#
# Parameters
#   @user		user extension
#   @action		action to be performed, cancel, set or anything
#   @value		value
#
# Returns
#   Current CF value
###################################################################################################
function Aastra_manage_cf_Asterisk($user,$action,$value)
{
Global $AA_FREEPBX_MODE;
Global $AA_FREEPBX_USEDEVSTATE;

# Translate user if needed
if($AA_FREEPBX_MODE=='2')
	{
	$user=Aastra_get_userdevice_Asterisk($user);
	$array_user=Aastra_get_user_info_Asterisk($user);
	$devices=array_flip(explode('&',trim($array_user['device'])));
	unset($devices['']);
	$devices=array_flip($devices);
	}
else $devices=array($user);

# Connect to AGI
$cf='';
$as=new AGI_AsteriskManager();
$res=$as->connect();

# Depending on action
switch($action)
	{
	case 'cancel':
		# DELETE CFWD
		$res=$as->database_del('CF',$user);
		if($AA_FREEPBX_USEDEVSTATE)
			{
			foreach($devices as $device) Aastra_set_devstate_asterisk('Custom:DEVCF'.$device,'NOT_INUSE',$as);
			Aastra_set_devstate_asterisk('Custom:CF'.$user,'NOT_INUSE',$as);
			}
		break;
	case 'set':
		# SET CFWD
		$res=$as->database_put('CF',$user,$value);
		$cf=$value;
		if($AA_FREEPBX_USEDEVSTATE)
			{
			foreach($devices as $device) Aastra_set_devstate_asterisk('Custom:DEVCF'.$device,'BUSY',$as);
			Aastra_set_devstate_asterisk('Custom:CF'.$user,'BUSY',$as);
			}
		break;
	default:
		# GET CFWD
		$res=$as->database_get('CF',$user);
		if($res) $cf=$res;
		break;
	}

# Disconnect properly
$as->disconnect();

# Return CF
return($cf);
}

###################################################################################################
# Aastra_manage_cfb_Asterisk(user,action,value)
#
# This function reads and write the CFB value in Asterisk Database.
#
# Parameters
#   @user		user extension
#   @action		action to be performed, cancel, set or anything
#   @value		value
#
# Returns
#   Current CF value
###################################################################################################
function Aastra_manage_cfb_Asterisk($user,$action,$value)
{
Global $AA_FREEPBX_MODE;
Global $AA_FREEPBX_USEDEVSTATE;

# Translate user if needed
if($AA_FREEPBX_MODE=='2')
	{
	$user=Aastra_get_userdevice_Asterisk($user);
	$array_user=Aastra_get_user_info_Asterisk($user);
	$devices=array_flip(explode('&',trim($array_user['device'])));
	unset($devices['']);
	$devices=array_flip($devices);
	}
else $devices=array($user);

# Connect to AGI
$cf='';
$as=new AGI_AsteriskManager();
$res=$as->connect();

# Depending on action
switch($action)
	{
	case 'cancel':
		# DELETE CFWD BUSY
		$res=$as->database_del('CFB',$user);
		break;
	case 'set':
		# SET CFWD BUSY
		$res=$as->database_put('CFB',$user,$value);
		$cf=$value;
		break;
	default:
		# GET CFWD
		$res=$as->database_get('CFB',$user);
		if($res) $cf=$res;
		break;
	}

# Disconnect properly
$as->disconnect();

# Return CF
return($cf);
}

###################################################################################################
# Aastra_manage_cfna_Asterisk(user,action,value)
#
# This function reads and write the CFU value in Asterisk Database.
#
# Parameters
#   @user		user extension
#   @action		action to be performed, cancel, set or anything
#   @value		value
#
# Returns
#   Current CF value
###################################################################################################
function Aastra_manage_cfna_Asterisk($user,$action,$value)
{
Global $AA_FREEPBX_MODE;
Global $AA_FREEPBX_USEDEVSTATE;

# Translate user if needed
if($AA_FREEPBX_MODE=='2')
	{
	$user=Aastra_get_userdevice_Asterisk($user);
	$array_user=Aastra_get_user_info_Asterisk($user);
	$devices=array_flip(explode('&',trim($array_user['device'])));
	unset($devices['']);
	$devices=array_flip($devices);
	}
else $devices=array($user);

# Connect to AGI
$cf='';
$as=new AGI_AsteriskManager();
$res=$as->connect();

# Depending on action
switch($action)
	{
	case 'cancel':
		# DELETE CFWD NA
		$res=$as->database_del('CFU',$user);
		break;
	case 'set':
		# SET CFWD NA
		$res=$as->database_put('CFU',$user,$value);
		$cf=$value;
		break;
	default:
		# GET CFWD
		$res=$as->database_get('CFU',$user);
		if($res) $cf=$res;
		break;
	}

# Disconnect properly
$as->disconnect();

# Return CF
return($cf);
}

###################################################################################################
# Aastra_manage_dnd_Asterisk(user,action)
#
# This function reads and write the DND value in Asterisk Database.
#
# Parameters
#   @user		user extension
#   @action		action to be performed, change or anything
#
# Returns
#   Current DND value
###################################################################################################
function Aastra_manage_dnd_Asterisk($user,$action)
{
Global $AA_FREEPBX_MODE;
Global $AA_FREEPBX_USEDEVSTATE;

# Translate user if needed
if($AA_FREEPBX_MODE=='2')
	{
	$user=Aastra_get_userdevice_Asterisk($user);
	$array_user=Aastra_get_user_info_Asterisk($user);
	$devices=array_flip(explode('&',trim($array_user['device'])));
	unset($devices['']);
	$devices=array_flip($devices);
	}
else $devices=array($user);

# Connect to AGI
$as=new AGI_AsteriskManager();
$res=$as->connect();

$dnddb = new CustomDnd();

# DND GET
if(($action=='get') or ($action=='change'))
	{
	$res=$as->database_get('DND',$user);
	if($res)
		{
			if($res=='YES'){
				$dnd=1;
				$dnddb->enableDnd($user);
			}
			else {
				$dnd=0;
				$dnddb->disableDnd($user);
			}
		}
	else {
		$dnd=0;
		$dnddb->disableDnd($user);
	}
	}

# Process change
if($action=='change')
	{
		if($dnd==0) $action='enable';
		else $action='disable';
	}

# Process rest of the actions
switch($action)
	{
	# Enable
	case 'enable':
		$res=$as->database_put('DND',$user,'YES');
		$dnd=1;
		if($AA_FREEPBX_USEDEVSTATE)
			{
			foreach($devices as $device) Aastra_set_devstate_asterisk('Custom:DEVDND'.$device,'BUSY',$as);
			Aastra_set_devstate_asterisk('Custom:DND'.$user,'BUSY',$as);
			}
		$dnddb->enableDnd($user);
		break;

	# Enable
	case 'disable':
		$res=$as->database_del('DND',$user);
		$dnd=0;
		if($AA_FREEPBX_USEDEVSTATE)
			{
			foreach($devices as $device) Aastra_set_devstate_asterisk('Custom:DEVDND'.$device,'NOT_INUSE',$as);
			Aastra_set_devstate_asterisk('Custom:DND'.$user,'NOT_INUSE',$as);
			}
		$dnddb->disableDnd($user);
		break;
	}

# Disconnect properly
$as->disconnect();

# Return DND
return($dnd);
}

###################################################################################################
# Aastra_manage_daynight_Asterisk(action,index)
#
# This function reads and write the daynight value(s) in Asterisk Database.
#
# Parameters
#   @action		action to be performed,
#				get_status
#				change
#				get_all
#   @index		is the FreePBX Day/Night Feature Code Index (0-9)
#				used for get_status and change
#
# Returns
#   Current Day/Night value (0 if Day, 1 if Night)
#   Array with configured index, description and status
###################################################################################################
function Aastra_manage_daynight_Asterisk($action,$index)
{
# Connect to AGI
$as=new AGI_AsteriskManager();
$res=$as->connect();

# Depending on action
switch($action)
	{
	# Change status for index
	case 'change':
		$res=$as->database_get('DAYNIGHT','C'.$index);
		if($res)
			{
			if($res=='NIGHT') $night=1;
			else $night=0;
			}
		else $night=0;
	      	if($night==0)
			{
	   		$res=$as->database_put('DAYNIGHT','C'.$index,'NIGHT');
			Aastra_set_devstate_asterisk('Custom:DAYNIGHT'.$index,'INUSE',$as);
	     		$night=1;
		  	}
	      else
			{
        		$res=$as->database_put('DAYNIGHT','C'.$index,'DAY');
			Aastra_set_devstate_asterisk('Custom:DAYNIGHT'.$index,'NOT_INUSE',$as);
	        	$night=0;
      			}
		break;

	# Get all configuration
	case 'get_all':
		$night=array();
		$db=Aastra_connect_freePBX_db_Asterisk();
		if($db!=NULL)
			{
			$db->setFetchMode(DB_FETCHMODE_ASSOC);
			$query=$db->getAll('SELECT ext,dest FROM daynight WHERE dmode="fc_description" ORDER BY ext');
			if (!PEAR::isError($query))
				{
				foreach($query as $key=>$value)
					{
					if($value['dest']!='') $night[$value['ext']]['desc']=$value['dest'];
					else $night[$value['ext']]['desc']=$value['ext'];
					$res=$as->database_get('DAYNIGHT','C'.$value['ext']);
					if($res)
						{
						if($res=='NIGHT') $night[$value['ext']]['night']=1;
						else $night[$value['ext']]['night']=0;
						}
					else $night[$value['ext']]['night']=0;
					$query2=$db->getAll('SELECT dest FROM daynight WHERE dmode="password" AND ext='.$value['ext']);
					if(!PEAR::isError($query2)) $night[$value['ext']]['password']=$query2[0]['dest'];
					}
				}
			}
		break;

	# Get status for index
	default:
		# Single index
		if($index!='ALL')
			{
			$res=$as->database_get('DAYNIGHT','C'.$index);
			if($res)
				{
				if($res=='NIGHT') $night=1;
				else $night=0;
				}
			else $night=0;
			}
		else
			{
			$night=0;
			$db=Aastra_connect_freePBX_db_Asterisk();
			if($db!=NULL)
				{
				$db->setFetchMode(DB_FETCHMODE_ASSOC);
				$query=$db->getAll('SELECT ext FROM daynight WHERE dmode="fc_description" ORDER BY ext');
				if (!PEAR::isError($query))
					{
					foreach($query as $key=>$value)
						{
						$res=$as->database_get('DAYNIGHT','C'.$value['ext']);
						if($res)
							{
							if($res=='NIGHT')
								{
								$night=1;
								break;
								}
							}
						}
					}
				}
			}
		break;
	}

# Disconnect properly
$as->disconnect();

# Return Day/Night
return($night);
}

###################################################################################################
# Aastra_get_parked_calls_Asterisk()
#
# This function returns the list of parked calls on the platform.
#
# Parameters
#   None
#
# Returns
#   Array
###################################################################################################
function Aastra_get_parked_calls_Asterisk()
{
# Initial array
$park=array();

# Connect to AGI
$as = new AGI_AsteriskManager();
$res = $as->connect();

# Prepare command
if(Aastra_compare_version_Asterisk('1.6'))
	{
	$command1='parking show default';
	$command2='core show channel';
	$parameter=1;
	}
else
	{
	$command1='show parkedcalls';
	$command2='show channel';
	$parameter=1;
	}

# Check current list
$res=$as->Command($command1);
$line=split("\n", $res['data']);
$count=0;
$found=False;
foreach($line as $myline)
	{
	if(Aastra_compare_version_Asterisk('1.6'))
		{
		if(strpos($myline,'Space') !== false) {
			$linevalue= explode(':', $myline);
			$space = trim($linevalue[1]);
		}
		if(strpos($myline,'Channel') !== false)
			{
		 		$linevalue= explode(':', $myline);
				if($linevalue[1]!='')
					{
					$park[$count][0]=$space;
					$res_i=$as->Command($command2.' '.$linevalue[1]);
					$line_i=@split("\n", $res_i['data']);
					foreach($line_i as $myline_i)
						{
						if(strstr($myline_i,'Caller ID Name:') and !strstr($myline_i,'(N/A)')) $park[$count][1]=substr(substr(strrchr($myline_i,':'),1),1);
						else if(strstr($myline_i,'Caller ID:')) $park[$count][1]=substr(substr(strrchr($myline_i,':'),1),1);
						}
					$count++;
				}
			}
		}
	}

# Disconnect properly
$as->disconnect();

# Return answer
return($park);
}

###################################################################################################
# Aastra_check_park_Asterisk(user,number)
#
# This function returns the orbit where a call is parked after the user hangs up.
#
# Parameters
#   @user		user extension
#   @number		Number coming from the on-hook action uri
#
# Returns
#   Orbit or ''
#
###################################################################################################
function Aastra_check_park_Asterisk($user,$number)
{
# No return
$return='';

# Connect to AGI
$as=new AGI_AsteriskManager();
$res=$as->connect();

# Prepare command
if(Aastra_compare_version_Asterisk('1.6'))
	{
	$command1='parkedcalls show';
	$command2='core show channel';
	$param_channel=1;
	$param_timeout=6;
	}
else
	{
	$command1='show parkedcalls';
	$command2='show channel';
	$param_channel=1;
	$param_timeout=6;
	}

# Check current list
$res=$as->Command($command1);
$line=split("\n", $res['data']);
$count=0;
$found=False;
foreach ($line as $myline)
	{
	if((Aastra_compare_version_Asterisk('1.6')) and (!$found))
		{
		if(strstr($myline,'Extension') and strstr($myline,'Channel'))
			{
			$found=True;
 			$linevalue= preg_split('/ /', $myline,-1,PREG_SPLIT_NO_EMPTY);
			$param_channel=array_search('Channel',$linevalue);
			$param_timeout=array_search('Timeout',$linevalue);
			}
		}
	if((!strstr($myline,'Privilege')) && (!strstr($myline,'Extension')) && (!strstr($myline,'parked')) && (!strstr($myline,'Parking')) && ($myline!=''))
		{
 		$linevalue= preg_split("/ /", $myline,-1,PREG_SPLIT_NO_EMPTY);
		if(($linevalue[0]!='')and (is_numeric($linevalue[0])))
			{
			$park[$count]['orbit']=$linevalue[0];
			$park[$count]['timeout']=substr($linevalue[$param_timeout],0,-1);
			$res_i=$as->Command($command2.' '.$linevalue[$param_channel]);
			$line_i=@split("\n", $res_i['data']);
			foreach ($line_i as $myline_i)
				{
				if(strstr($myline_i,'Caller ID:'))$park[$count]['number']=substr(substr(strrchr($myline_i,":"),1),1);
				if(substr($myline_i,0,10)=='EXTTOCALL=') $temp[0]=substr(strrchr($myline_i,'='),1);
				if(substr($myline_i,0,17)=='DIALEDPEERNUMBER=') $temp[1]=substr(strrchr($myline_i,'='),1);
				if(substr($myline_i,0,14)=='FILTERED_DIAL=') $temp[2]=substr(strrchr($myline_i,'='),1);
				if(substr($myline_i,0,6)=='FMGRP=') $temp[3]=substr(strrchr($myline_i,'='),1);
				}
			for($i=0;$i<4;$i++)
				{
				if($temp[$i]!='')
					{
					$park[$count]['user']=$temp[$i];
					break;
					}
				}
			unset($temp);
			$count++;
			}
		}
	}

# Disconnect properly
$as->disconnect();

# Get Park config
$conf_park=Aastra_get_park_config_Asterisk();

# Find the right call
$timeout=0;
$repark=0;
if(($number>=$conf_park['parkposmin']) and ($number<=$conf_park['parkposmax']))
	{
	$repark=1;
	$timeout=$conf_park['parkingtime']-20;
	}
if(count($park)>0)
	{
	foreach($park as $key=>$value)
		{
		if($repark==0)
			{
			if(($value['number']==$number) and ($value['user']==$user) and ($value['timeout']>$timeout))
				{
				$timeout=$value['timeout'];
				$return=$value['orbit'];
				}
			}
		else
			{
			if(($value['user']==$user) and ($value['timeout']>$timeout))
				{
				$timeout=$value['timeout'];
				$return=$value['orbit'];
				}
			}
		}
	}

# Return answer
return($return);
}

###################################################################################################
# Aastra_originate_Asterisk(channel,context,exten,priority,timeout,callerID,variable,account,application,data)
#
# This function originates a call from the platform
#
# Parameters
#    @channel		Channel on which to originate the call (The same as you specify in the Dial
#                    application command)
#    @context		Context to use on connect (must use Exten & Priority with it)
#    @exten		Extension to use on connect (must use Context & Priority with it)
#    @priority	Priority to use on connect (must use Context & Exten with it)
#    @timeout		Timeout (in milliseconds) for the connection to happen(defaults to 30000
#                    milliseconds)
#    @callerID 	CallerID to use for the call
#    @variable 	Channels variables to set (max 32). Variables will be set for both channels
#                   (local and connected).
#    @account		Account code for the call
#    @application	Application to use on connect (use Data for parameters)
#    @data		Data if Application parameters are used, passed as an array
#
# Returns
#    None
###################################################################################################
function Aastra_originate_Asterisk($channel,$context,$exten,$priority,$timeout,$callerID,$variable,$account,$application,$data)
{
# Prepare parameters
if(Aastra_compare_version_Asterisk('1.6')) $separator=',';
else $separator='|';
if(is_array($variable)) $param1=implode($separator,$variable);
else $param1=$variable;
if(is_array($data)) $param2=implode($separator,$data);
else $param2=$data;

# Asterisk Call using AGI
$as = new AGI_AsteriskManager();
$res = $as->connect();

# Send the request
$res=$as->originate($channel,$context,$exten,$priority,$timeout,$callerID,$param1,$account,$application,$param2);

# Disconnect properly
$as->disconnect();

# Return result
return($res);
}

###################################################################################################
# Aastra_get_meetme_list_Asterisk(confno)
#
# This function lists the members of a meetme conference
#
# Parameters
#    @confno		Meetme conference number
#
# Returns
#    Array		[0] UserID
#			[1] Extension
#			[2] Caller ID
#			[3] Muted (true or false)
#			[4] Admin (true or false)
###################################################################################################
function Aastra_get_meetme_list_Asterisk($confno)
{
# Asterisk Call using AGI
$as=new AGI_AsteriskManager();
$res=$as->connect();

# AGI command meetme list
$res=$as->Command('meetme list '.$confno);

# Disconnect properly
$as->disconnect();

# Process result
$line=split("\n",$res['data']);
$nbuser=0;
foreach($line as $myline)
	{
	if (substr($myline,0,4)=='User')
		{
		$linevalue=preg_split('/ /',$myline,-1,PREG_SPLIT_NO_EMPTY);
		$meetmechannel[$nbuser][0]=intval($linevalue[2]);
		$meetmechannel[$nbuser][1]=$linevalue[3];
		$meetmechannel[$nbuser][3]=False;
		$meetmechannel[$nbuser][4]=False;
		$name=array();
		$count=count($linevalue);
		for($i=4;$i<$count;$i++)
			{
			if($linevalue[$i]!='Channel:') $name[]=$linevalue[$i];
			else break;
			}
		$meetmechannel[$nbuser][2]=implode(' ',$name);
		$pos=strpos($myline,'Muted');
		if($pos!=false) $meetmechannel[$nbuser][3] = True;
		$pos=strpos($myline,'(Admin)');
		if($pos!=false) $meetmechannel[$nbuser][4] = True;
		$nbuser++;
		}
	}

# Return array
if(isset($meetmechannel)) return($meetmechannel);
else return(NULL);
}

###################################################################################################
# Aastra_meetme_action_Asterisk(confno,action,user-id)
#
# This function sends a meetme command such as kick, mute or unmute,
#
# Parameters
#    @confno		Meetme conference number
#    @action		Action to perform
#    @user-id		user id
#
# Returns
#    None
###################################################################################################
function Aastra_meetme_action_Asterisk($confno,$action,$user_id)
{
# Asterisk Call using AGI
$as = new AGI_AsteriskManager();
$res = $as->connect();

# Send command
$res = $as->Command('meetme '.$action.' '.$confno.' '.$user_id);
sleep(1);

# Disconnect properly
$as->disconnect();
}

###################################################################################################
# Aastra_connect_freePBX_db_Asterisk()
#
# This function connects to the freePBX database.
#
# Parameters
#    None
#
# Returns
#   db pointer or NULL if failed
###################################################################################################
function Aastra_connect_freePBX_db_Asterisk()
{
# Read freePBX config file
$amp_conf=Aastra_readINIfile('/etc/amportal.conf','#','=');

# Connect to database
$datasource=$amp_conf['']['ampdbengine'].'://'.$amp_conf['']['ampdbuser'].':'.$amp_conf['']['ampdbpass'].'@'.$amp_conf['']['ampdbhost'].'/'.$amp_conf['']['ampdbname'];
$db=DB::connect($datasource);

# Check connection
if(DB::isError($db))
	{
	# Debug message
	Aastra_debug('Cannot connect to freePBX database, error message='.$db->getMessage());
	$db=NULL;
	}

# Return db
return($db);
}

###################################################################################################
# Aastra_get_meetme_rooms_Asterisk()
#
# This function returns the list of configured meetme rooms on the platform and the current status.
#
# Parameters
#    None
#
# Returns
#    Array
#      key is the conference room number
#      name is the conference room description
#      parties is the number of current participants
###################################################################################################
function Aastra_get_meetme_rooms_Asterisk()
{
# open freePBX database
$db=Aastra_connect_freePBX_db_Asterisk();

# Database OK
if($db!=NULL)
	{
	# Make the query
	$db->setFetchMode(DB_FETCHMODE_ASSOC);
	$query = $db->getAll('SELECT exten,description,adminpin FROM meetme ORDER BY exten');
	if (!PEAR::isError($query))
		{
		foreach($query as $key=>$value)
			{
			if($value['description']!='') $conf_array[$value['exten']]['name']=$value['description'];
			else $conf_array[$value['exten']]['name']=$value['exten'];
			$conf_array[$value['exten']]['parties']=0;
			}
		}

	# Connect to AGI
	$as=new AGI_AsteriskManager();
	$res=$as->connect();

	# Send command
	if(Aastra_compare_version_Asterisk('1.6')) $res = $as->Command('meetme list');
	else $res = $as->Command('meetme');
	$line= split("\n", $res['data']);
	if(!stristr($line[1],'No active'))
		{
		$trace=0;
		foreach ($line as $myline)
			{
			if($trace==0)
				{
				if (substr($myline,0,4)=='Conf') $trace=1;
				}
			else
				{
				$linevalue= preg_split("/ /", $myline,-1,PREG_SPLIT_NO_EMPTY);
				if (substr($myline,0,1)=='*') $trace=0;
				else
					{
					if(isset($conf_array[$linevalue[0]])) $conf_array[$linevalue[0]]['parties']=intval($linevalue[1]);
					}
				}
			}
		}

	# Disconnect properly
	$as->disconnect();
	}

# Return array
return($conf_array);
}

###################################################################################################
# Aastra_get_meetme_room_details_Asterisk(room)
#
# This function returns the list of configured meetme rooms on the platform and the current status.
#
# Parameters
#    room	Meetme room number
#
# Returns
#    Array
#      adminpin is admin pin number
#      userpin is the user pin number
###################################################################################################
function Aastra_get_meetme_room_details_Asterisk($confno)
{
# open freePBX database
$db=Aastra_connect_freePBX_db_Asterisk();

# Database OK
if($db!=NULL)
	{
	# Make the query
	$db->setFetchMode(DB_FETCHMODE_ASSOC);
	$query = $db->getAll('SELECT adminpin,userpin FROM meetme WHERE exten='.$confno);
	if (!PEAR::isError($query))
		{
		$conf_array['adminpin']=$query[0]['adminpin'];
		$conf_array['userpin']=$query[0]['userpin'];
		}
	}

# Return array
if(isset($conf_array)) return($conf_array);
else return(NULL);
}


###################################################################################################
# Aastra_get_park_config_Asterisk()
#
# This function returns the current parking configuration
#
# Parameters
#    None
#
# Returns
#    Array
#	[parkext] 		Parking lot extension
#    	[parkposmin]		First parking slot
#    	[parkposmax]		Last parking slot
#    	[parkingtime]		Parking timeout
###################################################################################################
function Aastra_get_park_config_Asterisk()
{
Global $ASTERISK_LOCATION;

# FreePBX 2.4 implementation
if(file_exists($ASTERISK_LOCATION.'res_parking_additional.conf'))
	{
	# Read config file
	$conf_array=Aastra_readINIfile($ASTERISK_LOCATION.'res_parking_additional.conf',';','=');

	# Process config file
	$return['parkext']=$conf_array['default']['parkext'];
	sscanf($conf_array['default']['parkpos'],'%d-%d',$return['parkposmin'],$return['parkposmax']);
	$return['parkingtime']=$conf_array['default']['parkingtime'];
	}
else
	{
	# Read config file
	$conf_array=Aastra_readINIfile($ASTERISK_LOCATION.'parking_additional.inc',';','=>');

	# Process config file
	foreach($conf_array[''] as $key=>$value) $conf_array[''][$key]=substr($conf_array[''][$key],2);
	$return['parkext']=$conf_array['']['parkext'];
	sscanf($conf_array['']['parkpos'],'%d-%d',$return['parkposmin'],$return['parkposmax']);
	$return['parkingtime']=$conf_array['']['parkingtime'];
	}

# Return Data
return($return);
}

###################################################################################################
# Aastra_get_intercom_config_Asterisk()
#
# This function returns the current Intercom code configuration
#
# Parameters
#    None
#
# Returns
#    Intercom code as a string
###################################################################################################
function Aastra_get_intercom_config_Asterisk()
{
$intercom=trim(`grep "INTERCOMCODE =" /etc/asterisk/extensions_additional.conf |gawk '{print \$3}'`);
if($intercom=='nointercom') $intercom='';
return($intercom);
}

###################################################################################################
# Aastra_get_version_Asterisk()
#
# This function returns the current Asterisk version.
#
# Parameters
#    None
#
# Returns
#    Major version as a string (e.g. 1.6)
###################################################################################################
function Aastra_get_version_Asterisk()
{
# 1.4 by default
$version='1.4';

# Retrieve Value from extensions_additional.conf (freePBX)
$temp=trim(`grep "ASTVERSION =" /etc/asterisk/extensions_additional.conf |gawk '{print \$3}'`);

# Finalize the version
if($temp!='') $version=substr($temp,0,3);

# Return Version
return($version);
}

###################################################################################################
# Aastra_compare_version_Asterisk(version)
#
# This function compare the current Asterisk version to a given version.
#
# Parameters
#    version
#
# Returns
#    True if current version is better or equal to the given version
###################################################################################################
function Aastra_compare_version_Asterisk($version)
{
Global $AA_ASTERISK_VERSION;

# Process versions
$piece=preg_split("/\./",$AA_ASTERISK_VERSION);
$current=$piece[0]*10+$piece[1];
$piece=preg_split("/\./",$version);
$given=$piece[0]*10+$piece[1];

# Compare
if($current>=$given) return True;
else return False;
}

###################################################################################################
# Aastra_get_registry_Asterisk()
#
# This function returns the list of registered SIP phones
#
# Parameters
#    None
#
# Returns
#    Array
#	[ext] 		Parking lot extension
#    	[ip]		First parking slot
###################################################################################################
function Aastra_get_registry_Asterisk()
{
# Connect to AGI
$as=new AGI_AsteriskManager();
$res=$as->connect();

# GET Registry
$res=$as->Command('database show SIP/Registry');

# Disconnect properly
$as->disconnect();

# Process the answer
$line=split("\n", $res['data']);
$nb_lines=count($line);
for ($i=0;$i<$nb_lines;$i++)
	{
	if(stristr($line[$i],'SIP/Registry'))
		{
		$value=split(':', $line[$i]);
		$prompt[]=array(	'ext'=>trim(substr($value[0],strlen('/SIP/Registry/')),' '),
					'ip'=>trim($value[1],' ')
				);
		}
	}

# Return answer
return($prompt);
}

###################################################################################################
# Aastra_get_userdevice_Asterisk(device)
#
# This function returns the user attached to a device
#
# Parameters
#    device		device ID
#
# Returns
#    sring		user attached to the device
###################################################################################################
function Aastra_get_userdevice_Asterisk($device)
{
Global $AA_FREEPBX_MODE;

# Extensions by default
$user=$device;

# Check the freePBX mode
if($AA_FREEPBX_MODE=='2')
	{
	# Connect to AGI
	$as=new AGI_AsteriskManager();
	$res=$as->connect();

	# Read attached user
	$res = $as->Command('database get DEVICE/'.$device.' user');
	$line=split("\n", $res['data']);
	$data=split(" ", $line[0]);
	if($data[0]=="Value:") $user=$data[1];
	else
		{
		$data=split(" ", $line[1]);
		if($data[0]=="Value:") $user=$data[1];
		}

	# Disconnect properly
	$as->disconnect();

	# Return empty if 'none'
	if($user=='none') $user='';
	}

# Return user
return($user);
}

###################################################################################################
# Aastra_get_device_info_Asterisk(device)
#
# This function returns all the configuration data regarding a device. This is used in the user
# and device freePBX mode.
#
# Parameters
#    device		device ID
#
# Returns
#    Array
#	default_user	default user for the device
#      dial		how to dial the device
#	type		adhoc or fixed
#	user		user attached to the device
###################################################################################################
function Aastra_get_device_info_Asterisk($device)
{
# Prepare the request
$array_in=array('default_user','dial','type','user');

# Connect to AGI
$as = new AGI_AsteriskManager();
$res = $as->connect();

# All the requests
foreach($array_in as $key=>$value) $array_out[$value]=$as->database_get('DEVICE',$device.'/'.$value);

# Disconnect properly
$as->disconnect();

# Return user
return($array_out);
}

###################################################################################################
# Aastra_get_user_info_Asterisk(user)
#
# This function returns all the configuration data regarding a user. This is use in the user
# and device freePBX mode.
#
# Parameters
#    user		user ID
#
# Returns
#    Array
#	cidname	Caller ID name
#	cidnum		Caller ID number
#	device		List of devices the user is attached to
#	noanswer	No answer
#	outboundcid	Outbound Caller ID
#	password	Password
#	recording	Recording
#	ringtimer	Ring Timer
#	voicemail	Voicemail
###################################################################################################
function Aastra_get_user_info_Asterisk($user)
{
# Prepare the request
$array_in=array('cidname','cidnum','device','noanswer','outboundcid','password','recording','ringtimer','voicemail');

# Connect to AGI
$as = new AGI_AsteriskManager();
$res = $as->connect();

# All the requests
foreach($array_in as $key=>$value) $array_out[$value]=$as->database_get('AMPUSER',$user.'/'.$value);

# Disconnect properly
$as->disconnect();

# Return user
return($array_out);
}

###################################################################################################
# Aastra_check_user_login_Asterisk(user,password)
#
# This function tests the user credentials in the device and user mode.
#
# Parameters
#    user		user ID
#    password		password
#
# Returns
#    Boolean
###################################################################################################
function Aastra_check_user_login_Asterisk($user,$password)
{
# True by default
$return=True;

# Retrieve configuration
$array=Aastra_get_user_info_Asterisk($user);

# User exists?
if($array==NULL) $return=False;
else
	{
	if($array['password']!=$password) $return=False;
	}

# Return result
return($return);
}

###################################################################################################
# Aastra_get_user_directory_Asterisk($mode)
#
# This function returns the list of configured users.
#
# Parameters
#    mode		'user' or 'admin' optional
#
# Returns
#    Array[]
#	name		callerid
#	number		extension
#	mailbox	mailbox context
#      status		presence status
###################################################################################################
function Aastra_get_user_directory_Asterisk($mode='user')
{
# Connect to AGI
$as = new AGI_AsteriskManager();
$res = $as->connect();

# Get the list of users
$raw=$as->database_show('AMPUSER');

# Purge the answers
foreach($raw as $key=>$value)
	{
	if(strstr($key,'cidname'))
		{
		$number=preg_replace(array('/\/AMPUSER\//','/\/cidname/'),array('',''),$key);
		$array_out[$number]['number']=$number;
		$array_out[$number]['name']=$value;
		$array_out[$number]['status']=AA_PRESENCE_AVAILABLE;
		$array_out[$number]['voicemail']='novm';
		}
	if(strstr($key,'presence/status')) $array_out[preg_replace(array('/\/AMPUSER\//','/\/presence\/status/'),array('',''),$key)]['status']=$value;
	if(strstr($key,'voicemail')) $array_out[preg_replace(array('/\/AMPUSER\//','/\/voicemail/'),array('',''),$key)]['voicemail']=$value;
	}

# Disconnect properly
$as->disconnect();

# User mode
if($mode=='user')
	{
	# Retrieve configuration file
	$array_config_asterisk=Aastra_readINIfile(AASTRA_CONFIG_DIRECTORY.'asterisk.conf','#','=');
	if($array_config_asterisk['Directory']['hidden']!='')
		{
		$array_hidden=explode(',',$array_config_asterisk['Directory']['hidden']);
		foreach($array_hidden as $key=>$value)
			{
			if(strstr($value,'-'))
				{
				$array_tmp=explode('-',$value);
				if($array_tmp['0']!='') $array_check[$key]['min']=$array_tmp[0];
				else $array_check[$key]['min']='0';
				if($array_tmp['1']!='') $array_check[$key]['max']=$array_tmp[1];
				else $array_check[$key]['max']='9999999999';
				}
			else
				{
				$array_check[$key]['min']=$value;
				$array_check[$key]['max']=$value;
				}
			}
		foreach($array_out as $key=>$value)
			{
			foreach($array_check as $key2=>$value2)
				{
				if(($value['number']>=$value2['min']) and ($value['number']<=$value2['max']))
					{
					unset($array_out[$key]);
					break;
					}
				}
			}
		}
	}

# Return directory array
return($array_out);
}

###################################################################################################
# Aastra_propagate_changes_Asterisk(device,user,appli)
#
# This function sends a SIP Notify aastra-xml to all the devices configured with the user.
#
# Parameters
#    device		Device ID
#    user		User
#    appli		Array of applications to check
#
# Returns
#    None
###################################################################################################
function Aastra_propagate_changes_Asterisk($device,$user,$appli)
{
# Retrieve user info
$array_user=Aastra_get_user_info_Asterisk($user);

# Retrieve the list of devices for this user
$array_device=array_flip(explode('&',trim($array_user['device'])));

# Not the current one
unset($array_device['']);
if($device!='') unset($array_device[$device]);
$array_device=array_flip($array_device);

# Set application to check
foreach($array_device as $value)
	{
	$data=Aastra_get_user_context($value,'notify');
	foreach($appli as $value2) $data[$value2]='1';
	Aastra_save_user_context($value,'notify',$data);
	}

# Send Notification
Aastra_send_SIP_notify_Asterisk('aastra-xml',$array_device);
}

###################################################################################################
# Aastra_is_daynight_appli_allowed_Asterisk(user,index)
#
# This function checks if a user is allowed to have the Day/Night application.
#
# Parameters
#    user		User
#    index		Day/Night index to check or '' for the global application
#
# Returns
#    Boolean
###################################################################################################
function Aastra_is_daynight_appli_allowed_Asterisk($user,$index)
{
Global $array_config_daynight;

# No by default
$return=False;

# No index so global application
if($index=='') $index='ALL';

# Depending on the configuration
switch($array_config_daynight[$index]['appli'])
	{
	case 'ALL':
		$return=True;
		break;
	case '':
		break;
	default:
		$array=explode(',',$array_config_daynight[$index]['appli']);
		if(in_array($user,$array)) $return=True;
		break;
	}

# Return result
return($return);
}

###################################################################################################
# Aastra_is_daynight_notify_allowed_Asterisk(user,index)
#
# This function checks if a user is allowed to have the Day/Night notification for a given index.
#
# Parameters
#    user		User
#    index		Day/Night index to check
#
# Returns
#    Boolean
###################################################################################################
function Aastra_is_daynight_notify_allowed_Asterisk($user,$index='ALL')
{
Global $array_config_daynight;

# No by default
$return=False;

# One or all index?
if($index=='ALL')
	{
	# Retrieve status for all configured indexes
	$array_night=Aastra_manage_daynight_Asterisk('get_all','');

	# Test all indexes
	foreach($array_night as $index=>$value)
		{
		# Depending on the configuration
		switch($array_config_daynight[$index]['notify'])
			{
			case 'ALL':
				$return=True;
				break;
			case '':
				break;
			default:
				$array=explode(',',$array_config_daynight[$index]['notify']);
				if(in_array($user,$array)) $return=True;
				else
					{
					if(Aastra_is_daynight_appli_allowed_Asterisk($user,$index)) $return=True;
					}
				break;
			}
		if($return) break;
		}
	}
else
	{
	# Depending on the configuration
	switch($array_config_daynight[$index]['notify'])
		{
		case 'ALL':
			$return=True;
			break;
		case '':
			break;
		default:
			$array=explode(',',$array_config_daynight[$index]['notify']);
			if(in_array($user,$array)) $return=True;
			else
				{
				if(Aastra_is_daynight_appli_allowed_Asterisk($user,$index)) $return=True;
				}
			break;
		}
	}

# Return result
return($return);
}

###################################################################################################
# Aastra_check_signature_Asterisk(user)
#
# This function checks if the request is coming from the same phone. If not a message is displayed
# to the user.
#
# Parameters
#    user		User
#
# Returns
#    None
###################################################################################################
function Aastra_check_signature_Asterisk($user)
{
Global $AA_FREEPBX_MODE;
Global $AA_PHONE_SIGNATURE;

# Return if device/user mode
if(($AA_FREEPBX_MODE=='2') or (!$AA_PHONE_SIGNATURE)) return;

# Retrieve stored signature
$signature=Aastra_read_signature($user);

# Maybe a first time
if($signature['signature']=='')
	{
	# Store the signature
	Aastra_store_signature($user);
	}
else
	{
	# Check signature
	if(Aastra_getphone_fingerprint()!=$signature['signature'])
		{
		# Debug
		Aastra_debug('function=Aastra_check_signature, Phone fingerprint mismatch. Stored='.$signature['signature'].' Current= '.Aastra_getphone_fingerprint());

		# Display Error
		$output = "<AastraIPPhoneTextScreen>\n";
		$output .= "<Title>Authentication Error</Title>\n";
		$output .= "<Text>You are not authorized to use this application. Please contact your administrator.</Text>\n";
		$output .= "</AastraIPPhoneTextScreen>\n";
		header("Content-Type: text/xml");
		header("Content-Length: ".strlen($output));
		echo $output;
		exit;
		}
	}
}

###################################################################################################
# Aastra_manage_presence_Asterisk(user,action,type,value)
#
# This function manage the user presence records in the Asterisk database
#
# Parameters
#    user		user extension
#    action		action to perform (get, set or unset for notification only)
#    type		object impacted (status, action, notify, date or time), optional
#    value		value to set optional
#
# Returns
#    array
#	status
#	action
#      logout
#	return
#	notifym
#	notifyv
###################################################################################################
function Aastra_manage_presence_Asterisk($user,$action,$type=NULL,$value=NULL)
{
# Translate user if needed
$user=Aastra_get_userdevice_Asterisk($user);

# Connect to AGI
$as=new AGI_AsteriskManager();
$res=$as->connect();

# Process action
switch($action)
	{
	# Just the actions
	case 'action':
		# Preferences
		$array_action=array(AA_PRESENCE_AWAY,AA_PRESENCE_ATLUNCH,AA_PRESENCE_INMEETING,AA_PRESENCE_OUTOFOFFICE,AA_PRESENCE_ATHOME,AA_PRESENCE_DISCONNECTED);
		$get=$as->database_get('AMPUSER',$user.'/presence/actions');
		if($get)
			{
			$return['action']=unserialize(base64_decode($get));
			foreach($array_action as $state)
				{
				if($return['action'][$state]=='') $return['action'][$state]=AA_PRESENCE_ACT_NOTHING;
				}
			}
		else foreach($array_action as $state) $return['action'][$state]=AA_PRESENCE_ACT_NOTHING;
		$get=$as->database_get('AMPUSER',$user.'/presence/act_param');
		if($get) $return['act_param']=unserialize(base64_decode($get));
		else foreach($array_action as $state) $return['act_param'][$state]='';
		$get=$as->database_get('AMPUSER',$user.'/presence/logout');
		if($get) $return['logout']=$get;
		else $return['logout']='';
		break;

	# Just the logout
	case 'logout':
		# Retrieve data
		$get=$as->database_get('AMPUSER',$user.'/presence/logout');
		if($get) $return=$get;
		else $return='';
		break;

	# Just the status
	case 'status':
		$get=$as->database_get('AMPUSER',$user.'/presence/status');
		if($get) $return=$get;
		else $return=AA_PRESENCE_AVAILABLE;
		break;

	# Status + logout
	case 'check':
		# Retrieve data
		$get=$as->database_get('AMPUSER',$user.'/presence/logout');
		if($get) $return['logout']=$get;
		else $return['logout']='';
		$get=$as->database_get('AMPUSER',$user.'/presence/status');
		if($get) $return['status']=$get;
		else $return['status']=AA_PRESENCE_AVAILABLE;
		break;

	# Read database
	case 'get':
		# Status
		$get=$as->database_get('AMPUSER',$user.'/presence/status');
		if($get) $return['status']=$get;
		else $return['status']=AA_PRESENCE_AVAILABLE;

		# Preferences
		$array_action=array(AA_PRESENCE_AWAY,AA_PRESENCE_ATLUNCH,AA_PRESENCE_INMEETING,AA_PRESENCE_OUTOFOFFICE,AA_PRESENCE_ATHOME,AA_PRESENCE_DISCONNECTED);
		$get=$as->database_get('AMPUSER',$user.'/presence/actions');
		if($get)
			{
			$return['action']=unserialize(base64_decode($get));
			foreach($array_action as $state)
				{
				if($return['action'][$state]=='') $return['action'][$state]=AA_PRESENCE_ACT_NOTHING;
				}
			}
		else foreach($array_action as $state) $return['action'][$state]=AA_PRESENCE_ACT_NOTHING;
		$get=$as->database_get('AMPUSER',$user.'/presence/act_param');
		if($get) $return['act_param']=unserialize(base64_decode($get));
		else foreach($array_action as $state) $return['act_param'][$state]='';

		# Return date/time
		$get=$as->database_get('AMPUSER',$user.'/presence/return');
		if($get) $return['return']=$get;
		else $return['return']='0';

		# Notify Message
		$get=$as->database_get('AMPUSER',$user.'/presence/notifym');
		if($get) $return['notifym']=$get;
		else $return['notifym']='';

		# Notify Voice
		$get=$as->database_get('AMPUSER',$user.'/presence/notifyv');
		if($get) $return['notifyv']=$get;
		else $return['notifyv']='';

		# Logout
		$get=$as->database_get('AMPUSER',$user.'/presence/logout');
		if($get) $return['logout']=$get;
		else $return['logout']='';
		break;

	# Set values
	case 'set':
		$return=True;
		switch($type)
			{
			# Status change
			case 'status':
				# Not available
				if($value!=AA_PRESENCE_AVAILABLE) $res=$as->database_put('AMPUSER',$user.'/presence/status',$value);
				else
					{
					# Delete from database as well as date, time and notifications
					$res=$as->database_del('AMPUSER',$user.'/presence/status');
					$res=$as->database_del('AMPUSER',$user.'/presence/return');
					$res=$as->database_del('AMPUSER',$user.'/presence/notifym');
					$res=$as->database_del('AMPUSER',$user.'/presence/notifyv');
					}
				break;

			# Action change
			case 'action':
				# No action
				$res=$as->database_put('AMPUSER',$user.'/presence/actions',base64_encode(serialize($value[0])));
				$res=$as->database_put('AMPUSER',$user.'/presence/act_param',base64_encode(serialize($value[1])));
				break;

			# Date change
			case 'return':
				# No date
				if($value!='0') $res=$as->database_put('AMPUSER',$user.'/presence/return',$value);
				else $res=$as->database_del('AMPUSER',$user.'/presence/return');
				break;

			# Logout change
			case 'logout':
				# No time
				if($value!='') $res=$as->database_put('AMPUSER',$user.'/presence/logout',$value);
				else $res=$as->database_del('AMPUSER',$user.'/presence/logout');
				break;

			# Notify change
			case 'notifyv':
			case 'notifym':
				# Notify
				if($type=='notifym') $get=$as->database_get('AMPUSER',$user.'/presence/notifym');
				else $get=$as->database_get('AMPUSER',$user.'/presence/notifyv');
				if($get) $notify=$get;
				else $notify='';

				# No existing value
				if($value!='')
					{
					if($notify!='')
						{
						$explode=explode(',',$notify);
						if(!in_array($value,$explode)) $notify.=','.Aastra_get_userdevice_Asterisk($value);
						}
					else $notify=Aastra_get_userdevice_Asterisk($value);
					if($type=='notifym') $res=$as->database_put('AMPUSER',$user.'/presence/notifym',$notify);
					else $res=$as->database_put('AMPUSER',$user.'/presence/notifyv',$notify);
					}
				else
					{
					if($type=='notifym') $res=$as->database_del('AMPUSER',$user.'/presence/notifym');
					else $res=$as->database_del('AMPUSER',$user.'/presence/notifyv');
					}
				break;
			}
		break;

	# Unset value for Notification only
	case 'unset':
		$return=True;
		$value=Aastra_get_userdevice_Asterisk($value);
		$array=array('notifym','notifyv');
		foreach($array as $type)
			{
			if($type=='notifym') $get=$as->database_get('AMPUSER',$user.'/presence/notifym');
			else $get=$as->database_get('AMPUSER',$user.'/presence/notifyv');
			if($get) $notify=$get;
			else $notify='';
			if($notify!='')
				{
				$explode=explode(',',$notify);
				if(in_array($value,$explode))
					{
					unset($explode[array_search($value,$explode)]);
					$notify=implode(',',$explode);
					}
				if($notify!='')
					{
					if($type=='notifym') $res=$as->database_put('AMPUSER',$user.'/presence/notifym',$notify);
					else $res=$as->database_put('AMPUSER',$user.'/presence/notifyv',$notify);
					}
				else
					{
					if($type=='notifym') $res=$as->database_del('AMPUSER',$user.'/presence/notifym');
					else $res=$as->database_del('AMPUSER',$user.'/presence/notifyv');
					}
				}
			}
		break;
	}

# Disconnect properly
$as->disconnect();

# Return
return($return);
}

###################################################################################################
# Aastra_format_presence_dt_Asterisk(timestamp)
#
# This function properly formats the presence return date and time
#
# Parameters
#    timestamp	return time (can be null)
#
# Returns
#    array
#	Formatted lines
###################################################################################################
function Aastra_format_presence_dt_Asterisk($time)
{
Global $language;
Global $AA_FORMAT_DT;

# Get the size of the display
$size=Aastra_size_display_line();

# Decode date and time
if($time!='0')
	{
	$array_day=array(	'0'=>Aastra_get_label('Sun',$language),
				'1'=>Aastra_get_label('Mon',$language),
				'2'=>Aastra_get_label('Tue',$language),
				'3'=>Aastra_get_label('Wed',$language),
				'4'=>Aastra_get_label('Thu',$language),
				'5'=>Aastra_get_label('Fri',$language),
				'6'=>Aastra_get_label('Sat',$language)
			   );
	if($AA_FORMAT_DT=='US')
		{
		$date_screen=$array_day[date('w',$time)].' '.date('m/d/y',$time);
		$time_screen=date('h:i A',$time);
		}
	else
		{
		$date_screen=$array_day[date('w',$time)].' '.date('d/m/y',$time);
		$time_screen=date('H:i',$time);
		}
	$line[0]=sprintf(Aastra_get_label('Back %s',$language),$date_screen);
	$line[1]=sprintf(Aastra_get_label('At %s',$language),$time_screen);
	}
else
	{
	$line[0]=Aastra_get_label('No return date/time',$language);
	if(strlen($line[0])>$size) $line[0]=Aastra_get_label('No return info',$language);
	}

# Return formatted text
return($line);
}

###################################################################################################
# Aastra_manage_userinfo_Asterisk(user,action,array)
#
# This function manage the user info records in the Asterisk database
#
# Parameters
#    user		user extension
#    action		action to perform (get or set or unset for notification only)
#    array		array with the values
#
# Returns
#    array
#	cell
#	home
#	other
###################################################################################################
function Aastra_manage_userinfo_Asterisk($user,$action,$array=NULL)
{
# Translate user if needed
$user=Aastra_get_userdevice_Asterisk($user);

# Connect to AGI
$as=new AGI_AsteriskManager();
$res=$as->connect();

# Process action
switch($action)
	{
	# Read database
	case 'get':
	       # No answer
       	$return=array();

		# Cell Phone
		$get=$as->database_get('AMPUSER',$user.'/info/cell');
		if($get) $return['cell']=$get;

		# Home phone
		$get=$as->database_get('AMPUSER',$user.'/info/home');
		if($get) $return['home']=$get;

		# Other phone
		$get=$as->database_get('AMPUSER',$user.'/info/other');
		if($get) $return['other']=$get;
		break;

	# Set values
	case 'set':
		# Cell phone
		if($array['cell']) $res=$as->database_put('AMPUSER',$user.'/info/cell',$array['cell']);
		else $res=$as->database_del('AMPUSER',$user.'/info/cell');

		# Home phone
		if($array['home']) $res=$as->database_put('AMPUSER',$user.'/info/home',$array['home']);
		else $res=$as->database_del('AMPUSER',$user.'/info/home');

		# Cell phone
		if($array['other']) $res=$as->database_put('AMPUSER',$user.'/info/other',$array['other']);
		else $res=$as->database_del('AMPUSER',$user.'/info/other');

		# Return
		$return=$array;
		break;
	}

# Disconnect properly
$as->disconnect();

# Return
return($return);
}

###################################################################################################
# Aastra_manage_followme_Asterisk(user,action,value)
#
# This function manages the follow-me status in the Asterisk database
#
# Parameters
#    user		user extension
#    action		action to perform
#				get_status,
#				enable,
#				disable,
# 				change_status,
#				get_all,
#				set_prering,
#				set_grptime,
#				set_grplist,
#				set_grpconf.
#    value		data to insert only significant for set_ actions
#				set_prering string
#				set_grptime string
#				set_grplist array of numbers
#				set_grpconf boolean
#
# Returns
#  Action=get_status, change_status, enable or disable
#    Current value of follow-me (0=disabled, 1=enabled, 2=not configured)
#  Action=get_all
#    Array with all the values
#  Other actions
#    ''
###################################################################################################
function Aastra_manage_followme_Asterisk($user,$action,$value=NULL)
{
Global $AA_FREEPBX_MODE;
Global $AA_FREEPBX_USEDEVSTATE;

# Translate user if needed
if($AA_FREEPBX_MODE=='2')
	{
	$user=Aastra_get_userdevice_Asterisk($user);
	$array_user=Aastra_get_user_info_Asterisk($user);
	$devices=array_flip(explode('&',trim($array_user['device'])));
	unset($devices['']);
	$devices=array_flip($devices);
	}
else $devices=array($user);

# Connect to AGI
$as=new AGI_AsteriskManager();
$res=$as->connect();

# GET
if(($action=='get_status') or ($action=='change_status') or ($action=='get_all'))
	{
	$res=$as->Command('database get AMPUSER '.$user.'/followme/ddial');
	$data=strpos($res['data'],'Value:');
	if ($data!==false)
		{
		$temp=trim(substr($res['data'],6+$data));
		switch($temp)
			{
			case 'DIRECT':
				if($action!='get_all') $followme='1';
				else $followme['status']='1';
				break;
			case 'EXTENSION':
				if($action!='get_all') $followme='0';
				else $followme['status']='0';
				break;
			default:
				if($action!='get_all') $followme='2';
				else $followme['status']='2';
				break;
			}
		}
	else
		{
		if($action!='get_all') $followme='2';
		else $followme['status']='2';
		}
	}

# Process rest of the actions
switch($action)
	{
	# Reverse follow-me
	case 'change_status':
		# change follow-me status
		switch($followme)
			{
			case '0':
				$res=$as->Command('database put AMPUSER/'.$user.'/followme ddial DIRECT');
				if($AA_FREEPBX_USEDEVSTATE)
					{
					foreach($devices as $device) Aastra_set_devstate_asterisk('Custom:FOLLOWME'.$device,'INUSE',$as);
					}
				$followme=1;
				break;

			case '1':
				$res=$as->Command('database put AMPUSER/'.$user.'/followme ddial EXTENSION');
				if($AA_FREEPBX_USEDEVSTATE)
					{
					foreach($devices as $device) Aastra_set_devstate_asterisk('Custom:FOLLOWME'.$device,'NOT_INUSE',$as);
					}
				$followme=0;
				break;
			}
		break;

	# Enable
	case 'enable':
		$res=$as->Command('database put AMPUSER/'.$user.'/followme ddial DIRECT');
		if($AA_FREEPBX_USEDEVSTATE)
			{
			foreach($devices as $device)
				{
				foreach($devices as $device) Aastra_set_devstate_asterisk('Custom:FOLLOWME'.$device,'INUSE',$as);
				}
			}
		$followme=1;
		break;

	# Disable
	case 'disable':
		$res=$as->Command('database put AMPUSER/'.$user.'/followme ddial EXTENSION');
		if($AA_FREEPBX_USEDEVSTATE)
			{
			foreach($devices as $device)
				{
				foreach($devices as $device) Aastra_set_devstate_asterisk('Custom:FOLLOWME'.$device,'NOT_INUSE',$as);
				}
			}
		$followme=0;
		break;

	# Get complete configuration
	case 'get_all':
		$res=$as->Command('database get AMPUSER '.$user.'/followme/prering');
		$data=strpos($res['data'],'Value:');
		if ($data!==false) $followme['prering']=trim(substr($res['data'],6+$data));
		else $followme['prering']='';
		$res=$as->Command('database get AMPUSER '.$user.'/followme/grptime');
		$data=strpos($res['data'],'Value:');
		if ($data!==false) $followme['grptime']=trim(substr($res['data'],6+$data));
		else $followme['grptime']='';
		$res=$as->Command('database get AMPUSER '.$user.'/followme/grpconf');
		$data=strpos($res['data'],'Value:');
		if ($data!==false)
			{
			if(trim(substr($res['data'],6+$data))=='ENABLED') $followme['grpconf']=True;
			else $followme['grpconf']=False;
			}
		else $followme['grpconf']=False;
		$res=$as->Command('database get AMPUSER '.$user.'/followme/grplist');
		$data=strpos($res['data'],'Value:');
		if ($data!==false) $followme['grplist']=explode('-',trim(substr($res['data'],6+$data)));
		else $followme['grplist']=array();
		break;

	# Set grptime
	case 'set_grptime':
		$res=$as->Command('database put AMPUSER/'.$user.'/followme grptime '.$value);
		$followme='';
		$db=Aastra_connect_freePBX_db_Asterisk();
		if($db!=NULL) $db->query('UPDATE findmefollow SET grptime='.$value.' WHERE grpnum='.$user);
		$db->disconnect();
		break;

	# Set prering
	case 'set_prering':
		$res=$as->Command('database put AMPUSER/'.$user.'/followme prering '.$value);
		$followme='';
		$db=Aastra_connect_freePBX_db_Asterisk();
		if($db!=NULL) $db->query('UPDATE findmefollow SET pre_ring='.$value.' WHERE grpnum='.$user);
		$db->disconnect();
		break;

	# Set grpconf
	case 'set_grpconf':
		if($value) $update='ENABLED';
		else $update='DISABLED';
		$res=$as->Command('database put AMPUSER/'.$user.'/followme grpconf '.$update);
		$followme='';
		if($value) $update='CHECKED';
		else $update='';
		$db=Aastra_connect_freePBX_db_Asterisk();
		if($db!=NULL) $db->query('UPDATE findmefollow SET needsconf="'.$update.'" WHERE grpnum='.$user);
		$db->disconnect();
		break;

	# Set grplist
	case 'set_grplist':
		$value=array_unique(array_values($value));
		$update=implode('-',$value);
		$res=$as->Command('database put AMPUSER/'.$user.'/followme grplist '.$update);
		$followme='';
		$db=Aastra_connect_freePBX_db_Asterisk();
		if($db!=NULL) $db->query('UPDATE findmefollow SET grplist="'.$update.'" WHERE grpnum='.$user);
		$db->disconnect();
		break;
	}

# Disconnect properly
$as->disconnect();

# Return value
return($followme);
}

###################################################################################################
# Aastra_delete_temp_message_Asterisk(user)
#
# This function deletes the temporary message of any given user.
#
# Parameters
#    user		user extension
#
# Returns
#    None
###################################################################################################
function Aastra_delete_temp_message_Asterisk($user)
{
Global $AA_VM_BOXBASE;

# Translate user if needed
$user=Aastra_get_userdevice_Asterisk($user);

# Delete temporary message
$dir=$AA_VM_BOXBASE.'/'.$user;
@unlink($dir.'/temp.wav');
@unlink($dir.'/temp.WAV');
}

###################################################################################################
# Aastra_delete_name_message_Asterisk(user)
#
# This function deletes the name prompt of any given user.
#
# Parameters
#    user		user extension
#
# Returns
#    None
###################################################################################################
function Aastra_delete_name_message_Asterisk($user)
{
Global $AA_VM_BOXBASE;

# Translate user if needed
$user=Aastra_get_userdevice_Asterisk($user);

# Delete temporary message
$dir=$AA_VM_BOXBASE.'/'.$user;
@unlink($dir.'/greet.wav');
@unlink($dir.'/greet.WAV');
}

###################################################################################################
# Aastra_delete_busy_message_Asterisk(user)
#
# This function deletes the busy prompt of any given user.
#
# Parameters
#    user		user extension
#
# Returns
#    None
###################################################################################################
function Aastra_delete_busy_message_Asterisk($user)
{
Global $AA_VM_BOXBASE;

# Translate user if needed
$user=Aastra_get_userdevice_Asterisk($user);

# Delete temporary message
$dir=$AA_VM_BOXBASE.'/'.$user;
@unlink($dir.'/busy.wav');
@unlink($dir.'/busy.WAV');
}

###################################################################################################
# Aastra_delete_unavail_message_Asterisk(user)
#
# This function deletes the unavailable prompt of any given user.
#
# Parameters
#    user		user extension
#
# Returns
#    None
###################################################################################################
function Aastra_delete_unavail_message_Asterisk($user)
{
Global $AA_VM_BOXBASE;

# Translate user if needed
$user=Aastra_get_userdevice_Asterisk($user);

# Delete temporary message
$dir=$AA_VM_BOXBASE.'/'.$user;
@unlink($dir.'/unavail.wav');
@unlink($dir.'/unavail.WAV');
}

###################################################################################################
# Aastra_get_greeting_name_Asterisk(user)
#
# This function retrieves the location of the greeting name wav file.
#
# Parameters
#    user		user extension
#
# Returns
#    Location of the wav file or empty string
###################################################################################################
function Aastra_get_greeting_name_Asterisk($user)
{
Global $AA_VM_BOXBASE;

# Translate user if needed
$user=Aastra_get_userdevice_Asterisk($user);

# Not found
$return=False;

# Check file
if(file_exists($AA_VM_BOXBASE.'/'.$user.'/greet.wav') || file_exists($AA_VM_BOXBASE.'/'.$user.'/greet.WAV')) $return=$AA_VM_BOXBASE.'/'.$user.'/greet';

# Return user greeting
return($return);
}

###################################################################################################
# Aastra_get_greeting_status_Asterisk(user)
#
# This function retrieves the status of the greetings messages.
#
# Parameters
#    user		user extension
#
# Returns
#    array with the statuses
#	[busy]
#	[name]
#	[temp]
#	[unavail]
###################################################################################################
function Aastra_get_greeting_status_Asterisk($user)
{
Global $AA_VM_BOXBASE;

# Translate user if needed
$user=Aastra_get_userdevice_Asterisk($user);

# Check files
if(file_exists($AA_VM_BOXBASE.'/'.$user.'/greet.wav') || file_exists($AA_VM_BOXBASE.'/'.$user.'/greet.WAV')) $return['name']=True;
else $return['name']=False;
if(file_exists($AA_VM_BOXBASE.'/'.$user.'/busy.wav') || file_exists($AA_VM_BOXBASE.'/'.$user.'/busy.WAV')) $return['busy']=True;
else $return['busy']=False;
if(file_exists($AA_VM_BOXBASE.'/'.$user.'/unavail.wav') || file_exists($AA_VM_BOXBASE.'/'.$user.'/unavail.WAV')) $return['unavail']=True;
else $return['unavail']=False;
if(file_exists($AA_VM_BOXBASE.'/'.$user.'/temp.wav') || file_exists($AA_VM_BOXBASE.'/'.$user.'/temp.WAV')) $return['temp']=True;
else $return['temp']=False;

# Return greeting status
return($return);
}

###################################################################################################
# Aastra_dial_number_Asterisk(user,dial)
#
# This function launches a call from any user.
#
# Parameters
#    user		user extension
#    dial		number to dial
#
# Returns
#    None
###################################################################################################
function Aastra_dial_number_Asterisk($user,$dial)
{
if($dial)
	{
	# Retrieve true user
	$user=Aastra_get_userdevice_Asterisk($user);
	$user_details=Aastra_get_user_info_Asterisk($user);

	# Launch the call
	Aastra_originate_Asterisk('Local/*80'.$user.'@from-internal','9999','default',1,'',$user_details[cidname].' <'.$user_details[cidnum].'>','','','Dial','Local/'.$dial.'@from-internal');
	}
}

###################################################################################################
# Aastra_get_bulk_callerid_Asterisk($array)
#
# This function gets the callerID for a directory array
#
# Parameters
#    array		input array
#
# Returns
#    array completed with name
###################################################################################################
function Aastra_get_bulk_callerid_Asterisk($array)
{
Global $ASTERISK_LOCATION;
Global $AA_FREEPBX_MODE;
Global $language;

# Try in the config file first
$sip_array=Aastra_readINIfile($ASTERISK_LOCATION.'sip_additional.conf',';','=');

# Connect to AGI
$as=new AGI_AsteriskManager();
$res=$as->connect();

# Process all the extensions
foreach($array as $key=>$value)
	{
	# No name by default
	$name='';

	# Extension exists?
	if ($sip_array[$value['number']]==NULL)
		{
		if($AA_FREEPBX_MODE=='1') $name=Aastra_get_label('Unknown',$language);
		}
	else
		{
		# FreePBX old school
		if(!strstr($sip_array[$value['number']]['callerid'],'device'))
			{
			# Retrieve the value
			$temp=explode(" <",$sip_array[$value['number']]['callerid'],2);
			$number=$temp[0];
			}
		}

	# Still not found
	if($name=='')
		{
		# Get value in the database
		$name=$as->database_get('AMPUSER',$value['number'].'/cidname');
		}

	# Complete caller ID
	$array[$key]['name']=$name;
	}

# Disconnect properly
$as->disconnect();

# Return Caller ID
return($array);
}

###################################################################################################
# Aastra_get_bulk_registry_Asterisk($array)
#
# This function gets the registry status for a directory array
#
# Parameters
#    array		input array
#
# Returns
#    array completed with registry
###################################################################################################
function Aastra_get_bulk_registry_Asterisk($array)
{
# Connect to AGI
$as=new AGI_AsteriskManager();
$res=$as->connect();

# GET Registry
$res=$as->Command('database show SIP/Registry');

# Disconnect properly
$as->disconnect();

# Process the answer
$line=split("\n", $res['data']);
$nb_lines=count($line);
for ($i=0;$i<$nb_lines;$i++)
	{
	if(stristr($line[$i],'SIP/Registry'))
		{
		$value=split(':', $line[$i]);
		$registry[trim(substr($value[0],strlen('/SIP/Registry/')),' ')]=True;
		}
	}

# All false by default
foreach($array as $key=>$value)
	{
	if($registry[$value['number']]) $array[$key]['registry']=True;
	else $array[$key]['registry']=False;
	}

# Return answer
return($array);
}

###################################################################################################
# Aastra_queue_pause_Asterisk(agent,queue,pause)
#
# This function sets the pause status for an agent in a queue.
#
# Parameters
#    agent		agent extension (or device)
#    queue		queue number
#    pause		true or false (string not boolean)
#
# Returns
#    None
###################################################################################################
function Aastra_queue_pause_Asterisk($agent,$queue,$pause)
{
$asm=new AGI_AsteriskManager();
$asm->connect();
$asm->QueuePause($queue,'Local/'.Aastra_get_userdevice_Asterisk($agent).'@from-queue/n',$pause);
$asm->QueuePause($queue,'Local/'.Aastra_get_userdevice_Asterisk($agent).'@from-internal/n',$pause);
$asm->disconnect();
}

###################################################################################################
# Aastra_queue_add_Asterisk(agent,queue)
#
# This function adds a dynamic agent in a queue.
#
# Parameters
#    agent		agent extension (or device)
#    queue		queue number
#
# Returns
#    None
###################################################################################################
function Aastra_queue_add_Asterisk($agent,$queue,$penalty=0)
{
$asm=new AGI_AsteriskManager();
$asm->connect();
$asm->QueueAdd($queue,'Local/'.Aastra_get_userdevice_Asterisk($agent).'@'.Aastra_agent_context_Asterisk().'/n',$penalty);
$asm->disconnect();
}

###################################################################################################
# Aastra_queue_remove_Asterisk(agent,queue)
#
# This function removes a dynamic agent from a queue.
#
# Parameters
#    agent		agent extension (or device)
#    queue		queue number
#
# Returns
#    None
###################################################################################################
function Aastra_queue_remove_Asterisk($agent,$queue)
{
$asm=new AGI_AsteriskManager();
$asm->connect();
$asm->QueueRemove($queue,'Local/'.Aastra_get_userdevice_Asterisk($agent).'@from-internal/n');
$asm->QueueRemove($queue,'Local/'.Aastra_get_userdevice_Asterisk($agent).'@from-queue/n');
$asm->disconnect();
}

###################################################################################################
# Aastra_send_message_Asterisk(user,long_message,short_message,uri)
#
# This function immediately sends a message to a user.
#
# Parameters
#    user		user (not the device)
#    long_message	long message to display
#    short_message	short message to display
#    uri		uri for the alert
#
# Returns
#    None
###################################################################################################
function Aastra_send_message_Asterisk($user,$long_message,$short_message,$uri='')
{
Global $AA_FREEPBX_MODE;

# Post the message
if(($long_message!='') and ($short_message!=''))
	{
	# Mode extensions
	if($AA_FREEPBX_MODE=='1')
		{
		# Send to a single user
		$data=Aastra_get_user_context($user,'notify');
		$data['message']='1';
		Aastra_save_user_context($user,'notify',$data);
		$message['long']=$long_message;
		$message['short']=$short_message;
		$message['uri']=$uri;
		Aastra_save_user_context($user,'message',$message);
		Aastra_send_SIP_notify_Asterisk('aastra-xml',array('0'=>$user));
		}
	else
		{
		# Retrieve user info
		$array_user=Aastra_get_user_info_Asterisk($user);

		# Retrieve the list of devices for this user
		$array_device=array_flip(explode('&',trim($array_user['device'])));
		unset($array_device['']);
		$array_device=array_flip($array_device);

		# Process each device
		foreach($array_device as $user)
			{
			$data=Aastra_get_user_context($user,'notify');
			$data['message']='1';
			Aastra_save_user_context($user,'notify',$data);
			$message['long']=$long_message;
			$message['short']=$short_message;
			$message['uri']=$uri;
			Aastra_save_user_context($user,'message',$message);
			}

		# Notify everybody
		Aastra_send_SIP_notify_Asterisk('aastra-xml',$array_device);
		}
	}
}

###################################################################################################
# Aastra_add_parking_Asterisk(user)
#
# This function adds a user to the list of user being notified with a parking change.
#
# Parameters
#    user		user ID
#
# Returns
#    None
###################################################################################################
function Aastra_add_parking_Asterisk($user)
{
# User allowed?
if(Aastra_is_parking_notify_allowed_Asterisk(Aastra_get_userdevice_Asterisk($user)))
	{
	# Retrieve current configuration
	$data=Aastra_get_user_context('parking','user');

	# Not already there
	if(!in_array($user,$data))
		{
		# Save the user
		$data[]=$user;
		$data=array_values($data);

		# Save configuration
		Aastra_save_user_context('parking','user',$data);
		}
	}
}

###################################################################################################
# Aastra_remove_parking_Asterisk(user)
#
# This function removes a user from the list of user being notified with a parking change.
#
# Parameters
#    user		user ID
#
# Returns
#    None
###################################################################################################
function Aastra_remove_parking_Asterisk($user)
{
# Retrieve current configuration
$data=Aastra_get_user_context('parking','user');

# User is there
if(in_array($user,$data))
	{
	# Remove the user
	$data=array_flip($data);
	unset($data[$user]);
	$data=array_flip($data);

	# Save configuration
	Aastra_save_user_context('parking','user',$data);
	}
}

###################################################################################################
# Aastra_is_parking_notify_allowed_Asterisk(user)
#
# This function checks if a user is allowed to have the Parking notification.
#
# Parameters
#    user		User
#
# Returns
#    Boolean
###################################################################################################
function Aastra_is_parking_notify_allowed_Asterisk($user)
{
Global $AA_PARKING_EXCLUDE;

# Yes by default
$return=True;

# Check configuration
if($AA_PARKING_EXCLUDE!='')
	{
	if(in_array($user,$AA_PARKING_EXCLUDE)) $return=False;
	}

# Return result
return($return);
}

###################################################################################################
# Aastra_add_vmail_Asterisk(vmbox,user,count)
#
# This function adds the monitoring of a VM box by a user.
#
# Parameters
#    vmbox		Voicemail box number
#    user		User ID
#    count		Boolean which indicates if message counting is necessary
#
# Returns
#    None
###################################################################################################
function Aastra_add_vmail_Asterisk($vmbox,$user,$count=False)
{
# No update by default
$update=False;

# Retrieve current configuration
$data=Aastra_get_user_context('vmail','user');

# Mailbox does not exist
if(!in_array($vmbox,$data))
	{
	# Create it
	$data[$vmbox]['status']='off';
	$data[$vmbox]['count']=0;
	$data[$vmbox]['msg']=0;
	$update=True;
	}

# Not already there
if(isset($data[$vmbox]['user']))
	{
	if(!in_array($user,$data[$vmbox]['user']))
		{
		# Save the user
		$data[$vmbox]['user'][]=$user;
		$data[$vmbox]['user']=array_values($data[$vmbox]['user']);
		}
	}
else
	{
	# Save the user
	$data[$vmbox]['user'][]=$user;
	$data[$vmbox]['user']=array_values($data[$vmbox]['user']);
	}

# Add counting if needed
if($count)
	{
	$data[$vmbox]['count']++;
	$update=True;
	}

# Save configuration
if($update) Aastra_save_user_context('vmail','user',$data);
}

###################################################################################################
# Aastra_remove_vmail_Asterisk(vmbox,user,count)
#
# This function removes the monitoring of a VM box by a user.
#
# Parameters
#    vmbox		Voicemail box number
#    user		User ID
#    count		Boolean which indicates if message counting is necessary
#
# Returns
#    None
###################################################################################################
function Aastra_remove_vmail_Asterisk($vmbox,$user,$count=False)
{
# No update
$update=False;

# Retrieve current configuration
$data=Aastra_get_user_context('vmail','user');

# Specific VMBox
if($vmbox!='') $array[$vmbox]='';
else $array=$data;

# Browse all mailboxes
foreach($array as $vmbox=>$value)
	{
	if(isset($data[$vmbox]['user']))
		{
		if(in_array($user,$data[$vmbox]['user']))
			{
			# Remove the user
			$temp=array_flip($data[$vmbox]['user']);
			unset($temp[$user]);
			$temp=array_flip($temp);
			if(count($temp)!=0)
				{
				$data[$vmbox]['user']=$temp;
				if($count and $data[$vmbox]['count']>0) $data[$vmbox]['count']--;
				}
			else unset($data[$vmbox]);

			# Update needed
			$update=True;
			}
		}
	}

# Save configuration
if($update) Aastra_save_user_context('vmail','user',$data);
}

###################################################################################################
# Aastra_vm_status_Asterisk()
#
# This function returns the status of all the VM boxes on the platform.
#
# Parameters
#    None
#
# Returns
#    Array[VMbox]=nb of msg
###################################################################################################
function Aastra_vm_status_Asterisk()
{
# Asterisk Call using AGI
$as=new AGI_AsteriskManager();
$res=$as->connect();

# Prepare command
if(Aastra_compare_version_Asterisk('1.6')) $command='voicemail show users';
else $command='show voicemail users';

# AGI command
$res=$as->Command($command);

# Process result
$line=split("\n", $res['data']);
$nbuser=0;
foreach ($line as $myline)
	{
	if((!strstr($myline,'Privilege')) && (!strstr($myline,'Context')) && ($myline!='')) $array[trim(substr($myline,11,6))]=trim(substr($myline,54,6));
	}

# Disconnect properly
$as->disconnect();

# Return result
return($array);
}

###################################################################################################
# Aastra_ask_language_Asterisk()
#
# This function returns information on the languages configured for the phone and if it is needed
# to ask the user to select a language.
#
# Parameters
#    None
#
# Returns
#    Array
#      [0]	Boolean indicates if language needs to be asked
#	[1]	Array with all configured languages
#	[2]	Default language index
###################################################################################################
function Aastra_ask_language_Asterisk()
{
$return[0]=False;
$array=Aastra_readCFGfile(AASTRA_TFTP_DIRECTORY.'/aastra.cfg','#',':');
if($array['']['ask_language']=='1')
	{
	$return[0]=True;
	$return[1][0]='en';
	for($i=1;$i<5;$i++)
		{
		if($array['']['language '.$i]!='')
			{
			sscanf($array['']['language '.$i],'lang_%[^.].txt',$code);
			$return[1][$i]=$code;
			}
		}
	}
$return[2]=$array['']['language'];
return($return);
}

###################################################################################################
# Aastra_get_startup_profile_Asterisk(user)
#
# This function returns the profile to use for self-configuration for any givem user/device based
# on the configuration in asterisk.conf. If user/device is not configured, default profile is
# returned.
#
# Parameters
#    user	user or device ID
#
# Returns
#    profile to use
###################################################################################################
function Aastra_get_startup_profile_Asterisk($user)
{
# No profile yet
$profile='';

# Retrieve configuration
$array_config_asterisk=Aastra_readINIfile(AASTRA_CONFIG_DIRECTORY.'asterisk.conf','#','=');

# Search configuration
if(isset($array_config_asterisk['Profiles']))
	{
	foreach($array_config_asterisk['Profiles'] as $prof=>$users)
		{
		$array_users=explode(',',$users);
		foreach($array_users as $key=>$value)
			{
			if(strstr($value,'-'))
				{
				$array_tmp=explode('-',$value);
				if($array_tmp['0']!='') $array_check[$key]['min']=$array_tmp[0];
				else $array_check[$key]['min']='0';
				if($array_tmp['1']!='') $array_check[$key]['max']=$array_tmp[1];
				else $array_check[$key]['max']='9999999999';
				}
			else
				{
				$array_check[$key]['min']=$value;
				$array_check[$key]['max']=$value;
				}
			}

		foreach($array_check as $key=>$value)
			{
			if(($user>=$value['min']) and ($user<=$value['max']))
				{
				$profile=$prof;
				break;
				}
			}

		# Exit if found
		if($profile!='') break;
		}
	}

# Default profile if not found
if($profile=='') $profile=$array_config_asterisk['Startup']['profile'];

# Return profile
return($profile);
}

##################################################################################################
# Aastra_get_status_index_asterisk(function)
#
# This function returns the status index for a given function.
#
# Parameters
#   @function		array
#
# Returns
#   integer		status index
###################################################################################################
function Aastra_get_status_index_Asterisk($function)
{
$array=array('dnd','cfwd','away','follow','daynight_0','daynight_1','daynight_2','daynight_3','daynight_4','daynight_5','daynight_6','daynight_7','daynight_8','daynight_9','logout');
if($function!='')
	{
	$array=array_flip($array);
	return($array[$function]);
	}
else return($array);
}

##################################################################################################
# Aastra_get_hints_asterisk(directory)
#
# This function completes the directory data with the dynamic status of the user.
#
# Parameters
#   @directory		array
#
# Returns
#   array			input array completed with the 'hint' status
###################################################################################################
function Aastra_get_hints_asterisk($directory)
{
# Connect to AGI
$as=new AGI_AsteriskManager();
$res=$as->connect();

# SIP show peer
$res=$as->Command('core show hints');

# Disconnect properly
$as->disconnect();

# Process answer
$line=split("\n", $res['data']);
foreach($line as $parameter)
	{
	if(strstr($parameter,'ext-local'))
		{
		$status=preg_split('/ /', $parameter,NULL,PREG_SPLIT_NO_EMPTY);
		$extension=split('@',$status[0]);
		if(isset($directory[$extension[0]]))
			{
			if($directory[$extension[0]])
				{
				$state=split(':',$status[3]);
				$directory[$extension[0]]['hint']=$state[1];
				}
			}
		}
	}

# Return answer
return($directory);
}

##################################################################################################
# Aastra_get_user_hint_asterisk(user)
#
# This function returns the dynamic status for a user.
#
# Parameters
#   @user		user
#
# Returns
#   string		dynamic status
###################################################################################################
function Aastra_get_user_hint_asterisk($user)
{
# Retrieve real user
$user=Aastra_get_userdevice_Asterisk($user);

# Initial status
$status='';

# Connect to AGI
$as = new AGI_AsteriskManager();
$res = $as->connect();

# SIP show peer
if(Aastra_compare_version_Asterisk('1.6')) $res = $as->Command('core show hint '.$user);
else $res = $as->Command('core show hints');

# Disconnect properly
$as->disconnect();

# Process answer
$line=split("\n", $res['data']);
foreach($line as $parameter)
	{
	if(Aastra_compare_version_Asterisk('1.6')) $test=strstr($parameter,'ext-local');
	else $test=(strstr($parameter,'ext-local') && strstr($parameter,$user));
	if($test)
		{
		$status=preg_split('/ /', $parameter,NULL,PREG_SPLIT_NO_EMPTY);
		$extension=split('@',$status[0]);
		if($extension[0]==$user)
			{
			$state=split(':',$status[3]);
			$status=$state[1];
			break;
			}
		}
	}

# Return answer
return($status);
}

###################################################################################################
# Aastra_format_callerid_Asterisk(cid,mode)
#
# This function formats a caller ID (XXXXX <YYY>).
#
# Parameters
#    @cid	Caller ID string
#    @mode	Mode formatting (1=basic, 2=advanced)
#
#
# Returns
#    Mode=basic	Formatted string
#    Mode=advanced	Array(name,number,dial)
###################################################################################################
function Aastra_format_callerid_Asterisk($cid,$mode='1')
{
# Remove the quotes
$cid=preg_replace('/\"/','',$cid);

# Retrieve Name and Number
$value=explode(" <",$cid,2);
if($value[1])
	{
	# Name and number
	$name=$value[0];
	preg_match("/<([0-9]+)/",$cid,$num);
	if($num[1]) $number=$num[1];
	else $number='';

	# Valid name
	if($name!=preg_replace('/[^(\x20-\x7F)]*/','',$name)) $name='';
	if($name=='Unknown') $name='';
	}
else
	{
	# Name or number, number?
	$number=$cid;
	if($number!=preg_replace('/[^(0-9)]*/','',$number)) $number='';

	# Name then?
	if($number=='')
		{
		$name=$cid;
		if($name!=preg_replace('/[^(\x20-\x7F)]*/','',$name)) $name='';
		if($name=='Unknown') $name='';
		}
	}

# Which mode?
if($mode=='1')
	{
	# Recreate cid for display
	if($name!='') $return=$name;
	else
		{
		if($number!='') $return=$number;
		else $return='';
		}
	}
else
	{
	# Split name/number
	if($name!='') $return['name']=$name;
	else $return['name']='';
	if($number!='')
		{
		$return['number']=$number;
		$return['dial']=True;
		}
	else
		{
		$return['number']='';
		$return['dial']=False;
		}
	}

# Return formatted CID
return($return);
}

function Aastra_propagate_daynight_Asterisk($device,$index)
{
Global $ASTERISK_LOCATION;

# Get the list of devices
$sip_array=Aastra_readINIfile($ASTERISK_LOCATION.'sip_additional.conf',';','=');
foreach($sip_array as $key=>$value)
	{
	if($value['callerid']!='') $array_device[]=$key;
	}

# Remove current device
$array_device=array_flip($array_device);
unset($array_device[$device]);
unset($array_device['']);
$array_device=array_flip($array_device);

# Remove the devices without the application
foreach($array_device as $key=>$value)
	{
	if(!Aastra_is_daynight_notify_allowed_Asterisk(Aastra_get_userdevice_Asterisk($value),$index)) unset($array_device[$key]);
	else
		{
		$data=Aastra_get_user_context($value,'notify');
		$data['daynight']='1';
		Aastra_save_user_context($value,'notify',$data);
		}
	}

# Send Notification
Aastra_send_SIP_notify_Asterisk('aastra-xml',$array_device);
}

function Aastra_get_polling_interval_Asterisk($header=NULL)
{
Global $AA_FREEPBX_USEDEVSTATE;

# 30 minutes by default
$polling='1800';

# DEVSTATE and Notify
if(($AA_FREEPBX_USEDEVSTATE) and (Aastra_is_sip_notify_supported($header))) $polling='86400';

# Return value
return($polling);
}

function Aastra_redirect_Asterisk($channel,$extension,$priority,$context)
{
# Connect to AGI
$asm=new AGI_AsteriskManager();
$asm->connect();

# Redirect the call
$res=$asm->redirect($channel,$extension,$priority,$context);

# Disconnect properly
$asm->disconnect();

# Return result
if($res['Response']=='Success') $return=True;
else $return=False;
return($return);
}

function Aastra_get_queues_Asterisk()
{
global $queue_details;

# Connect to AGI
$asm=new AGI_AsteriskManager();
$asm->connect();

# Add event handler to retrieve results of the query
$asm->add_event_handler('queueparams','Aastra_asm_event_queues_Asterisk');
$asm->add_event_handler('queuestatuscomplete','Aastra_asm_event_queues_Asterisk');

# Get all the queues
while(!$queue_details) $asm->QueueStatus();

# Open freePBX database
$db=Aastra_connect_freePBX_db_Asterisk();

# Process the list of queues
$index=0;
for($i=0;$i<sizeof($queue_details);$i++)
	{
	# Valid queue
	if(isset($queue_details[$i]['Queue']))
		{
	      	if(($queue_details[$i]['Queue']!='') && ($queue_details[$i]['Queue']!='default'))
			{
			# Collect information
			$queues[$index]['Queue']=$queue_details[$i]['Queue'];
			$queues[$index]['Calls']=$queue_details[$i]['Calls'];
			$queues[$index]['Holdtime']=$queue_details[$i]['Holdtime'];
			$queues[$index]['Completed']=$queue_details[$i]['Completed'];
			$queues[$index]['Abandoned']=$queue_details[$i]['Abandoned'];

			# Get description and password
		       $sql='SELECT descr,password FROM queues_config WHERE extension='.$queue_details[$i]['Queue'];
      			$result=$db->query($sql);
	      		list($queues[$index]['Description'],$queues[$index]['Password'])= $result->fetchRow(DB_FETCHMODE_ARRAY);

			# Next valid queue
			$index++;
      			}
		}
	}

# Disconnect properly
$asm->disconnect();

# Return
return($queues);
}

function Aastra_get_queue_members_Asterisk($queue)
{
global $queue_members;

# Connect to AGI
$asm=new AGI_AsteriskManager();
$asm->connect();

# Add event handlers to retrieve the answer
$asm->add_event_handler('queuestatuscomplete','Aastra_asm_event_queues_Asterisk');
$asm->add_event_handler('queuemember','Aastra_asm_event_agents_Asterisk');

# Retrieve info
$count=0;
while(!$queue_members)
	{
      	$asm->QueueStatus();
      	$count++;
      	if($count==10) break;
    	}

# Process info
$index=0;
if(count($queue_members)>0)
	{
	foreach($queue_members as $agent_a)
		{
		if($agent_a['Queue']==$queue)
			{
			if(preg_match('@^(?:Local/)?([^\@from\-internal/n]+)@i',$agent_a['Location'], $matches))
				{
				$members[$index]['agent']=$matches[1];
			      	if($agent_a['Paused']=='1') $members[$index]['paused']=True;
				else $members[$index]['paused']=False;
				$members[$index]['type']=$agent_a['Membership'];
				$index++;
				}
			}
		}
	}

# Disconnect properly
$asm->disconnect();

# Return Status
if(isset($members)) return($members);
else return(NULL);
}

function Aastra_get_queue_entries_Asterisk($queue)
{
global $queue_entries;

# Connect to AGI
$asm=new AGI_AsteriskManager();
$asm->connect();

# Add event handlers to retrieve the answer
$asm->add_event_handler('queuestatuscomplete','Aastra_asm_event_queues_asterisk');
$asm->add_event_handler('queueentry','Aastra_asm_event_entries_Asterisk');

# Retrieve info
$count=0;
while(!$queue_entries)
	{
      	$asm->QueueStatus();
      	$count++;
      	if($count==10) break;
    	}

# Process info
$index=0;
if(count($queue_entries)>0)
	{
	foreach($queue_entries as $entry)
		{
		if($entry['Queue']==$queue)
			{
			$entries[$index]=$entry;
			$index++;
			}
		}
	}

# Disconnect properly
$asm->disconnect();

# Return Status
if(isset($entries)) return($entries);
else return(NULL);
}

function Aastra_asm_event_queues_Asterisk($e,$parameters,$server,$port)
{
global $queue_details;
if(sizeof($parameters))
	{
      	if($parameters['Event']='QueueParams') $queue_details[]=$parameters;
    	}
}

function Aastra_asm_event_agents_Asterisk($e,$parameters,$server,$port)
{
global $queue_members;

if(sizeof($parameters))
	{
      	if($parameters['Event']='QueueMember') $queue_members[]=$parameters;
    	}
}

function Aastra_asm_event_entries_Asterisk($e,$parameters,$server,$port)
{
global $queue_entries;

if(sizeof($parameters))
	{
      	if($parameters['Event']='QueueEntry') $queue_entries[]=$parameters;
    	}
}

function Aastra_get_daynight_name_Asterisk($index)
{
# Return index by default;
$return=$index;

# Connect to freePBX database
$db=Aastra_connect_freePBX_db_Asterisk();

# Connected
if($db!=NULL)
	{
	$db->setFetchMode(DB_FETCHMODE_ASSOC);
	$query=$db->getAll('SELECT dest FROM daynight WHERE dmode="fc_description" and ext="'.$index.'"');
	if (!PEAR::isError($query))
		{
		if($query[0]['dest']!='') $return=$query[0]['dest'];
		}
	}

# Return name
return($return);
}

function Aastra_change_vm_password($user,$password)
{
Global $ASTERISK_LOCATION;
Global $AA_VM_CONTEXT;

# False by default
$return=False;

# Read the file
$lines=@file($ASTERISK_LOCATION.'voicemail.conf');
$section=NULL;
$dump=True;
foreach($lines as $line)
	{
	$line=rtrim($line);
	if(preg_match("/^\s*\[([a-z]*)\]\s*$/i", $line, $m)) $section=$m[1];
	if(($section==$AA_VM_CONTEXT) && preg_match("/^([0-9]*)\s*=>?\s*([0-9]*)\s*,(.*)$/",$line,$m))
		{
	      	if ($m[1]==$user)
			{
			$dump=False;
			$output[]=$m[1].' => '.$password.','.$m[3];
			$return=True;
			}
		}
	if($dump) $output[]=$line;
	else $dump=True;
	}

# Rewrite the file
if($return)
	{
	if ($fd = fopen($ASTERISK_LOCATION.'voicemail.conf','w'))
		{
		fwrite($fd,implode("\n",$output)."\n");
		fclose($fd);
		}
	else $return=False;
	}

# Reload the VM
if($return)
	{
	# Reload VM configuration
	$as=new AGI_AsteriskManager();
	$res=$as->connect();
	$as->Reload('app_voicemail');
	$as->disconnect();
	}

# Return result
return($return);
}

###################################################################################################
# Aastra_agent_context_Asterisk()
#
# This function return default agent context, from-internal is freePBX=2.5 from-queue if >=2.6.
#
# Parameters
#    None
#
# Returns
#    Boolean
###################################################################################################
function Aastra_agent_context_Asterisk()
{
# No by default
$return='from-internal';

# Get freePBX version
$array=Aastra_xml2array('/var/www/html/admin/modules/framework/module.xml');
if(substr($array['module']['version'],0,3)>='2.6') $return='from-queue';

# Return result
return($return);
}

###################################################################################################
# Aastra_get_number_mapping_Asterisk()
#
# This function returns the number mapping for all users.
#
# Parameters
#    None
#
# Returns
#    Array[]
#	name		callerid
#	number		extension
#	mailbox	mailbox context
#      status		presence status
###################################################################################################
function Aastra_get_number_mapping_Asterisk()
{
# Connect to AGI
$as=new AGI_AsteriskManager();
$res=$as->connect();

# Get the list of users
$raw=$as->database_show('AMPUSER');

# Purge the answers
foreach($raw as $key=>$value)
	{
	if(strstr($key,'info/cell')) $array_out[$value]=preg_replace(array('/\/AMPUSER\//','/\/info\/cell/'),array('',''),$key);
	if(strstr($key,'info/home')) $array_out[$value]=preg_replace(array('/\/AMPUSER\//','/\/info\/home/'),array('',''),$key);
	if(strstr($key,'info/other')) $array_out[$value]=preg_replace(array('/\/AMPUSER\//','/\/info\/other/'),array('',''),$key);
	}

# Disconnect properly
$as->disconnect();

# Return directory array
return($array_out);
}

###################################################################################################
# Aastra_set_devstate_Asterisk(device,state,asm)
#
# This function change the DEVSTATE for a custom device.
#
# Parameters
#    device		Custom device
#    state		UNKNOWN | NOT_INUSE | INUSE | BUSY | INVALID | UNAVAILABLE | RINGING | RINGINUSE | ONHOLD
#    asm		Manager connection if available
# Returns
#    None
###################################################################################################
function Aastra_set_devstate_Asterisk($device,$state,$asm=NULL)
{
# Connect to AGI if needed
if($asm==NULL)
	{
	$as=new AGI_AsteriskManager();
	$res=$as->connect();
	}
else $as=$asm;

if(Aastra_compare_version_Asterisk('1.6')) $res=$as->Command('devstate change '.$device.' '.$state);
else
	{
	$res = $as->Command('core set global DEVSTATE('.$device.') '.$state);
	$res = $as->Command('core set global DEVICE_STATE('.$device.') '.$state);
	}

# Disconnect properly
if($asm==NULL) $as->disconnect();
}

###################################################################################################
# Aastra_send_userevent_Asterisk(body,asm)
#
# This function generates a manager user event.
#
# Parameters
#    body		Custom device
#    asm		Manager connection if available
# Returns
#    None
###################################################################################################
function Aastra_send_userevent_Asterisk($event,$data,$asm=NULL)
{
# Connect to AGI if needed
if($asm==NULL)
	{
	$as=new AGI_AsteriskManager();
	$res=$as->connect();
	}
else $as=$asm;

# Send request
$res=$as->UserEvent($event,$data);

# Disconnect properly
if($asm==NULL) $as->disconnect();
}

###################################################################################################
# Aastra_status_config_Asterisk()
#
# This function reads the presence status configuration file.
#
# Parameters
#    None
# Returns
#    Array
###################################################################################################
function Aastra_status_config_Asterisk()
{
Global $language;

# Read config file
$array=Aastra_readINIfile(AASTRA_CONFIG_DIRECTORY.'status.conf','#','=');

# Do some cleanup
foreach($array as $key=>$value)
	{
	if(!is_numeric($key) or ($key<1) or ($key>98)) unset($array[$key]);
	else
		{
		if(($value['type']!='out') or ($value['type']!='available') or ($value['type']!='unavailable')) $array[$key]['type']='available';
		}
	}

# Add available
$array[AA_PRESENCE_AVAILABLE]=array(	'label'=>Aastra_get_label('Available',$language),
						'type'=>'available',
						'mnemonic'=>'_'
					);

# Add disconnected
$array[AA_PRESENCE_DISCONNECTED]=array(	'label'=>Aastra_get_label('Disconnected',$language),
						'type'=>'unavailable',
						'mnemonic'=>Aastra_get_label('D',$language)
					);

# Return array
return($array);
}


###################################################################################################
# Aastra_get_vm_password(user)
#
# This function reads the voice mail password of a user.
#
# Parameters
#    user	user extension
# Returns
#    Array
###################################################################################################
function Aastra_get_vm_password($extension)
{
Global $ASTERISK_LOCATION;
Global $AA_VM_CONTEXT;

$return[0]=false;
$return[1]='';
$lines=@file($ASTERISK_LOCATION.'voicemail.conf');
$section=NULL;
foreach($lines as $line)
	{
	$line=rtrim($line);
	if(preg_match("/^\s*\[([a-z]*)\]\s*$/i", $line, $m)) $section=$m[1];
	if(($section==$AA_VM_CONTEXT) && preg_match("/^([0-9]*)\s*=>?\s*([0-9]*)\s*,(.*)$/",$line,$m))
		{
       	if ($m[1]==$extension)
			{
			$return[0]=True;
			$return[1]=$m[2];
			break;
			}
		}
	}
return $return;
}
?>
