<?php
#############################################################################
# Asterisk Voice Mail access
#
# Copyright 2007-2010 Aastra Telecom Ltd
#
# Usage
# script.php?ext=USER&user=BOX
#
# Where
#    USER is the user extension on the platform
#    BOX is the Voice mail box ID
#
# Supported Aastra Phones (1.4.2 or better)
#    480i, 480iCT
#
#############################################################################

#############################################################################
# PHP customization for includes and warnings
#############################################################################
$os = strtolower(PHP_OS);
if(strpos($os, "win") === false) ini_set('include_path',ini_get('include_path').':include:../include');
else ini_set('include_path',ini_get('include_path').';include;../include');
error_reporting(E_ERROR | E_PARSE);

#############################################################################
# Includes
#############################################################################
require_once('AastraAsterisk.php');

#############################################################################
# Private functions
#############################################################################

function message_context($messages,$msg)
{
# Identify current message
$next='';
$previous='';
$found=False;

# Decode current Message
sscanf($msg,'%4s-%s',$msg_id,$msg_folder);

# Browse messages
foreach($messages as $key=>$m)
	{
	if(($found) && ($next=='')) 
		{
		$next=sprintf('%s-%s',$m['id'],$m['folder']);
		break;
		}
	if (($m['id']==$msg_id) && ($m['folder']==$msg_folder))
		{ 
		if(!$found)
			{
			$message=$m;
			$index=$key;
			$found=True;
			}
		}
	else
		{
		if(!$found) $previous=sprintf('%s-%s',$m['id'],$m['folder']);
		}
	}

# Return results
return(array('found'=>$found,'message'=>$message,'next'=>$next,'previous'=>$previous,'index'=>$index));
}

function format_date($value)
{
Global $AA_FORMAT_DT;

# Day of the week
$array_day=array(	'0'=>Aastra_get_label('Sun',$language),
			'1'=>Aastra_get_label('Mon',$language),
			'2'=>Aastra_get_label('Tue',$language),
			'3'=>Aastra_get_label('Wed',$language),
			'4'=>Aastra_get_label('Thu',$language),
			'5'=>Aastra_get_label('Fri',$language),
			'6'=>Aastra_get_label('Sat',$language)
		   );

# Apply local settings
if($AA_FORMAT_DT=='US') $date=strftime($array_day[strftime('%w',$value)].' '.'%m/%d',$value);
else $date=strftime($array_day[strftime('%w',$value)].' '.'%d/%m',$value);

# Return result
return($date);
}

function format_time($value)
{
Global $AA_FORMAT_DT;

# Apply local settings
if($AA_FORMAT_DT=='US') $time=strftime('%I:%M %p',$value);
else $time=strftime('%H:%M',$value);

# Return result
return($time);
}

#############################################################################
# Main code
#############################################################################
# Retrieve parameters
$ext=Aastra_getvar_safe('ext');
$user=Aastra_getvar_safe('user');
$action=Aastra_getvar_safe('action','list');
$page=Aastra_getvar_safe('page','1');
$orig_d=Aastra_getvar_safe('orig_d');
$orig_p=Aastra_getvar_safe('orig_p');
$msg=Aastra_getvar_safe('msg');
$cause=Aastra_getvar_safe('cause','end');
$last_msg=Aastra_getvar_safe('last_msg');
$dpage=Aastra_getvar_safe('dpage','1');
$dindex=Aastra_getvar_safe('dindex');
$set=Aastra_getvar_safe('set','1');

# Trace
Aastra_trace_call('vmail_2_asterisk','ext='.$ext.', user='.$user.' ,action='.$action.' ,msg='.$msg);

# Test User Agent
Aastra_test_phone_versions(array('1'=>'','2'=>'1.4.2.','3'=>'','4'=>'','5'=>''),'0');

# Get Language
$language=Aastra_get_language();

# Keep URL
$XML_SERVER.='?ext='.$ext.'&user='.$user;

# Compatibility 
$MaxLines=AASTRA_MAXLINES;

