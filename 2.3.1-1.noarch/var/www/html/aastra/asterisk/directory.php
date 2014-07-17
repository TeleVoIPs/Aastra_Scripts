<?php
#############################################################################
# Asterisk Directory for Aastra SIP Phones R 1.4.2 or better
#
# Copyright 2005-2010 Aastra Telecom Ltd
#
# Usage
# 	script.php?user=USER
# 	USER is the extension of the phone on the Asterisk platform
#
# Supported Aastra Phones
#    All phones
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
$origin=Aastra_getvar_safe('origin');

# Trace
Aastra_trace_call('directory_asterisk','user='.$user);

# Test User Agent
Aastra_test_phone_versions(array('1'=>'1.4.2.','2'=>'1.4.2.','3'=>'2.5.3.','4'=>'2.5.3.','5'=>'3.0.1.'),'0');

# Launch application
require_once('AastraIPPhoneExecute.class.php');
$object=new AastraIPPhoneExecute();
$object->addEntry($XML_SERVER_PATH.'directory_'.Aastra_phone_type().'.php?user='.$user.'&origin='.$origin);

# Output XML object
$object->output();
exit();
?>