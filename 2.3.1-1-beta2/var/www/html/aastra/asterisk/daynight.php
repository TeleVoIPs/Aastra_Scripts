<?php
#############################################################################
# Asterisk Day/Night
#
# Aastra SIP Phones 1.4.2 or better
#
# Copyright 2007-2010 Aastra Telecom Ltd
# Some code adapted from Copyrighted 2008 Schmooze Communications, LLC
#
# Supported Aastra Phones
#    All Phones
#
# Usage
# script.php?user=USER&index=INDEX
#    	USER is the extension of the phone on the platform
#	INDEX is the day/night index (optional)
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
# Active code
#############################################################################
# Retrieve parameters
$user=Aastra_getvar_safe('user');
$action=Aastra_getvar_safe('action');
$status=Aastra_getvar_safe('status');
$index=Aastra_getvar_safe('index');
$mode=Aastra_getvar_safe('mode');
$password=Aastra_getvar_safe('password');

# Trace
Aastra_trace_call('day/night_asterisk','user='.$user.', action='.$action.', index='.$index.', status='.$status.', password='.$password);

# Test User Agent
Aastra_test_phone_versions(array('1'=>'1.4.2.','2'=>'1.4.2.','3'=>'2.5.3.','4'=>'2.5.3.','5'=>'3.0.1.'),'0');

# Get language
$language=Aastra_get_language();

# Global compatibility
$nb_softkeys=Aastra_number_softkeys_supported();
$is_icons=Aastra_is_icons_supported();

# Initial action
if($action=='')
	{
	# Set mode
	if($index=='') $mode='ALL';
	else $mode='SINGLE';

	# Test if application is allowed
	if(!Aastra_is_daynight_appli_allowed_Asterisk(Aastra_get_userdevice_Asterisk($user),$index))
		{
		# Display error
		$action='error';
		$err_title=Aastra_get_label('Error',$language);
		$err_text=Aastra_get_label('You are not allowed to use this application with the current configuration. Please contact your administrator.',$language);
		}
	else
		{
		# Retrieve status for all configured indexes
		$array_night=Aastra_manage_daynight_Asterisk('get_all','');

		# Depending on mode
		if($mode!='ALL')
			{
			# Check requested index configured
			if(!$array_night[$index])
				{
				# Display error
				$action='error';
				$err_title=Aastra_get_label('Configuration Error',$language);
				$err_text=Aastra_get_label('The requested day/night index is not configured. Please contact your administrator.',$language);
				}
			else $action='change';
			}
		else
			{
			# Initial action
			if(count($array_night)>0) $action='list';
			else
				{
				# Display error
				$action='error';
				$err_title=Aastra_get_label('Configuration Error',$language);
				$err_text=Aastra_get_label('No day/night index configured. Please contact your administrator.',$language);
				}
			}

		# Cleanup
		unset($array_night);
		}
	}

# Callback
$XML_SERVER.='?user='.$user.'&mode='.$mode;

