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
#    6730i, 6731i, 6751i, 6753i, 9143i running firmware 2.5.3 or better
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
$action=Aastra_getvar_safe('action','search');
$selection=Aastra_getvar_safe('selection');
$origin=Aastra_getvar_safe('origin');
$lookup=Aastra_getvar_safe('lookup','');

# Trace
Aastra_trace_call('directory_3_asterisk','user='.$user.', action='.$action.', speed='.$speed.', selection='.$selection);

# Test User Agent
Aastra_test_phone_versions(array('1'=>'','2'=>'','3'=>'2.5.3.','4'=>'','5'=>''),'0');

# Get Language
$language=Aastra_get_language();

# Save return URI
$XML_SERVER.='?user='.$user.'&origin='.$origin;

# Compute MaxLines
$MaxLines=AASTRA_MAXLINES-2;

# Process action
switch($action)
	{
	# Search the directory
	case 'search':
		# InputScreen
		require_once('AastraIPPhoneInputScreen.class.php');
		$object = new AastraIPPhoneInputScreen();
	      	$object->setTitle(Aastra_get_label('Directory Lookup',$language));
		$object->setURL($XML_SERVER.'&action=list');
	      	$object->setDestroyOnExit();
		$object->setType('string');
		$object->setPrompt(Aastra_get_label('Letters in name',$language));
		$object->setParameter('lookup');
		if($lookup!='') $object->setDefault($lookup);
		break;

	# Display status
	case 'zoom':
		# Retrieve user status
		$away=Aastra_manage_presence_Asterisk($selection,'get','status');

		# Labels for status
		$status_text=Aastra_status_config_Asterisk();

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
		$dial_uri='Dial:'.$selection;
		$icom_uri='Dial:'.$intercom.$selection;
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
			if(!$notify) $object->addEntry(Aastra_get_label('Notify me',$language),$XML_SERVER.'&action=notify&page='.$page.'&selection='.$selection.'&lookup='.$lookup);
			else $object->addEntry(Aastra_get_label('Unnotify me',$language),$XML_SERVER.'&action=unnotify&page='.$page.'&selection='.$selection.'&lookup='.$lookup);
			}
		$object->addEntry(Aastra_get_label('Back',$language),$XML_SERVER.'&action=list&page='.$page.'&selection='.$selection.'&lookup='.$lookup);
		break;

	# Notify
	case 'notify':
		# Display choice
		require_once('AastraIPPhoneTextMenu.class.php');
		$object=new AastraIPPhoneTextMenu();
		$object->setDestroyOnExit();
		$object->setStyle('none');
		$object->setTitle(Aastra_get_label('Notification type',$language));
		$object->addEntry(Aastra_get_label('Message',$language),$XML_SERVER.'&action=set_notifym&page='.$page.'&selection='.$selection.'&lookup='.$lookup);
		$object->addEntry(Aastra_get_label('Phone Call',$language),$XML_SERVER.'&action=set_notifyv&page='.$page.'&selection='.$selection.'&lookup='.$lookup);
		break;

	# Set Notify
	case 'set_notifym':
	case 'set_notifyv':
		# Add the user to the list
		if($action=='set_notifym') Aastra_manage_presence_Asterisk($selection,'set','notifym',$user);
		else Aastra_manage_presence_Asterisk($selection,'set','notifyv',$user);

		# Display text
		require_once('AastraIPPhoneTextScreen.class.php');
		$object=new AastraIPPhoneTextScreen();
		$object->setDestroyOnExit();
		$object->setTitle(Aastra_get_label('Notification Set',$language));
		$object->setText(sprintf(Aastra_get_label('You will be notified when %s returns.',$language),Aastra_get_callerid_Asterisk($selection)));

		# DoneAction
		$object->setDoneAction($XML_SERVER.'&action=zoom&page='.$page.'&selection='.$selection.'&lookup='.$lookup);
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
		$object->setDoneAction($XML_SERVER.'&action=zoom&page='.$page.'&selection='.$selection.'&lookup='.$lookup);
		break;
	
	# Display directory
	case 'list':
		# Get list of users
		$directory=Aastra_get_user_directory_Asterisk();

		# Remove current user
		unset($directory[Aastra_get_userdevice_Asterisk($user)]);

		# Search?
		if($lookup!='')
			{
			# Filter the search
			foreach($directory as $key=>$value)
				{
				if(!stristr($value['name'],$lookup)) unset($directory[$key]);
				}
			}

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
			$object->setWrapList();

			# Next Page
			if($page!=1) $object->addEntry(Aastra_get_label('Previous Page',$language),$XML_SERVER.'&action=list&page='.($page-1).'&lookup='.$lookup);

			# Display items
			$index=1;
			$rank=1;
			$min='';
			$max='';
			foreach ($directory as $v) 
				{
				if(($index>=(($page-1)*$MaxLines+1)) and ($index<=$page*$MaxLines))
					{
					if($min=='') $min=substr($v['name'],0,2);
					if($v['number']==$selection) $object->setDefaultIndex($rank);
					if($AA_PRESENCE_STATE) $object->addEntry('['.$status_text[$v['status']]['mnemonic'].']'.$v['name'],$XML_SERVER.'&action=zoom&selection='.$v['number'].'&lookup='.$lookup,$v['number']);
					else $object->addEntry($v['name'],'Dial:'.$v['number'],$v['number']);
					$rank++;
					$max=substr($v['name'],0,2);
					}
				$index++;
				}

			# Next Page
			if($page!=$last) $object->addEntry(Aastra_get_label('Next Page',$language),$XML_SERVER.'&action=list&page='.($page+1).'&lookup='.$lookup);

			# Title
			if($lookup=='')
				{
				if($last!=1) $object->setTitle(sprintf(Aastra_get_label('Directory (%s-%s)',$language),$min,$max));
				else $object->setTitle(Aastra_get_label('Directory',$language));
				}
			else
				{
				if($last!=1) $object->setTitle(sprintf(Aastra_get_label('Results (%s-%s)',$language),$min,$max));
				else $object->setTitle(Aastra_get_label('Results',$language));
				}
			}
		else
			{
			# Display error
			require_once('AastraIPPhoneTextScreen.class.php');
			$object=new AastraIPPhoneTextScreen();
			$object->setDestroyOnExit();
			if($lookup=='')
				{
				$object->setTitle(Aastra_get_label('Directory error',$language));
				$object->setText(Aastra_get_label('Directory list is empty. Please contact your administrator.',$language));
				}
			else
				{
				$object->setTitle(Aastra_get_label('Lookup error',$language));
				$object->setText(Aastra_get_label('Sorry no match.',$language));
				}

			# Softkey
			if($lookup!='') $object->setDoneAction($XML_SERVER.'&action=search&lookup='.$lookup);
			}
		break;
	}

# Output XML object
$object->output();
exit();
?>