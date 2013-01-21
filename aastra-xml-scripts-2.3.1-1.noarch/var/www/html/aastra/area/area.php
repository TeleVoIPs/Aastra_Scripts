<?php
#############################################################################
# North America area code finder
# Aastra SIP Phones R1.4.2 or better
#
# php source code
# Copyright Aastra Telecom Ltd 2008
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
require_once('AastraIPPhoneTextScreen.class.php');
require_once('AastraIPPhoneInputScreen.class.php');

#############################################################################
# Beginning of the active code
#############################################################################
# Retrieve parameters
$area=Aastra_getvar_safe('area');

# Trace
Aastra_trace_call('area','area='.$area);

# Test User Agent
Aastra_test_phone_version('1.4.2.',0);

# Get language
$language=Aastra_get_language();

# Global compatibility
$nb_softkeys=Aastra_number_softkeys_supported();

# Test parameter
if($area)
	{
	# Load area codes
	$array=Aastra_readINIfile('area_codes.txt','#','=');
	$object = new AastraIPPhoneTextScreen();
	$object->setDestroyOnExit();
	$object->setTitle(sprintf(Aastra_get_label('Area code %s',$language),$area));
	if($array[''][$area]!='') $object->setText($array[''][$area]);
	else $object->setText(sprintf(Aastra_get_label('Area code %s not found.',$language),$area));

	# Softkeys
	if($nb_softkeys>0)
		{
		if($nb_softkeys<7)
			{
			$object->addSoftkey('4',Aastra_get_label('Back',$language), $XML_SERVER);
			$object->addSoftkey('6',Aastra_get_label('Exit',$language), 'SoftKey:Exit');
			}
		else
			{
			$object->addSoftkey('9',Aastra_get_label('New Lookup',$language), $XML_SERVER);
			$object->addSoftkey('10',Aastra_get_label('Exit',$language), 'SoftKey:Exit');
			}
		}
	$object->setCancelAction($XML_SERVER);
	}
else
	{
	# Input area code
	$object=new AastraIPPhoneInputScreen();
	$object->setTitle(Aastra_get_label('Area code finder',$language));
	$object->setPrompt(Aastra_get_label('Enter area code',$language));
	$object->setParameter('area');
	$object->setType('number');
	$object->setURL($XML_SERVER);
	$object->setDestroyOnExit();
	
	# Softkeys
	if($nb_softkeys>0)
		{
		if($nb_softkeys<7)
			{
			$object->addSoftkey('1',Aastra_get_label('Backspace',$language),'SoftKey:BackSpace');
			$object->addSoftkey('5',Aastra_get_label('Lookup',$language),'SoftKey:Submit');
			$object->addSoftkey('6',Aastra_get_label('Exit',$language),'SoftKey:Exit');
			}
		else
			{
			$object->addSoftkey('10',Aastra_get_label('Exit',$language),'SoftKey:Exit');
			}
		}
	}

# Top title
if(Aastra_is_top_title_supported()) 
	{
	$object->setTopTitle(Aastra_get_label('Area code lookup'),'','1');
	$object->addIcon('1','http://'.$AA_XML_SERVER.'/'.$AA_XMLDIRECTORY.'/icons/area.png');
	}

# Display object
$object->output();
exit;
?>
