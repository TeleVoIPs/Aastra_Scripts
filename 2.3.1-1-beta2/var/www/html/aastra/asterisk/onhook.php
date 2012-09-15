<?php
#############################################################################
# Asterisk OnHook
#
# Aastra SIP Phones 1.4.2 or better
#
# Copyright 2008 Aastra Telecom Ltd
#
# script.php?user=USER&number=NUMBER&name=NAME
# USER is the user extension
# NUMBER is the remote phone number
# NAME is the remote phone Caller ID
#############################################################################

#############################################################################
# PHP customization for includes and warnings
#############################################################################
$os = strtolower(PHP_OS);
if(strpos($os, 'win') === false) ini_set('include_path',ini_get('include_path').':include:../include');
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
$number=Aastra_getvar_safe('number');
$name=Aastra_getvar_safe('name');
$user=Aastra_getvar_safe('user');

# Not identified yet
$found=0;

# Trace
Aastra_trace_call('onhook_asterisk','number='.$number.', name='.$name.', user='.$user);

# Get header info
$header=Aastra_decode_HTTP_header();

# Get language
$language=Aastra_get_language();

# If VoiceMail
if(($name=='VoiceMail') or ($name=='RecordGreetings') or ($number=='vmail') or ($number=='VMAIL'))
	{
	# Retrieve session
	$array=Aastra_read_session('vmail',$user);

	# Remove session
	Aastra_delete_session($user);

	# Call initial script
	require_once('AastraIPPhoneExecute.class.php');
	$object=new AastraIPPhoneExecute();
	$object->addEntry($array['uri_onhook']);

	# Stop the search
	$found=1;
	}

# If Away RecordTemporary
if(($found==0) and ($name=='RecordTemporary'))
	{
       # Call initial script
	require_once('AastraIPPhoneExecute.class.php');
       $object=new AastraIPPhoneExecute();
       $object->addEntry($XML_SERVER_PATH.'/away.php?user='.$user);

	# Stop the search
       $found=1;
	}

# Call parked?
if(($found==0) and ($number!='Paging') and ($number!=''))
	{
	# Translate user if needed
	$user=Aastra_get_userdevice_Asterisk($user);

	# Check if call has been parked
	$park=Aastra_check_park_Asterisk($user,$number);

	# Display orbit
	if($park!='')
		{
		# Display current status
		require_once('AastraIPPhoneStatus.class.php');
		$object=new AastraIPPhoneStatus();
		$object->setSession('Park');
	     	$object->setBeep();
     		if(Aastra_size_display_line()>16) $object->addEntry('1',sprintf(Aastra_get_label('Call Parked at %s',$language),$park),'alert',5);
		else $object->addEntry('1',sprintf(Aastra_get_label('Parked at %s',$language),$park),'alert',5);

		# Bug 3.2.1
		if(Aastra_test_phone_version('3.2.1.','1')==0) sleep(4);

		# End of search 
		$found=1;
		}
	}

# No match whatsoever
if($found==0)
	{
	require_once('AastraIPPhoneExecute.class.php');
	$object=new AastraIPPhoneExecute();
	$object->addEntry('');
	}

# Display XML object
# Trace
Aastra_trace_call('onhook_asterisk',$object->generate());
$object->output();
?>
