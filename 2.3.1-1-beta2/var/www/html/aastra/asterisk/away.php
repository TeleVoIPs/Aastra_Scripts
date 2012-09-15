<?php
#############################################################################
# Asterisk Presence management
#
# Aastra SIP Phones 1.4.2 or better
#
# Copyright 2007-2010 Aastra Telecom Ltd
#
# Supported Aastra Phones
#   All phones
#
# Usage
# 	script.php?user=USER
# 	USER is the extension of the phone on the Asterisk platform
#
#############################################################################

#############################################################################
# PHP customization for includes and warnings
#############################################################################
$os = strtolower(PHP_OS);
if(strpos($os, "win") === false) ini_set('include_path',ini_get('include_path').':include:../include');
else ini_set('include_path',ini_get('include_path').';include;..\include');
error_reporting(E_ERROR | E_PARSE);

#############################################################################
# Includes
#############################################################################
require_once('AastraAsterisk.php');

#############################################################################
# Body
#############################################################################
# Retrieve parameters
$user=Aastra_getvar_safe('user');
$action=Aastra_getvar_safe('action','main');
$state=Aastra_getvar_safe('state');
$value1=Aastra_getvar_safe('value1');
$value2=Aastra_getvar_safe('value2');
$step=Aastra_getvar_safe('step','1');
$type=Aastra_getvar_safe('type');
$selection=Aastra_getvar_safe('selection');

# Trace
Aastra_trace_call('away_asterisk','user='.$user.', action='.$action.', state='.$state.', value1='.$value1);

# Test User Agent
Aastra_test_phone_versions(array('1'=>'1.4.2.','2'=>'1.4.2.','3'=>'2.5.3.','4'=>'2.5.3.','5'=>'3.0.1.'),'0');

# Get Language
$language=Aastra_get_language();

# Global compatibility
$nb_softkeys=Aastra_number_softkeys_supported();
$is_sip_notify=Aastra_is_sip_notify_supported();

# Check if presence is configured
if((!$AA_PRESENCE_STATE) and ($action=='main'))
	{
	# Display error message
	require_once('AastraIPPhoneTextScreen.class.php');
	$object=new AastraIPPhoneTextScreen();
	$object->setDestroyOnExit();
	$object->setTitle(Aastra_get_label('System Error',$language));
	$object->setText(Aastra_get_label('Presence is not enabled on your system. Please contact your administrator.',$language));
	if($nb_softkeys)
		{
		if($nb_softkeys==6) $object->addSoftkey('6',Aastra_get_label('Exit',$language),'SoftKey:Exit');
		else $object->addSoftkey('10',Aastra_get_label('Exit',$language),'SoftKey:Exit');
		}
	$object->output();
	exit;
	}

# Keep return URI
$XML_SERVER.='?user='.$user;

# Labels for status
$status_text=Aastra_status_config_Asterisk();

# Pre-process action
switch($action)
	{
	# Modify or clear personal number
	case 'clear_info':
	case 'set_info':
		# Retrieve stored data
		$array_user=Aastra_manage_userinfo_Asterisk($user,'get');

		# Update data
		if($action=='set_info') $array_user[$type]=$value1;
		else
			{
			$type=$selection;
			$array_user[$type]='';
			}
		Aastra_manage_userinfo_Asterisk($user,'set',$array_user);

		# Next action
		$action='info';
		break;

	# Set forward number
	case 'set_number':
		# Check password
		if((strlen($type)!=0) and is_numeric($type)) $action='set_prefs';
		else $action=$selection;
		break;
	}