# Process play_exit
if($action=='play_exit')
	{
	# New message to move?
	if($cause!='delete')
		{
		sscanf($msg,'%4s-%s',$msg_id,$msg_folder);
		if($msg_folder=='INBOX')
			{
			# Move message
			$new_msg=Aastra_move_to_folder_Asterisk($user,$msg_id,'INBOX','Old');

			# Move succeeded, keep new message
			if($new_msg!='') $msg=$new_msg;
			}
		}

	# Next action
	$action=$orig_p;
	if($orig_p=='list') $last_msg=$msg;
	}

# Process delete
if($action=='del_message')
	{
	# Retrieve messages
	if(!isset($messages)) $messages=Aastra_get_vmessages_Asterisk($user);

	# Retrieve context
	$message=message_context($messages,$msg);

	# Message found
	if($message['found'])
		{
		# Delete message
		sscanf($msg,'%4s-%s',$msg_id,$msg_folder);
		if(Aastra_delete_message_Asterisk($user,$msg_folder,$msg_id))
			{
			# Delete in the cached messages
			unset($messages[$message['index']]);
			$messages=array_values($messages);

			# Potential next message
			$next='';
			if($message['next']!='') $next=$message['next'];
			else
				{
				if($message['previous']!='') $next=$message['previous'];
				}

			# Default next action
			$msg=$next;
			$action=$orig_d;
			
			# Last message
			if($action=='list') $last_msg=$msg;
			}
		else
			{
			# Error message
			$err_title=sprintf(Aastra_get_label('Mailbox %s',$language),$user);
			$err_text=Aastra_get_label('Failed to delete Message.',$language);
			if($orig_d=='playing') $orig_d=$orig_p;
			if($orig_d=='list') $last_msg=$msg;
			$err_key=array('6',Aastra_get_label('Continue',$language),$XML_SERVER.'&action='.$orig_d.'&msg='.msg);
			$action='error';
			}
		}
	else
		{
		# Error message
		$err_title=sprintf(Aastra_get_label('Mailbox %s',$language),$user);
		$err_text=Aastra_get_label('Failed to delete Message.',$language);
		if($orig_d=='playing') $orig_d=$orig_p;
		if($orig_d=='list') $last_msg=$msg;
		$err_key=array('6',Aastra_get_label('Continue',$language),$XML_SERVER.'&action='.$orig_d.'&msg='.$msg);
		$action='error';
		}
	
	# Cleanup
	unset($message);
	}

# Pre-process action
switch($action)
	{
	# List/Detail
	case 'list':
	case 'detail':
	case 'playing':
		# Retrieve messages
		if(!isset($messages)) $messages=Aastra_get_vmessages_Asterisk($user);
		$message_count=count($messages);

		# No message?
		if($message_count==0) $action='empty_box';
		else
			{
			# Find first message if needed	
			if(($action=='detail') and ($msg=='')) $msg=sprintf('%s-%s',$messages[0]['id'],$messages[0]['folder']);
			}
		break;

	# Delete greeting messages
	case 'del_greetings':
		# Delete Greetings message
		switch($msg)
			{
			case 'name':
				Aastra_delete_name_message_Asterisk($user);
				break;
			case 'unavail':
				Aastra_delete_unavail_message_Asterisk($user);
				break;
			case 'busy':
				Aastra_delete_busy_message_Asterisk($user);
				break;
			case 'temp':
				Aastra_delete_temp_message_Asterisk($user);
				break;
			case 'ALL':
				Aastra_delete_name_message_Asterisk($user);
				Aastra_delete_unavail_message_Asterisk($user);
				Aastra_delete_busy_message_Asterisk($user);
				Aastra_delete_temp_message_Asterisk($user);
				break;
			}

		# Back to options
		$action='options';
		break;

	# Toggle auto-answer/auto-login
	case 'autoanswer':
	case 'autologin':
		# Retrieve context
		$vmail_options=Aastra_get_user_context($ext,'vmail-options');

		# Toggle option
		if($action=='autologin')
			{
			if($vmail_options['auto_login']=='1') $vmail_options['auto_login']='0';
			else  $vmail_options['auto_login']='1';
			}
		else
			{
			if(($vmail_options['auto_answer']=='')  or ($vmail_options['auto_answer']=='1')) $vmail_options['auto_answer']='0';
			else  $vmail_options['auto_answer']='1';
			}

		# Save context
		Aastra_save_user_context($ext,'vmail-options',$vmail_options);

		# Next action
		$action='options';
		break;

	# Update password
	case 'set_password':
		# Check password
		if((strlen($paused)!=0) and is_numeric($paused))
			{
			Aastra_change_vm_password($user,$paused);
			$action='error';
			$err_title=Aastra_get_label('Password change',$language);
			$err_text=Aastra_get_label('Password updated',$language);
			$err_key=array('6',Aastra_get_label('Continue',$language),$XML_SERVER.'&action=options&origin='.$origin);
			}
		else $action='chg_password';
		break;
	}

