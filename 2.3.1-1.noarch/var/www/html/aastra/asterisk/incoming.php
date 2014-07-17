<?php
#############################################################################
# Asterisk Incoming
#
# Aastra SIP Phones 1.4.2 or better
#
# Copyright 2008 Aastra Telecom Ltd
#
# Usage
# script.php?user=USER&number=NUMBER&name=NAME
#    USER is user extension on the platform
#    NUMBER is the remote phone number ($$REMOTE_NUMBER$$)
#    NAME is the incoming Caller ID ($$INCOMINGNAME$$)
#
#############################################################################

#############################################################################
# PHP customization for includes and warnings
#############################################################################
$os = strtolower(PHP_OS);
if(strpos($os, 'win') === false) ini_set('include_path',ini_get('include_path').':include:../include');
else ini_set('include_path',ini_get('include_path').';include;..\include');
error_reporting(E_ERROR | E_PARSE);

#############################################################################
# Includes
#############################################################################
require_once('AastraCommon.php');
require_once('AastraIPPhoneExecute.class.php');

#############################################################################
# Retrieve parameters
$number=Aastra_getvar_safe('number');
$name=Aastra_getvar_safe('name');

# Not identified yet
$found=0;

# Trace
Aastra_trace_call('incoming_asterisk','number=['.$number.'], name=['.$name.']');

# VoiceMail old code
if($name=='VoiceMail')
	{
	# Retrieve session
	$array=Aastra_read_session('vmail',$user);

	# Call initial script
	if($array['uri_incoming']!='')
		{
		$object = new AastraIPPhoneExecute();
		$object->addEntry($array['uri_incoming']);
		$found=1;
		}
	}

# ALERT SYSTEM
if(file_exists('../alert/alert.php') and ($name=='Alert Server'))
	{
	# Call initial script	
	$object = new AastraIPPhoneExecute();
	$object->addEntry($XML_SERVER_PATH.'../alert/alert.php?action=display&mac='.$number);
	$object->addEntry($XML_SERVER_PATH.'../alert/alert.php?action=msg&mac='.$number);
	$object->addEntry($XML_SERVER_PATH.'../alert/alert.php?action=key&mac='.$number);
	$object->addEntry($XML_SERVER_PATH.'../alert/alert.php?action=led&mac='.$number);
	$found=1;
	}

# No match whatsoever
if($found==0)
	{
	$object = new AastraIPPhoneExecute();
	$object->addEntry('');
	}

##########################################
# Display output
$object->output();
?>
