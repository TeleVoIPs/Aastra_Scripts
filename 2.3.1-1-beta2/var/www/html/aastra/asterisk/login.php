<?php
#############################################################################
# Asterisk Login for deviceanduser mode
#
# Aastra SIP Phones 2.3.0 or better
#
# Copyright 2008 Aastra Telecom Ltd
#
# Supported Aastra Phones
#   All phones
#
# script.php?device=DEVICE
#   DEVICE is the deviceID
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
require_once('AastraCommon.php');
require_once('AastraAsterisk.php');
require_once('AastraIPPhoneTextScreen.class.php');
require_once('AastraIPPhoneTextMenu.class.php');
require_once('AastraIPPhoneInputScreen.class.php');
require_once('AastraIPPhoneExecute.class.php');

#############################################################################
# Main code
#############################################################################
# Retrieve parameters
$device=Aastra_getvar_safe('device');
$user=Aastra_getvar_safe('user');
$password=Aastra_getvar_safe('password');
$action=Aastra_getvar_safe('action');
$step=Aastra_getvar_safe('step','1');
$lang=Aastra_getvar_safe('lang');
$cl=Aastra_getvar_safe('cl');

# Trace
Aastra_trace_call('login_asterisk','device='.$device.', user='.$user.', password='.$password.', action='.$action);

# Keep the URL
$XML_SERVER.='?device='.$device;

# Test User Agent
Aastra_test_phone_versions(array('1'=>'','2'=>'','3'=>'2.5.3.','4'=>'2.5.3.','5'=>'3.0.1.'),'0');

# Global compatibility
$nb_softkeys=Aastra_number_softkeys_supported();

# No action yet
if($action=='')
	{
	$code=Aastra_ask_language_Asterisk();
	if($code[0]) $action='language';
	else $action='input';
	}

# Get Language
if($lang!='') $language=$lang;
else $language=Aastra_get_language();

# Callback
if($action!='language') $XML_SERVER.='&lang='.$lang.'&cl='.$cl;

# Retrieve user information
$device_info=Aastra_get_device_info_Asterisk($device);

# Device must be ad-hoc
if($device_info['type']!='adhoc')
	{
	# Display error as a TextScreen
	$object=new AastraIPPhoneTextScreen();
	$object->setDestroyOnExit();
	$object->setTitle(Aastra_get_label('Error',$language));
	$object->setText(Aastra_get_label('You are not allowed to change user on this phone.',$language));

	# Softkeys
	if($nb_softkeys)
		{
		if($nb_softkeys==6) $object->addSoftkey('6',Aastra_get_label('Exit',$language),'SoftKey:Exit');
		else $object->addSoftkey('10',Aastra_get_label('Exit',$language),'SoftKey:Exit');
		}
	}
