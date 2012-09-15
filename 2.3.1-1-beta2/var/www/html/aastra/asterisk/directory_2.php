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
#    480i and 480i CT running 1.4.2 or better
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
$speed=Aastra_getvar_safe('speed');
$selection=Aastra_getvar_safe('selection');
$origin=Aastra_getvar_safe('origin');
$orig_s=Aastra_getvar_safe('orig_s');

# Trace
Aastra_trace_call('directory_asterisk','user='.$user.', action='.$action.', speed='.$speed.', selection='.$selection);

# Test User Agent
Aastra_test_phone_versions(array('1'=>'','2'=>'1.4.2.','3'=>'','4'=>'','5'=>''),'0');

# Retrieve phone information
$header=Aastra_decode_HTTP_header();

# Get Language
$language=Aastra_get_language();

# Save return URI
$XML_SERVER.='?user='.$user.'&origin='.$origin;

# Compute MaxLines
$MaxLines=AASTRA_MAXLINES;

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

	# Display Speed dial
	case 'select':
		# Get user context
		$conf_speed=Aastra_get_user_context(Aastra_get_userdevice_Asterisk($user),'speed');

		# Find first available spot
		$found=0;
		$i=0;
		while(($found==0) and ($i<$MaxLines))
			{
			if($conf_speed[$i]['name']=='') $found=1;
			$i++;
			}

		# Display list
		require_once('AastraIPPhoneTextMenu.class.php');
		$object=new AastraIPPhoneTextMenu();
		$object->setDestroyOnExit();
		if($found==1) $object->setDefaultIndex($i);
		$object->setTitle(Aastra_get_label('Speed Dial List',$language));
		for($i=0;$i<$MaxLines;$i++)
			{
			$name=$conf_speed[$i]['name'];
			if($name=='') $name='......................';
			$object->addEntry($name,$XML_SERVER.'&action=set&speed='.$i.'&selection='.$selection.'&page='.$page,'');
			}

		# Softkeys
		$object->addSoftkey('1',Aastra_get_label('Select',$language),'SoftKey:Select');
		$object->addSoftkey('4',Aastra_get_label('Cancel',$language),$XML_SERVER.'&action='.$orig_s.'&page='.$page.'&selection='.$selection);
		$object->addSoftkey('6',Aastra_get_label('Exit',$language),'SoftKey:Exit');
		break;

	# Save speed dial value
	case 'set':
		# Get user context
		$conf_speed=Aastra_get_user_context(Aastra_get_userdevice_Asterisk($user),'speed');

		# Save value
		$conf_speed[$speed]['name']=Aastra_get_callerid_Asterisk($selection);
		$conf_speed[$speed]['work']=$selection;
		Aastra_save_user_context(Aastra_get_userdevice_Asterisk($user),'speed',$conf_speed);
		
		# Display position
		require_once('AastraIPPhoneTextScreen.class.php');
		$object=new AastraIPPhoneTextScreen();
		$object->setDestroyOnExit();
		$object->setTitle(Aastra_get_label('List Updated',$language));
		$position=$speed+1;		
		$object->setText(sprintf(Aastra_get_label('%s stored in speed dial list at position %d.',$language),$conf_speed[$speed]['name'],$position));

		# Softkey
		$object->addSoftkey('6',Aastra_get_label('Close',$language),$XML_SERVER.'&action='.$orig_s.'&page='.$page.'&selection='.$selection);
		break;

	# Display status
	case 'zoom':
		# Retrieve user status
		$away=Aastra_manage_presence_Asterisk($selection,'get','status');

		# Labels for status
		$status_text=Aastra_status_config();

		# Check notification
		if($away['status']!=AA_PRESENCE_AVAILABLE)
			{
			$explodem=explode(',',$away['notifym']);
			$explodev=explode(',',$away['notifyv']);
			$real_user=Aastra_get_userdevice_Asterisk($user);
			if(in_array($real_user,$explodem) or in_array($real_user,$explodev)) $notify=True;
			else $notify=False;
			}
		else
			{
			# Dynamic status
			$hint=Aastra_get_user_hint_asterisk($selection);
			}

		# Use a TextScreen
		require_once('AastraIPPhoneTextScreen.class.php');
		$object=new AastraIPPhoneTextScreen();
		$object->setDestroyOnExit();
		$object->setTitle(Aastra_get_callerid_Asterisk($selection).' ('.$selection.')');
		if($away['status']!=AA_PRESENCE_AVAILABLE)
			{
			$line=Aastra_format_presence_dt_Asterisk($away['return']);
			$dt=$line[0];
			if($line[1]!='') $dt.=' '.$line[1];
			if($notify) $text=sprintf(Aastra_get_label('Currently %s. %s. %s.',$language),$status_text[$away['status']]['label'],$dt,Aastra_get_label('Notification on return',$language));
			else $text=sprintf(Aastra_get_label('Currently %s. %s.',$language),$status_text[$away['status']]['label'],$dt);
			}
		else 
			{
			$text=sprintf(Aastra_get_label('Currently %s.',$language),$status_text[$away['status']]['label']);
			if($hint=='Idle') $text.=' '.Aastra_get_label('Phone is idle',$language);
			else
				{
				if($hint=='Ringing') $text.=' '.Aastra_get_label('Phone is ringing',$language);
				else $text.=' '.Aastra_get_label('Phone is in use',$language);
				}
			}
		$object->setText($text);

		# Softkeys
		$object->addSoftKey('1',Aastra_get_label('Dial',$language),$XML_SERVER.'&action=dial&selection='.$selection);
		if(($away['status']!=AA_PRESENCE_AVAILABLE) and ($away['status']!=AA_PRESENCE_DISCONNECTED))
			{
			if(!$notify) $object->addSoftKey('2',Aastra_get_label('Notify me',$language),$XML_SERVER.'&action=notify&page='.$page.'&selection='.$selection);
			else $object->addSoftKey('2',Aastra_get_label('Unnotify',$language),$XML_SERVER.'&action=unnotify&page='.$page.'&selection='.$selection);
			}
		if($AA_SPEEDDIAL_STATE) $object->addSoftkey('3',Aastra_get_label('+Speed',$language),$XML_SERVER.'&action=select&orig_s=zoom&page='.$page.'&selection='.$selection);
		$intercom=Aastra_get_intercom_config_Asterisk();
		if($intercom!='') $object->addSoftKey('4',Aastra_get_label('Icom',$language),$XML_SERVER.'&action=dial&selection='.$intercom.$selection);
		$object->addSoftKey('5',Aastra_get_label('Back',$language),$XML_SERVER.'&action=list&page='.$page.'&selection='.$selection);
		$object->addSoftKey('6',Aastra_get_label('Exit',$language),'SoftKey:Exit');
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

		# Softkey
		$object->addSoftkey('6',Aastra_get_label('Close',$language),$XML_SERVER.'&action=zoom&page='.$page.'&selection='.$selection);
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

		# Softkey
		$object->addSoftkey('6',Aastra_get_label('Close',$language),$XML_SERVER.'&action=zoom&page='.$page.'&selection='.$selection);
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
			$index=1;
			$rank=1;
			$dial=2;
			foreach ($directory as $v) 
				{
				if(($index>=(($page-1)*$MaxLines+1)) and ($index<=$page*$MaxLines))
					{
					if($v['number']==$selection) $object->setDefaultIndex($rank);
					$name=$v['name'];
					if($AA_PRESENCE_STATE) $name='['.$status_text[$v['status']]['mnemonic'].']'.$v['name'];
					$object->addEntry($name,$v['number'],$v['number']);
					$rank++;
					}
				$index++;
				}

			# Softkeys
			if(!$AA_PRESENCE_STATE)
				{
				$object->addSoftkey('1',Aastra_get_label('Dial',$language),'SoftKey:Dial');
				if($AA_SPEEDDIAL_STATE) $object->addSoftkey('3',Aastra_get_label('+Speed',$language),$XML_SERVER.'&action=select&orig_s=list&page='.$page);
				}
			else
				{
				$object->addSoftkey('1',Aastra_get_label('Select',$language),$XML_SERVER.'&action=zoom&page='.$page);
				$object->addSoftkey('3',Aastra_get_label('Dial',$language),'SoftKey:Dial');
				if($origin!='presence') 
					{
					if($AA_SPEEDDIAL_STATE) $object->addSoftkey('4',Aastra_get_label('+Speed',$language),$XML_SERVER.'&action=select&orig_s=listpage='.$page);
					}
				else $object->addSoftkey('4',Aastra_get_label('Back',$language),$XML_SERVER_PATH.'/away.php?user='.$user);
				}
			if($page!=1) $object->addSoftkey('2',Aastra_get_label('Previous',$language),$XML_SERVER.'&action=list&page='.($page-1));
			if($page!=$last) $object->addSoftkey('5',Aastra_get_label('Next',$language),$XML_SERVER.'&action=list&page='.($page+1));
			$object->addSoftkey('6',Aastra_get_label('Exit',$language),'SoftKey:Exit');
			}
		else
			{
			# Display error
			require_once('AastraIPPhoneTextScreen.class.php');
			$object=new AastraIPPhoneTextScreen();
			$object->setDestroyOnExit();
			$object->setTitle(Aastra_get_label('Directory error',$language));
			$object->setText(Aastra_get_label('Directory list is empty. Please contact your administrator.',$language));

			# Softkey
			$object->addSoftkey('6',Aastra_get_label('Exit',$language),'SoftKey:Exit');
			}
		break;
	}

# Output XML object
$object->output();
exit();
?>