# Depending on action
switch($action)
	{
	# Display
	case 'display':
		# Display current status
		if(Aastra_is_formattedtextscreen_supported())
			{
			# Advanced display
			require_once('AastraIPPhoneFormattedTextScreen.class.php');
			$object=new AastraIPPhoneFormattedTextScreen();
			$object->setdestroyOnExit();
			$object->setTimeout('2');
			if(Aastra_size_formattedtextscreen()>3)
				{
				$object->addLine('');
				$object->addLine('');
				}
			if ($status==1) $object->addLine(sprintf(Aastra_get_label('Night Mode activated (%d)',$language),$index),'double','center');
			else $object->addLine(sprintf(Aastra_get_label('Day Mode activated (%d)',$language),$index),'double','center');
			if($nb_softkeys==6) $object->addSoftKey('6','','SoftKey:Exit');
			}
		else
			{
			# Basic display
			require_once('AastraIPPhoneTextScreen.class.php');
			$object=new AastraIPPhoneTextScreen();
			$object->setDestroyOnExit();
			if($status==1) $object->setTitle(sprintf(Aastra_get_label('Night Mode (%d)',$language),$index));
			else $object->setTitle(sprintf(Aastra_get_label('Day Mode (%d)',$language),$index));
			$object->setText(Aastra_get_label('Activated',$language));
			$object->addSoftKey('6',Aastra_get_label('Exit',$language),'SoftKey:Exit');
			}
		break;

	# Idle screen message
	case 'msg':
		# Update idle screen
		require_once('AastraIPPhoneStatus.class.php');
		$object=new AastraIPPhoneStatus();
		$object->setSession('aastra-xml');
		$status_index=Aastra_get_status_index_Asterisk('daynight_'.$index);
		if($status==1) 
			{
			if($array_config_daynight[$index]['label_night']!='') $display=sprintf($array_config_daynight[$index]['label_night'],$index,Aastra_get_daynight_name_Asterisk($index));
			else $display=sprintf(Aastra_get_label('Night Mode (%d)',$language),$index);	
			}
		else $display='';
		$object->addEntry($status_index,$display);
		break;

	# Key label
	case 'label':
		# Retrieve key
		$data=Aastra_get_user_context($user,'daynight');
		$key=$data['key'][$index];

	      	# Update key label
		require_once('AastraIPPhoneConfiguration.class.php');
      		$object=new AastraIPPhoneConfiguration();
      		if($status==1) $object->addEntry($key.' label',Aastra_get_label('Day Mode',$language));
	     	else $object->addEntry($key.' label',Aastra_get_label('Night Mode',$language));
	    	break;

	# LED (bug 6739i bug workaround)
	case 'led':
		# Retrieve key
		$data=Aastra_get_user_context($user,'daynight');
		$key=$data['key'][$index];

		# Change LED if supported
		require_once('AastraIPPhoneExecute.class.php');
      		$object=new AastraIPPhoneExecute();
		if($status==1) $object->addEntry('Led: '.$key.'=on');
		else $object->addEntry('Led: '.$key.'=off');
		sleep(2);
		break;

	# List with all the indexes
	case 'list':
		# Authenticate user
		Aastra_check_signature_Asterisk($user);

		# Retrieve all status
		$array_night=Aastra_manage_daynight_Asterisk('get_all','');

		# Textmenu
		require_once('AastraIPPhoneTextMenu.class.php');
		$object=new AastraIPPhoneTextMenu();
		$object->setDestroyOnExit();
		if(Aastra_is_style_textmenu_supported()) $object->setStyle('none');
		$object->setTitle(Aastra_get_label('Day/Night Control',$language));
		$day=0;
		$night=0;
		foreach($array_night as $i=>$value)
			{
			if($value['night']=='1') 
				{
				$display=sprintf(Aastra_get_label('%s (%d)-NIGHT',$language),$value['desc'],$i);
				if($value['password']!='') $day=2;
				else
					{
					if($day!=2) $day=1;
					}
				}
			else 
				{
				$display=sprintf(Aastra_get_label('%s (%d)-DAY',$language),$value['desc'],$i);
				if($value['password']!='') $night=2;
				else
					{
					if($night!=2) $night=1;
					}
				}

			$icon='';
			$protected=' ';
			if($value['password']!='') 
				{
				if($is_icons) $icon='1';
				else $protected='*';
				}
			else 
				{
				if($is_icons) $icon='2';
				else $protected='-';
				}
			$object->addEntry($protected.$display,$XML_SERVER.'&action=change&index='.$i,'&index='.$i,$icon);
			}

		# Default position
		if($index!='') $object->setDefaultIndex($index+1);

		# Softkeys
		if($nb_softkeys)
			{
			if($nb_softkeys==6)
				{
				# Regular phone
				$object->addSoftkey('1',Aastra_get_label('Toggle',$language),'SoftKey:Select');
				if($day==1) $object->addSoftkey('2',Aastra_get_label('All DAY',$language),$XML_SERVER.'&action=day');
				if($night==1) $object->addSoftkey('3',Aastra_get_label('All NIGHT',$language),$XML_SERVER.'&action=night');
				$object->addSoftkey('6',Aastra_get_label('Exit',$language),'SoftKey:Exit');
				}
			else
				{
				# 6739i
				$object->addSoftkey('1',Aastra_get_label('Toggle',$language),$XML_SERVER.'&action=change');
				if($day==1) $object->addSoftkey('6',Aastra_get_label('All DAY',$language),$XML_SERVER.'&action=day');
				if($night==1) $object->addSoftkey('7',Aastra_get_label('All NIGHT',$language),$XML_SERVER.'&action=night');
				$object->addSoftkey('10',Aastra_get_label('Exit',$language),'SoftKey:Exit');
				}
			}

		# Icons
		if($is_icons)
			{
			if(Aastra_phone_type()!=5)
				{
				$object->addIcon(1,'00003E4E4A4E3E0000000000');
				$object->addIcon(2,'00004E8E8A8E7E0000000000');
				}
			else
				{
				$object->addIcon(1,'Icon:Lock');
				$object->addIcon(2,'Icon:UnLock');
				}
			}
		break;

	# Change values
	case 'change':
		# Authenticate user
		if($action=='change') Aastra_check_signature_Asterisk($user);

		# Retrieve all status
		$array_night=Aastra_manage_daynight_Asterisk('get_all','');

		# Check if password configured
		if($array_night[$index]['password']!='')
			{
			# Input password
			require_once('AastraIPPhoneInputScreen.class.php');
			$object=new AastraIPPhoneInputScreen();
			$object->setDestroyOnExit();
			$object->setTitle(sprintf('%s (%d)',$array_night[$index]['desc'],$index));
		     	$object->setPrompt(Aastra_get_label('Enter Password',$language));
			$object->setParameter('password');
			$object->setType('number');
			$object->setPassword();
			$object->setURL($XML_SERVER.'&action=set_change&index='.$index);

			# Softkeys
			if($nb_softkeys)
				{
				if($nb_softkeys==6)
					{
					$object->addSoftkey('1',Aastra_get_label('Backspace',$language),'SoftKey:BackSpace');
					if($mode=='SINGLE') $object->addSoftkey('5',Aastra_get_label('Cancel',$language),'SoftKey:Exit');
					else $object->addSoftkey('5',Aastra_get_label('Cancel',$language),$XML_SERVER.'&action=list&index='.$index);
					$object->addSoftkey('6',Aastra_get_label('Submit',$language),'SoftKey:Submit');
					}
				else
					{
					if($mode=='SINGLE') $object->addSoftkey('10',Aastra_get_label('Cancel',$language),'SoftKey:Exit');
					else 
						{
						$object->addSoftkey('10',Aastra_get_label('Cancel',$language),$XML_SERVER.'&action=list&index='.$index);
						$object->setCancelAction($XML_SERVER.'&action=list&index='.$index);
						}
					}
				}
			}
		else
			{
			# Do the change
			require_once('AastraIPPhoneExecute.class.php');
			$object=new AastraIPPhoneExecute();
			$object->addEntry($XML_SERVER.'&action=set_change&index='.$index);
			}
		break;

	# Switch values
	case 'set_change':
		# Retrieve all status
		$array_night=Aastra_manage_daynight_Asterisk('get_all','');

		# Password needed
		$update=False;
		if($array_night[$index]['password']!='')
			{
			if($password==$array_night[$index]['password']) $update=True;
			}
		else $update=True;

		# Update needed?
		if($update)
			{
			# Change Day/Night status
			$night=Aastra_manage_daynight_Asterisk('change',$index);

			# Prepare display update
			require_once('AastraIPPhoneExecute.class.php');
			$object=new AastraIPPhoneExecute();

			# Update via the "check" mechanism
			$object->addEntry($XML_SERVER.'&action=check');

			# Send a SIP Notification to everybody allowd
			if(!$AA_FREEPBX_USEDEVSTATE) Aastra_propagate_daynight_Asterisk($user,$index);

			# Display results
			if($mode=='SINGLE') $object->addEntry($XML_SERVER.'&action=display&status='.$night.'&index='.$index);
			else $object->addEntry($XML_SERVER.'&action=list&index='.$index);
			}
		else
			{
			# Display error message
			require_once('AastraIPPhoneTextScreen.class.php');
			$object=new AastraIPPhoneTextScreen();
			$object->setDestroyOnExit();
			$object->setTitle(Aastra_get_label('Error',$language));
			$object->setText(Aastra_get_label('Password mismatch.',$language));

			# Softkeys
			if($nb_softkeys)
				{
				if($nb_softkeys==6)
					{
					if($mode=='SINGLE') $object->addSoftkey('5',Aastra_get_label('Cancel',$language), 'SoftKey:Exit');
					else $object->addSoftkey('5',Aastra_get_label('Cancel',$language), $XML_SERVER.'&action=list&index='.$index);
					$object->addSoftkey('6',Aastra_get_label('Close',$language), $XML_SERVER.'&action=change&index='.$index);
					}
				else
					{
					if($mode=='SINGLE') $object->addSoftkey('9',Aastra_get_label('Cancel',$language), 'SoftKey:Exit');
					else 
						{
						$object->addSoftkey('9',Aastra_get_label('Cancel',$language), $XML_SERVER.'&action=list&index='.$index);
						$object->setCancelAction($XML_SERVER.'&action=list&index='.$index);
						}
					$object->addSoftkey('10',Aastra_get_label('Close',$language), $XML_SERVER.'&action=change&index='.$index);
					}
				}
			}
		break;

	# Check or Register
	case 'check':
	case 'register':
		# Context change
		$context=False;

		# Combined Status
		$combined=0;

		# Retrieve status for all configured indexes
		$array_night=Aastra_manage_daynight_Asterisk('get_all','');

		# Get last Day/Night status
		$data=Aastra_get_user_context($user,'daynight');

		# Prepare display update
		require_once('AastraIPPhoneExecute.class.php');
		$object=new AastraIPPhoneExecute();

		# Each configured index
		foreach($array_night as $index=>$value)
			{
			# Combined state
			if($value['night']=='1') $combined='1';

			# User notified for this index?
			if(Aastra_is_daynight_notify_allowed_Asterisk($user,$index))
				{
				# Update needed by default
				$update=1;

				# Get current Day/Night status
				$night=$value['night'];
				$last=$data['last'][$index];

				# Update Day/Night status
				if($last!=$night)
					{
					$data['last'][$index]=$night;
					$context=True;
					}

				# Update needed?
				if(($action=='check') and ($night==$last)) $update=0;
				if(($action=='register') and ($night==0)) $update=0;

				# Update needed
				if($update==1)
					{
					# Change msg status
					$object->addEntry($XML_SERVER.'&action=msg&status='.$night.'&index='.$index);

					# Key for the application?
					$key=$data['key'][$index];
					if($key!='')
						{
						# Change label if supported
						if(Aastra_is_configuration_supported() and ($array_config_daynight[$index]['chg_label']!='0')) $object->addEntry($XML_SERVER.'&action=label&status='.$night.'&index='.$index);	

						# Change LED if supported
						if(Aastra_is_ledcontrol_supported())
							{
							# Bug 6739i workaround
            						$header=Aastra_decode_HTTP_header();
							if($header['model']=='Aastra6739i') $object->addEntry($XML_SERVER.'&action=led&status='.$night.'&index='.$index);
							else
								{
								if($night==1) $object->addEntry('Led: '.$key.'=on');
								else $object->addEntry('Led: '.$key.'=off');
								}
							}
						}
					}
				}
			}

		# Combined state
		$night=$combined;
		$last=$data['last']['ALL'];
		if(($night!=$last) or ($action=='register'))
			{
			# Save in context
			$data['last']['ALL']=$night;
			$context=True;

			# Retrieve key
			$key=$data['key']['ALL'];
			if($key!='')
				{
				# Change LED if supported
				if(Aastra_is_ledcontrol_supported())
					{
					if($night==1) $object->addEntry('Led: '.$key.'=on');
					else $object->addEntry('Led: '.$key.'=off');
					}
				}		
			}

		# Do nothing
		$object->addEntry('');

		# Save Day/Night context
		if($context) Aastra_save_user_context($user,'daynight',$data);
		break;

	# Display error
	case 'error':
		# Display error as a TextScreen
		require_once('AastraIPPhoneTextScreen.class.php');
		$object=new AastraIPPhoneTextScreen();
		$object->setDestroyOnExit();
		$object->setTitle($err_title);
		$object->setText($err_text);

		# Softkeys
		if($nb_softkeys)
			{
			if($nb_softkeys==6) $object->addSoftkey('6',Aastra_get_label('Exit',$language),'SoftKey:Exit');
			else $object->addSoftkey('10',Aastra_get_label('Exit',$language),'SoftKey:Exit');
			}
		break;

	# Global day/night change
	case 'day':
	case 'night':
		# Retrieve all status
		$array_night=Aastra_manage_daynight_Asterisk('get_all','');

		# Next actions
		require_once('AastraIPPhoneExecute.class.php');
		$object=new AastraIPPhoneExecute();	

		# Force day or night
		foreach($array_night as $i=>$value)
			{
			$update=False;
			if(($value['night']=='1') and ($action=='day')) $update=True;
			if(($value['night']=='0') and ($action=='night')) $update=True;
			if($update)
				{
				$night=Aastra_manage_daynight_Asterisk('change',$i);
				if((!$AA_FREEPBX_USEDEVSTATE) or (!Aastra_is_sip_notify_supported()))
					{
					# Send a SIP Notification to everybody allowd
					Aastra_propagate_daynight_Asterisk($user,$i);
					}
				}
			}

		# Update via the "check" mechanism
		$object->addEntry($XML_SERVER.'&action=check');

		# Back to the list
		$object->addEntry($XML_SERVER.'&action=list&index='.$index);
		break;

	# Default
	default:
		# Unexpected action
		Aastra_debug('Unexpected action: '.$action);
		require_once('AastraIPPhoneExecute.class.php');
		$object=new AastraIPPhoneExecute();	
		$object->addEntry('');
		break;
	}

# Display object
$object->output();
exit;
?>
