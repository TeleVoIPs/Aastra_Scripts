<?php
#############################################################################
# Asterisk Call Forward
#
# Aastra SIP Phones 1.4.2 or better
#
# Copyright 2005-2010 Aastra Telecom Ltd
#
# Supported Aastra Phones
#   All phones
#
# Usage
# 	script.php?user=USER
# 	USER is the extension of the phone on the Asterisk platform
#    OR
#	script.php?user=USER&value=XXXXX
# 	USER is the extension of the phone on the Asterisk platform
#      XXXXX is the number to forward to
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
$action=Aastra_getvar_safe('action');
$value=Aastra_getvar_safe('value');
$type=Aastra_getvar_safe('type');
$selection=Aastra_getvar_safe('selection');

# Trace
Aastra_trace_call('cfwd_asterisk','user='.$user.', action='.$action.', value='.$value.', type='.$type.', selection='.$selection);

# Test User Agent
Aastra_test_phone_versions(array('1'=>'1.4.2.','2'=>'1.4.2.','3'=>'2.5.3.','4'=>'2.5.3.','5'=>'3.0.1.'),'0');

# Get Language
$language=Aastra_get_language();

# Get global compatibility
$nb_softkeys=Aastra_number_softkeys_supported();
$is_icons=Aastra_is_icons_supported();
$is_style_textmenu=Aastra_is_style_textmenu_supported();

# Check launch mode
if($action=='')
	{
	if($value!='')
		{
		# Get current status
		$cf=Aastra_manage_cf_Asterisk($user,'main',$value);

		# Toggle
		if($cf=='') $action='set';
		else $action='cancel';
		$mode='1';
		}
	else $action='main';
	}

# Keep return URI
$XML_SERVER.='?user='.$user;

