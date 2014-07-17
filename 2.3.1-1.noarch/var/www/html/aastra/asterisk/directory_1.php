<?php
#############################################################################
# Asterisk Directory for Aastra SIP Phones
#
# Copyright 2005-2010 Aastra Telecom Ltd
#
# Presence management adapted from 
# 	Copyright (C) 2008 Ethan Schroeder
# 	ethan.schroeder@schmoozecom.com
#
# Usage
# 	script.php?user=USER
# 	USER is the extension of the phone on the Asterisk platform
#
# Supported Aastra Phones
#    9112ii, 9133i running firmware 1.4.2 or better
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
# Main code
#############################################################################

# Retrieve parameters
$user=Aastra_getvar_safe('user');
$page=Aastra_getvar_safe('page','1');
$action=Aastra_getvar_safe('action','list');
$selection=Aastra_getvar_safe('selection');
$origin=Aastra_getvar_safe('origin');

# Trace
Aastra_trace_call('directory_1_asterisk','user='.$user.', action='.$action.', speed='.$speed.', selection='.$selection);

# Test User Agent
Aastra_test_phone_versions(array('1'=>'1.4.2','2'=>'','3'=>'','4'=>'','5'=>''),'0');

# Get Language
$language=Aastra_get_language();

# Save return URI
$XML_SERVER.='?user='.$user.'&origin='.$origin;

# Compute MaxLines
$MaxLines=AASTRA_MAXLINES-2;

