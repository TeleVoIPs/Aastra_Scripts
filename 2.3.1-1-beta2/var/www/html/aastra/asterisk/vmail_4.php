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
# Supported Aastra Phones (2.5.3 or better)
#    6755i, 6757i, 6757iCT, 9480i, 9480iCT
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
$paused=Aastra_getvar_safe('paused','no');
$last_msg=Aastra_getvar_safe('last_msg');
$dpage=Aastra_getvar_safe('dpage','1');
$dindex=Aastra_getvar_safe('dindex');
$set=Aastra_getvar_safe('set','1');

# Trace
Aastra_trace_call('vmail_4_asterisk','ext='.$ext.', user='.$user.' ,action='.$action);

# Test User Agent
Aastra_test_phone_versions(array('1'=>'','2'=>'','3'=>'','4'=>'2.5.3.','5'=>''),'0');

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
		$object->setTitle(sprintf('%s (%d)',$user,$new_count));
		$object->setStyle('none');
		$object->setWrapList();

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

				# Display on 2 lines with icons
	  			$date=format_date($item['origtime']);
		  		$time=format_time($item['origtime']);
				$display=array('0'=>$cid,'1'=>sprintf('%s %s',$date,$time),'2'=>2,'3'=>' ');

				# Compute icon
				if($item['folder']=='INBOX') $icon='1';
				else $icon='2';
		    		$object->addEntry($display,'&msg='.$msg,'&msg='.$msg,$icon);
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

		# Icons
		$object->addIcon('1','00FEC6AA928A92AAC6FE0000');
		$object->addIcon('2','00FEC6AA522A120A06020000');
		break;

	# Message zoom
	case 'detail':
	case 'playing':
		# Global compatibility
		$nbsp=chr(0xa0);

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

			# Graphic supported
			if(Aastra_is_pixmap_graphics_supported())
				{
				# Image Screen
				require_once('AastraIPPhoneImageScreen.class.php');
				$object=new AastraIPPhoneImageScreen();
				$object->setDestroyOnExit();
				require_once('AastraIPPhoneSPImage.class.php');
				$SPimage=new AastraIPPhoneSPImage();

				# Special config
				if($action=='playing') 
					{
					if(Aastra_is_lockincall_supported()) $object->setLockinCall();
					else $object->setLockin();
					$object->setAllowDTMF();
					}

				# Icons for Browse
				if($action=='detail')
					{
					if(($message['next']!='') and ($message['previous']!='')) $SPimage->addIcon('1','286CEE6C2800');
					else
						{
						if($message['next']!='') $SPimage->addIcon('1','080C0E0C0800');
						if($message['previous']!='') $SPimage->addIcon('1','2060E0602000');
						}
					}

				# Bitmap on the left
				if($action=='detail')
					{
					if($message['message']['folder']=='INBOX') $SPimage->setBitmap('new',2,1);
					else $SPimage->setBitmap('read',2,1);
					}
				else 
					{
					if($paused=='yes') $SPimage->setBitmap('paused',3,1);
					else $SPimage->setBitmap('playing',3,1);
					}
				$SPimage->setText(sprintf('%03d',$message['message']['number']+1),1,'left');
				$SPimage->setText($cid['name'],1,'left',4);
				$SPimage->setText($cid['number'],2,'left',4);
				$SPimage->setText($date.' '.$time,4,'left',4);
				$SPimage->setText($duration,5,'left',4);
				if($action=='detail') 
					{
					if(($message['next']!='') or ($message['previous']!='')) $SPimage->setText('#1#',5,'left',23);
					}
				$object->setSPImage($SPimage);
				}
			else
				{
				# Formatted text screen
				require_once('AastraIPPhoneFormattedTextScreen.class.php');
				$object=new AastraIPPhoneFormattedTextScreen();
				$object->setDestroyOnExit();
				if($action=='playing') 
					{
					if(Aastra_is_lockincall_supported()) $object->setLockinCall();
					else $object->setLockin();
					$object->setAllowDTMF();
					if($paused=='yes') $text=Aastra_get_label('PAUSED',$language);
					else $text=Aastra_get_label('PLAYING',$language);
					}
				else
					{
					if($message['message']['folder']=='INBOX') $text=Aastra_get_label('NEW',$language);
					else $text=Aastra_get_label('READ',$language);
					}
				$object->addLine(sprintf('%s-%03d',$text,$message['message']['number']+1),NULL,'right');
				$object->addLine($cid['name']);
				$object->addLine($cid['number']);
				$object->addLine($date.' '.$time.' ('.$duration.')');
			
				# Up/Down indicators
				if($action=='detail')
					{
					$text='';
					if($message['next']!='') $text='v';
					if($message['previous']!='') $text.='^';
					if($text!='') $object->addLine($text,NULL,'center');
					}
				}
			
			# Softkeys
			if($action=='detail')
				{
				# All keys on one page
				$object->addSoftkey('1',Aastra_get_label('Play',$language),$XML_SERVER.'&action=play_message&orig_p=detail&msg='.$msg);
				if($cid['dial']) $object->addSoftkey('2',Aastra_get_label('Call back',$language),$XML_SERVER.'&action=callback&msg='.$msg.'&cause='.$cid['number']);
				$object->addSoftkey('3',Aastra_get_label('Forward',$language),$XML_SERVER.'&action=forward&msg='.$msg);
				$object->addSoftkey('4',Aastra_get_label('Delete',$language),$XML_SERVER.'&orig_d=detail&action=del_message&msg='.$msg);
				$object->addSoftkey('6',Aastra_get_label('Back',$language),$XML_SERVER.'&action=list&last_msg='.$msg);
				}
			else
				{
				$object->addSoftkey('1','>|'.$nbsp.Aastra_get_label('Replay',$language),$XML_SERVER.'&action=play_dtmf&msg=1');
				$object->addSoftkey('2','<<'.$nbsp.Aastra_get_label('REW',$language),$XML_SERVER.'&action=play_dtmf&msg=4');
				if($paused=='no') $object->addSoftkey('3','||'.$nbsp.Aastra_get_label('Pause',$language),$XML_SERVER.'&action=play_pause&msg='.$msg.'&paused='.$paused);
				else $object->addSoftkey('3','|>'.$nbsp.Aastra_get_label('Resume',$language),$XML_SERVER.'&action=play_pause&msg='.$msg.'&paused='.$paused);
				$object->addSoftkey('4',Aastra_get_label('Delete',$language).$nbsp.'X',$XML_SERVER.'&action=play_del&msg='.$msg);
				$object->addSoftkey('5',Aastra_get_label('FWD',$language).$nbsp.'>>',$XML_SERVER.'&action=play_dtmf&msg=6');
				$object->addSoftkey('6',Aastra_get_label('Stop',$language).$nbsp.'[]',$XML_SERVER.'&action=play_stop&msg='.$msg);
				}

			# Scroll URIs
			if($action=='detail')
				{
				if($message['previous']!='') $object->setScrollUp($XML_SERVER.'&action=detail&msg='.$message['previous']);
				if($message['next']!='') $object->setScrollDown($XML_SERVER.'&action=detail&msg='.$message['next']);
				$object->setScrollLeft($XML_SERVER.'&action=list&last_msg='.$msg);
				$object->setScrollRight($XML_SERVER.'&action=play_message&orig_p=detail&msg='.$msg);
				}
			else
				{
				$object->setScrollLeft($XML_SERVER.'&action=play_dtmf&msg=4');
				$object->setScrollRight($XML_SERVER.'&action=play_dtmf&msg=6');
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
		$object->setStyle('none');
		$object->setWrapList();

		# VMAIL user Options
		$object->addEntry(Aastra_get_label('Change Password',$language),$XML_SERVER.'&action=chg_password&origin='.$origin);
		$vmail_options=Aastra_get_user_context($ext,'vmail-options');
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
		
		# Save session
		$array=array(	'uri_onhook'=>$XML_SERVER.'&action=options',
				'user'=>$user,
				'type'=>$msg,
				'action'=>$action
				);
		Aastra_save_session('vmail','600',$array,$ext);

		# Return empty object
		require_once('AastraIPPhoneExecute.class.php');
		$object=new AastraIPPhoneExecute();
		$object->addEntry('Dial:vmail');
		$object->addEntry($XML_SERVER.'&action=disp_greetings&msg='.$msg.'&cause='.$action);
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
	
		# Graphic supported
		if(Aastra_is_pixmap_graphics_supported())
			{
			# Display image
			require_once('AastraIPPhoneImageScreen.class.php');
			$object=new AastraIPPhoneImageScreen();
			$object->setDestroyOnExit();
			if(Aastra_is_lockincall_supported()) $object->setLockinCall();
			else $object->setLockin();
			$object->setallowDTMF();
			require_once('AastraIPPhoneSPImage.class.php');
			$SPimage=new AastraIPPhoneSPImage();

			# Title
			$SPimage->setText(sprintf(Aastra_get_label('Mailbox %s',$language),$user),1,'right',0);

			# Bitmap on the left
			if($cause=='record')
				{
				$SPimage->setBitmap('recording',3,1);
				$SPimage->setText(Aastra_get_label('Recording...',$language),3,'left',4);
				}
			else
				{
				$SPimage->setBitmap('playing',3,1);
				$SPimage->setText(Aastra_get_label('Playing...',$language),3,'left',4);
				}
			$SPimage->setText($greeting,4,'left',4);
			$object->setSPImage($SPimage);
			}
		else
			{
			# New formatted text screen
			require_once('AastraIPPhoneFormattedTextScreen.class.php');
			$object=new AastraIPPhoneFormattedTextScreen();
			$object->setDestroyOnExit();
			if(Aastra_is_lockincall_supported()) $object->setLockinCall();
			else $object->setLockin();
			$object->setallowDTMF();
			$object->addLine('');
			if($cause=='record') $object->addLine(Aastra_get_label('Recording',$language));
			else $object->addLine(Aastra_get_label('Playing',$language));
			$object->addLine($greeting,NULL,'center');
			}

		# Softkeys
		$object->addSoftkey('6',Aastra_get_label('Cancel',$language),$XML_SERVER.'&action=cancel_greetings');
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
		# Input Screen
		require_once('AastraIPPhoneInputScreen.class.php');
		$object=new AastraIPPhoneInputScreen();
		$object->setDestroyOnExit();

		# Input number
		$object->setTitle(Aastra_get_label('Edit Number',$language));
		$object->setType('number');
		$object->setPrompt(Aastra_get_label('Number to dial',$language));
		$object->setParameter('paused');
		$object->setURL($XML_SERVER.'&action=dial&msg='.$msg.'&cause='.$cause);
		$object->setDefault($cause);

		# Common Softkeys
		$object->addSoftkey('1',Aastra_get_label('Backspace',$language),'SoftKey:BackSpace');
		$object->addSoftkey('5',Aastra_get_label('Dial',$language),'SoftKey:Submit');
		$object->addSoftkey('6',Aastra_get_label('Cancel',$language),$XML_SERVER.'&action=detail&msg='.$msg);
		break;

	# Dial
	case 'dial':
		# PhoneExecute
		require_once('AastraIPPhoneExecute.class.php');
		$object=new AastraIPPhoneExecute();

		# Number not valid? 
		if((strlen($paused)<1) || (preg_match('/[^0-9]/',$paused))) 
			{
			$object->setBeep();
			$object->addEntry($XML_SERVER.'&action=callback&msg='.$msg.'&cause='.$cause);
			}
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
					if($value['select']) $object->addEntry($value['name'],$XML_SERVER.'&action=dselect&msg='.$msg.'&dpage='.$dpage.'&dindex='.$key,'',1);
					else $object->addEntry($value['name'],$XML_SERVER.'&action=dselect&msg='.$msg.'&dpage='.$dpage.'&dindex='.$key,'',2);
					if($key==$dindex) $object->setDefaultIndex($rank);
					$rank++;
					}
				$index++;
				}

			# Icons
			$object->addIcon('1','0000000804020C30C0000000');
			$object->addIcon('2','000000000000000000000000');

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
			require_once('AastraIPPhoneTextScreen.class.php');
			$object=new AastraIPPhoneTextScreen();
			$object->setDestroyOnExit();
			$object->setTitle(Aastra_get_label('Message forward',$language));	
			$object->setText(Aastra_get_label('No other user with voicemail configured on the platform.',$language));	
			$object->addSoftkey('6',Aastra_get_label('Close',$language), $XML_SERVER.'&action=detail&msg='.$msg);
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
		$flash_next=$XML_SERVER.'&action=detail&msg='.$msg;
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
			$object->addLine($lines[0],'double','center');
			}
		else
			{
			if($nb_lines<4) $object->addLine('');
			foreach($lines as $value) $object->addLine($value,NULL,'center');
			}

		# Softkeys
		$object->setRefresh(2,$flash_next);
		$object->addSoftkey('6','',$flash_next);
		break;
	}

# Output XML object
$object->output();
exit();
?>