# Depending on action
switch($action)
	{
	# Periodic update
	case 'check':
	case 'register':
		# Update needed
		$update=1;

		# Get presence
		$status=Aastra_manage_presence_Asterisk($user,'status');

		# Get last status, last logout and key
		$data=Aastra_get_user_context($user,'away');
		$last=$data['last'];
		$key=$data['key'];

		# Save status
		$data['last']=$status;
		if($status!=$last) Aastra_save_user_context($user,'away',$data);

		# Update needed?
		if(($action=='check') and ($status==$last)) $update=0;
		if(($action=='register') and ($status==AA_PRESENCE_AVAILABLE)) $update=0;

		# Prepare display update
		require_once('AastraIPPhoneExecute.class.php');
		$object = new AastraIPPhoneExecute();

		# Status update
		if($update==1)
			{
			$object->addEntry($XML_SERVER.'&action=msg&state='.$status);
			$object->setBeep();
			if($key!='')
				{
				if(Aastra_is_ledcontrol_supported())
					{
					if($status==AA_PRESENCE_AVAILABLE) $object->AddEntry('Led: '.$key.'=off');
					else $object->AddEntry('Led: '.$key.'=on');
					}
				}
			}

		# Add do nothing if necessary
		if($update==0)
			{
			# Do nothing
			$object->addEntry('');
			}
		break;

	# Save new presence status
	case 'set_change':
		# Get current status
		$away=Aastra_manage_presence_Asterisk($user,'get');
		$previous_state=$away['status'];

		# Change the value in the database
		Aastra_manage_presence_Asterisk($user,'set','status',$value1);

		# Next actions
		require_once('AastraIPPhoneExecute.class.php');
		$object=new AastraIPPhoneExecute();

		# Cleanup
		$dnd_enable=False;
		$dnd_disable=False;
		$cfwd_enable=False;
		$cfwd_disable=False;
		$fmfm_enable=False;
		$fmfm_disable=False;

		# Cleanup DND/FMFM/CFWD
		if($value1==AA_PRESENCE_AVAILABLE)
			{
			switch($away['action'][$previous_state])
				{
				case AA_PRESENCE_ACT_DND:
					$dnd_disable=True;
					break;
				case AA_PRESENCE_ACT_FM:
					$fmfm_disable=True;
					break;
				case AA_PRESENCE_ACT_CFWD:
					$cfwd_disable=True;
					break;
				}
			}
		else
			{
			if($previous_state==AA_PRESENCE_AVAILABLE)
				{
				switch($away['action'][$value1])
					{
					case AA_PRESENCE_ACT_DND:
						$dnd_enable=True;
						break;
					case AA_PRESENCE_ACT_FM:
						$fmfm_enable=True;
						break;
					case AA_PRESENCE_ACT_CFWD:
						$cfwd_enable=True;
						break;
					}
				}
			else
				{
				switch($away['action'][$previous_state])
					{
					case AA_PRESENCE_ACT_DND:
						$dnd_disable=True;
						break;
					case AA_PRESENCE_ACT_FM:
						$fmfm_disable=True;
						break;
					case AA_PRESENCE_ACT_CFWD:
						$cfwd_disable=True;
						break;
					}
				switch($away['action'][$value1])
					{
					case AA_PRESENCE_ACT_DND:
						$dnd_enable=True;
						break;
					case AA_PRESENCE_ACT_FM:
						$fmfm_enable=True;
						break;
					case AA_PRESENCE_ACT_CFWD:
						$cfwd_enable=True;
						break;
					}
				}
			}
		if($dnd_enable and $dnd_disable) 
			{
			$dnd_enable=False;
			$dnd_disable=False;
			}
		if($fmfm_enable and $fmfm_disable) 
			{
			$fmfm_enable=False;
			$fmfm_disable=False;
			}
		if($cfwd_enable and $cfwd_disable) 
			{
			$cfwd_enable=True;
			$cfwd_disable=False;
			}

		# Enable/disable DND
		if($dnd_enable) Aastra_manage_dnd_Asterisk($user,'enable');
		if($dnd_disable) Aastra_manage_dnd_Asterisk($user,'disable');
		if($dnd_enable or $dnd_disable)
			{
			if($AA_FREEPBX_MODE=='1')
				{
				$object->addEntry($XML_SERVER_PATH.'dnd.php?user='.$user.'&action=check');
				}
			else 
				{
				if(!$AA_FREEPBX_USEDEVSTATE) Aastra_propagate_changes_Asterisk('',Aastra_get_userdevice_Asterisk($user),array('dnd'));
				}
			}

		# Enable/disable FMFM
		if($fmfm_enable) Aastra_manage_followme_Asterisk($user,'enable');
		if($fmfm_disable) Aastra_manage_followme_Asterisk($user,'disable');
		if($fmfm_enable or $fmfm_disable)
			{
			if($AA_FREEPBX_MODE=='1')
				{
				$object->addEntry($XML_SERVER_PATH.'follow.php?user='.$user.'&action=check');
				}
			else 
				{
				if(!$AA_FREEPBX_USEDEVSTATE) Aastra_propagate_changes_Asterisk('',Aastra_get_userdevice_Asterisk($user),array('follow'));
				}
			}

		# Enable/disable CFWD
		if($cfwd_enable) Aastra_manage_cf_Asterisk($user,'set',$away['act_param'][$value1]);
		if($cfwd_disable) Aastra_manage_cf_Asterisk($user,'cancel','');
		if($cfwd_enable or $cfwd_disable)
			{
			if($AA_FREEPBX_MODE=='1')
				{
				$object->addEntry($XML_SERVER_PATH.'cfwd.php?user='.$user.'&action=check');
				}
			else 
				{
				Aastra_propagate_changes_Asterisk('',Aastra_get_userdevice_Asterisk($user),array('cfwd'));
				}
			}

		# Complete the 'Back' status
		if($value1==AA_PRESENCE_AVAILABLE) 
			{
			# Delete Temp message
			Aastra_delete_temp_message_Asterisk($user);

			# Send message notifications if needed
			if($away['notifym']!='')
				{
				$explode=explode(',',$away['notifym']);
				$long=sprintf(Aastra_get_label('%s is back',$language),Aastra_get_callerid_Asterisk(Aastra_get_userdevice_Asterisk($user)));
				$short=sprintf(Aastra_get_label('%s is back',$language),Aastra_get_userdevice_Asterisk($user));
				foreach($explode as $data) Aastra_send_message_Asterisk($data,$long,$short,'Dial:'.Aastra_get_userdevice_Asterisk($user));
				}

			# Send voice notifications if needed
			if($away['notifyv']!='')
				{
				# Retrieve name recording
				$name_recording=Aastra_get_greeting_name_Asterisk($user);

				# Run in background so we aren't waiting for a response
				$cmd='/usr/bin/php '.Aastra_getvar_safe('DOCUMENT_ROOT','','SERVER').'/'.$AA_XMLDIRECTORY.'/asterisk/notify.php '.$away['notifyv'].' '.Aastra_get_userdevice_Asterisk($user).' '.$name_recording;
				$cmd=escapeshellcmd($cmd);
				$cmd='('.$cmd.') >/dev/null &';
				system($cmd);
				}
			}

		# Update LED and idle screen
		if($is_sip_notify and $AA_ISYMPHONY)
			{
			# Send a user event to aastra-daemon1 to update status
			Aastra_send_userevent_Asterisk('PresenceStatusChanged',Aastra_get_userdevice_Asterisk($user).','.$value1);
			}
		else $object->addEntry($XML_SERVER.'&action=check');

		# Reset return date/time
		if($AA_ISYMPHONY and ($value1==AA_PRESENCE_AVAILABLE)) Aastra_send_userevent_Asterisk('PresenceReturnChanged',Aastra_get_userdevice_Asterisk($user).',0');
			
		# Prepare display update
		if($value1!=AA_PRESENCE_AVAILABLE) $object->addEntry($XML_SERVER.'&action=main');
		else $object->addEntry($XML_SERVER.'&action=display_back');
		break;

	# Change status
	case 'change':
		# Get current status
		$away=Aastra_manage_presence_Asterisk($user,'get');

		# Display choice
		require_once('AastraIPPhoneTextMenu.class.php');
		$object = new AastraIPPhoneTextMenu();
		if(Aastra_is_style_textmenu_supported()) $object->setStyle('radio');
		$object->setDestroyOnExit();
		$object->setTitle(Aastra_get_label('Change Status',$language));
		$index=1;
		if($away['status']!=AA_PRESENCE_AVAILABLE) $object->addEntry(Aastra_get_label('I am back',$language), $XML_SERVER.'&action=set_change&value1='.AA_PRESENCE_AVAILABLE,'');
		foreach($status_text as $key=>$status)
			{
			if(($key!=AA_PRESENCE_AVAILABLE) and ($key!=AA_PRESENCE_DISCONNECTED)) 
				{
				$object->addEntry($status_text[$key]['label'],$XML_SERVER.'&action=set_change&value1='.$key,'');
				$index++;
				if($away['status']==$key) $object->setDefaultIndex($index);
				}
			}

		# Softkeys
		if($nb_softkeys)
			{
			if($nb_softkeys==6)
				{
				$object->addSoftKey('1',Aastra_get_label('Select',$language),'SoftKey:Select');
				$object->addSoftKey('5',Aastra_get_label('Cancel',$language),$XML_SERVER.'&action=main');
				$object->addSoftKey('6',Aastra_get_label('Exit',$language),'SoftKey:Exit');
				}
			else
				{
				$object->addSoftKey('9',Aastra_get_label('Cancel',$language),$XML_SERVER.'&action=main');
				$object->addSoftKey('10',Aastra_get_label('Exit',$language),'SoftKey:Exit');
				}
			}
		$object->setCancelAction($XML_SERVER.'&action=main');
		break;

	# Set date
	case 'set_date_time':
		# Process input date
		if(($value1!='') and ($value2!=''))
			{
			if($AA_FORMAT_DT=='US') list($month,$day,$year)=split('/',$value1);
			else list($day,$month,$year)=split('/',$value1);
			$timepart=substr($value2,0,8);
			list($hour,$minute,$second)=split(':',$timepart);
			if($AA_FORMAT_DT=='US')  
				{
				$pm=substr($value2,8,2);
				if(($pm=='PM') or ($pm=='pm')) 
					{
					if($hour!='12') $hour+=12;
					}
				else
					{
					if($hour=='12') $hour=0;
					}
				}
			$value=mktime($hour,$minute,'0',$month,$day,$year);
			}
		else $value='0';

		# Store date
		Aastra_manage_presence_Asterisk($user,'set','return',$value);

		# Send a user event to aastra-daemon1 to update iSymphony
		if($AA_ISYMPHONY) Aastra_send_userevent_Asterisk('PresenceReturnChanged',Aastra_get_userdevice_Asterisk($user).','.$value);
			
		# Go back to main display
		require_once('AastraIPPhoneExecute.class.php');
		$object=new AastraIPPhoneExecute();
		$object->addEntry($XML_SERVER.'&action=main');
		break;

	# Set Preferences
	case 'set_prefs':
		# Get current status
		$away=Aastra_manage_presence_Asterisk($user,'action');

		# Update
		$away['action'][$state]=$value1;
		$away['act_param'][$state]=$type;

		# Store data
		Aastra_manage_presence_Asterisk($user,'set','action',array($away['action'],$away['act_param']));

		# Go back to main display
		require_once('AastraIPPhoneExecute.class.php');
		$object=new AastraIPPhoneExecute();
		$object->addEntry($XML_SERVER.'&action=input_prefs&state='.$state);
		break;

	# Input date/time
	case 'input_date_time':
		# Get current status
		$away=Aastra_manage_presence_Asterisk($user,'get');

		# Get default date and time
		if($AA_FORMAT_DT=='US') 
			{
			if($away['return']!='0') 
				{
				$default_date=date('m/d/Y',$away['return']);
				$default_time=date('h:i:sA',$away['return']);
				}
			else
				{
				$default_date=date('m/d/Y',date('m'),date('d'),date('Y'));
				$default_time=date('h:i:sA',mktime(date('H'),date('i'),0,date('m'),date('d'),date('Y')));
				}
			}
		else
			{
			if($away['return']!='0') 
				{
				$default_date=date('d/m/Y',$away['return']);
				$default_time=date('H:i:s',$away['return']);
				}
			else
				{
				$default_date=date('d/m/Y',date('d'),date('m'),date('Y'));
				$default_time=date('H:i:s',mktime(date('H'),date('i'),0,date('m'),date('d'),date('Y')));
				}
			}


		# Create input screen
		require_once('AastraIPPhoneInputScreen.class.php');
		$object = new AastraIPPhoneInputScreen();
		$object->setDestroyOnExit();
		$object->setTitle(Aastra_get_label('Return Date/Time',$language));

		# Multiple input fields?
		if(Aastra_is_multipleinputfields_supported())
			{
			# Empty field
			$object->setURL($XML_SERVER.'&action=set_date_time');
			$object->setDisplayMode('condensed');
			$object->addField('empty'); 

			# Date
			if($AA_FORMAT_DT=='US') $object->addField('dateUS'); 
			else $object->addField('dateInt'); 
			$object->setFieldPrompt(Aastra_get_label('Date',$language));
			$object->setFieldParameter('value1');
			$object->setFieldDefault($default_date);

			# Time
			if($AA_FORMAT_DT=='US') $object->addField('timeUS'); 
			else $object->addField('timeInt'); 
			$object->setFieldPrompt(Aastra_get_label('Time',$language));
			$object->setFieldParameter('value2');
			$object->setFieldDefault($default_time);

			# Softkeys
			if($nb_softkeys)
				{
				if($nb_softkeys==6)
					{
					if($away['return']!='') $object->addSoftKey('1',Aastra_get_label('No Date',$language),$XML_SERVER.'&action=set_date_time&value1=&value2=');
					$object->addSoftKey('4',Aastra_get_label('Cancel',$language),$XML_SERVER.'&action=main');
					$object->addSoftKey('5',Aastra_get_label('Submit',$language),'SoftKey:Submit');
					$object->addSoftKey('6',Aastra_get_label('Exit',$language),'SoftKey:Exit');
					}
				else
					{
					if($away['return']!='') $object->addSoftKey('1',Aastra_get_label('No Date/time',$language),$XML_SERVER.'&action=set_date_time&value1=&value2=');
					$object->addSoftKey('9',Aastra_get_label('Cancel',$language),$XML_SERVER.'&action=main');
					$object->addSoftKey('10',Aastra_get_label('Exit',$language),'SoftKey:Exit');
					}
				}
			$object->setCancelAction($XML_SERVER.'&action=main');
			}
		else
			{
			# First step
			if($step=='1')
				{
				# Input date
				if($AA_FORMAT_DT=='US') $object->setType('dateUS'); 
				else $object->setType('dateInt');
				$object->setPrompt(Aastra_get_label('Enter Date',$language));
				$object->setParameter('value1');
				if($value1!='') $object->setDefault($value1);
				$object->setDefault($default_date);
				$object->setURL($XML_SERVER.'&action=input_date_time&step=2');
				$object->setCancelAction($XML_SERVER.'&action=main');
				}
			else
				{
				if($AA_FORMAT_DT=='US') $object->setType('timeUS'); 
				else $object->setType('timeInt');
				$object->setPrompt(Aastra_get_label('Enter Time',$language));
				$object->setParameter('value2');
				$object->setDefault($default_time);
				$object->setURL($XML_SERVER.'&action=set_date_time&value1='.$value1);
				$object->setCancelAction($XML_SERVER.'&action=set_date_time&value1='.$value1);
				}
			}
		break;

	# Update idle screen message status
	case 'msg':
		# update screen message
		require_once('AastraIPPhoneStatus.class.php');
		$object=new AastraIPPhoneStatus();
		$object->setSession('aastra-xml');
		$index=Aastra_get_status_index_Asterisk('away');
		if ($state==AA_PRESENCE_AVAILABLE) $object->addEntry($index,'');
		else 
			{
			if(!$status_text[$state]) $status=Aastra_get_label('Unknown',$language);
			else $status=$status_text[$state]['label'];
			if(Aastra_size_display_line()>16) 
				{
				if(Aastra_is_status_uri_supported())
					{
					$object->addEntry($index,sprintf(Aastra_get_label('You are %s',$language),$status),'',NULL,$XML_SERVER.'&action=set_change&value1=0',1);
					$object->addIcon('1','Icon:PresenceAbsent');
					}
				else $object->addEntry($index,sprintf(Aastra_get_label('You are %s',$language),$status));
				}
			else $object->addEntry($index,$status);
			}
		break;

	# Input default action
	case 'input_prefs2':
		# Get current status
		$away=Aastra_manage_presence_Asterisk($user,'action');

		# Retrieve personal numbers
		$array_user=Aastra_manage_userinfo_Asterisk($user,'get');
		$forward='edit_number2';
		foreach($array_user as $key=>$value1)
			{
			if($value1!='') $forward='select_number';
			break;
			}

		# Display choice
		require_once('AastraIPPhoneTextMenu.class.php');
		$object=new AastraIPPhoneTextMenu();
		if(Aastra_is_style_textmenu_supported()) $object->setStyle('radio');
		$object->setDestroyOnExit();
		$object->setTitle($status_text[$state]['label']);
		if(Aastra_size_display_line()>16)
			{
			$list1=Aastra_get_label('Do Nothing',$language);
			$list2=Aastra_get_label('Activate DND',$language);
			$list3=Aastra_get_label('Activate Follow-me',$language);
			$list4=Aastra_get_label('Activate CFWD',$language);
			}
		else
			{
			$list1=Aastra_get_label('Do Nothing',$language);
			$list2=Aastra_get_label('Do Not Disturb',$language);
			$list3=Aastra_get_label('Follow-me',$language);
			$list4=Aastra_get_label('Forward',$language);
			}
		$object->addEntry($list1,$XML_SERVER.'&action=set_prefs&value1='.AA_PRESENCE_ACT_NOTHING.'&state='.$state,'');
		if($state!=AA_PRESENCE_DISCONNECTED) $object->addEntry($list2, $XML_SERVER.'&action=set_prefs&value1='.AA_PRESENCE_ACT_DND.'&state='.$state,'');
		$object->addEntry($list4,$XML_SERVER.'&action='.$forward.'&value1='.AA_PRESENCE_ACT_CFWD.'&state='.$state,'');
		if((Aastra_manage_followme_Asterisk($user,'get_status')!=2) and ($state!=AA_PRESENCE_DISCONNECTED)) $object->addEntry($list3, $XML_SERVER.'&action=set_prefs&value1='.AA_PRESENCE_ACT_FM.'&state='.$state,'');
		switch($away['action'][$state])
			{
			case AA_PRESENCE_ACT_DND:
				$object->setDefaultIndex('2');
				break;
			case AA_PRESENCE_ACT_CFWD:
				$object->setDefaultIndex('3');
				break;
			case AA_PRESENCE_ACT_FM:
				$object->setDefaultIndex('4');
				break;
			default:
				$object->setDefaultIndex('1');
				break;
			}

		# Softkeys
		if($nb_softkeys)
			{
			if($nb_softkeys==6)
				{
				$object->addSoftKey('1',Aastra_get_label('Select',$language),'SoftKey:Select');	
				$object->addSoftKey('5',Aastra_get_label('Back',$language),$XML_SERVER.'&action=input_prefs&state='.$state);
				$object->addSoftKey('6',Aastra_get_label('Exit',$language),'SoftKey:Exit');
				}
			else
				{
				$object->addSoftKey('9',Aastra_get_label('Back',$language),$XML_SERVER.'&action=input_prefs&state='.$state);
				$object->addSoftKey('10',Aastra_get_label('Exit',$language),'SoftKey:Exit');
				}
			}
		$object->setCancelAction($XML_SERVER.'&action=input_prefs');
		break;

	# Input default actions
	case 'input_prefs':
		# Get current status
		$away=Aastra_manage_presence_Asterisk($user,'action');

		# Display choice
		require_once('AastraIPPhoneTextMenu.class.php');
		$object=new AastraIPPhoneTextMenu();
		if(Aastra_is_style_textmenu_supported()) $object->setStyle('none');
		if(Aastra_is_textmenu_wrapitem_supported()) $object->setWrapList();
		$object->setDestroyOnExit();
		$object->setTitle(Aastra_get_label('Preferences',$language));
		foreach($status_text as $key=>$status)
			{
			if(($key!=AA_PRESENCE_AVAILABLE) and ($key!=AA_PRESENCE_DISCONNECTED)) $array_action[]=$key;	
			}
		if(!$AA_ISYMPHONY) $array_action[]=AA_PRESENCE_DISCONNECTED;
		if(($AA_FREEPBX_MODE=='1') and ($is_sip_notify)) $index=2;
		else $index=1;
		foreach($array_action as $cstate)
			{
			switch($away['action'][$cstate])
				{
				case AA_PRESENCE_ACT_DND:
					$text=Aastra_get_label('DND',$language);
					break;
				case AA_PRESENCE_ACT_CFWD:
					$text=Aastra_get_label('Forward',$language);
					break;
				case AA_PRESENCE_ACT_FM:
					$text=Aastra_get_label('Follow',$language);
					break;
				default:
					$text=Aastra_get_label('Nothing',$language);
					break;
				}
			$object->addEntry(sprintf('%s (%s)',$status_text[$cstate]['label'],$text),$XML_SERVER.'&action=input_prefs2&state='.$cstate,'');
			if($state!='')
				{
				if($state==$cstate) $object->setDefaultIndex($index);
				}
			$index++;
			}

		# Softkeys
		if($nb_softkeys)
			{
			if($nb_softkeys==6)
				{
				$object->addSoftKey('1',Aastra_get_label('Select',$language),'SoftKey:Select');	
				$object->addSoftKey('5',Aastra_get_label('Back',$language),$XML_SERVER.'&action=main');
				$object->addSoftKey('6',Aastra_get_label('Exit',$language),'SoftKey:Exit');
				}
			else
				{
				$object->addSoftKey('9',Aastra_get_label('Back',$language),$XML_SERVER.'&action=main');
				$object->addSoftKey('10',Aastra_get_label('Exit',$language),'SoftKey:Exit');
				}
			}
		$object->setCancelAction($XML_SERVER.'&action=main');
		break;

	# Default is Current status
	case 'main':
		# Authenticate user
		Aastra_check_signature_Asterisk($user);

		# Get current status
		$away=Aastra_manage_presence_Asterisk($user,'get');

		# Retrieve personal numbers
		$array_user=Aastra_manage_userinfo_Asterisk($user,'get');
		$array_user=array_flip($array_user);
		$array_label=array(	'cell'=>Aastra_get_label('Cell',$language),
					'home'=>Aastra_get_label('Home',$language),
					'other'=>Aastra_get_label('Other',$language)
					);

		# If phone supports softkeys
		if(Aastra_is_softkeys_supported())
			{
			# Depending on the phone
			if(Aastra_is_formattedtextscreen_supported())
				{
				# Display current status
				require_once('AastraIPPhoneFormattedTextScreen.class.php');
				$object=new AastraIPPhoneFormattedTextScreen();
				$object->setDestroyOnExit();
				if(Aastra_is_formattedtextscreen_color_supported())
					{
					if($away['status']!=AA_PRESENCE_AVAILABLE) $color='red';
					else $color='green';
					$object->addLine($status_text[$away['status']]['label'],'double','center',$color);
					$object->addLine('');
					}
				else $object->addLine($status_text[$away['status']]['label'],'double','center');
				if($nb_softkeys==6) $object->setScrollStart(Aastra_size_formattedtextscreen()-2);
				if($away['status']!=AA_PRESENCE_AVAILABLE)
					{
					$line=Aastra_format_presence_dt_Asterisk($away['return']);
					foreach($line as $data) $object->addLine($data);
					}
				if($away['status']==AA_PRESENCE_AVAILABLE)
					{
					foreach($status_text as $key=>$status)
						{
						if(($key!=AA_PRESENCE_AVAILABLE) and ($key!=AA_PRESENCE_DISCONNECTED)) $array_action[]=$key;	
						}
					if(!$AA_ISYMPHONY) $array_action[]=AA_PRESENCE_DISCONNECTED;
					foreach($array_action as $state)
						{
						switch($away['action'][$state])
							{
							case AA_PRESENCE_ACT_DND:
								$object->addLine(sprintf(Aastra_get_label('DND if %s',$language),$status_text[$state]['label']));
								break;
							case AA_PRESENCE_ACT_CFWD:
								if($nb_softkeys==10)
									{
									if($array_user[$away['act_param'][$state]]!='') $object->addLine(sprintf(Aastra_get_label('Forward to %s (%s) if %s',$language),$away['act_param'][$state],$array_label[$array_user[$away['act_param'][$state]]],$status_text[$state]['label']));
									else $object->addLine(sprintf(Aastra_get_label('Forward to %s if %s',$language),$away['act_param'][$state],$status_text[$state]['label']));
									}
								else $object->addLine(sprintf(Aastra_get_label('Forward if %s',$language),$status_text[$state]['label']));
								break;
							case AA_PRESENCE_ACT_FM:
								$object->addLine(sprintf(Aastra_get_label('Follow-me if %s',$language),$status_text[$state]['label']));
								break;
							}
						}
					}
				else
					{
					switch($away['action'][$away['status']])
						{
						case AA_PRESENCE_ACT_DND:
							if(Aastra_manage_dnd_Asterisk($user,'get')=='1') $object->addLine(Aastra_get_label('DND activated',$language));
							break;
						case AA_PRESENCE_ACT_CFWD:
							if(Aastra_manage_cf_Asterisk($user,'get')!='') $object->addLine(Aastra_get_label('Forward activated',$language));
							break;
						case AA_PRESENCE_ACT_FM:
							if(Aastra_manage_followme_Asterisk($user,'get_status')=='1') $object->addLine(Aastra_get_label('Follow-me activated',$language));
							break;
						}
					}
				if($nb_softkeys==6) $object->setScrollEnd();
				}
			else
				{
				# Display current status
				require_once('AastraIPPhoneTextScreen.class.php');
				$object=new AastraIPPhoneTextScreen();
				$object->setDestroyOnExit();
				$object->setTitle(Aastra_get_label('Current Status',$language));
				$text_action_array=array();
				if($away['status']==AA_PRESENCE_AVAILABLE)
					{
					foreach($status_text as $key=>$status)
						{
						if(($key!=AA_PRESENCE_AVAILABLE) and ($key!=AA_PRESENCE_DISCONNECTED)) $array_action[]=$key;	
						}
					if(!$AA_ISYMPHONY) $array_action[]=AA_PRESENCE_DISCONNECTED;
					foreach($array_action as $state)
						{
						switch($away['action'][$state])
							{
							case AA_PRESENCE_ACT_DND:
								$text_action_array[]=sprintf(Aastra_get_label('DND if %s',$language),$status_text[$state]['label']);
								break;
							case AA_PRESENCE_ACT_CFWD:
								$text_action_array[]=sprintf(Aastra_get_label('Forward if %s',$language),$status_text[$state]['label']);
								break;
							case AA_PRESENCE_ACT_FM:
								$text_action_array[]=sprintf(Aastra_get_label('Follow-me if %s',$language),$status_text[$state]['label']);
								break;
							case AA_PRESENCE_ACT_NOTHING:
								$text_action_array[]=sprintf(Aastra_get_label('No action if %s',$language),$status_text[$state]['label']);
								break;
							}
						}
					}
				else
					{
					switch($away['action'][$away['status']])
						{
						case AA_PRESENCE_ACT_DND:
							if(Aastra_manage_dnd_Asterisk($user,'get')=='1') $text_action_array[]=Aastra_get_label('DND activated',$language);
							break;
						case AA_PRESENCE_ACT_CFWD:
							if(Aastra_manage_cf_Asterisk($user,'get')!='') $text_action_array[]=Aastra_get_label('Forward activated',$language);
							break;
						case AA_PRESENCE_ACT_FM:
							if(Aastra_manage_followme_Asterisk($user,'get_status')=='1') $text_action_array[]=Aastra_get_label('Follow-me activated',$language);
							break;
						}
					}
				if(count($text_action_array)!=0) $text_action=implode(', ',$text_action_array);
				else $text_action='';
				if($away['status']!=AA_PRESENCE_AVAILABLE)
					{
					if(Aastra_is_datetime_input_supported())
						{
						$line=Aastra_format_presence_dt_Asterisk($away['return']);
						$dt=$line[0];
						if($line[1]!='') $dt.=' '.$line[1];
						if($text_action!='') $text=sprintf(Aastra_get_label('Currently %s. %s. %s.',$language),$status_text[$away['status']]['label'],$dt,$text_action);
						else $text=sprintf(Aastra_get_label('Currently %s. %s.',$language),$status_text[$away['status']]['label'],$dt);
						}
					else 
						{
						if($text_action!='') $text=sprintf(Aastra_get_label('Currently %s. %s.',$language),$status_text[$away['status']]['label'],$text_action);
						else $text=sprintf(Aastra_get_label('Currently %s.',$language),$status_text[$away['status']]['label']);
						}
					}
				else 
					{
					if($text_action!='') $text=sprintf(Aastra_get_label('Currently %s. %s.',$language),$status_text[$away['status']]['label'],$text_action);
					else $text=sprintf(Aastra_get_label('Currently %s.',$language),$status_text[$away['status']]['label']);
					}
				$object->setText($text);
				}

			# Softkeys
			if($nb_softkeys==6)
				{
				$object->addSoftKey('1',Aastra_get_label('Chg Status',$language),$XML_SERVER.'&action=change');
				if($away['status']!=AA_PRESENCE_AVAILABLE)
					{
					if(Aastra_is_datetime_input_supported()) $object->addSoftKey('2',Aastra_get_label('Return',$language),$XML_SERVER.'&action=input_date_time');
					$object->addSoftKey('4',Aastra_get_label('Temp Msg',$language),$XML_SERVER.'&action=rec_msg');
					}
				else 
					{
					$object->addSoftKey('2',Aastra_get_label('My Numbers',$language),$XML_SERVER.'&action=info');
					$object->addSoftKey('4',Aastra_get_label('Prefs',$language),$XML_SERVER.'&action=input_prefs');
					}
				$object->addSoftKey('5',Aastra_get_label('Where is?',$language),$XML_SERVER_PATH.'/directory.php?user='.$user.'&origin=presence');
				$object->addSoftKey('6',Aastra_get_label('Exit',$language),'SoftKey:Exit');
				}
			else
				{
				$object->addSoftKey('1',Aastra_get_label('Change Status',$language),$XML_SERVER.'&action=change');
				if($away['status']!=AA_PRESENCE_AVAILABLE)
					{
					$object->addSoftKey('2',Aastra_get_label('I AM BACK',$language),$XML_SERVER.'&action=set_change&value1='.AA_PRESENCE_AVAILABLE);
					$object->addSoftKey('3',Aastra_get_label('Return Date/Time',$language),$XML_SERVER.'&action=input_date_time');
					$object->addSoftKey('5',Aastra_get_label('Temp Message',$language),$XML_SERVER.'&action=rec_msg');
					}
				else 
					{
					$object->addSoftKey('2',Aastra_get_label('My Numbers',$language),$XML_SERVER.'&action=info');
					$object->addSoftKey('6',Aastra_get_label('Preferences',$language),$XML_SERVER.'&action=input_prefs');
					}
				$object->addSoftKey('7',Aastra_get_label('Where is?',$language),$XML_SERVER_PATH.'/directory.php?user='.$user.'&origin=presence');
				$object->addSoftKey('10',Aastra_get_label('Exit',$language),'SoftKey:Exit');
				}
			}
		else
			{
			# No softkey so display actions
			require_once('AastraIPPhoneTextMenu.class.php');
			$object=new AastraIPPhoneTextMenu();
			if(Aastra_is_style_textmenu_supported()) $object->setStyle('radio');
			$object->setDestroyOnExit();
			$object->setTitle($status_text[$away['status']]['label']);
			if($away['status']!=AA_PRESENCE_AVAILABLE) $object->addEntry(Aastra_get_label('I am back',$language), $XML_SERVER.'&action=set_change&value1='.AA_PRESENCE_AVAILABLE,'');
			foreach($status_text as $key=>$value) $object->addEntry($value['label'],$XML_SERVER.'&action=set_change&value1='.$key,'');
			if($away['status']!=AA_PRESENCE_AVAILABLE)
				{
				if(Aastra_is_datetime_input_supported()) $object->addEntry(Aastra_get_label('Return Date/Time',$language),$XML_SERVER.'&action=input_date_time');
				$object->addEntry(Aastra_get_label('Temp Msg',$language),$XML_SERVER.'&action=rec_msg');
				}
			$object->addEntry(Aastra_get_label('Preferences',$language),$XML_SERVER.'&action=input_prefs');
			}
		break;

	# User information
	case 'info':
		# Retrieve stored data
		$array_user=Aastra_manage_userinfo_Asterisk($user,'get');

		# All indexes
		$array_index=array(	'cell'=>array('1',Aastra_get_label('(M)',$language),'1'),
					'home'=>array('2',Aastra_get_label('(H)',$language),'2'),
					'other'=>array('3',Aastra_get_label('(O)',$language),'3')
					);

		# Personal phone numbers
		require_once('AastraIPPhoneTextMenu.class.php');
		$object=new AastraIPPhoneTextMenu();
		$object->setDestroyOnExit();
		if($is_style_textmenu) $object->setStyle('none');
		$object->setTitle(Aastra_get_label('Personal Numbers',$language));
		if($type!='') $object->setDefaultIndex($array_index[$type][2]);
		
		# Numbers
		foreach($array_index as $key=>$value1)
			{
			if($array_user[$key]!='') $label=$array_user[$key];
			else $label='.....................';
			if($is_icons) $icon=$value1[0];
			else 
				{
				$icon='';
				$label=$value1[1].' '.$label;
				}
			$object->addEntry($label,$XML_SERVER.'&action=edit_info&type='.$key,$key,$icon);
			}

		# Icons
		if($is_icons)
			{
			if(Aastra_phone_type()!=5)
				{
				$object->addIcon(1,Aastra_get_custom_icon('Cellphone'));
				$object->addIcon(2,Aastra_get_custom_icon('Home'));
				$object->addIcon(3,Aastra_get_custom_icon('Phone'));
				}
			else
				{
				$object->addIcon(3,'Icon:PhoneOnHook');
				$object->addIcon(1,'Icon:CellPhone');
				$object->addIcon(2,'Icon:Home');
				}
			}


		# Softkeys
		if($nb_softkeys)
			{
			if($nb_softkeys==6)
				{
				$object->addSoftkey('1',Aastra_get_label('Edit',$language), 'SoftKey:Select');
				$object->addSoftkey('2',Aastra_get_label('Clear',$language), $XML_SERVER.'&action=clear_info');
				$object->addSoftkey('5',Aastra_get_label('Back',$language), $XML_SERVER);
				$object->addSoftkey('6',Aastra_get_label('Exit',$language), 'SoftKey:Exit');
				}
			else
				{
				$object->addSoftkey('1',Aastra_get_label('Clear',$language), $XML_SERVER.'&action=clear_info');
				$object->addSoftkey('9',Aastra_get_label('Back',$language), $XML_SERVER);
				$object->addSoftkey('10',Aastra_get_label('Exit',$language), 'SoftKey:Exit');
				$object->setCancelAction($XML_SERVER);
				}
			}
		break;

	# Edit personal numbers
	case 'edit_info':
		# Retrieve stored data
		$array_user=Aastra_manage_userinfo_Asterisk($user,'get');

		# Various labels
		$array_type=array(	'cell'=>array('Cell Phone','Cell phone number'),
					'home'=>array('Home Phone','Home phone number'),
					'other'=>array('Other Phone','Other phone number')
					);

		# Input new call forward
		require_once('AastraIPPhoneInputScreen.class.php');
		$object=new AastraIPPhoneInputScreen();
		$object->setDestroyOnExit();
		$object->setTitle($array_type[$type][0]);
		$object->setPrompt($array_type[$type][1]);
		$object->setParameter('value1');
		$object->setType('number');
		$object->setURL($XML_SERVER.'&action=set_info&type='.$type);
		$object->setDefault($array_user[$type]);

		# Softkeys 
		if($nb_softkeys)
			{
			if($nb_softkeys==6)
				{
				$object->addSoftkey('1',Aastra_get_label('Backspace',$language),'SoftKey:BackSpace');
				$object->addSoftkey('5',Aastra_get_label('Cancel',$language),$XML_SERVER.'&action=info&type='.$type);
				$object->addSoftkey('6',Aastra_get_label('Submit',$language),'SoftKey:Submit');
				}
			else
				{
				$object->addSoftkey('10',Aastra_get_label('Cancel',$language),$XML_SERVER.'&action=info&type='.$type);
				$object->setCancelAction($XML_SERVER.'&action=info&type='.$type);
				}
			}
		break;

	# Edit forward number
	case 'edit_number1':
	case 'edit_number2':
		# Get current status
		$away=Aastra_manage_presence_Asterisk($user,'action');

		# Input new number
		require_once('AastraIPPhoneInputScreen.class.php');
		$object=new AastraIPPhoneInputScreen();
		$object->setDestroyOnExit();
		$object->setTitle(Aastra_get_label('Destination',$language));
		$object->setPrompt(Aastra_get_label('Enter number',$language));
		$object->setParameter('type');
		$object->setType('number');
		$object->setURL($XML_SERVER.'&action=set_number&state='.$state.'&value1='.AA_PRESENCE_ACT_CFWD.'&selection='.$action);
		$object->setDefault($away['act_param'][$state]);

		# Softkeys 
		if($nb_softkeys)
			{
			if($nb_softkeys==6)
				{
				$object->addSoftkey('1',Aastra_get_label('Backspace',$language),'SoftKey:BackSpace');
				if($action=='edit_number1') $object->addSoftkey('5',Aastra_get_label('Cancel',$language),$XML_SERVER.'&action=select_number&state='.$state);
				else $object->addSoftkey('5',Aastra_get_label('Cancel',$language),$XML_SERVER.'&action=input_prefs2&state='.$state);
				$object->addSoftkey('6',Aastra_get_label('Submit',$language),'SoftKey:Submit');
				}
			else
				{
				if($action=='edit_number1') 
					{
					$object->addSoftkey('10',Aastra_get_label('Cancel',$language),$XML_SERVER.'&action=select_number&state='.$state);
					$object->setCancelAction($XML_SERVER.'&action=select_number&state='.$state);
					}
				else 	
					{
					$object->addSoftkey('10',Aastra_get_label('Cancel',$language),$XML_SERVER.'&action=input_prefs2&state='.$state);
					$object->setCancelAction($XML_SERVER.'&action=input_prefs2&state='.$state);
					}
				}
			}
		break;

	# Select destination
	case 'select_number':
		# Retrieve stored data
		$array_user=Aastra_manage_userinfo_Asterisk($user,'get');

		# All indexes
		$array_index=array(	'cell'=>array('1',Aastra_get_label('(M)',$language),'1'),
					'home'=>array('2',Aastra_get_label('(H)',$language),'2'),
					'other'=>array('3',Aastra_get_label('(O)',$language),'3')
					);

		# Personal phone numbers
		require_once('AastraIPPhoneTextMenu.class.php');
		$object=new AastraIPPhoneTextMenu();
		$object->setDestroyOnExit();
		if($is_style_textmenu) $object->setStyle('radio');
		$object->setTitle(Aastra_get_label('Select Destination',$language));
		
		# Numbers
		foreach($array_index as $key=>$value)
			{
			if($array_user[$key]!='') 
				{
				$label=$array_user[$key];
				if($is_icons) $icon=$value[0];
				else 
					{
					$icon='';
					$label=$value[1].' '.$label;
					}
				$object->addEntry($label,$XML_SERVER.'&action=set_prefs&state='.$state.'&value1='.AA_PRESENCE_ACT_CFWD.'&type='.$array_user[$key],'',$icon);
				}
			}

		# Manuel entry
		if($is_icons) $icon='4';
		else $icon='';
		$object->addEntry(Aastra_get_label('Enter Number',$language),$XML_SERVER.'&action=edit_number1&state='.$state,'',$icon);

		# Icons
		if($is_icons)
			{
			$object->addIcon(1,Aastra_get_custom_icon('Cellphone'));
			$object->addIcon(2,Aastra_get_custom_icon('Home'));
			$object->addIcon(3,Aastra_get_custom_icon('Phone'));
			$object->addIcon(4,Aastra_get_custom_icon('Keypad'));
			}

		# Softkeys
		if($nb_softkeys)
			{
			if($nb_softkeys==6)
				{
				$object->addSoftkey('1',Aastra_get_label('Select',$language), 'SoftKey:Select');
				$object->addSoftkey('5',Aastra_get_label('Cancel',$language), $XML_SERVER.'&action=input_prefs2&state='.$state);
				$object->addSoftkey('6',Aastra_get_label('Exit',$language), 'SoftKey:Exit');
				}
			else
				{
				$object->addSoftkey('9',Aastra_get_label('Cancel',$language), $XML_SERVER.'&action=input_prefs2&state='.$state);
				$object->addSoftkey('10',Aastra_get_label('Exit',$language), 'SoftKey:Exit');
				}
			}

		# Cancel action
		$object->setCancelAction($XML_SERVER.'&action=input_prefs2&state='.$state);
		break;

	# Record Temp message
	case 'rec_msg':
		# Get current status
		$away=Aastra_manage_presence_Asterisk($user,'get');

		# Next step
		require_once('AastraIPPhoneExecute.class.php');
		$object=new AastraIPPhoneExecute();

		# Test if phone can dial
		if(!Aastra_is_dialuri_supported())
			{
			# No display change
			$object->addEntry('');
			$object->output(True);

			# Disable DND if needed
			$dnd=Aastra_manage_dnd_Asterisk($user,'get');
			if(($away['action'][$away['status']]==AA_PRESENCE_ACT_DND) or ($dnd==1))
				{
				$dnd=1;
				Aastra_manage_dnd_Asterisk($user,'disable');
				}

			# Disable Follow-me if needed
			$follow=Aastra_manage_followme_Asterisk($user,'get_status');
			if(($away['action'][$away['status']]==AA_PRESENCE_ACT_FM) or ($follow==1))
				{
				$follow=1;
				Aastra_manage_followme_Asterisk($user,'disable');
				}

			# Disable CFWD if needed
			$cfwd=Aastra_manage_cf_Asterisk($user,'get','');
			if(($away['action'][$away['status']]==AA_PRESENCE_ACT_CFWD) or ($cfwd!=''))
				{
				if($cfwd=='') $cfwd=$away['act_param'][$away['status']];
				Aastra_manage_cf_Asterisk($user,'cancel','');
				}

			# Launch the call
			$vmail_options=Aastra_get_user_context($user,'vmail-options');
			if($vmail_options['auto_answer']=='') 
				{
				$vmail_options['auto_answer']='1';
				Aastra_save_user_context($ext,'vmail-options',$vmail_options);
				}
			if($vmail_options['auto_answer']=='1') Aastra_originate_Asterisk('Local/'.Aastra_get_userdevice_Asterisk($user).'@from-internal','s','record-temp-vm',1,'',Aastra_get_label('RecordTemporary',$language).' <0>',array('_USER='.Aastra_get_userdevice_Asterisk($user),'_ALERT_INFO=info=alert-autoanswer'));
			else Aastra_originate_Asterisk('Local/'.Aastra_get_userdevice_Asterisk($user).'@from-internal','s','record-temp-vm',1,'',Aastra_get_label('RecordTemporary',$language).' <0>',array('_USER='.Aastra_get_userdevice_Asterisk($user)));

			# Re-enable DND/Follow-me/CFWD if needed
			if($dnd==1) Aastra_manage_dnd_Asterisk($user,'enable');
			if($follow==1) Aastra_manage_followme_Asterisk($user,'enable');
			if($cfwd!='') Aastra_manage_cf_Asterisk($user,'set',$cfwd);
			}
		else
			{
			# Save session
			$array=array(	'uri_onhook'=>$XML_SERVER,
					'user'=>$user,
					'type'=>'temp',
					'action'=>'record'
					);
			Aastra_save_session('vmail','600',$array,$user);

			# Dial special number
			$object->addEntry('Dial:vmail');
			}
		break;

	# Display 
	case 'display_back':
		# Display user message
		if(Aastra_is_formattedtextscreen_supported())
			{
			require_once('AastraIPPhoneFormattedTextScreen.class.php');
			$object=new AastraIPPhoneFormattedTextScreen();
			if(Aastra_size_formattedtextscreen()>2) $object->addLine('');
			if(Aastra_is_formattedtextscreen_color_supported()) 
				{
				$object->addLine('');
				$object->addLine('');
				$object->addLine('');
				$object->addLine(Aastra_get_label('Welcome Back',$language),'large','center','green');
				}
			else $object->addLine(Aastra_get_label('Welcome Back',$language),'double','center');
			}
		else
			{
			require_once('AastraIPPhoneTextScreen.class.php');
			$object=new AastraIPPhoneTextScreen();
			$object->setTitle(Aastra_get_label('Presence',$language));
			$object->setText(Aastra_get_label('Welcome Back',$language));
			}
		$object->setDestroyOnExit();

		# Time-out on the display
		if(Aastra_is_timeout_supported()) $object->setTimeout('3');

		# Softkeys
		if($nb_softkeys)
			{
			if($nb_softkeys==6)
				{
				if(Aastra_is_timeout_supported()) $object->addSoftKey('6','','SoftKey:Exit');
				else $object->addSoftKey('6',Aastra_get_label('Close',$language),'SoftKey:Exit');
				}	
			else
				{
				if(!Aastra_is_timeout_supported()) $object->addSoftKey('10',Aastra_get_label('Close',$language),'SoftKey:Exit');
				}
			}
		break;
	}

# Display answer
$object->output();
exit;
?>