# Process action
switch($action)
	{
	# Poor man's dial
	case 'dial':
		# Do nothing
		require_once('AastraIPPhoneExecute.class.php');
		$object=new AastraIPPhoneExecute();
		$object->addEntry('');
		$object->output(True);

		# Dial the number
		Aastra_dial_number_Asterisk($user,$selection);
		exit;
		break;

	# Display status
	case 'zoom':
		# Retrieve user status
		$away=Aastra_manage_presence_Asterisk($selection,'get','status');

		# Labels for status
		$status_text=Aastra_status_config_Asterisk();;

		# Check notification
		if($away['status']!=AA_PRESENCE_AVAILABLE)
			{
			$explodem=explode(',',$away['notifym']);
			$explodev=explode(',',$away['notifyv']);
			$real_user=Aastra_get_userdevice_Asterisk($user);
			if(in_array($real_user,$explodem) or in_array($real_user,$explodev)) $notify=True;
			else $notify=False;
			}

		# Retrieve Intercom data
		$intercom=Aastra_get_intercom_config_Asterisk();

		# Display a list
		$dial_uri=$XML_SERVER.'&action=dial&selection='.$selection;
		$icom_uri=$XML_SERVER.'&action=dial&selection='.$intercom.$selection;
		require_once('AastraIPPhoneTextMenu.class.php');
		$object=new AastraIPPhoneTextMenu();
		$object->setDestroyOnExit();
		$object->setStyle('none');
		$object->setWrapList();
		$object->setTitle(Aastra_get_callerid_Asterisk($selection));
		$object->addEntry($status_text[$away['status']]['label'],$dial_uri);
		if($away['status']!=AA_PRESENCE_AVAILABLE)
			{
			$line=Aastra_format_presence_dt_Asterisk($away['return']);
			$object->addEntry($line[0],$dial_uri);
			if($line[1]!='') $object->addEntry($line[1],$dial_uri);
			if($notify) $object->addEntry(Aastra_get_label('Notification on return',$language),$XML_SERVER.'&action=unnotify&page='.$page.'&selection='.$selection);
			}
		$object->addEntry(Aastra_get_label('Dial',$language),$dial_uri);
		$object->addEntry(Aastra_get_label('Icom',$language),$icom_uri);
		if(($away['status']!=AA_PRESENCE_AVAILABLE) and ($away['status']!=AA_PRESENCE_DISCONNECTED))
			{
			if(!$notify) $object->addEntry(Aastra_get_label('Notify me',$language),$XML_SERVER.'&action=notify&page='.$page.'&selection='.$selection);
			else $object->addEntry(Aastra_get_label('Unnotify me',$language),$XML_SERVER.'&action=unnotify&page='.$page.'&selection='.$selection);
			}
		$object->addEntry(Aastra_get_label('Back',$language),$XML_SERVER.'&action=list&page='.$page.'&selection='.$selection);
		break;

	# Set Notify
	case 'notify':
		# Add the user to the list
		Aastra_manage_presence_Asterisk($selection,'set','notifyv',$user);

		# Display text
		require_once('AastraIPPhoneTextScreen.class.php');
		$object=new AastraIPPhoneTextScreen();
		$object->setDestroyOnExit();
		$object->setTitle(Aastra_get_label('Notification Set',$language));
		$object->setText(sprintf(Aastra_get_label('You will be notified when %s returns.',$language),Aastra_get_callerid_Asterisk($selection)));

		# DoneAction
		$object->setDoneAction($XML_SERVER.'&action=zoom&page='.$page.'&selection='.$selection);
		break;

	# Unnotify
	case 'unnotify':
		# Add the user to the list
		Aastra_manage_presence_Asterisk($selection,'unset','notify',$user);

		# Display text
		require_once('AastraIPPhoneTextScreen.class.php');
		$object=new AastraIPPhoneTextScreen();
		$object->setDestroyOnExit();
		$object->setTitle(Aastra_get_label('Notification Cancelled',$language));
		$object->setText(sprintf(Aastra_get_label('You will NOT ANYMORE be notified when %s returns.',$language),Aastra_get_callerid_Asterisk($selection)));

		# DoneAction
		$object->setDoneAction($XML_SERVER.'&action=zoom&page='.$page.'&selection='.$selection);
		break;
	
	# Display directory
	case 'list':
		# Get list of users
		$directory=Aastra_get_user_directory_Asterisk();

		# Remove current user
		unset($directory[Aastra_get_userdevice_Asterisk($user)]);

		# Sort Directory by name
		Aastra_natsort2d($directory,'name');

		# Number of records
		$index=count($directory);

		# At least one record
		if($index>0)
			{
			# Retrieve last page
			$last=intval($index/$MaxLines);
			if(($index-$last*$MaxLines) != 0) $last++;

			# Get status if needed
			if($AA_PRESENCE_STATE) $directory=Aastra_get_hints_asterisk($directory);

			# Labels for status
			$status_text=Aastra_status_config_Asterisk();

			# Display Page
			require_once('AastraIPPhoneTextMenu.class.php');
			$object=new AastraIPPhoneTextMenu();
			$object->setDestroyOnExit();
			$object->setStyle('none');
			if($last!=1) $object->setTitle(sprintf(Aastra_get_label('Directory (%s/%s)',$language),$page,$last));
			else $object->setTitle(Aastra_get_label('Directory',$language));

			# Next Page
			if($page!=1) $object->addEntry(Aastra_get_label('Previous Page',$language),$XML_SERVER.'&action=list&page='.($page-1));

			# Display items
			$index=1;
			$rank=1;
			foreach ($directory as $v) 
				{
				if(($index>=(($page-1)*$MaxLines+1)) and ($index<=$page*$MaxLines))
					{
					if($v['number']==$selection) $object->setDefaultIndex($rank);
					if($AA_PRESENCE_STATE) $object->addEntry('['.$status_text[$v['status']]['mnemonic'].']'.$v['name'],$XML_SERVER.'&action=zoom&selection='.$v['number'],$v['number']);
					else $object->addEntry($v['name'],$XML_SERVER.'&action=dial&selection='.$v['number'],$v['number']);
					$rank++;
					}
				$index++;
				}

			# Next Page
			if($page!=$last) $object->addEntry(Aastra_get_label('Next Page',$language),$XML_SERVER.'&action=list&page='.($page+1));
			}
		else
			{
			# Display error
			require_once('AastraIPPhoneTextScreen.class.php');
			$object=new AastraIPPhoneTextScreen();
			$object->setDestroyOnExit();
			$object->setTitle(Aastra_get_label('Directory error',$language));
			$object->setText(Aastra_get_label('Directory list is empty. Please contact your administrator.',$language));
			}
		break;
	}

# Output XML object
$object->output();
exit();
?>