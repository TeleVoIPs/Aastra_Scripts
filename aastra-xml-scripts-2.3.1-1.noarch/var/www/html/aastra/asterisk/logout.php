<?php
#############################################################################
# Asterisk Logout
#
# Aastra SIP Phones 1.4.2 or better
#
# Copyright 2005-2010 Aastra Telecom Ltd
#
# Supported Aastra Phones
#   All phones
#
# script.php?user=USER
#   USER is the user extension
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
# Private functions
#############################################################################
function format_time($input)
{
Global $AA_FORMAT_DT;

# Split the input
$timepart=substr($input,0,5);
list($hour,$minute)=explode('-',$timepart);
if($AA_FORMAT_DT=='US')  
	{
	switch($hour)
		{
		case '00':
		case '0':
			$hour='12';
			$ampm='AM';
			break;
		case '12':
			$ampm='PM';
			break;
		default:
			if($hour>12)
				{
				$hour-=12;
				$ampm='PM';
				}
			else $ampm='AM';
			break;
		}
	$return=$hour.':'.$minute.' '.$ampm;
	}
else $return=$hour.':'.$minute;

# Return formatted text
return($return);
}

#############################################################################
# Body
#############################################################################
# Retrieve parameters
$user=Aastra_getvar_safe('user');
$password=Aastra_getvar_safe('password');
$action=Aastra_getvar_safe('action','input');
$page=Aastra_getvar_safe('page','1');
$value=Aastra_getvar_safe('value');
$origin=Aastra_getvar_safe('origin');

# Trace
Aastra_trace_call('logout_asterisk','user='.$user.', password='.$password.', action='.$action);

# Test User Agent
Aastra_test_phone_versions(array('1'=>'1.4.2.','2'=>'1.4.2.','3'=>'2.5.3.','4'=>'2.5.3.','5'=>'3.0.1.'),'0');

# Get MAC address and type of phone
$header=Aastra_decode_HTTP_header();

# Save return URI
$XML_SERVER.='?user='.$user;

# Get Language
$language=Aastra_get_language();

# Compatibility
$nb_softkeys=Aastra_number_softkeys_supported();
$is_sip_notify=Aastra_is_sip_notify_supported();

# Device must be ad-hoc
if($AA_FREEPBX_MODE=='2')
	{
	$device_info=Aastra_get_device_info_Asterisk($user);
	if($device_info['type']!='adhoc') 
		{
		# Display error as a TextScreen
		require_once('AastraIPPhoneTextScreen.class.php');
		$object=new AastraIPPhoneTextScreen();
		$object->setDestroyOnExit();
		$object->setTitle(Aastra_get_label('Error',$language));
		$object->setText(Aastra_get_label('You are not allowed to logout from this phone.',$language));

		# Softkeys
		if($nb_softkeys)
			{
			if($nb_softkeys==6) $object->addSoftkey('6',Aastra_get_label('Exit',$language),'SoftKey:Exit');
			else $object->addSoftkey('10',Aastra_get_label('Exit',$language),'SoftKey:Exit');
			}
		$object->output();
		exit;
		}
	else
		{
		if($device_info['default_user']==$device_info['user'])
			{
			# In fact login
			require_once('AastraIPPhoneExecute.class.php');
			$object=new AastraIPPhoneExecute();
			$object->addEntry($XML_SERVER_PATH.'login.php?device='.$user);
			$object->output();
			exit;
			}
		}
	}

