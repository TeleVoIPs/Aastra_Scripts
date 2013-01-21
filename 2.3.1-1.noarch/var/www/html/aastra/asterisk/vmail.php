<?php
#############################################################################
# Asterisk Voice Mail Login
#
# Aastra SIP Phones 1.4.2 or better
#
# Copyright 2005-2009 Aastra Telecom Ltd
#
# Usage
# script.php?ext=USER
# OR
# script.php?ext=USER&user=BOX
#
# Where
#    USER is the user extension on the platform (mandatory)
#    BOX is the Voice mail box ID
#
# Supported Aastra Phones
#   All Phones but 9112i and 9133i
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
# Main code
#############################################################################
# Retrieve parameters
$ext=Aastra_getvar_safe('ext');
$pin=Aastra_getvar_safe('pin');
$user=Aastra_getvar_safe('user');
$action=Aastra_getvar_safe('action','init');

# Trace
Aastra_trace_call('vmail_login-asterisk','action='.$action.', ext='.$ext.', user='.$user.', pin='.$pin);

# Test User Agent
Aastra_test_phone_versions(array('1'=>'','2'=>'1.4.2.','3'=>'2.5.3','4'=>'2.5.3.','5'=>'3.0.1'),'0');

# Get Language
$language=Aastra_get_language();

# Keep URL
$XML_SERVER.='?ext='.$ext;

# Compatibility with non softkey phones and 6739i
$nb_softkeys=Aastra_number_softkeys_supported();

# Check if ext is configured
if($ext=='')
	{
	# Display error
	$action='error';
	$err_title=Aastra_get_label('Configuration Error',$language);
	$err_text=Aastra_get_label('Please contact your administrator.',$language);
	if($nb_softkeys)
		{
		if($nb_softkeys==6) $err_key=array('6',Aastra_get_label('Exit',$language),'SoftKey:Exit');
		$err_key=array('10',Aastra_get_label('Exit',$language),'SoftKey:Exit');
		}
	}