# Pre-process action
switch($action)
	{
	# Modify or clear personal number
	case 'clear':
	case 'modify':
		# Retrieve stored data
		$array_user=Aastra_manage_userinfo_Asterisk($user,'get');

		# Update data
		if($action=='modify') $array_user[$type]=$value;
		else
			{
			$type=$selection;
			$array_user[$type]='';
			}
		Aastra_manage_userinfo_Asterisk($user,'set',$array_user);

		# Next action
		$action='info';
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

		# Get call forward
		$cf=Aastra_manage_cf_Asterisk($user,$action,$value);

		# Get last CFWD status
		$data=Aastra_get_user_context($user,'cfwd');
		$last=$data['last'];
		$key=$data['key'];

		# Save CFWD status
		$data['last']=$cf;
		if($cf!=$last) Aastra_save_user_context($user,'cfwd',$data);

		# Update needed?
		if(($action=='check') and ($cf==$last)) $update=0;
		if(($action=='register') and ($cf=='')) $update=0;

		# Prepare display update
		require_once('AastraIPPhoneExecute.class.php');
		$object=new AastraIPPhoneExecute();
		if($update==1)
			{
			$object->addEntry($XML_SERVER.'&action=msg&value='.$cf);
			if($key!='')
				{
				if(Aastra_is_ledcontrol_supported())
					{
					if($cf=='')$object->AddEntry('Led: '.$key.'=off');
					else $object->AddEntry('Led: '.$key.'=on');
					}
				}
			}
		else
			{
			# Do nothing
			$object->addEntry('');
			}
		break;

	# Modify current setting
	case 'cancel':
	case 'cancel2':
	case 'set':
		# Set call forward
		if($action!='cancel2') $cf=Aastra_manage_cf_Asterisk($user,$action,$value);
		else  $cf=Aastra_manage_cf_Asterisk($user,'cancel',$value);

		# Prepare display update
		require_once('AastraIPPhoneExecute.class.php');
		$object=new AastraIPPhoneExecute();

		# Sync LED and idle screen
		$object->addEntry($XML_SERVER.'&action=check');

		$data=Aastra_get_user_context($user,'cfwd');
		$last=$data['last'];
		$key=$data['key'];
		$data['last']=$cf;
		if($cf!=$last) 
			{
			Aastra_save_user_context($user,'cfwd',$data);
			$object->setBeep();
			$object->addEntry($XML_SERVER.'&action=msg&value='.$cf);
			if($key!='')
				{
				if(Aastra_is_ledcontrol_supported())
					{
					if($cf=='')$object->AddEntry('Led: '.$key.'=off');
					else $object->AddEntry('Led: '.$key.'=on');
					}
				}
			}

		# Send a SIP Notification if mode is device and user
		if((!$AA_FREEPBX_USEDEVSTATE) and ($AA_FREEPBX_MODE=='2')) Aastra_propagate_changes_Asterisk($user,Aastra_get_userdevice_Asterisk($user),array('cfwd'));
		
		# Display status screen
		if(($mode!='1') and ($action!='cancel2')) $object->AddEntry($XML_SERVER);
		break;

	# Enter forward number
	case 'change':
	case 'change2':
		# Retrieve last input
		$data=Aastra_get_user_context($user,'cfwd');

		# Input new call forward
		require_once('AastraIPPhoneInputScreen.class.php');
		$object=new AastraIPPhoneInputScreen();
		$object->setDestroyOnExit();
		$object->setTitle(sprintf(Aastra_get_label('Call Forward for %s',$language),Aastra_get_userdevice_Asterisk($user)));
		$object->setPrompt(Aastra_get_label('Enter destination',$language));
		$object->setParameter('value');
		$object->setType('number');
		$object->setURL($XML_SERVER.'&action=set');
		$object->setDefault($data['input']);

		# Softkeys 
		if($nb_softkeys)
			{
			if($nb_softkeys==6)
				{
				$object->addSoftkey('1',Aastra_get_label('Backspace',$language),'SoftKey:BackSpace');
				if($action=='change') $object->addSoftkey('4',Aastra_get_label('Cancel',$language),$XML_SERVER);
				else $object->addSoftkey('4',Aastra_get_label('Cancel',$language),$XML_SERVER.'&action=select');
				$object->addSoftkey('5',Aastra_get_label('Submit',$language),'SoftKey:Submit');
				$object->addSoftkey('6',Aastra_get_label('Exit',$language),'SoftKey:Exit');
				}
			else
				{
				if($action=='change') $object->addSoftkey('9',Aastra_get_label('Cancel',$language),$XML_SERVER);
				else $object->addSoftkey('9',Aastra_get_label('Cancel',$language),$XML_SERVER.'&action=select');
				$object->addSoftkey('10',Aastra_get_label('Exit',$language),'SoftKey:Exit');
				}
			}

		# Cancel Action (Back)
		if($action=='change') $object->setCancelAction($XML_SERVER);
		else $object->setCancelAction($XML_SERVER.'&action=select');
		break;

	# Edit personal numbers
	case 'edit':
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
		$object->setParameter('value');
		$object->setType('number');
		$object->setURL($XML_SERVER.'&action=modify&type='.$type);
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
			else $object->addSoftkey('9',Aastra_get_label('Cancel',$language),$XML_SERVER.'&action=info&type='.$type);
			}

		# Cancel Action (back)
		$object->setCancelAction($XML_SERVER.'&action=info&type='.$type);
		break;

	# Update idle screen message status
	case 'msg':
		# Update screen message
		require_once('AastraIPPhoneStatus.class.php');
		$object=new AastraIPPhoneStatus();
		$object->setSession('aastra-xml');
		$index=Aastra_get_status_index_Asterisk('cfwd');
		if($value=='') $object->addEntry($index,'');
		else 
			{
			if(Aastra_is_status_uri_supported())
				{
				$object->addEntry($index,Aastra_get_label('CFWD activated',$language),'',NULL,$XML_SERVER.'&action=cancel2',1);
				$object->addIcon('1','Icon:CallFailed');
				}
			else $object->addEntry($index,Aastra_get_label('CFWD activated',$language));
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
		foreach($array_index as $key=>$value)
			{
			if($array_user[$key]!='') $label=$array_user[$key];
			else $label='.....................';
			if($is_icons) $icon=$value[0];
			else 
				{
				$icon='';
				$label=$value[1].' '.$label;
				}
			$object->addEntry($label,$XML_SERVER.'&action=edit&type='.$key,$key,$icon);
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
				$object->addSoftkey('2',Aastra_get_label('Clear',$language), $XML_SERVER.'&action=clear');
				$object->addSoftkey('5',Aastra_get_label('Back',$language), $XML_SERVER);
				$object->addSoftkey('6',Aastra_get_label('Exit',$language), 'SoftKey:Exit');
				}
			else
				{
				$object->addSoftkey('6',Aastra_get_label('Clear',$language), $XML_SERVER.'&action=clear');
				$object->addSoftkey('9',Aastra_get_label('Back',$language), $XML_SERVER);
				$object->addSoftkey('10',Aastra_get_label('Exit',$language), 'SoftKey:Exit');
				}
			}
		else $object->addEntry(Aastra_get_label('Back',$language), $XML_SERVER);

		# Cancel Action (back)
		$object->setCancelAction($XML_SERVER);
		break;

	# Select destination
	case 'select':
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
				$object->addEntry($label,$XML_SERVER.'&action=set&value='.$array_user[$key],'',$icon);
				}
			}

		# Manuel entry
		if($is_icons) $icon='4';
		else $icon='';
		$object->addEntry(Aastra_get_label('Enter Number',$language),$XML_SERVER.'&action=change2','',$icon);

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
				$object->addSoftkey('5',Aastra_get_label('Cancel',$language), $XML_SERVER);
				$object->addSoftkey('6',Aastra_get_label('Exit',$language), 'SoftKey:Exit');
				}
			else
				{
				$object->addSoftkey('9',Aastra_get_label('Cancel',$language), $XML_SERVER);
				$object->addSoftkey('10',Aastra_get_label('Exit',$language), 'SoftKey:Exit');
				}
			}

		# Cancel action
		$object->setCancelAction($XML_SERVER);
		break;

	# Default is Current status
	case 'main':
		# Authenticate user
		Aastra_check_signature_Asterisk($user);

		# Retrieve personal numbers
		$array_user=Aastra_manage_userinfo_Asterisk($user,'get');
		$change='change';
		foreach($array_user as $key=>$value)
			{
			if($value!='') $change='select';
			break;
			}

		# Retrieve current status
		$cf=Aastra_manage_cf_Asterisk($user,$action,$value);

		# Softkeys?
		if($nb_softkeys)
			{
			# Textscreen for softkey phones
			require_once('AastraIPPhoneTextScreen.class.php');
			$object=new AastraIPPhoneTextScreen();
			$object->setDestroyOnExit();
			$object->setTitle(sprintf(Aastra_get_label('Call Forward for %s',$language),Aastra_get_userdevice_Asterisk($user)));
			if($cf=='') 
				{
				$object->setText(Aastra_get_label('Call Forward is currently deactivated.',$language));	
				$object->addSoftkey('1',Aastra_get_label('Activate',$language), $XML_SERVER.'&action='.$change);
				}
			else 
				{
				$array_user=array_flip($array_user);
				$text=sprintf(Aastra_get_label('Call Forward is currently set to %s',$language),$cf);
				if($array_user[$cf]!='') 
					{
					$array_label=array(	'cell'=>Aastra_get_label('Cell',$language),
								'home'=>Aastra_get_label('Home',$language),
								'other'=>Aastra_get_label('Other',$language)
								);

					$text.=' ('.$array_label[$array_user[$cf]].')';
					}
				$object->setText($text);
				$object->addSoftkey('1',Aastra_get_label('Change',$language), $XML_SERVER.'&action='.$change);
				$object->addSoftkey('2',Aastra_get_label('Deactivate',$language), $XML_SERVER.'&action=cancel');
				}
			if($nb_softkeys==6) 
				{
				$object->addSoftkey('3',Aastra_get_label('My Numbers',$language), $XML_SERVER.'&action=info');
				$object->addSoftkey('6',Aastra_get_label('Exit',$language), 'SoftKey:Exit');
				}
			else 
				{
				$object->addSoftkey('6',Aastra_get_label('My Numbers',$language), $XML_SERVER.'&action=info');
				$object->addSoftkey('10',Aastra_get_label('Exit',$language), 'SoftKey:Exit');
				}
			}
		else
			{
			# TextMenu for non softkey phone
			require_once('AastraIPPhoneTextMenu.class.php');
			$object=new AastraIPPhoneTextMenu();
			$object->setDestroyOnExit();
			if ($cf=='') 
				{
				$object->setTitle(Aastra_get_label('CFWD deactivated',$language));		
				$object->addEntry(Aastra_get_label('Activate',$language), $XML_SERVER.'&action='.$change);
				}
			else 
				{
				$object->setTitle(sprintf(Aastra_get_label('CFWD set (%s)',$language),$cf));
				$object->addEntry(Aastra_get_label('Deactivate',$language), $XML_SERVER.'&action=cancel');
				$object->addEntry(Aastra_get_label('Change',$language), $XML_SERVER.'&action='.$change);
				}
			$object->addEntry(Aastra_get_label('My numbers',$language), $XML_SERVER.'&action=info');
			$object->addEntry(Aastra_get_label('Exit',$language), 'SoftKey:Exit');
			}
		break;
	}

# Display answer
$object->output();
exit;
?>
