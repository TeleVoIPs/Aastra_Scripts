<?php
#############################################################################
# Directory front end
# Aastra SIP Phones R1.4.2 or better
#
# php source code
# Copyright Aastra Telecom 2008-2010
#
# Supported Aastra Phones
#    All Phones but 9112i, 9133i
#
# directory.php?user=USER
# when
#    USER	is the username, if not provided the phone MAC address of the
#		phone is used.
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

#############################################################################
# Private functions
#############################################################################

#############################################################################
# Main code
#############################################################################
# Collect parameters
$user=Aastra_getvar_safe('user');
$action=Aastra_getvar_safe('action','input');
$source=Aastra_getvar_safe('source');

# Test if Asterisk mode
$asterisk=False;
if(file_exists('../include/AastraAsterisk.php'))
	{
	$asterisk=True;
	require_once('AastraAsterisk.php');
	}

# Log call to the application
if($asterisk) Aastra_trace_call('asterisk_directory','action='.$action.', lookup='.$lookup.', page='.$page.', index='.$index.', lastn='.$lastn.', firstn='.$firstn.', user='.$user.', speed='.$speed);
else Aastra_trace_call('directory','action='.$action.', lookup='.$lookup.', page='.$page.', index='.$index.', lastn='.$lastn.', firstn='.$firstn.', user='.$user.', speed='.$speed);

# Test User Agent
Aastra_test_phone_versions(array('1'=>'','2'=>'1.4.2.','3'=>'2.5.3.','4'=>'2.5.3.','5'=>'3.0.1.'),'0');

# Get Language
$language=Aastra_get_language();

# Get global compatibility
$nb_softkeys=Aastra_number_softkeys_supported();

# Update URI
$XML_SERVER.='?user='.$user;

# Get csv file location
$ARRAY_CONFIG=Aastra_readINIfile(AASTRA_CONFIG_DIRECTORY.'directory.conf','#','=');
unset($ARRAY_CONFIG['Dialplan']);
$first_source='';
foreach($ARRAY_CONFIG as $source=>$value)
	{
	switch($value['type'])
		{
		case 'csv':
			if($first_source=='') $first_source=$source;
			$ARRAY_CONFIG[$source]['uri']=$XML_SERVER_PATH.'csv_directory.php?user='.$user.'&source='.$source;
			break;
		case 'ldap':
			if($first_source=='') $first_source=$source;
			$ARRAY_CONFIG[$source]['uri']=$XML_SERVER_PATH.'ldap_directory.php?user='.$user.'&source='.$source;
			break;
		case 'script':
			if($first_source=='') $first_source=$source;
			$search=array('/\$\$AA_XML_SERVER_AA\$\$/',
					'/\$\$AA_XMLDIRECTORY_AA\$\$/'
					);
			$replace=array($AA_XML_SERVER,
	 				 $AA_XMLDIRECTORY
					);
			$ARRAY_CONFIG[$source]['uri']=preg_replace($search, $replace, $ARRAY_CONFIG[$source]['uri']);
			if($ARRAY_CONFIG[$source]['uri']=='') unset($ARRAY_CONFIG[$source]);
			break;
		default:
			unset($ARRAY_CONFIG[$source]);
			break;
		}
	}

# Depending on how many directories are configured
switch(count($ARRAY_CONFIG))
	{
	# No directory
	case '0':
		require_once('AastraIPPhoneTextScreen.class.php');
		$object=new AastraIPPhoneTextScreen();
		$object->setDestroyOnExit();
		$object->setTitle(Aastra_get_label('Error',$language));
		$object->setText(Aastra_get_label('No directory configured. Please contact your administrator.',$language));
		if($nb_softkeys)
			{
			if($nb_softkeys==6) $object->addSoftkey('6',Aastra_get_label('Exit',$language),'SoftKey:Exit');
			else 
				{
				$object->addSoftkey('10',Aastra_get_label('Exit',$language),'SoftKey:Exit',1);
				$object->addIcon(1,'Icon:CircleRed');
				}
			}
		break;

	# Just one
	case '1':
		# Straight to the directory
		require_once('AastraIPPhoneExecute.class.php');
		$object=new AastraIPPhoneExecute();
		$object->addEntry($ARRAY_CONFIG[$first_source]['uri']);
		break;

	# More than one
	default:
		# Display selection
		require_once('AastraIPPhoneTextMenu.class.php');
		$object=new AastraIPPhoneTextMenu();
		$object->setDestroyOnExit();
		$object->setTitle(Aastra_get_label('Directory Selection',$language));
		foreach($ARRAY_CONFIG as $source=>$value) $object->addEntry($value['label'],$value['uri'].'&back=1');

		# Softkeys
		if($nb_softkeys)
			{
			if($nb_softkeys==6)
				{
				$object->addSoftkey('1',Aastra_get_label('Select',$language),'SoftKey:Select');
				$object->addSoftkey('6',Aastra_get_label('Exit',$language),'SoftKey:Exit');
				}
			else 
				{
				$object->addSoftkey('10',Aastra_get_label('Exit',$language),'SoftKey:Exit',1);
				$object->addIcon(1,'Icon:CircleRed');
				}
			}
		break;
	}

# Send XML answer
$object->output();
exit;
?>