else
	{
	# Depending on action
	switch($action)
		{
		# Language Selection
		case 'language':
			# Associated labels
			$array_lang=array(	'en'=>Aastra_get_label('English',$language),
						'fr'=>Aastra_get_label('French (Europe)',$language),
						'fr_ca'=>Aastra_get_label('French (Canada)',$language),
						'de'=>Aastra_get_label('German',$language),
						'it'=>Aastra_get_label('Italian',$language),
						'es'=>Aastra_get_label('Spanish (Europe)',$language),
						'es_mx'=>Aastra_get_label('Spanish (Mexico)',$language),
						'pt'=>Aastra_get_label('Portuguese (Europe)',$language),
						'pt_br'=>Aastra_get_label('Portuguese (Brazil)',$language)
					);

			# Create TextMenu
			$object=new AastraIPPhoneTextMenu();
			$object->setDestroyOnExit();
			if(Aastra_is_lockin_supported()) $object->setLockIn();
			$object->setTitle(Aastra_get_label('Select Language',$language));
			$index=1;
			foreach($code[1] as $key=>$value) 
				{
				$object->addEntry($array_lang[$value],$XML_SERVER.'&action=input&lang='.$value.'&cl='.$key);
				if($key==$code[2]) $object->setDefaultIndex($index);
				$index++;
				}
			if($nb_softkeys)
				{
				if($nb_softkeys==6) 
					{
					$object->addSoftkey('1',Aastra_get_label('Select',$language),'SoftKey:Select');
					$object->addSoftkey('6',Aastra_get_label('Cancel',$language),'SoftKey:Exit');
					}
				else $object->addSoftkey('6',Aastra_get_label('Cancel',$language),'SoftKey:Exit');
				}
			break;

		# Input data
		case 'input':
			switch($step)
				{
				case '1':
					if(Aastra_is_multipleinputfields_supported())
						{
					       $object=new AastraIPPhoneInputScreen();
				       	$object->setTitle(Aastra_get_label('User Login',$language));
					       $object->setDisplayMode('condensed');
				       	$object->setURL($XML_SERVER.'&action=submit');
					       $object->setDestroyOnExit();
			       		$object->addField('empty');
					       $object->addField('number');
				       	$object->setFieldPrompt(Aastra_get_label('User:',$language));
					       $object->setFieldParameter('user');
					       $object->addField('number');
			       		$object->setFieldPrompt(Aastra_get_label('Password:',$language));
				       	$object->setFieldPassword();
					       $object->setFieldParameter('password');
						}
					else
						{
						$object = new AastraIPPhoneInputScreen();
						$object->setTitle(Aastra_get_label('User Login',$language));
						$object->setPrompt(Aastra_get_label('Enter user',$language));
						$object->setParameter('user');
						$object->setType('number');
						$object->setURL($XML_SERVER.'&action=input&step=2');
						$object->setDestroyOnExit();
						if($extension!='') $object->setDefault($extension);
						}
					if($nb_softkeys) 
						{
						if($nb_softkeys==6)
							{
							$object->addSoftkey('1',Aastra_get_label('Backspace',$language),'SoftKey:BackSpace');
							$object->addSoftkey('5',Aastra_get_label('Cancel',$language),'SoftKey:Exit');
							$object->addSoftkey('6',Aastra_get_label('Submit',$language),'SoftKey:Submit');
							}
						else $object->addSoftkey('10',Aastra_get_label('Cancel',$language),'SoftKey:Exit');
						}
					break;

				case '2':
					$object=new AastraIPPhoneInputScreen();
					$object->setDestroyOnExit();
					$object->setTitle(Aastra_get_label('User Login',$language));
					$object->setPrompt(Aastra_get_label('Enter password',$language));
					$object->setParameter('password');
					$object->setType('number');
					$object->setPassword();
					$object->setURL($XML_SERVER.'&action=submit&user='.$user);
					if($nb_softkeys) 
						{
						if($nb_softkeys==6)
							{
							$object->addSoftkey('1',Aastra_get_label('Backspace',$language),'SoftKey:BackSpace');
							$object->addSoftkey('5',Aastra_get_label('Cancel',$language),'SoftKey:Exit');
							$object->addSoftkey('6',Aastra_get_label('Submit',$language),'SoftKey:Submit');
							}
						else $object->addSoftkey('10',Aastra_get_label('Cancel',$language),'SoftKey:Exit');
						}
					break;
				}
			break;

		# Submit
		case 'submit':
			# credentials OK
			if(Aastra_check_user_login_Asterisk($user,$password))
				{
				# Retrieve user template
				$profile=Aastra_get_startup_profile_Asterisk($user);
				if(file_exists($profile.'-device-user.prf'))
					{
					# Store language if needed
					if($lang!='')
						{
						$data['language']=$lang;
						$data['code']=$cl;
						$array_wl=array(	'fr'=>'French',
									'it'=>'Italian',
									'es'=>'Spanish',
									'en'=>'English',
									'de'=>'German',
									'pt'=>'Portuguese'
								);
						if($array_wl[substr($lang,0,2)]!='') $data['clear']=$array_wl[substr($lang,0,2)];
						else $data['clear']='';
						Aastra_save_user_context($device,'language',$data);
						}
	
					# Trigger a sync on the phone
					$object=new AastraIPPhoneExecute();
					$object->setTriggerDestroyOnExit();
		     			$object->addEntry('Dial:loguser'.$user);
					}
				else
					{
					# Display error as a TextScreen
					$object=new AastraIPPhoneTextScreen();
					$object->setDestroyOnExit();
					$object->setTitle(Aastra_get_label('Configuration error',$language));
					$object->setText(Aastra_get_label('Configuration file cannot be generated. Please contact your administrator.',$language));
					if($nb_softkeys) 
						{
						if($nb_softkeys==6) $object->addSoftkey('6',Aastra_get_label('Exit',$language),'SoftKey:Exit');
						else $object->addSoftkey('10',Aastra_get_label('Exit',$language),'SoftKey:Exit');
						}
					}
				}
			else
				{
				# Display error as a TextScreen
				$object=new AastraIPPhoneTextScreen();
				$object->setDestroyOnExit();
				$object->setTitle(Aastra_get_label('User Login',$language));
				$object->setText(Aastra_get_label('Wrong user credentials.',$language));
				if($nb_softkeys) 
					{
					if($nb_softkeys==6) 
						{
						$object->addSoftkey('5',Aastra_get_label('Cancel',$language),'SoftKey:Exit');
						$object->addSoftkey('6',Aastra_get_label('Close',$language),$XML_SERVER.'&action=input');
						}
					else
						{
						$object->addSoftkey('9',Aastra_get_label('Cancel',$language),'SoftKey:Exit');
						$object->addSoftkey('10',Aastra_get_label('Close',$language),$XML_SERVER.'&action=input');
						}
					}
				}
			break;
		}
	}

# Display XML Object
$object->output();
exit;
?>7