# Process action
switch($action)
	{
	# Periodic update
	case 'check':
	case 'register':
		# Update needed
		$update=1;

		# Get presence
		$logout=Aastra_manage_presence_Asterisk($user,'logout');

		# Get last status, last logout and key
		$data=Aastra_get_user_context($user,'autologout');
		$last=$data['last'];

		# Save logout
		$data['last']=$logout;
		if($logout!=$last) Aastra_save_user_context($user,'autologout',$data);

		# Update needed?
		if(($action=='check') and ($last==$logout)) $update=0;
		if(($action=='register') and ($logout=='')) $update=0;

		# Prepare display update
		require_once('AastraIPPhoneExecute.class.php');
		$object=new AastraIPPhoneExecute();

		# Logout update
		if($update==1) $object->addEntry($XML_SERVER.'&action=msg&value='.$logout);
		else
			{
			# Do nothing
			$object->addEntry('');
			}
		break;

	# Update idle screen message status
	case 'msg':
		# update screen message
		require_once('AastraIPPhoneStatus.class.php');
		$object=new AastraIPPhoneStatus();
		$object->setSession('aastra-xml');
		$index=Aastra_get_status_index_Asterisk('logout');
		if($value=='') $object->addEntry($index,'');
		else 
			{
			if(Aastra_size_display_line()>16) $object->addEntry($index,sprintf(Aastra_get_label('Auto-logout %s',$language),format_time($value)));
			else $object->addEntry($index,Aastra_get_label('Auto-logout set',$language));
			}
		break;

	# Input Logout
	case 'input_logout':
		# Get current status
		$logout=Aastra_manage_presence_Asterisk($user,'logout');

		# Create input screen
		require_once('AastraIPPhoneInputScreen.class.php');
		$object=new AastraIPPhoneInputScreen();
		$object->setDestroyOnExit();
		$object->setTitle(Aastra_get_label('Set Logout Time',$language));
		$default='';
		if($AA_FORMAT_DT=='US') 
			{
			$object->setType('timeUS');
			$default=date('h:i:sA',mktime(date('H'),date('i'),0,date('m'),date('d'),date('Y')));
			}
		else 
			{
			$default=date('H:i:s',mktime(date('H'),date('i'),0,date('m'),date('d'),date('Y')));
			$object->setType('timeInt');
			}
		$object->setPrompt(Aastra_get_label('Enter Time',$language));
		$object->setParameter('value');
		$object->setDefault($default);
		$object->setURL($XML_SERVER.'&action=set_logout&origin='.$origin.'&password='.$password);

		# Softkeys
		if($nb_softkeys)
			{
			if($nb_softkeys==6)
				{
				$object->addSoftKey('1',Aastra_get_label('No Time',$language),$XML_SERVER.'&action=set_logout&value=&origin='.$origin.'&password='.$password);
				$object->addSoftKey('4',Aastra_get_label('Cancel',$language),$XML_SERVER.'&action='.$origin.'&password='.$password);
				$object->addSoftKey('5',Aastra_get_label('Submit',$language),'SoftKey:Submit');
				$object->addSoftKey('6',Aastra_get_label('Exit',$language),'SoftKey:Exit');
				}
			else
				{
				$object->addSoftKey('1',Aastra_get_label('No Time',$language),$XML_SERVER.'&action=set_logout&value=&origin='.$origin.'&password='.$password);
				$object->addSoftKey('9',Aastra_get_label('Cancel',$language),$XML_SERVER.'&action='.$origin.'&password='.$password);
				$object->addSoftKey('10',Aastra_get_label('Exit',$language),'SoftKey:Exit');
				}
			}
		$object->setCancelAction($XML_SERVER.'&action=main');
		break;

	# Set Logout time
	case 'set_logout':
		# Process input time
		if($value!='')
			{
			$timepart=substr($value,0,8);
			list($hour,$minute,$second)=explode(':',$timepart);
			if($AA_FORMAT_DT=='US')  
				{
				$pm=substr($value,8,2);
				if(($pm=='PM') or ($pm=='pm')) 
					{
					if($hour!='12') $hour+=12;
					}
				else
					{
					if($hour=='12') $hour=0;
					}
				}
			$value=$hour.'-'.$minute;
			}

		# Store date
		Aastra_manage_presence_Asterisk($user,'set','logout',$value);

		# Go back to input
		require_once('AastraIPPhoneExecute.class.php');
		$object=new AastraIPPhoneExecute();
		if($AA_AUTOLOGOUT_MSG) $object->addEntry($XML_SERVER.'&action=check');
		$object->addEntry($XML_SERVER.'&action='.$origin.'&password='.$password);
		break;

	# Display the reboot
	case 'display':
		require_once('AastraIPPhoneTextScreen.class.php');
		$object=new AastraIPPhoneTextScreen();
		$object->setDestroyOnExit();
		$object->setTitle(Aastra_get_label('REBOOT',$language));
		$object->setText(Aastra_get_label('Reboot',$language));
		if($nb_softkeys==6) $object->addSoftkey('6','','SoftKey:Exit');
		break;

	# Display the reset
	case 'display2':
		require_once('AastraIPPhoneTextScreen.class.php');
		$object=new AastraIPPhoneTextScreen();
		$object->setDestroyOnExit();
		$object->setTitle(Aastra_get_label('RESET',$language));
		$object->setText(Aastra_get_label('Resetting Phone...',$language));
		if($nb_softkeys==6) $object->addSoftkey('6','','SoftKey:Exit');
		break;

	# Get user input
	case 'input':
		# Extensions mode
		if($AA_FREEPBX_MODE=='1')
			{
			# If password enabled
			if($AA_LOGOUT_PW)
				{
				# Input password for the user
				require_once('AastraIPPhoneInputScreen.class.php');
				$object=new AastraIPPhoneInputScreen();
				$object->setDestroyOnExit();
				$object->setTitle(Aastra_get_label('Logout',$language));
				$object->setPrompt(Aastra_get_label('Enter password',$language));
				$object->setParameter('password');
				$object->setType('number');
				$object->setPassword();
				$object->setURL($XML_SERVER.'&action=submit');

				# Softkeys
				if($nb_softkeys)
					{
					if($nb_softkeys==6)
						{
						$object->addSoftkey('1',Aastra_get_label('Backspace',$language),'SoftKey:BackSpace');
						$object->addSoftkey('5',Aastra_get_label('Cancel',$language),'SoftKey:Exit');
						$object->addSoftkey('6',Aastra_get_label('Submit',$language),'SoftKey:Submit');
						}
					else $object->addSoftkey('9',Aastra_get_label('Cancel',$language),'SoftKey:Exit');
					}
				}
			else
				{
				# Only if auto-logout supported
				if(($is_sip_notify) and $AA_AUTOLOGOUT)
					{
					# Confirm logout
					require_once('AastraIPPhoneTextMenu.class.php');
					$object=new AastraIPPhoneTextMenu();
					$object->setDestroyOnExit();
					$object->setStyle('none');
					$object->setTitle(Aastra_get_label('Confirm Logout',$language));
					$object->addEntry(Aastra_get_label('Logout',$language),$XML_SERVER.'&action=logout');
					$logout=Aastra_manage_presence_Asterisk($user,'logout');
					if($logout=='') $object->addEntry(Aastra_get_label('Auto-logout (None)',$language),$XML_SERVER.'&action=input_logout&origin=input','');
					else $object->addEntry(sprintf(Aastra_get_label('Auto-logout (%s)',$language),format_time($logout)),$XML_SERVER.'&action=input_logout&origin=input','');
					$object->addEntry(Aastra_get_label('Cancel',$language),$XML_SERVER.'&action=cancel');

					# Softkeys
					if($nb_softkeys)
						{
						if($nb_softkeys==6)
							{
							$object->addSoftkey('1',Aastra_get_label('Select',$language),'SoftKey:Select');
							$object->addSoftkey('6',Aastra_get_label('Exit',$language),'SoftKey:Exit');
							}
						else $object->addSoftkey('10',Aastra_get_label('Exit',$language),'SoftKey:Exit');
						}
					}
				else
					{
					# Direct logout
					require_once('AastraIPPhoneExecute.class.php');
					$object=new AastraIPPhoneExecute();
					$object->addEntry($XML_SERVER.'&action=logout');
					}
				}
			}
		else
			{
			# Confirm logout
			require_once('AastraIPPhoneTextMenu.class.php');
			$object=new AastraIPPhoneTextMenu();
			$object->setDestroyOnExit();
			$object->setStyle('none');
			$object->setTitle(Aastra_get_label('Confirm Logout',$language));
			$object->addEntry(Aastra_get_label('Logout',$language),$XML_SERVER.'&action=submit');
			$object->addEntry(Aastra_get_label('Cancel',$language),$XML_SERVER.'&action=cancel');

			# Softkeys
			if($nb_softkeys)
				{
				if($nb_softkeys==6)
					{
					$object->addSoftkey('1',Aastra_get_label('Select',$language),'SoftKey:Select');
					$object->addSoftkey('6',Aastra_get_label('Exit',$language),'SoftKey:Exit');
					}
				else $object->addSoftkey('10',Aastra_get_label('Exit',$language),'SoftKey:Exit');
				}
			}
		break;

	# Erase screen if user cancels
	case 'cancel':
		require_once('AastraIPPhoneExecute.class.php');
		$object=new AastraIPPhoneExecute();
		$object->setTriggerDestroyOnExit();
		$object->addEntry('');
		break;

	# Submit password
	case 'submit':
		# MODE USER
		if($AA_FREEPBX_MODE=='1')
			{
			# Authentication failed
			if (!Aastra_verify_user_Asterisk($user,$password,'login'))
				{
				# Display error
				require_once('AastraIPPhoneTextScreen.class.php');
				$object=new AastraIPPhoneTextScreen();
				$object->setDestroyOnExit();
				$object->setTitle(Aastra_get_label('Authentication failed',$language));
				$object->setText(Aastra_get_label('Wrong credentials.',$language));

				# Softkeys
				if($nb_softkeys)
					{
					if($nb_softkeys==6)
						{
						$object->addSoftkey('5',Aastra_get_label('Cancel',$language),'SoftKey:Exit');
						$object->addSoftkey('6',Aastra_get_label('Close',$language),$XML_SERVER);
						}
					else
						{
						$object->addSoftkey('9',Aastra_get_label('Cancel',$language),'SoftKey:Exit');
						$object->addSoftkey('10',Aastra_get_label('Close',$language),$XML_SERVER);
						}
					}
				}
			else
				{
				# Only if auto-logout is supported//configured
				if(($is_sip_notify) and $AA_AUTOLOGOUT)
					{
					# Confirm logout
					require_once('AastraIPPhoneTextMenu.class.php');
					$object=new AastraIPPhoneTextMenu();
					$object->setDestroyOnExit();
					$object->setStyle('none');
					$object->setTitle(Aastra_get_label('Confirm Logout',$language));
					$object->addEntry(Aastra_get_label('Logout',$language),$XML_SERVER.'&action=logout');
					$logout=Aastra_manage_presence_Asterisk($user,'logout');
					if($logout=='') $object->addEntry(Aastra_get_label('Auto-logout (None)',$language),$XML_SERVER.'&action=input_logout&origin=submit&password='.$password,'');
					else $object->addEntry(sprintf(Aastra_get_label('Auto-logout (%s)',$language),format_time($logout)),$XML_SERVER.'&action=input_logout&origin=submit&password='.$password,'');
					$object->addEntry(Aastra_get_label('Cancel',$language),$XML_SERVER.'&action=cancel');

					# Softkeys
					if($nb_softkeys)
						{
						if($nb_softkeys==6)
							{
							$object->addSoftkey('1',Aastra_get_label('Select',$language),'SoftKey:Select');
							$object->addSoftkey('6',Aastra_get_label('Exit',$language),'SoftKey:Exit');
							}
						else $object->addSoftkey('10',Aastra_get_label('Exit',$language),'SoftKey:Exit');
						}
					}
				else
					{
					# Direct logout
					require_once('AastraIPPhoneExecute.class.php');
					$object=new AastraIPPhoneExecute();
					$object->addEntry($XML_SERVER.'&action=logout');
					}
				}
			}
		else
			{
			# Get device and user
			$device=$user;
			$user=Aastra_get_userdevice_Asterisk($device);

			# Remove from Parking list and voice mail
			Aastra_remove_parking_Asterisk($device);
			$count=!Aastra_is_ledcontrol_supported();
			Aastra_remove_vmail_Asterisk('',$device,$count);

			# Trigger a sync on the phone
			require_once('AastraIPPhoneExecute.class.php');
			$object=new AastraIPPhoneExecute();
			$object->setTriggerDestroyOnExit();

			# Clear the phone
			if(Aastra_is_local_reset_supported())
				{
				$object->AddEntry('Command: ClearCallersList');
				$object->AddEntry('Command: ClearDirectory');
				$object->AddEntry('Command: ClearRedialList');
				}

			# Perform the logout
     			$object->addEntry('Dial:logout');
			}
		break;

	# Dynamic configuration
	case 'configuration':
		# Save config
		$array_config=Aastra_get_user_context($user,'logout');

		# Configuration XML object
		require_once('AastraIPPhoneConfiguration.class.php');
		$object=new AastraIPPhoneConfiguration();

		# Send the partial configuration
		$index=1;
		# Bug 3.2.1 send all the configuration twice
		$page=intval(($page-1)/2)+1;

		foreach($array_config as $key=>$value)
			{
			if(($index>=(($page-1)*AASTRA_MAXCONFIGURATIONS+1)) and ($index<=$page*AASTRA_MAXCONFIGURATIONS)) $object->addEntry($key,$value);
			$index++;
			}
		break;

	# Logout
	case 'logout':
	case 'forced_logout':
		# Update config file
		if($action=='logout') Aastra_update_HDconfig_file(AASTRA_PATH_CACHE.'startup_asterisk.cfg',$user);

		# Remove from Parking list
		Aastra_remove_parking_Asterisk($user);

		# Remove from VM List
		$count=!Aastra_is_ledcontrol_supported();
		Aastra_remove_vmail_Asterisk('',$user,$count);

		# Retrieve Caller ID
		$callerid=Aastra_get_callerid_Asterisk($user);
				
		# Send an email
		Aastra_send_HDmail($header,$callerid.' '.$user,'LOGOUT',$AA_EMAIL,$AA_SENDER);

		# Compute what to do
		$test=False;
		if($header['model']=='Aastra6739i')
			{
			if(Aastra_test_phone_version('3.2.0.',1,$header)==0) $test=True;
			}
		else
			{
			if(Aastra_is_dynamic_sip_supported()) $test=True;
			}

		# Depending on Dynamic SIP support
		if($test)
			{
			# Get new configuration
			$array_mac=Aastra_readCFGfile(AASTRA_TFTP_DIRECTORY.'/'.$header['mac'].'.cfg','#',':');
			$array_aastra=Aastra_readCFGfile(AASTRA_TFTP_DIRECTORY.'/aastra.cfg','#',':');
			$array_intersect=array_intersect_key($array_aastra[''],$array_mac['']);
			foreach($array_mac[''] as $key=>$value) $array_config[$key]='';
			foreach($array_intersect as $key=>$value) $array_config[$key]=$value;

			# Save config
			Aastra_save_user_context($user,'logout',$array_config);
					
			# Erase mac.cfg
			Aastra_delete_mac($header['mac']);

			# How many pages?
			$last=intval(count($array_config)/AASTRA_MAXCONFIGURATIONS);
			if((count($array_config)-$last*AASTRA_MAXCONFIGURATIONS)!=0) $last++;

			# 3.2.1 bug double all the configuration messages
			$last=$last*2;

			# Prepare the requests for the configuration
			require_once('AastraIPPhoneExecute.class.php');
			$object=new AastraIPPhoneExecute();
			$object->addEntry($XML_SERVER.'&action=display2');
			$object->addEntry($XML_SERVER.'&action=purge_msg');
			$object->AddEntry('Command: ClearCallersList');
			$object->AddEntry('Command: ClearDirectory');
			$object->AddEntry('Command: ClearRedialList');
			if(Aastra_is_ledcontrol_supported())
				{
				$cfwd=Aastra_get_user_context($user,'cfwd');
				if($cfwd['key']!='') $object->AddEntry('Led: '.$cfwd['key'].'=off');
				$dnd=Aastra_get_user_context($user,'dnd');
				if($dnd['key']!='') $object->AddEntry('Led: '.$dnd['key'].'=off');
				$daynight=Aastra_get_user_context($user,'daynight');
				if($daynight['key']) 
					{
					foreach($daynight['key'] as $key=>$value) $object->AddEntry('Led: '.$daynight['key'][$key].'=off');
					}
				$away=Aastra_get_user_context($user,'away');
				if($away['key']!='') $object->AddEntry('Led: '.$away['key'].'=off');
				$agent=Aastra_get_user_context($user,'agent');
				if($agent['key']!='') $object->AddEntry('Led: '.$agent['key'].'=off');
				$follow=Aastra_get_user_context($user,'follow');
				if($follow['key']!='') $object->AddEntry('Led: '.$follow['key'].'=off');
				$parking=Aastra_get_user_context($user,'parking');
				if($parking['key']!='') $object->AddEntry('Led: '.$parking['key'].'=off');
				$vmail=Aastra_get_user_context($user,'vmail');
				foreach($vmail as $box=>$value) if($value['key']!='') $object->AddEntry('Led: '.$value['key'].'=off');
				}
			for($i=1;$i<=$last;$i++) $object->addEntry($XML_SERVER.'&action=configuration&page='.$i);
			$object->addEntry($array_config['action uri startup']);

			# Clear the critical keys
			Aastra_save_user_context($user,'cfwd',NULL);
			Aastra_save_user_context($user,'dnd',NULL);
			Aastra_save_user_context($user,'daynight',NULL);
			Aastra_save_user_context($user,'away',NULL);
			Aastra_save_user_context($user,'agent',NULL);
			Aastra_save_user_context($user,'follow',NULL);
			Aastra_save_user_context($user,'parking',NULL);
			Aastra_save_user_context($user,'vmail',NULL);
			}
		else
			{
			# Erase mac.cfg
			Aastra_delete_mac($header['mac']);

			# Reboot needed
			require_once('AastraIPPhoneExecute.class.php');
			$object=new AastraIPPhoneExecute();
			# Bug 673i
			if($header['model']!='Aastra6739i') $object->addEntry($XML_SERVER.'&action=display');
			else $object->setTriggerDestroyOnExit();
			if((Aastra_is_local_reset_supported())  and ($header['model']!='Aastra6739i'))
				{
				$object->addEntry('Command: ClearCallersList');
				$object->addEntry('Command: ClearDirectory');
				$object->addEntry('Command: ClearRedialList');
				$object->addEntry('Command: ClearLocal');
				}
			else
				{
				if(Aastra_is_fastreboot_supported()) $object->AddEntry('Command: FastReboot');
				else $object->addEntry('Command: Reset');
				}
			}

		# Set status to Disconnected
		if($AA_PRESENCE_STATE and ($action!='forced_logout') and (!$AA_ISYMPHONY))
			{
			if(Aastra_manage_presence_Asterisk($user,'status')==AA_PRESENCE_AVAILABLE) 
				{
				Aastra_manage_presence_Asterisk($user,'set','status',AA_PRESENCE_DISCONNECTED);
				$away=Aastra_manage_presence_Asterisk($user,'action');
				switch($away['action'][AA_PRESENCE_DISCONNECTED])
					{
					case AA_PRESENCE_ACT_FM:
						Aastra_manage_followme_Asterisk($user,'enable');
						break;
					case AA_PRESENCE_ACT_CFWD:
						Aastra_manage_cf_Asterisk($user,'set',$away['act_param'][AA_PRESENCE_DISCONNECTED]);
						break;
					}
				}
			}
		break;

	# Purge Message status
	case 'purge_msg':
		require_once('AastraIPPhoneStatus.class.php');
		$object=new AastraIPPhoneStatus();
		$object->setSession('aastra-xml');
		$array=Aastra_get_status_index_Asterisk('');
		foreach($array as $key=>$value) $object->addEntry($key,'');
		break;
	}

# Display XML Object
$object->output();
exit;
?>
