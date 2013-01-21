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
# Supported Aastra Phones (3.0.1 or better)
#    6739i
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
$origin=Aastra_getvar_safe('origin');
$orig_d=Aastra_getvar_safe('orig_d');
$orig_p=Aastra_getvar_safe('orig_p');
$msg=Aastra_getvar_safe('msg');
$greet=Aastra_getvar_safe('greet');
$cause=Aastra_getvar_safe('cause','end');
$paused=Aastra_getvar_safe('paused','no');
$last_msg=Aastra_getvar_safe('last_msg');
$dpage=Aastra_getvar_safe('dpage','1');
$dindex=Aastra_getvar_safe('dindex');

# Trace
Aastra_trace_call('vmail_5_asterisk','ext='.$ext.', user='.$user.' ,action='.$action);

# Test User Agent
Aastra_test_phone_versions(array('1'=>'','2'=>'','3'=>'','4'=>'','5'=>'3.0.1.'),'0');

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

	# Depending on cause
	switch($cause)
		{
		# Delete
		case 'delete':
			# Change action
			$action='del_message';
			$orig_d='playing';
			break;

		# Stop
		case 'end':
			$action=$orig_p;
			if($orig_p=='list') $last_msg=$msg;
			break;

		# Others
		default:
			Aastra_debug('Unexpected cause for play_exit, cause='.$cause);
			break;
		}
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
			if($orig_d!='playing') $action=$orig_d;
			else $action=$orig_p;

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
			$err_key=array('10',Aastra_get_label('Continue',$language),$XML_SERVER.'&action='.$orig_d.'&msg='.msg);
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
		$err_key=array('10',Aastra_get_label('Continue',$language),$XML_SERVER.'&action='.$orig_d.'&msg='.$msg);
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

	# Callback
	case 'callback':
		# Retrieve messages if origin is List
		if(($origin=='list') and (!isset($messages))) $messages=Aastra_get_vmessages_Asterisk($user);
		break;

	# Delete greeting messages
	case 'del_greetings':
		# Delete Greetings message
		switch($greet)
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
	case 'autologin':
		# Toggle auto-answer
		$vmail_options=Aastra_get_user_context($ext,'vmail-options');
		if($vmail_options['auto_login']=='1') $vmail_options['auto_login']='0';
		else  $vmail_options['auto_login']='1';
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
			$action='flash';
			$flash_next=$XML_SERVER.'&action=options&origin='.$origin;
			$flash_text=Aastra_get_label('Password updated',$language);
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
		if($last_page==1) $object->setTitle(sprintf(Aastra_get_label('Mailbox %s (%d)',$language),$user,$new_count));
		else $object->setTitle(sprintf(Aastra_get_label('Mailbox %s (%d) Page %d/%d',$language),$user,$new_count,$page,$last_page));

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
		$object->setBase($XML_SERVER.'&action=play_message&orig_p=list');

		# List messages
		foreach($messages as $item)
			{
			# Message to be displayed?
			if(($index>=($page-1)*$MaxLines) && ($index<$page*$MaxLines ))
				{
				# Message ID
				$msg=sprintf('%s-%s',$item['id'],$item['folder']);

				# Caller ID
				$cid=Aastra_format_callerid_Asterisk($item['callerid'],'2');
				$name=$cid['name'];
				$number=$cid['number'];
				if($name=='') $name=Aastra_get_label('Unknown',$language);
				if($number=='') $number=Aastra_get_label('Unknown',$language);
				if($item['folder']=='INBOX') $icon='(N) ';
				else $icon='--- ';
				$cid=$icon.$cid;
				$sec=$item['duration']%60;
				$min=($item['duration']-$sec)/60;
				$duration=sprintf('%02d:%02d',$min,$sec);

				# Display on 2 lines with icons
	  			$date=format_date($item['origtime']);
		  		$time=format_time($item['origtime']);
				$display=array('0'=>$cid,'1'=>sprintf('%s %s',$date,$time),'2'=>2,'3'=>' ');

				# Add element
				if(Aastra_is_icons_supported())
					{
					if($item['folder']=='INBOX') $icon='1';
					else $icon='2';
					$object->addEntry($name.' ('.$number.') '.$date.' '.$time.' ('.$duration.')','&msg='.$msg,'&msg='.$msg,$icon);
					}
				else $object->addEntry($icon.$name.' ('.$number.') '.$date.' '.$time.' ('.$duration.')','&msg='.$msg,'&msg='.$msg);
				}

			# Next Message
			$index++;
			}

		# Reset menu item base
		$object->resetBase();

		# Softkeys
		$object->addSoftkey('1',Aastra_get_label('Play',$language),$XML_SERVER.'&action=play_message&orig_p=list');
		$object->addSoftkey('2',Aastra_get_label('Options',$language),$XML_SERVER.'&action=options&origin=list');
		if($page!=1) $object->addSoftkey('3',Aastra_get_label('Previous Page',$language),$XML_SERVER.'&page='.($page-1));
		$object->addSoftkey('4',Aastra_get_label('Call back',$language),$XML_SERVER.'&action=callback&origin=list');
		$object->addSoftkey('5',Aastra_get_label('Forward',$language),$XML_SERVER.'&action=forward&origin=list');
		$object->addSoftkey('6',Aastra_get_label('Delete',$language),$XML_SERVER.'&orig_d=list&action=del_message');
		$object->addsoftkey('7',Aastra_get_label('Change User',$language),$XML_SERVER_PATH.'vmail.php?ext='.$ext);
		if($page!=$last_page) $object->addSoftkey('8',Aastra_get_label('Next Page',$language),$XML_SERVER.'&page='.($page+1));
		$object->addsoftkey('9',Aastra_get_label('Detail Mode',$language),$XML_SERVER.'&action=detail');
		$object->addSoftkey('10',Aastra_get_label('Exit',$language),'SoftKey:Exit');

		# Icons
		if(Aastra_is_icons_supported())
			{
			$object->addIcon('1','Icon:Envelope');
			$object->addIcon('2','Icon:EnvelopeOpen');
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

			# Formatted text screen
			require_once('AastraIPPhoneFormattedTextScreen.class.php');
			$object=new AastraIPPhoneFormattedTextScreen();
			$object->setDestroyOnExit();
			if($message['message']['folder']=='INBOX') 
				{
				$text=Aastra_get_label('NEW',$language);
				$color='red';
				}
			else 
				{
				$text=Aastra_get_label('READ',$language);
				$color='green';
				}
			$object->addLine(sprintf('%s-%03d',$text,$message['message']['number']+1),'large','right',$color);
			$object->addLine('');
			$object->addLine($cid['name'],'large');
			$object->addLine($cid['number']);
			$object->addLine($date.' '.$time);
			if($message['message']['duration']<5) $color='red';
			else $color='';
			$object->addLine(sprintf(Aastra_get_label('Duration: %s',$language),$duration),NULL,NULL,$color);
			if($action=='playing') 
				{
				$object->setLockin();
				$object->setAllowDTMF();
				if($paused=='yes') $text=Aastra_get_label('PAUSED',$language);
				else $text=Aastra_get_label('NOW PLAYING',$language);
				$object->addLine('');
				$object->addLine('');
				$object->addLine($text,'large','center','red');
				}

			# Softkeys
			if($action=='detail')
				{
				# All keys on one page
				$object->addSoftkey('1',Aastra_get_label('Play',$language),$XML_SERVER.'&action=play_message&orig_p=detail&msg='.$msg);
				$object->addSoftkey('2',Aastra_get_label('Options',$language),$XML_SERVER.'&action=options&origin=detail&msg='.$msg);
				if($message['previous']!='') $object->addSoftkey('3',Aastra_get_label('Previous',$language),$XML_SERVER.'&action=detail&msg='.$message['previous']);
				if($cid['dial']) $object->addSoftkey('4',Aastra_get_label('Call back',$language),$XML_SERVER.'&action=callback&msg='.$msg.'&cause='.$cid['number'].'&origin=detail');
				$object->addSoftkey('5',Aastra_get_label('Forward',$language),$XML_SERVER.'&action=forward&msg='.$msg.'&origin=detail');
				$object->addSoftkey('6',Aastra_get_label('Delete',$language),$XML_SERVER.'&orig_d=detail&action=del_message&msg='.$msg);
				$object->addsoftkey('7',Aastra_get_label('Change User',$language),$XML_SERVER_PATH.'vmail.php?ext='.$ext);
				if($message['next']!='') $object->addSoftkey('8',Aastra_get_label('Next',$language),$XML_SERVER.'&action=detail&msg='.$message['next']);
				$object->addSoftkey('9',Aastra_get_label('List Mode',$language),$XML_SERVER.'&action=list&last_msg='.$msg);
				$object->addSoftkey('10',Aastra_get_label('Exit',$language),'SoftKey:Exit');
				}
			else
				{
				$object->addSoftkey('3',Aastra_get_label('Replay',$language),$XML_SERVER.'&action=play_dtmf&msg=1');
				$object->addSoftkey('4',Aastra_get_label('REW',$language),$XML_SERVER.'&action=play_dtmf&msg=4');
				if($paused=='no') $object->addSoftkey('5',Aastra_get_label('Pause',$language),$XML_SERVER.'&action=play_pause&msg='.$msg.'&paused='.$paused);
				else $object->addSoftkey('5',Aastra_get_label('Resume',$language),$XML_SERVER.'&action=play_pause&msg='.$msg.'&paused='.$paused);
				$object->addSoftkey('8',Aastra_get_label('Delete',$language),$XML_SERVER.'&action=play_del&msg='.$msg);
				$object->addSoftkey('9',Aastra_get_label('FWD',$language),$XML_SERVER.'&action=play_dtmf&msg=6');
				$object->addSoftkey('10',Aastra_get_label('Stop',$language),$XML_SERVER.'&action=play_stop&msg='.$msg);
				$object->setCancelAction($XML_SERVER.'&action=nothing');
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
		$object->addSoftkey('6',Aastra_get_label('Options',$language),$XML_SERVER.'&action=options');
		$object->addsoftkey('9',Aastra_get_label('Change User',$language),$XML_SERVER_PATH.'vmail.php?ext='.$ext);
		$object->addSoftkey('10',Aastra_get_label('Exit',$language),'SoftKey:Exit');
		break;

	# Play wave file
	case 'play_message':
		# Decode current Message
		sscanf($msg,'%4s-%s',$msg_id,$msg_folder);

		# Save session
		$array=array(	'uri_onhook'=>$XML_SERVER.'&action=play_exit&orig_p='.$orig_p.'&msg='.$msg,
				'uri_outgoing'=>$XML_SERVER.'&action=playing&msg='.$msg,
				'action'=>'play',
				'user'=>$user,
				'type'=>'message',
				'folder'=>$msg_folder,
				'msgid'=>$msg_id,
				'time'=>time()
				);
		Aastra_save_session('vmail','600',$array,$ext);

		# Return empty object	
		require_once('AastraIPPhoneExecute.class.php');
		$object=new AastraIPPhoneExecute();
		$object->addEntry('Dial:vmail');
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
		$object->addEntry('Key:KeyPad0');
		break;

	# Play DTMF
	case 'play_dtmf':
		# Send the DTMF
		require_once('AastraIPPhoneExecute.class.php');
		$object=new AastraIPPhoneExecute();
		$object->addEntry('Key:KeyPad'.$msg);
		break;

	# Pause/resume
	case 'play_pause':
		# Send a 5
		require_once('AastraIPPhoneExecute.class.php');
		$object=new AastraIPPhoneExecute();
		$object->addEntry('Key:KeyPad5');

		# Toggle pause state
		if($paused=='no') $paused='yes';
		else $paused='no';

		# Callback the updated display
		$object->addEntry($XML_SERVER.'&action=playing&msg='.$msg.'&paused='.$paused);
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

		# VMAIL user Options
		$object->addEntry(Aastra_get_label('Change Password',$language),$XML_SERVER.'&action=chg_password&origin='.$origin);
		$vmail_options=Aastra_get_user_context($ext,'vmail-options');
		if($vmail_options['auto_login']=='1') $object->addEntry(Aastra_get_label('Disable Auto-login',$language),$XML_SERVER.'&action=autologin&origin='.$origin);
		else $object->addEntry(Aastra_get_label('Enable Auto-login',$language),$XML_SERVER.'&action=autologin&origin='.$origin);

		# Greetings
		if($status['name']) 
			{
			$object->addEntry(Aastra_get_label('Play Name',$language),$XML_SERVER.'&action=play_greetings&msg='.$msg.'&greet=name&origin='.$origin);
			$object->addEntry(Aastra_get_label('Modify Name',$language),$XML_SERVER.'&action=rec_greetings&msg='.$msg.'&greet=name&origin='.$origin);
			$object->addEntry(Aastra_get_label('Delete Name',$language),$XML_SERVER.'&action=del_greetings&msg='.$msg.'&greet=name&origin='.$origin);
			$del_greetings=True;
			}
		else $object->addEntry(Aastra_get_label('Record Name',$language),$XML_SERVER.'&action=rec_greetings&msg='.$msg.'&greet=name&origin='.$origin);
		if($status['unavail']) 
			{
			$object->addEntry(Aastra_get_label('Play Unavailable',$language),$XML_SERVER.'&action=play_greetings&msg='.$msg.'&greet=unavail&origin='.$origin);
			$object->addEntry(Aastra_get_label('Modify Unavailable',$language),$XML_SERVER.'&action=rec_greetings&msg='.$msg.'&greet=unavail&origin='.$origin);
			$object->addEntry(Aastra_get_label('Delete Unavailable',$language),$XML_SERVER.'&action=del_greetings&msg='.$msg.'&greet=unavail&origin='.$origin);
			$del_greetings=True;
			}
		else $object->addEntry(Aastra_get_label('Record Unavailable',$language),$XML_SERVER.'&action=rec_greetings&msg='.$msg.'&greet=unavail&origin='.$origin);
		if($status['busy']) 
			{
			$object->addEntry(Aastra_get_label('Play Busy',$language),$XML_SERVER.'&action=play_greetings&msg='.$msg.'&greet=busy&origin='.$origin);
			$object->addEntry(Aastra_get_label('Modify Busy',$language),$XML_SERVER.'&action=rec_greetings&msg='.$msg.'&greet=busy&origin='.$origin);
			$object->addEntry(Aastra_get_label('Delete Busy',$language),$XML_SERVER.'&action=del_greetings&msg='.$msg.'&greet=busy&origin='.$origin);
			$del_greetings=True;
			}
		else $object->addEntry(Aastra_get_label('Record Busy',$language),$XML_SERVER.'&action=rec_greetings&msg='.$msg.'&greet=busy&origin='.$origin);
		if($status['temp']) 
			{
			$object->addEntry(Aastra_get_label('Play Temporary',$language),$XML_SERVER.'&action=play_greetings&msg='.$msg.'&greet=temp&origin='.$origin);
			$object->addEntry(Aastra_get_label('Modify Temporary',$language),$XML_SERVER.'&action=rec_greetings&msg='.$msg.'&greet=temp&origin='.$origin);
			$object->addEntry(Aastra_get_label('Delete Temporary',$language),$XML_SERVER.'&action=del_greetings&msg='.$msg.'&greet=temp&origin='.$origin);
			$del_greetings=True;
			}
		else $object->addEntry(Aastra_get_label('Record Temporary',$language),$XML_SERVER.'&action=rec_greetings&msg='.$msg.'&greet=temp&origin='.$origin);
		if($del_greetings) $object->addEntry(Aastra_get_label('Reset greetings',$language),$XML_SERVER.'&action=del_greetings&msg='.$msg.'&greet=ALL&origin='.$origin);

		# Softkeys
		if($origin=='list') $object->addSoftkey('9',Aastra_get_label('Back',$language),$XML_SERVER.'&action=list&last_msg='.$msg);
		else $object->addSoftkey('9',Aastra_get_label('Back',$language),$XML_SERVER.'&action=list&last_msg='.$msg);
		$object->addSoftkey('10',Aastra_get_label('Exit',$language),'SoftKey:Exit');

		# Cancel Action
		$object->setCancelAction($XML_SERVER.'&action='.$origin);
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
		$object->addSoftkey('10',Aastra_get_label('Cancel',$language),$XML_SERVER.'&action=options&origin='.$origin);
		$object->setCancelAction($XML_SERVER.'&action=options&origin='.$origin);
		break;

	# Play/Record Greetings
	case 'play_greetings':
	case 'rec_greetings':
		# Action
		if($action=='play_greetings') $action='play';
		else $action='record';
		
		# Save session
		$array=array(	'uri_onhook'=>$XML_SERVER.'&action=options&origin='.$origin.'&msg='.$msg,
				'uri_outgoing'=>$XML_SERVER.'&action=disp_greetings&greet='.$greet.'&cause='.$action,
				'user'=>$user,
				'type'=>$greet,
				'action'=>$action,
				'time'=>time()
				);
		Aastra_save_session('vmail','600',$array,$ext);

		# Return empty object
		require_once('AastraIPPhoneExecute.class.php');
		$object=new AastraIPPhoneExecute();
		$object->addEntry('Dial:vmail');
		break;

	# Display greetings
	case 'disp_greetings':
		# Greeting label
		switch($greet)
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
	
		# New formatted text screen
		require_once('AastraIPPhoneFormattedTextScreen.class.php');
		$object=new AastraIPPhoneFormattedTextScreen();
		$object->setDestroyOnExit();
		$object->setLockin();
		$object->setallowDTMF();
		$object->addLine('');
		$object->addLine($greeting,'large','center','green');
		$object->addLine('');
		$object->addLine('');
		if($cause=='record') $object->addLine(Aastra_get_label('RECORDING',$language),'large','center','red');
		else $object->addLine(Aastra_get_label('NOW PLAYING',$language),'large','center','red');

		# Softkeys
		$object->addSoftkey('10',Aastra_get_label('Cancel',$language),$XML_SERVER.'&action=cancel_greetings');
		break;

	# Cancel recording
	case 'cancel_greetings':
		# Cancel recording
		require_once('AastraIPPhoneExecute.class.php');
		$object=new AastraIPPhoneExecute();
		$object->addEntry('Key:Goodbye');
		$object->setTriggerDestroyonExit();
		break;

	# Callback
	case 'callback':
		# Depending on origin
		if($origin=='list')
			{
			# Retrieve current message
			$message=message_context($messages,$msg);

			# Message not found
			if(!$message['found'])
				{
				# Error message
				$err_title=sprintf(Aastra_get_label('Mailbox %s',$language),$user);
				$err_text=Aastra_get_label('Failed to retrieve Message.',$language);
				if($origin=='list') $err_key=array('10',Aastra_get_label('Continue',$language),$XML_SERVER.'&action=list&last_msg='.$msg);
				else $err_key=array('10',Aastra_get_label('Continue',$language),$XML_SERVER.'&action=detail&msg='.$msg);
				$action='error';
				}
			else
				{
				# Retrieve number
				$cid=Aastra_format_callerid_Asterisk($message['message']['callerid'],'2');
				if($cid['dial']) $cause=$cid['number'];
				else $action='nothing';
				}
			}

		# Next step
		if($action=='callback')
			{
			# Input Screen
			require_once('AastraIPPhoneInputScreen.class.php');
			$object=new AastraIPPhoneInputScreen();
			$object->setDestroyOnExit();

			# Input number
			$object->setTitle(Aastra_get_label('Edit Number',$language));
			$object->setType('number');
			$object->setPrompt(Aastra_get_label('Number to dial',$language));
			$object->setParameter('paused');
			$object->setURL($XML_SERVER.'&action=dial&msg='.$msg.'&cause='.$cause.'&origin='.$origin);
			$object->setDefault($cause);

			# Softkeys
			if($origin=='list') 
				{
				$object->addSoftkey('10',Aastra_get_label('Cancel',$language),$XML_SERVER.'&action=list&last_msg='.$msg);
				$object->setCancelAction($XML_SERVER.'&action=list&last_msg='.$msg);
				}
			else 
				{
				$object->addSoftkey('10',Aastra_get_label('Cancel',$language),$XML_SERVER.'&action=detail&msg='.$msg);
				$object->setCancelAction($XML_SERVER.'&action=detail&msg='.$msg);
				}
			}
		break;

	# Dial
	case 'dial':
		# PhoneExecute
		require_once('AastraIPPhoneExecute.class.php');
		$object=new AastraIPPhoneExecute();

		# Number not valid? 
		if((strlen($paused)<1) || (preg_match('/[^0-9]/',$paused))) $object->addEntry($XML_SERVER.'&action=callback&msg='.$msg.'&cause='.$cause.'&origin='.$origin);
		else $object->addEntry('Dial:'.$paused);
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
					if($value['select']) 
						{
						$submit=True;
						$object->addEntry('X '.$value['name'],$XML_SERVER.'&action=dselect&msg='.$msg.'&dpage='.$dpage.'&dindex='.$key);
						}
					else $object->addEntry('_ '.$value['name'],$XML_SERVER.'&action=dselect&msg='.$msg.'&dpage='.$dpage.'&dindex='.$key);
					if($key==$dindex) $object->setDefaultIndex($rank);
					$rank++;
					}
				$index++;
				}
			# Softkeys
			$object->addSoftkey('1',Aastra_get_label('Reverse Selection',$language),$XML_SERVER.'&action=reverse&msg='.$msg.'&dpage='.$dpage.'&origin='.$origin);
			if($dpage!='1') $object->addSoftkey('3',Aastra_get_label('Previous Page',$language),$XML_SERVER.'&action=forward2&msg='.$msg.'&dpage='.($dpage-1).'&origin='.$origin);
			if($dpage!=$last) $object->addSoftkey('8',Aastra_get_label('Next Page',$language),$XML_SERVER.'&action=forward2&msg='.$msg.'&dpage='.($dpage+1).'&origin='.$origin);
			if($submit) $object->addSoftkey('9',Aastra_get_label('Submit',$language),$XML_SERVER.'&action=dsubmit&msg='.$msg.'&origin='.$origin);
			if($origin=='list') $object->addSoftkey('10',Aastra_get_label('Cancel',$language),$XML_SERVER.'&action=list&last_msg='.$msg);
			else $object->addSoftkey('10',Aastra_get_label('Cancel',$language),$XML_SERVER.'&action=detail&msg='.$msg);
			}
		else
			{
			# Display error message
			require_once('AastraIPPhoneTextScreen.class.php');
			$object=new AastraIPPhoneTextScreen();
			$object->setDestroyOnExit();
			$object->setTitle(Aastra_get_label('Message forward',$language));	
			$object->setText(Aastra_get_label('No other user with voicemail configured on the platform.',$language));	
			if($origin=='list') $object->addSoftkey('10',Aastra_get_label('Continue',$language),$XML_SERVER.'&action=detail&msg='.$msg);
			else $object->addSoftkey('10',Aastra_get_label('Continue',$language),$XML_SERVER.'&action=list&msg='.$msg);
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
		$action='flash';
		if($origin=='list') $flash_next=$XML_SERVER.'&action=list&last_msg='.$msg;
		else $flash_next=$XML_SERVER.'&action=detail&msg='.$msg;
		$flash_text=Aastra_get_label('Message forwarded to the selected user(s).',$language);
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

	# Flash message
	case 'flash':
		# New formatted text screen
		require_once('AastraIPPhoneFormattedTextScreen.class.php');
		$object=new AastraIPPhoneFormattedTextScreen();
		$object->setDestroyOnExit();
		$size=Aastra_size_display_line();
		if(strlen($flash_text)>$size)
			{
			$temp=wordwrap($flash_text,$size,"\n",True);
			$lines=explode("\n",$temp);
			}
		else $lines[0]=$flash_text;
		$nb_lines=count($lines);
		if($nb_lines==1)
			{
			$object->addLine('');
			$object->addLine('');
			$object->addLine('');
			$object->addLine('');
			$object->addLine('');
			$object->addLine($lines[0],'double','center');
			}
		else
			{
			if($nb_lines<4) $object->addLine('');
			foreach($lines as $value) $object->addLine($value,'double','center');
			}

		# Softkeys
		$object->setRefresh(2,$flash_next);
		break;

	# Do nothing
	case 'nothing':
		# New text screen
		require_once('AastraIPPhoneExecute.class.php');
		$object=new AastraIPPhoneExecute();
		$object->addEntry('');
		break;
	}

# Output XML object
$object->output();
exit();
?>

