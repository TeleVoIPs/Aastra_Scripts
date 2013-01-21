#!/usr/bin/env php
<?php 
#############################################################################
# Away Status / Presence Notification Script
#
# When run, takes 3 command line parameters (so this can be run in the 
# background independent from the XML processing and delivery): notifications,
# user, option user name recording (voicemail greet).  These options feed
# into a manager originate, which opens up a standard *80 intercom to the 
# user and plays them (or their voicemail) a message.  Most of the magic
# happens in the dialplans located at /etc/asterisk/extensions-away-status.conf
#
# Copyright (C) 2008 Ethan Schroeder
# ethan.schroeder@schmoozecom.com
#
# All rights reserved.  THIS COPYRIGHT NOTICE MUST NOT BE REMOVED
# UNDER ANY CIRCUMSTANCES.
#
# A big thanks from the entire community to the following "heros
# who made this application possible for GPL release through their
# generous financial contributions:
#
# Trixbox users:
#
# peterfam - initial bounty organizer
# gbrook - initial bounty
# jahyde - initial bounty
# dwright154 - initial bounty
# necits -  for aastra-xml-scripts 2.1.1 support
# microman -  for aastra-xml-scripts 2.1.1 support
# Davis & Floyd Inc. - for iSymphony support
# Anyone else I missed: Just contact me to be put on the heroes list!
#
# Please support these users in the future!
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version
# of the License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE
#
###################################################################

###################################################################
# Includes
###################################################################
#error_reporting(E_ERROR | E_PARSE);
require_once '../include/AastraCommon.php';
require_once '../include/AastraAsterisk.php';

###################################################################
# Main code
###################################################################
# Wait 5 seconds to begin for not much other reason than this lets 
# you test the functionality with your own extension
sleep(5); 

# Collect arguments
$notifications=$argv[1];
$notify=explode(',',$notifications);
$extension=$argv[2];
$vars['__EXTENSION']=$extension;
if($argc==4) $vars['NAME_RECORDING']=$argv[3];

# Connect to the AGI
$asm=new AGI_AsteriskManager();
$asm->connect();

# Get language
$language=Aastra_get_language();

# Notify each phone
for($i=0; $i<sizeof($notify); $i++)  
	{
    	$vars['__REALCALLERIDNUM']=$notify[$i];
    	$state=$asm->ExtensionState($notify[$i],'default');
    	if($state['Status']!=0) $vars['NOTIFY_VM']='true';
    	while(list($key,$val)=each($vars)) $vars_arr[] = "$key=$val";
	if(Aastra_compare_version_Asterisk('1.6')) $vars=implode(',',$vars_arr);
	else $vars=implode('|',$vars_arr);
	$res=$asm->Originate2('Local/'.$notify[$i].'@presence-notify',9999,'default',1,null,null,null,sprintf(Aastra_get_label('%s is back <%s>',$language),$extension,$extension),$vars);
    	flush();
    	sleep(30); // give them a break inbetween calls, so they don't get flood of them all at once ;)
  	}

# Disconnect properly
$asm->disconnect();
?>