# Process action
switch($action)
	{
	# List
	case 'list':
		# Get number of pages
		$last_page=intval($message_count/$MaxLines);
		if(($message_count-$last_page*$MaxLines) != 0) $last_page++;

		# Count new messages
		$new_count=0;
		foreach($messages as $message)
			{
			if($message['folder']=='INBOX') $new_count++;
			}

		# Create the Textmenu
		require_once('AastraIPPhoneTextMenu.class.php');
		$object=new AastraIPPhoneTextMenu();
		$object->setDestroyOnExit();
		$object->setStyle('none');
		$object->setTitle(sprintf('%s (%d)',$user,$new_count));

		# Position cursor on last input
		if($last_msg!='')
			{
			$index=1;
			sscanf($last_msg,'%4s-%s',$msg_id,$msg_folder);
			foreach ($messages as $item)
				{
			 	if(($msg_id==$item['id']) && ($msg_folder==$item['folder'])) 
					{
					$page=intval($index/$MaxLines);
					if(($index-$page*$MaxLines) != 0) $page++;
					$object->setDefaultIndex($index-(($page-1)*$MaxLines));
					break;
					}
				else $index++;
				}
			}
		$index=0;

		# Set menu Item base
		$object->setBase($XML_SERVER.'&action=detail');

		# List messages
		foreach($messages as $item)
			{
			# Message to be displayed?
			if(($index>=($page-1)*$MaxLines) && ($index<$page*$MaxLines ))
				{
				# Message ID
				$msg=sprintf('%s-%s',$item['id'],$item['folder']);

				# Caller ID
				$cid=Aastra_format_callerid_Asterisk($item['callerid']);
				if($cid=='') $cid=Aastra_get_label('Unknown',$language);
				if($item['folder']=='INBOX') $cid='[] '.$cid;
				else $cid='-- '.$cid;

				# Compute icon
		    		$object->addEntry($cid,'&msg='.$msg,'&msg='.$msg,$icon);
				}

			# Next Message
			$index++;
			}

		# Reset menu item base
		$object->resetBase();

		# More than one page?
		if($last_page!=1)
			{
			# Menu_set
			if($set=='1')
				{
				# Page 1
				$object->addSoftkey('1',Aastra_get_label('Play',$language),$XML_SERVER.'&action=play_message&orig_p=list&set=1');
				if($page!=1) $object->addSoftkey('2',Aastra_get_label('Previous',$language),$XML_SERVER.'&page='.($page-1).'&set=1');
				$object->addSoftkey('3',Aastra_get_label('Details',$language),'SoftKey:Select');
				$object->addSoftkey('4',Aastra_get_label('Delete',$language),$XML_SERVER.'&orig_d=list&action=del_message&set=1');
				if($page!=$last_page) $object->addSoftkey('5',Aastra_get_label('Next',$language),$XML_SERVER.'&page='.($page+1).'&set=1');
				$object->addSoftkey('6',Aastra_get_label('More',$language),$XML_SERVER.'&page='.$page.'&set=2');
				}
			else
				{
				# Page 2
				$object->addSoftkey('1',Aastra_get_label('Options',$language),$XML_SERVER.'&action=options&page='.$page.'&set=2');
				if($page!=1) $object->addSoftkey('2',Aastra_get_label('Previous',$language),$XML_SERVER.'&page='.($page-1).'&set=2');
				$object->addsoftkey('3',Aastra_get_label('Chg User',$language),$XML_SERVER_PATH.'vmail.php?ext='.$ext);
				$object->addSoftkey('4',Aastra_get_label('Exit',$language),'SoftKey:Exit');
				if($page!=$last_page) $object->addSoftkey('5',Aastra_get_label('Next',$language),$XML_SERVER.'&page='.($page+1).'&set=2');
				$object->addSoftkey('6',Aastra_get_label('More',$language),$XML_SERVER.'&page='.$page.'&set=1');
				}
			}
		else
			{
			# All on one page of keys
			$object->addSoftkey('1',Aastra_get_label('Play',$language),$XML_SERVER.'&action=play_message&orig_p=list');
			$object->addSoftkey('2',Aastra_get_label('Details',$language),'SoftKey:Select');
			$object->addSoftkey('3',Aastra_get_label('Delete',$language),$XML_SERVER.'&orig_d=list&action=del_message');
			$object->addSoftkey('4',Aastra_get_label('Options',$language),$XML_SERVER.'&action=options');
			$object->addsoftkey('5',Aastra_get_label('Chg User',$language),$XML_SERVER_PATH.'vmail.php?ext='.$ext);
			$object->addSoftkey('6',Aastra_get_label('Exit',$language),'SoftKey:Exit');
			}
		break;

	# Message zoom
	case 'detail':
	case 'playing':
		# Retrieve current message
		$message=message_context($messages,$msg);

		# Message not found
		if(!$message['found'])
			{
			# Error message
			$err_title=sprintf(Aastra_get_label('Mailbox %s',$language),$user);
			$err_text=Aastra_get_label('Failed to retrieve Message.',$language);
			$err_key=array('6',Aastra_get_label('Continue',$language),$XML_SERVER.'&action=detail');
			$action='error';
			}
		else
			{
			# Format Caller ID, date, time and duration
			$cid=Aastra_format_callerid_Asterisk($message['message']['callerid'],'2');
			if($cid['name']=='') $cid['name']=Aastra_get_label('Unknown',$language);
			if($cid['number']=='') $cid['number']=Aastra_get_label('Unknown',$language);
			$date=format_date($message['message']['origtime']);
			$time=format_time($message['message']['origtime']);
			$sec=$message['message']['duration']%60;
			$min=($message['message']['duration']-$sec)/60;
			$duration=sprintf('%02d:%02d',$min,$sec);

			# Text screen
			require_once('AastraIPPhoneTextScreen.class.php');
			$object=new AastraIPPhoneTextScreen();
			$object->setDestroyOnExit();

			# Title
			if($action=='detail')
				{
				if($message['message']['folder']=='INBOX') $text=Aastra_get_label('NEW',$language);
				else $text=Aastra_get_label('READ',$language);
				}
			else 
				{
				$object->setTimeout('600');
				$text=Aastra_get_label('PLAYING',$language);
				}
			$object->setTitle(sprintf('%s-%03d',$text,$message['message']['number']+1));

			# Text
			if($message['message']['folder']=='INBOX') $text=sprintf(Aastra_get_label('New message left by %s (%s) on %s at %s. Duration is %s.',$language),$cid['name'],$cid['number'],$date,$time,$duration);
			else $text=sprintf(Aastra_get_label('Saved message left by %s (%s) on %s at %s. Duration is %s.',$language),$cid['name'],$cid['number'],$date,$time,$duration);
	     		$object->setText($text);

			# Softkeys
			if($action=='detail')
				{
				# All keys on one page
				$object->addSoftkey('1',Aastra_get_label('Play',$language),$XML_SERVER.'&action=play_message&orig_p=detail&msg='.$msg);
				$object->addSoftkey('3',Aastra_get_label('Forward',$language),$XML_SERVER.'&action=forward&msg='.$msg);
				$object->addSoftkey('4',Aastra_get_label('Delete',$language),$XML_SERVER.'&orig_d=detail&action=del_message&msg='.$msg);
				$object->addSoftkey('6',Aastra_get_label('Back',$language),$XML_SERVER.'&action=list&last_msg='.$msg);
				}
			}
		break;

	# Empty box
	case 'empty_box':
		# New text screen
		require_once('AastraIPPhoneTextScreen.class.php');
		$object=new AastraIPPhoneTextScreen();
		$object->setDestroyOnExit();
		$object->setTitle(sprintf(Aastra_get_label('Mailbox %s',$language),$user));
		$object->setText(Aastra_get_label('No Voice Message.',$language));
		$object->addSoftkey('1',Aastra_get_label('Options',$language),$XML_SERVER.'&action=options');
		$object->addsoftkey('5',Aastra_get_label('Chg User',$language),$XML_SERVER_PATH.'vmail.php?ext='.$ext);
		$object->addSoftkey('6',Aastra_get_label('Exit',$language),'SoftKey:Exit');
		break;

	# Play wave file
	case 'play_message':
		# Decode current Message
		sscanf($msg,'%4s-%s',$msg_id,$msg_folder);

		# Save session
		$array=array(	'uri_incoming'=>$XML_SERVER.'&action=playing&orig_p='.$orig_p.'&msg='.$msg,
				'uri_onhook'=>$XML_SERVER.'&action=play_exit&orig_p='.$orig_p.'&msg='.$msg
				);
		Aastra_save_session('vmail','600',$array,$ext);

		# Return empty object
		require_once('AastraIPPhoneExecute.class.php');
		$object=new AastraIPPhoneExecute();
		$object->addEntry('');
		$object->output(True);

		# Asterisk Call using AGI
		$vmail_options=Aastra_get_user_context($ext,'vmail-options');
		if($vmail_options['auto_answer']=='') 
			{
			$vmail_options['auto_answer']='1';
			Aastra_save_user_context($ext,'vmail-options',$vmail_options);
			}
		if($vmail_options['auto_answer']=='1') Aastra_originate_Asterisk('SIP/'.$ext,'default','9999',1,'','VoiceMail <'.$msg_id.'>','__SIPADDHEADER=Alert-Info: \;info=alert-autoanswer','','ControlPlayback',array($ASTERISK_SPOOLDIR.'voicemail/default/'.$user.'/'.$msg_folder.'/msg'.$msg_id,'2000','6','4','0','5','1'));
		else Aastra_originate_Asterisk('SIP/'.$ext,'default','9999',1,'','VoiceMail <'.$msg_id.'>','','','ControlPlayback',array($ASTERISK_SPOOLDIR.'voicemail/default/'.$user.'/'.$msg_folder.'/msg'.$msg_id,'2000','6','4','0','5','1'));
		exit;
		break;

	# Stop/Delete while playing
	case 'play_stop':
	case 'play_del':
		# Update uri_onhook if delete needed
		if($action=='play_del')
			{
			$array=Aastra_read_session('vmail',$ext);
			$array['uri_onhook'].='&cause=delete';
			Aastra_save_session('vmail','600',$array,$ext);
			}

		# Send a 0 to stop playing
		require_once('AastraIPPhoneExecute.class.php');
		$object=new AastraIPPhoneExecute();
		$object->addEntry('Key:Goodbye');
		$object->addEntry('Key:Goodbye');
		break;

	# Option menu
	case 'options':
		# Delete ALL
		$del_greetings==False;

		# Get current status
		$status=Aastra_get_greeting_status_Asterisk($user);

		# Display choice
		require_once('AastraIPPhoneTextMenu.class.php');
		$object=new AastraIPPhoneTextMenu();
		$object->setDestroyOnExit();
		$object->setTitle(Aastra_get_label('Options',$language));
		$object->setStyle('none');

		# Password change
		$object->addEntry(Aastra_get_label('Change Password',$language),$XML_SERVER.'&action=chg_password&origin='.$origin);

		# Auto-answer
		$vmail_options=Aastra_get_user_context($ext,'vmail-options');
		if($vmail_options['auto_answer']=='') 
			{
			$vmail_options['auto_answer']='1';
			Aastra_save_user_context($ext,'vmail-options',$vmail_options);
			}
		if($vmail_options['auto_answer']=='1') $object->addEntry(Aastra_get_label('Disable Auto-answer',$language),$XML_SERVER.'&action=autoanswer');
		else $object->addEntry(Aastra_get_label('Enable Auto-answer',$language),$XML_SERVER.'&action=autoanswer');

		# Auto-login
		if($vmail_options['auto_login']=='1') $object->addEntry(Aastra_get_label('Disable Auto-login',$language),$XML_SERVER.'&action=autologin');
		else $object->addEntry(Aastra_get_label('Enable Auto-login',$language),$XML_SERVER.'&action=autologin');

		# Greetings
		if($status['name']) 
			{
			$object->addEntry(Aastra_get_label('Play Name',$language),$XML_SERVER.'&action=play_greetings&msg=name');
			$object->addEntry(Aastra_get_label('Modify Name',$language),$XML_SERVER.'&action=rec_greetings&msg=name');
			$object->addEntry(Aastra_get_label('Delete Name',$language),$XML_SERVER.'&action=del_greetings&msg=name');
			$del_greetings=True;
			}
		else $object->addEntry(Aastra_get_label('Record Name',$language),$XML_SERVER.'&action=rec_greetings&msg=name');
		if($status['unavail']) 
			{
			$object->addEntry(Aastra_get_label('Play Unavailable',$language),$XML_SERVER.'&action=play_greetings&msg=unavail');
			$object->addEntry(Aastra_get_label('Modify Unavailable',$language),$XML_SERVER.'&action=rec_greetings&msg=unavail');
			$object->addEntry(Aastra_get_label('Delete Unavailable',$language),$XML_SERVER.'&action=del_greetings&msg=unavail');
			$del_greetings=True;
			}
		else $object->addEntry(Aastra_get_label('Record Unavailable',$language),$XML_SERVER.'&action=rec_greetings&msg=unavail');
		if($status['busy']) 
			{
			$object->addEntry(Aastra_get_label('Play Busy',$language),$XML_SERVER.'&action=play_greetings&msg=busy');
			$object->addEntry(Aastra_get_label('Modify Busy',$language),$XML_SERVER.'&action=rec_greetings&msg=busy');
			$object->addEntry(Aastra_get_label('Delete Busy',$language),$XML_SERVER.'&action=del_greetings&msg=busy');
			$del_greetings=True;
			}
		else $object->addEntry(Aastra_get_label('Record Busy',$language),$XML_SERVER.'&action=rec_greetings&msg=busy');
		if($status['temp']) 
			{
			$object->addEntry(Aastra_get_label('Play Temporary',$language),$XML_SERVER.'&action=play_greetings&msg=temp');
			$object->addEntry(Aastra_get_label('Modify Temporary',$language),$XML_SERVER.'&action=rec_greetings&msg=temp');
			$object->addEntry(Aastra_get_label('Delete Temporary',$language),$XML_SERVER.'&action=del_greetings&msg=temp');
			$del_greetings=True;
			}
		else $object->addEntry(Aastra_get_label('Record Temporary',$language),$XML_SERVER.'&action=rec_greetings&msg=temp');
		if($del_greetings) $object->addEntry(Aastra_get_label('Reset greetings',$language),$XML_SERVER.'&action=del_greetings&msg=ALL');

		# Auto-answer
		$vmail_options=Aastra_get_user_context($ext,'vmail-options');
		if($vmail_options['auto_answer']=='') 
			{
			$vmail_options['auto_answer']='1';
			Aastra_save_user_context($ext,'vmail-options',$vmail_options);
			}
		if($vmail_options['auto_answer']=='1') $object->addEntry(Aastra_get_label('Disable Auto-answer',$language),$XML_SERVER.'&action=autoanswer');
		else $object->addEntry(Aastra_get_label('Enable Auto-answer',$language),$XML_SERVER.'&action=autoanswer');

		# Auto-login
		if($vmail_options['auto_login']=='1') $object->addEntry(Aastra_get_label('Disable Auto-login',$language),$XML_SERVER.'&action=autologin');
		else $object->addEntry(Aastra_get_label('Enable Auto-login',$language),$XML_SERVER.'&action=autologin');

		# Softkeys
		$object->addSoftkey('1',Aastra_get_label('Select',$language),'SoftKey:Select');
		$object->addSoftkey('5',Aastra_get_label('Back',$language),$XML_SERVER);
		$object->addSoftkey('6',Aastra_get_label('Exit',$language),'SoftKey:Exit');

		# Cancel Action
		$object->setCancelAction($XML_SERVER);
		break;

	# Change password
	case 'chg_password':
		# Input Screen
		require_once('AastraIPPhoneInputScreen.class.php');
		$object=new AastraIPPhoneInputScreen();
		$object->setDestroyOnExit();

		# Input number
		$object->setTitle(Aastra_get_label('Password Change',$language));
		$object->setType('number');
		$object->setPassword();
		$object->setPrompt(Aastra_get_label('New password',$language));
		$object->setParameter('paused');
		$object->setURL($XML_SERVER.'&action=set_password&origin='.$origin);

		# Softkeys
		$object->addSoftkey('1',Aastra_get_label('Backspace',$language),'SoftKey:BackSpace');
		$object->addSoftkey('5',Aastra_get_label('Submit',$language),'SoftKey:Submit');
		$object->addSoftkey('6',Aastra_get_label('Cancel',$language),$XML_SERVER.'&action=options&origin='.$origin);
		break;

	# Play/Record Greetings
	case 'play_greetings':
	case 'rec_greetings':
		# Action
		if($action=='play_greetings') $action='play';
		else $action='record';

		# Retrieve context
		$context=$action.'-'.$msg.'-vm';

		# Save session
		$array=array(	'uri_incoming'=>$XML_SERVER.'&action=disp_greetings&msg='.$msg.'&cause='.$action,
				'uri_onhook'=>$XML_SERVER.'&action=options'
				);
		Aastra_save_session('vmail','600',$array,$ext);

		# Return empty object
		require_once('AastraIPPhoneExecute.class.php');
		$object=new AastraIPPhoneExecute();
		$object->addEntry('');
		$object->output(True);

		# Launch the call
		$vmail_options=Aastra_get_user_context($ext,'vmail-options');
		if($vmail_options['auto_answer']=='') 
			{
			$vmail_options['auto_answer']='1';
			Aastra_save_user_context($ext,'vmail-options',$vmail_options);
			}
		if($vmail_options['auto_answer']=='1') Aastra_originate_Asterisk('SIP/'.Aastra_get_userdevice_Asterisk($ext),'s',$context,1,'','VoiceMail <9999>',array('_USER='.Aastra_get_userdevice_Asterisk($user),'__SIPADDHEADER=Alert-Info: \;info=alert-autoanswer'));
		else Aastra_originate_Asterisk('SIP/'.Aastra_get_userdevice_Asterisk($ext),'s',$context,1,'','VoiceMail <9999>','_USER='.Aastra_get_userdevice_Asterisk($user));
		break;

	# Display greetings
	case 'disp_greetings':
		# Greeting label
		switch($msg)
			{
			case 'name':
				$greeting=Aastra_get_label('Name',$language);
				break;
			case 'unavail':
				$greeting=Aastra_get_label('Unavailable Greeting',$language);
				break;
			case 'busy':
				$greeting=Aastra_get_label('Busy Greeting',$language);
				break;
			case 'temp':
				$greeting=Aastra_get_label('Temporary Greeting',$language);
				break;
			}
	
		# New text screen
		require_once('AastraIPPhoneTextScreen.class.php');
		$object=new AastraIPPhoneTextScreen();
		$object->setDestroyOnExit();
		if($cause=='record') $object->setTitle(Aastra_get_label('Recording...',$language));
		else $object->setTitle(Aastra_get_label('Playing...',$language));
		$object->setText($greeting);
		$object->setTimeout('300');
		break;

	# Forward
	case 'forward':
	case 'forward2':
	case 'dselect':
	case 'reverse':
		# Retrieve directory
		if($action=='forward')
			{
			# Retrieve list of users
			$directory=Aastra_get_user_directory_Asterisk();

			# Sort Directory by name
			Aastra_natsort2d($directory,'name');

			# Complete directory
			foreach ($directory as $key=>$value) 
				{
				if($value['number']==$user) unset($directory[$key]);
				else 
					{
					if($value['voicemail']=='default') $value['select']=False;
					else unset($directory[$key]);
					}
				}

			# Save the session
			$array=array('directory' => base64_encode(serialize($directory)));
			Aastra_save_session('vmail','600',$array,$ext);
			}
		else
			{
			# Retrieve cached data
			$array=Aastra_read_session('vmail',$ext);
			$directory=unserialize(base64_decode($array['directory']));
			}

		# At least one name
		$index=count($directory);
		if($index>0)
			{
			# Retrieve last page
			$last=intval($index/$MaxLines);
			if(($index-$last*$MaxLines)!=0) $last++;

			# Select/Unselect
			if($action=='dselect')
				{
				if($directory[$dindex]['select']) $directory[$dindex]['select']=False;
				else $directory[$dindex]['select']=True;
				}

			# Reverse
			if($action=='reverse')
				{
				foreach ($directory as $key=>$value) 
					{
					if($value['select']) $directory[$key]['select']=False;
					else $directory[$key]['select']=True;
					}
				}

			# Save the session
			$array=array('directory' => base64_encode(serialize($directory)));
			Aastra_save_session('vmail','600',$array,$ext);

			# Display Page
			require_once('AastraIPPhoneTextMenu.class.php');
			$object=new AastraIPPhoneTextMenu();
			$object->setDestroyOnExit();
			$object->setStyle('none');
			if($last!=1) $object->setTitle(sprintf(Aastra_get_label('Msg Forward (%s/%s)',$language),$dpage,$last));
			else $object->setTitle(Aastra_get_label('Message Forward',$language));
			$index=1;
			$rank=1;
			$submit=False;

			# Display items
			foreach ($directory as $key=>$value) 
				{
				if(($index>=(($dpage-1)*$MaxLines+1)) and ($index<=$dpage*$MaxLines)) 
					{
					if($value['select']) $submit=True;
					if($value['select']) $object->addEntry('X '.$value['name'],$XML_SERVER.'&action=dselect&msg='.$msg.'&dpage='.$dpage.'&dindex='.$key);
					else $object->addEntry('- '.$value['name'],$XML_SERVER.'&action=dselect&msg='.$msg.'&dpage='.$dpage.'&dindex='.$key);
					if($key==$dindex) $object->setDefaultIndex($rank);
					$rank++;
					}
				$index++;
				}

			# Softkeys
			$object->addSoftkey('1',Aastra_get_label('Select',$language),'SoftKey:Select');
			if($dpage!='1') $object->addSoftkey('2',Aastra_get_label('Previous',$language),$XML_SERVER.'&action=forward2&msg='.$msg.'&dpage='.($dpage-1));
			$object->addSoftkey('3',Aastra_get_label('Reverse',$language),$XML_SERVER.'&action=reverse&msg='.$msg.'&dpage='.$dpage);
			if($submit) $object->addSoftkey('4',Aastra_get_label('Submit',$language),$XML_SERVER.'&action=dsubmit&msg='.$msg);
			if($dpage!=$last) $object->addSoftkey('5',Aastra_get_label('Next',$language),$XML_SERVER.'&action=forward2&msg='.$msg.'&dpage='.($dpage+1));
			$object->addSoftkey('6',Aastra_get_label('Cancel',$language),$XML_SERVER.'&action=detail&msg='.$msg);
			}
		else
			{
			# Display error message
			$action='error';
			$err_title=Aastra_get_label('Message forward',$language);
			$err_text=Aastra_get_label('No other user with voicemail configured on the platform.',$language);
			$err_key=array('6',Aastra_get_label('Continue',$language),$XML_SERVER.'&action=detail&msg='.$msg);
			}
		break;

	# Submit forward
	case 'dsubmit':
		# Decode current Message
		sscanf($msg,'%4s-%s',$msg_id,$msg_folder);

		# Retrieve cached data
		$array=Aastra_read_session('vmail',$ext);
		$directory=unserialize(base64_decode($array['directory']));

		# Forward to all selected
		foreach($directory as $key=>$value) 
			{
			if($value['select']) Aastra_forward_to_user_Asterisk($user,$value['number'],$msg_id,$msg_folder,'INBOX');
			}

		# Display message
		$action='error';
		$err_title=Aastra_get_label('Message forward',$language);
		$err_text=Aastra_get_label('Message forwarded to the selected user(s).',$language);
		$err_key=array('6',Aastra_get_label('Continue',$language),$XML_SERVER.'&action=detail&msg='.$msg);
		break;
	}

# Post-process action
switch($action)
	{
	# Error message
	case 'error':
		# New text screen
		require_once('AastraIPPhoneTextScreen.class.php');
		$object=new AastraIPPhoneTextScreen();
		$object->setDestroyOnExit();
		$object->setTitle($err_title);
		$object->setText($err_text);
		$object->addSoftkey($err_key[0],$err_key[1],$err_key[2]);
		break;
	}

# Output XML object
$object->output();
exit();
?>