# Process action
switch($action)
	{
	# Init
	case 'init':
		# No user yet
		if($user=='')
			{
			require_once('AastraIPPhoneInputScreen.class.php');
			if(Aastra_is_multipleinputfields_supported())
				{
				# Multiple input fields Mailbox and password
				$object=new AastraIPPhoneInputScreen();
				$object->setDestroyOnExit();
				$object->setDisplayMode('condensed');
				$object->setTitle(Aastra_get_label('VoiceMail Access',$language));
				$object->setURL($XML_SERVER.'&action=check');
				$object->addField('empty');
				$object->addField('number');
			     	$object->setFieldPrompt(Aastra_get_label('Mailbox:',$language));
				$object->setFieldParameter('user');
				$object->addField('number');
			     	$object->setFieldPrompt(Aastra_get_label('Password:',$language));
				$object->setFieldParameter('pin');
				$object->setFieldPassword('yes');
				}
			else
				{
				# Single input field Just Mailbox
				$object=new AastraIPPhoneInputScreen();
				$object->setDestroyOnExit();
				$object->setTitle(Aastra_get_label('VoiceMail Access',$language));
				$object->setPrompt(Aastra_get_label('Enter Mailbox Number',$language));
				$object->setParameter('user');
				$object->setType('number');
				$object->setURL($XML_SERVER.'&action=password');
				$object->setDestroyOnExit();
				}

			# Softkeys		
			if($nb_softkeys)
				{
				if($nb_softkeys==6)
					{
					$object->addSoftkey('1',Aastra_get_label('Backspace',$language),'SoftKey:BackSpace');
					$object->addSoftkey('5',Aastra_get_label('Submit',$language),'SoftKey:Submit');
					$object->addSoftkey('6',Aastra_get_label('Exit',$language),'SoftKey:Exit');
					}
				else $object->addSoftkey('10',Aastra_get_label('Exit',$language),'SoftKey:Exit');
				}
			}
		else
			{
			# Check auto-login
			$vmail_options=Aastra_get_user_context($ext,'vmail-options');
			$auto_login=False;
			if(($vmail_options['auto_login']=='1') and ($user==Aastra_get_userdevice_Asterisk($ext))) 
				{
				Aastra_check_signature_Asterisk($user);
				$auto_login=True;
				}

			# No auto-login
			if(!$auto_login)
				{
				# No pin yet?
				if($pin=='')
					{
					# Ask for password
					require_once('AastraIPPhoneExecute.class.php');
					$object=new AastraIPPhoneExecute();
					$object->addEntry($XML_SERVER.'&action=password&user='.$user);
					}
				else
					{
					# Check credentials
					$object->addEntry($XML_SERVER.'&action=check&user='.$user.'&pin='.$pin);
					}
				}
			else
				{
				# Launch application
				$action='launch';
				}
			}
		break;

	# Check credentials
	case 'check':
		# Credentials Not OK?
		if(!$userinfo=Aastra_verify_user_Asterisk($user,$pin,'vm'))
			{
			# Error message
			require_once('AastraIPPhoneTextScreen.class.php');
			$object=new AastraIPPhoneTextScreen();
			$object->setDestroyOnExit();
			$object->setTitle(Aastra_get_label('Authentication failed',$language));	
			$object->setText(Aastra_get_label('Wrong user and/or password',$language));	
			
			# Softkeys
			if($nb_softkeys)
				{
				if($nb_softkeys==6)
					{
					$object->addSoftkey('4',Aastra_get_label('Back',$language),$XML_SERVER);
					$object->addSoftkey('6',Aastra_get_label('Exit',$language),'SoftKey:Exit');
					}
				else
					{
					$object->addSoftkey('9',Aastra_get_label('Back',$language),$XML_SERVER);
					$object->addSoftkey('10',Aastra_get_label('Exit',$language),'SoftKey:Exit');
					}
				
				}
			$object->setCancelAction($XML_SERVER.'&user='.$user);
			}
		else
			{
			# Launch application
			$action='launch';
			}
		break;

	# Password
	case 'password':
		# Input Password
		require_once('AastraIPPhoneInputScreen.class.php');
		$object=new AastraIPPhoneInputScreen();
		$object->setTitle(sprintf(Aastra_get_label('VoiceMail Access (%s)',$language),$user));
		$object->setPrompt(Aastra_get_label('Enter Password',$language));
		$object->setParameter('pin');
		$object->setType('number');
		$object->setURL($XML_SERVER.'&user='.$user.'&action=check');
		$object->setPassword();
		$object->setDestroyOnExit();
	
		# Softkeys
		if($nb_softkeys)
			{
			if($nb_softkeys==6)
				{
				$object->addSoftkey('1',Aastra_get_label('Backspace',$language),'SoftKey:BackSpace');
				$object->addSoftkey('4',Aastra_get_label('Chg. User',$language),$XML_SERVER);
				$object->addSoftkey('5',Aastra_get_label('Submit',$language),'SoftKey:Submit');
				$object->addSoftkey('6',Aastra_get_label('Exit',$language),'SoftKey:Exit');
				}
			else
				{
				$object->addSoftkey('9',Aastra_get_label('Change User',$language),$XML_SERVER);
				$object->addSoftkey('10',Aastra_get_label('Exit',$language),'SoftKey:Exit');
				}
			}
		break;
	}

# Post process action
switch($action)
	{
	# Error
	case 'error':
		# New text screen
		require_once('AastraIPPhoneTextScreen.class.php');
		$object=new AastraIPPhoneTextScreen();
		$object->setDestroyOnExit();
		$object->setTitle($err_title);
		$object->setText($err_text);
		if($nb_softkeys) $object->addSoftkey($err_key[0],$err_key[1],$err_key[2]);
		else 
			{
			if($err_key[2]!='SoftKey:Exit') $object->setDoneAction($err_key[2]);
			}
		break;

	# Launch
	case 'launch':
		# Launch application
		require_once('AastraIPPhoneExecute.class.php');
		$object=new AastraIPPhoneExecute();
		$object->addEntry($XML_SERVER_PATH.'vmail_'.Aastra_phone_type().'.php?ext='.$ext.'&user='.$user);
		break;
	}

# Output XML object
$object->output();
exit();
?>

