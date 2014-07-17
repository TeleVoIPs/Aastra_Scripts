<?php
#############################################################################
# Asterisk DND
#
# Aastra SIP Phones 1.4.2 or better
#
# Copyright 2005-2010 Aastra Telecom Ltd
#
# Supported Aastra Phones
#    All Phones
#
# Usage
# 	script.php?user=USER
# 	USER is the extension of the phone on the platform
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
$action=Aastra_getvar_safe('action','change');
$status=Aastra_getvar_safe('status');

# Trace
Aastra_trace_call('dnd_asterisk','user='.$user.', action='.$action.', status='.$status);

# Test User Agent
Aastra_test_phone_versions(array('1'=>'1.4.2.','2'=>'1.4.2.','3'=>'2.5.3.','4'=>'2.5.3.','5'=>'3.0.1.'),'0');

# Authenticate user
if($action=='change') Aastra_check_signature_Asterisk($user);

# Get language
$language=Aastra_get_language();

# Update callback
$XML_SERVER.='?user='.$user;

# Depending on action
switch($action)
	{
	# Update Message status
	case 'msg':
		# Update idle screen
		require_once('AastraIPPhoneStatus.class.php');
		$object=new AastraIPPhoneStatus();
		$object->setSession('aastra-xml');
		$index=Aastra_get_status_index_Asterisk('dnd');
		if ($status==1) 
			{
			if(Aastra_is_status_uri_supported())
				{
				$object->addEntry($index,Aastra_get_label('DND activated',$language),'',NULL,$XML_SERVER,1);
				$object->addIcon('1','Icon:Prohibit');
				}
			else $object->addEntry($index,Aastra_get_label('DND activated',$language));
			}
		else $object->addEntry($index,'');
		break;

	# Switch On/Off
	case 'change':
		# Change DND status
		$dnd=Aastra_manage_dnd_Asterisk($user,'change');

		# Prepare display update
		require_once('AastraIPPhoneExecute.class.php');
		$object=new AastraIPPhoneExecute();

		# Pause/Unpause from queues
		if($AA_DNDPAUSE)
			{
			if($dnd) Aastra_queue_pause_Asterisk($user,'','true');
			else Aastra_queue_pause_Asterisk($user,'','false');
			}

		# Update LED and idle screen
		$data=Aastra_get_user_context($user,'dnd');
		$key=$data['key'];
		$last=$data['last'];
		$data['last']=$dnd;
		if($dnd!=$last) 
			{
			Aastra_save_user_context($user,'dnd',$data);
			$object->setBeep();
			$object->addEntry($XML_SERVER.'&action=msg&status='.$dnd);
			if(($key!='') and Aastra_is_ledcontrol_supported())
				{
				if($dnd==1) $object->addEntry('Led: '.$key.'=on');
				else $object->addEntry('Led: '.$key.'=off');
				}
			}

		# Send a SIP Notification if mode is device and user
		if((!$AA_FREEPBX_USEDEVSTATE) and ($AA_FREEPBX_MODE=='2')) Aastra_propagate_changes_Asterisk($user,Aastra_get_userdevice_Asterisk($user),array('dnd'));
		break;

	# Initial or recurrent check
	case 'check':
	case 'register':
		# Update needed
		$update=1;

		# Get current DND status
		$dnd=Aastra_manage_dnd_Asterisk($user,'get');

		# Get last DND status
		$data=Aastra_get_user_context($user,'dnd');
		$last=$data['last'];
		$key=$data['key'];

		# Save DND status
		$data['last']=$dnd;
		if($dnd!=$last) Aastra_save_user_context($user,'dnd',$data);

		# Update needed?
		if(($action=='check') and ($dnd==$last)) $update=0;
		if(($action=='register') and ($dnd==0)) $update=0;

		# Prepare display update
		require_once('AastraIPPhoneExecute.class.php');
		$object=new AastraIPPhoneExecute();
		if($update==1)
			{
			# Change msg status
			$object->addEntry($XML_SERVER.'&action=msg&status='.$dnd);

			# Change LED if supported
			if(($key!='') and Aastra_is_ledcontrol_supported())
				{
				if($dnd==1) $object->addEntry('Led: '.$key.'=on');
				else $object->addEntry('Led: '.$key.'=off');
				}
			}
		else
			{
			# Do nothing
			$object->addEntry('');
			}
		break;
	}

# Display object
$object->output();
exit;
?>
