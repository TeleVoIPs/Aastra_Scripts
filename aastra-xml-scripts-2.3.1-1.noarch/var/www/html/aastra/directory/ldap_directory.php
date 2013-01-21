<?php
###################################################################################################
# Example of an XML Directory using LDAP as data source for the Aastra IP Phones
# Copyright Aastra Telecom 2009-2010
#
# Script needs PHP5 or higher with LDAP extension
#
# Aastra SIP Phones Firmware 2.3.0 or higher. 
# Supported phones: All 67xxi phones.
#
# Configuration:
#   See file ../config/directory.conf
#
# Usage:
#   Configure an XML softkey on an Aastra IP Phone pointing to this script.
#
###################################################################################################

#############################################################################
# PHP customization for includes and warnings
#############################################################################
$os = strtolower(PHP_OS);
if(strpos($os, "win") === false) ini_set('include_path',ini_get('include_path').':include:../include');
else ini_set('include_path',ini_get('include_path').';include;..\include');
error_reporting(E_ERROR | E_WARNING | E_PARSE);

#############################################################################
# Includes
#############################################################################
require_once('AastraCommon.php');

#############################################################################
# Private functions
#############################################################################

function get_user_config($user)
{
Global $asterisk;

if($asterisk) $array_user=Aastra_get_user_context(Aastra_get_userdevice_Asterisk($user),'contacts');
else $array_user=Aastra_get_user_context($user,'contacts');
if($array_user['display']=='') $array_user['display']='firstlast';
if($array_user['sort']=='') $array_user['sort']='first';
if($asterisk) Aastra_save_user_context(Aastra_get_userdevice_Asterisk($user),'contacts',$array_user);
else Aastra_save_user_context($user,'contacts',$array_user);
return($array_user);
}

#############################################################################
# query_ldap(lookup,firstn,lastn)
#
# Query the LDAP server using the provided search criteria.
#
# Parameters
#     @lookup 	lookup string (last or first name)
#     @firstn 	lookup string for first name
#     @lastn	 	lookup string for last name
#
# Returns 
#     Array of all directory records matching the criterion. 
#     NULL if no matches found.
#
#############################################################################
function query_ldap($lookup,$firstn,$lastn,$source) 
{
global $LANGUAGE;

# Retrieve LDAP basic configuration
$system=Aastra_readINIfile(AASTRA_CONFIG_DIRECTORY.'directory.conf','#','=');
$LDAP_SERVER = $system[$source]['hostname'];
$LDAP_PORT = $system[$source]['port'];
$LDAP_ROOT_DN = $system[$source]['basedn'];
$LDAP_USERDOMAIN = $system[$source]['userdomain'];
$LDAP_USERNAME = $system[$source]['username'];
$LDAP_PASSWORD = $system[$source]['userpassword'];

########################################### Advanced LDAP Configuration #########################################
$LDAP_SIZELIMIT = 400; // limits the number of entries fetched. 0 means no limit.
$LDAP_TIMELIMIT = 5; // max number of seconds spent on the search. 0 means no limit.

# What additional attributes are retrieved from LDAP. Possible attributes: "title", "department", "company"
#$LDAP_ADDITIONAL_ATTRIBUTES = array("title", "department", "company");

# LDAP attribute mapping. Put empty string if attribute does not exist in LDAP.
$LDAP_PHONE_ATTRIBUTE = 'telephonenumber'; // name of the LDAP attribute that contains the office phone number
$LDAP_MOBILE_ATTRIBUTE = 'mobile'; // name of the LDAP attribute that contains the mobile (cell) phone number. "mobile" or "mobiletelephonenumber".
$LDAP_HOMEPHONE_ATTRIBUTE = 'homephone'; // name of the LDAP attribute that contains the home phone number, typically "homephone"
########################################### Advanced LDAP Configuration #########################################

# Prepare search filter		
if ($lookup != '') $searchfilter='(|(sn='.$lookup.'*)(givenName='.$lookup.'*))';
else 
	{
	if ($firstn!='' and $lastn!='') $searchfilter='(&(sn='.$lastn.'*)(givenName='.$firstn.'*))';
	else 
		{
		if ($firstn!='') $searchfilter='(givenName='.$firstn.'*)';
		else $searchfilter='(sn='.$lastn.'*)';
		}
	}
	
# Refine searchfilter in order to exclude "contacts"
#$searchfilter="(&".$searchfilter."(!(objectclass=contact)))";

# Prepare LDAP attributes to look for
$ldap_attribues=array('cn','sn','givenname');
array_push($ldap_attribues, $LDAP_PHONE_ATTRIBUTE, $LDAP_MOBILE_ATTRIBUTE, $LDAP_HOMEPHONE_ATTRIBUTE);

# Complete with optional attributes	
if (is_array($LDAP_ADDITIONAL_ATTRIBUTES)) $ldap_attribues = array_merge($ldap_attribues, $LDAP_ADDITIONAL_ATTRIBUTES);

# Connect to LDAP SERVER
$ds=ldap_connect($LDAP_SERVER,$LDAP_PORT);
	
# Connected to the LDAPserver
if($ds) 
	{
	# Bind type
	if (trim($LDAP_USERNAME) != '') 
		{
		# bind with credentials
		if($LDAP_USERDOMAIN!='') $username=$LDAP_USERDOMAIN.chr(92).$LDAP_USERNAME;
		else $username=$LDAP_USERNAME;
		$lb=ldap_bind($ds,$username,$LDAP_PASSWORD);
		} 
	else 
		{
		# anonymous bind
		$lb = ldap_bind($ds);
		}

	# Bind successful		
	if ($lb) 
		{
		# Launch the search
		$sr=ldap_search($ds, $LDAP_ROOT_DN, $searchfilter, $ldap_attribues, 0, $LDAP_SIZELIMIT, $LDAP_TIMELIMIT); 

		# Search is successful
		if($sr) 
			{
			# Retrieve dialplan configuration
			$dialplan=$system['Dialplan'];

			# Convert comma/semicolon/whitespace separated list into array
			$dialplan['local']=preg_split('/,|;|\s/',$system['Dialplan']['local']); 

			# Retrieve results
			$entries=ldap_get_entries($ds, $sr);
			$j=0;

			# Process each answer
			for ($i=0; $i<$entries['count']; $i++) 
				{
				# Retrieve phone numbers
				$office =$entries[$i][$LDAP_PHONE_ATTRIBUTE][0];
				$mobile =$entries[$i][$LDAP_MOBILE_ATTRIBUTE][0];
				$home   =$entries[$i][$LDAP_HOMEPHONE_ATTRIBUTE][0];

				# Keep entries that contain at least one phone number (office, mobile, home). Number must have at least one digit.
				if (preg_match("/[0-9]+/",$office) or preg_match("/[0-9]+/",$mobile) or preg_match("/[0-9]+/",$home)) 
					{
					$result_array[$j]['office'] = $office;
					$result_array[$j]['officeDigits'] = prepare_number($office,$dialplan);
					$result_array[$j]['mobile'] = $mobile;
					$result_array[$j]['mobileDigits'] = prepare_number($mobile,$dialplan);
					$result_array[$j]['home'] = $home;
					$result_array[$j]['homeDigits'] = prepare_number($home,$dialplan);
					if (empty($entries[$i]["sn"][0])) $result_array[$j]['name'] = $entries[$i]["cn"][0];
					 else 
						{
						$result_array[$j]['name'] = $entries[$i]["sn"][0];
						$result_array[$j]['firstname'] = $entries[$i]["givenname"][0];
						}
					$result_array[$j]['title'] = $entries[$i]["title"][0];
					$result_array[$j]['department'] = $entries[$i]["department"][0];
					$result_array[$j]['company'] = $entries[$i]["company"][0];
					$j++;
					} 
				}

			# Close LDAP connection
			ldap_close($ds);
			
			# Return results
			return empty($result_array) ? NULL : $result_array;
			} 
		else 
			{
			# Close LDAP connection
			ldap_close($ds);

			# Display error message
			display_message(Aastra_get_label('Server error',$LANGUAGE),Aastra_get_label('Cannot send query to LDAP Server. Please contact your administrator.',$LANGUAGE));
			exit;
			}
		} 
	else 
		{
		# Close LDAP connection
		ldap_close($ds);

		# Display error message
		display_message(Aastra_get_label('Server error',$LANGUAGE),Aastra_get_label('Cannot bind to LDAP Server. Please contact your administrator.',$LANGUAGE));
		exit;
		} 
	} 
else 
	{
	# Display error message
	display_message(Aastra_get_label('Server error',$LANGUAGE),Aastra_get_label('Cannot connect to LDAP Server. Please contact your administrator.',$LANGUAGE));
	exit;
	}
}

#############################################################################
# prepare_number(input,dialplan)
#
# Convert number string found in directory into a dial-able number
#
# Parameters
#     @input	 	phone number as found in directory
#     @dialplan	dialplan configuration
#
# Returns 
#     Dialable number 
#############################################################################
function prepare_number($input,$dialplan) 
{
# Returns the same by default
$output=$input;
	
# Remove spaces
$output=str_replace(' ','',$output);
	
# Remove dashes
$output=str_replace('-','',$output);
	
# Remove (0)
$output=str_replace('(0)','',$output);
	
# Remove ( and )
$output=str_replace('(','',$output);
$output=str_replace(')','',$output);
	
# Convert international numbers from own country into national format (replace +<countrycode> by long distance prefix)
$output=str_replace('+'.$dialplan['country'], $dialplan['long distance'],$output);
	
# Replace '+' sign with international prefix
$output=str_replace('+',$dialplan['international'],$output);

# Check length of number. If equal or less than $LOCAL_NUMBER_MAX_LENGTH, treat number as local
if (strlen($output) <= $dialplan['localextlen']) return $output;
	
# Check local number prefixes
foreach ($dialplan['local'] as $local_prefix) 
	{
	if (empty($local_prefix)) continue;
	if (!(strpos($output,$local_prefix) === false)) 
		{
		# remove prefix and mark number as local
		$output=str_replace($local_prefix,'',$output);
		return $output;
		}
	}

# Add outgoing prefix
$output=$dialplan['outgoing'].$output;	

# Return number		
return $output;
}

#############################################################################
# display_message($title,$message)
#
# Send message to the phone
#
# Parameters
#     @title	 	Title of the message
#     @message 	Body of the message
#############################################################################

function display_message($title,$message,$backURL=NULL) 
{
global $LANGUAGE;

# Depending on phone type
if(Aastra_phone_type()!=5)
	{
	# non 6739i
	require_once('AastraIPPhoneTextScreen.class.php');
	$object = new AastraIPPhoneTextScreen();
	$object->setTitle($title);
	$object->setText($message);
	$object->addSoftkey('5', Aastra_get_label('Back',$LANGUAGE), $backURL);
	$object->addSoftkey('6', Aastra_get_label('Exit',$LANGUAGE), 'SoftKey:Exit');
	}
else 
	{
	# 6739i/8000i
	require_once('AastraIPPhoneFormattedTextScreen.class.php');
	$object = new AastraIPPhoneFormattedTextScreen();
	$object->addLine('','double','center');
	$object->addLine($title,'double','center','red');
	$object->setScrollStart('3');
	$object->addLine('');
	$object->addLine('');
	$object->addLine($message,NULL,'center');
	$object->setScrollEnd();
	$object->addLine('',NULL,'center');
	$object->addSoftkey('9', Aastra_get_label('Back',$LANGUAGE),$backURL,1);
	$object->addSoftkey('10', Aastra_get_label('Exit',$LANGUAGE),'SoftKey:Exit',2);
	$object->addIcon(1,'Icon:ArrowLeft');
	$object->addIcon(2,'Icon:CircleRed');
	}

# Common parameters
$object->setDestroyOnExit();
$object->setCancelAction($backURL);
$object->output();
exit;
}

#############################################################################
# WARNINGErrorHandler($code, $string, $file, $line)
#
# Called by error handler in case of WARNINGs
#
#############################################################################

function WARNINGErrorHandler($code, $string, $file, $line) 
{ 
global $LANGUAGE;
	
if (preg_match("/Sizelimit exceeded/i",$string)) display_message(Aastra_get_label('LDAP Error',$LANGUAGE),Aastra_get_label('Result size limit exceeded. Provide more letters.',$LANGUAGE),$XML_SERVER);
else display_message(Aastra_get_label('Application error',$LANGUAGE),Aastra_get_label('Error Message',$LANGUAGE).": ".$string,$XML_SERVER);
exit;
}


#############################################################################
# Main code
#############################################################################

# Collect parameters
$action    =trim(Aastra_getvar_safe('action'));
$user      =trim(Aastra_getvar_safe('user'));
if($user=='')
	{
	$header=Aastra_decode_HTTP_header();
	$user=$header['mac'];
	}
$lookup    =trim(Aastra_getvar_safe('lookup'));
$lastname  =trim(Aastra_getvar_safe('lastname'));
$firstname =trim(Aastra_getvar_safe('firstname'));
$source    =trim(Aastra_getvar_safe('source'));
$back      =trim(Aastra_getvar_safe('back'));
$passwd    =trim(Aastra_getvar_safe('passwd'));

# Set custom error handler for E_WARNING errors in order to catch LDAP errors
set_error_handler('WARNINGErrorHandler', E_WARNING); 

# Test if Asterisk mode
$asterisk=False;
if(file_exists('../include/AastraAsterisk.php'))
	{
	$asterisk=True;
	require_once('AastraAsterisk.php');
	}

# Log call to the application
if($asterisk) Aastra_trace_call('asterisk_LDAP_dir','source='.$source.', action='.$action.', user='.$user.', lookup='.$lookup.', lastname='.$lastname.', firstname='.$firstname);
else Aastra_trace_call('LDAP_directory','source='.$source.', action='.$action.', user='.$user.', lookup='.$lookup.', lastname='.$lastname.', firstname='.$firstname);

# Test User Agent
# PROV
#Aastra_test_phone_versions(array('1'=>'','2'=>'','3'=>'2.5.3.','4'=>'2.5.3.','5'=>'3.0.1.'),'0');

# Get Language
$LANGUAGE=Aastra_get_language();

# Get global compatibility
$nb_softkeys=Aastra_number_softkeys_supported();
$is_style_textmenu=Aastra_is_style_textmenu_supported();
$is_textmenu_wrapitem=Aastra_is_textmenu_wrapitem_supported();

# Update URI
$XML_SERVER.='?user='.$user.'&source='.$source.'&back='.$back;

# Get ldap parameters
$error=False;
$ARRAY_CONFIG=Aastra_readINIfile(AASTRA_CONFIG_DIRECTORY.'directory.conf','#','=');
if($ARRAY_CONFIG[$source]['hostname']!='') 
	{
	if($ARRAY_CONFIG[$source]['speed']=='0') $SPEED=False;
	else $SPEED=True;
	if($asterisk)
		{
		if(!$AA_SPEEDDIAL_STATE) $SPEED=FALSE;
		}
	}
else $error=True;

# Configuration error
if($error)
	{
	require_once('AastraIPPhoneTextScreen.class.php');
	$object=new AastraIPPhoneTextScreen();
	$object->setDestroyOnExit();
	$object->setTitle(Aastra_get_label('Error',$LANGUAGE));
	$object->setText(Aastra_get_label('Database location not found. Please contact your administrator. Source='.$source,$LANGUAGE));
	if($nb_softkeys)
		{
		if($nb_softkeys==6) $object->addSoftkey('6',Aastra_get_label('Exit',$LANGUAGE),'SoftKey:Exit');
		else $object->addSoftkey('10',Aastra_get_label('Exit',$LANGUAGE),'SoftKey:Exit');
		}
	$object->output();
	exit;
	}

# Add user if password OK
if(!empty($passwd))
	{
	if($passwd==$ARRAY_CONFIG[$source]['password']) Aastra_add_access('csv_directory_'.$source,$user);
	else
		{
		$object = new AastraIPPhoneTextScreen();
		$object->setDestroyOnExit();
		$object->setTitle(Aastra_get_label('Access Denied',$LANGUAGE));
		$object->setText(Aastra_get_label('Wrong password.',$LANGUAGE));
		$object->output();
		exit;
		}
	}

# If needed test if user is authorized
if($ARRAY_CONFIG[$source]['password']!='') Aastra_test_access('csv_directory_'.$source,'passwd',$XML_SERVER,$user);

# Get user context
$ARRAY_USER=get_user_config($user);

# If no search string was provided, don't perfom search
if(empty($lookup) && empty($lastname) && empty($firstname) && ($action=='search')) $action='input';

# Process action
switch($action) 
	{
	# Settings
	case 'settings':
		# Display selection
		require_once('AastraIPPhoneTextMenu.class.php');
		$object=new AastraIPPhoneTextMenu();
		$object->setDestroyOnExit();
		$object->setTitle(Aastra_get_label('Settings',$LANGUAGE));
		if($is_style_textmenu) $object->setStyle('none');
		if($index!='') $object->setDefaultIndex($index);
		$text=array(	'firstlast'=>Aastra_get_label('First Last',$LANGUAGE),
				'lastfirst'=>Aastra_get_label('Last First',$LANGUAGE)
				);
		$object->addEntry(sprintf(Aastra_get_label('Display: %s',$LANGUAGE),$text[$ARRAY_USER['display']]),$XML_SERVER.'&action=set_display','');
		$text=array(	'last'=>Aastra_get_label('Last Name',$LANGUAGE),
				'first'=>Aastra_get_label('First Name',$LANGUAGE)
				);
		$object->addEntry(sprintf(Aastra_get_label('Sort: %s',$LANGUAGE),$text[$ARRAY_USER['sort']]),$XML_SERVER.'&action=set_sort','');

		# Softkeys
		if($nb_softkeys)
			{
			if($nb_softkeys==6)
				{
				$object->addSoftkey('1',Aastra_get_label('Change',$LANGUAGE),'SoftKey:Select');
				$object->addSoftkey('4',Aastra_get_label('Back',$LANGUAGE),$XML_SERVER.'&action=input');
				$object->addSoftkey('6',Aastra_get_label('Exit',$LANGUAGE),'SoftKey:Exit');
				}
			else
				{
				$object->addSoftkey('9',Aastra_get_label('Back',$LANGUAGE),$XML_SERVER.'&action=input',1);
				$object->addSoftkey('10',Aastra_get_label('Exit',$LANGUAGE),'SoftKey:Exit',2);
				$object->setCancelAction($XML_SERVER.'&action=input');
				$object->addIcon(1,'Icon:ArrowLeft');
				$object->addIcon(2,'Icon:CircleRed');
				}
			}
		else 
			{
			$object->addEntry(Aastra_get_label('Back',$LANGUAGE),$XML_SERVER.'&action=input');
			$object->setCancelAction($XML_SERVER.'&action=input');
			}
		break;

	# Change display value
	case 'set_display':
		# Save value
		if($ARRAY_USER['display']=='firstlast') $ARRAY_USER['display']='lastfirst';
		else $ARRAY_USER['display']='firstlast';
		if($asterisk) Aastra_save_user_context(Aastra_get_userdevice_Asterisk($user),'contacts',$ARRAY_USER);
		else Aastra_save_user_context($user,'contacts',$ARRAY_USER);

		# Call back
		require_once('AastraIPPhoneExecute.class.php');
		$object=new AastraIPPhoneExecute();
		$object->addEntry($XML_SERVER.'&action=settings&index=1');
		break;

	# Change sort value
	case 'set_sort':
		# Save value
		if($ARRAY_USER['sort']=='first') $ARRAY_USER['sort']='last';
		else $ARRAY_USER['sort']='first';
		if($asterisk) Aastra_save_user_context(Aastra_get_userdevice_Asterisk($user),'contacts',$ARRAY_USER);
		else Aastra_save_user_context($user,'contacts',$ARRAY_USER);

		# Call back
		require_once('AastraIPPhoneExecute.class.php');
		$object=new AastraIPPhoneExecute();
		$object->addEntry($XML_SERVER.'&action=settings&index=2');
		break;

	# Search
	case 'search':
		# Make the LDAP query
		$result=query_ldap($lookup,$firstname,$lastname,$source);
		$hits=count($result);

		# How many matches
		if ($hits==0) 
			{
			# No match
			display_message(Aastra_get_label('No match found',$LANGUAGE),Aastra_get_label('Please modify your search.',$LANGUAGE),$XML_SERVER.'?lastname='.$lastname.'&firstname='.$firstname.'&lookup='.$lookup);
			}
		else
			{
			# Display Results 
			require_once('AastraIPPhoneScrollableDirectory.class.php');
			$object = new AastraIPPhoneScrollableDirectory();
			if($hits==1) $object->setTitle(Aastra_get_label('One Match',$LANGUAGE));
			else $object->setTitle(sprintf(Aastra_get_label('%s Matches',$LANGUAGE),$hits));
			if($ARRAY_USER['display']=='firstlast') $object->setNameDisplayFormat(0);
			else $object->setNameDisplayFormat(1);
			if($ARRAY_USER['sort']=='first') $object->natsortByFirstname(); 
			else $object->natsortByLastname();
			if(Aastra_number_softkeys_supported()==10) $object->setBackKeyPosition(9);
			$object->setBackURI($XML_SERVER.'&lastname='.$lastname.'&firstname='.$firstname.'&lookup='.$lookup);
			$object->setEntries($result);
			}
		break;
	
	# INIT
	case 'input':
	default:
		# InputScreen
		require_once('AastraIPPhoneInputScreen.class.php');
		$object = new AastraIPPhoneInputScreen();
		$object->setDestroyOnExit();
       	if($ARRAY_CONFIG[$source]['label']=='') $object->setTitle(Aastra_get_label('Directory Lookup',$LANGUAGE));
		else $object->setTitle($ARRAY_CONFIG[$source]['label']);
      		$object->setURL($XML_SERVER.'&action=search');

		# Depending on the phone
		if(Aastra_is_multipleinputfields_supported()) 
			{
			# Multiple fields supported
			$object->setDisplayMode('condensed');
			if(!empty($lookup)) $object->setDefaultIndex('4');
			else
				{
				if (!empty($lastname)) $object->setDefaultIndex('2');
				else if (!empty($firstname))$object->setDefaultIndex('3');
				}

			# Fields
	   		$object->addField('empty');
	    		$object->addField('string');
	   		$object->setFieldPrompt(Aastra_get_label('Last Name:',$LANGUAGE));
	    		$object->setFieldParameter('lastname');
			$object->setFieldDefault($lastname);
	   	 	$object->addField('string');
	   		$object->setFieldPrompt(Aastra_get_label('First Name:',$LANGUAGE));
	    		$object->setFieldParameter('firstname');
			$object->setFieldDefault($firstname);
	    		$object->addField('string');
	   		$object->setFieldPrompt(Aastra_get_label('Or Anywhere:',$LANGUAGE));
	    		$object->setFieldParameter('lookup');
			$object->setFieldDefault($lookup);
			}
		else 
			{
			# Single field
			$object->setPrompt(Aastra_get_label('Last/Firstname?',$LANGUAGE));
			$object->setParameter('lookup');
			$object->setType('string');
			$object->setDefault($lookup);
			}

		# Softkeys
		if($nb_softkeys)
			{
			if($nb_softkeys==6)
				{
		      		$object->addSoftkey('1',Aastra_get_label('Backspace',$LANGUAGE),'SoftKey:BackSpace');
				$object->addSoftkey('2',Aastra_get_label('Reset',$LANGUAGE),$XML_SERVER.'&action=input');
				$object->addSoftkey('3',Aastra_get_label('ABC',$LANGUAGE),'SoftKey:ChangeMode');
				$object->addSoftkey('4',Aastra_get_label('Settings',$LANGUAGE),$XML_SERVER.'&action=settings');
				$object->addSoftkey('5',Aastra_get_label('Lookup',$LANGUAGE),'SoftKey:Submit');
				$object->addSoftkey('6',Aastra_get_label('Exit',$LANGUAGE),'SoftKey:Exit');
				}
			else
				{
				$object->addSoftkey('1',Aastra_get_label('Reset',$LANGUAGE),$XML_SERVER.'&action=input',1);
				$object->addSoftkey('5',Aastra_get_label('Settings',$LANGUAGE),$XML_SERVER.'&action=settings',2);
				$object->addSoftkey('6',Aastra_get_label('Search',$LANGUAGE),'SoftKey:Submit',5);
				if($back=='1')$object->addSoftkey('9',Aastra_get_label('Back',$LANGUAGE),$XML_SERVER_PATH.'directory.php?user='.$user,3);
				$object->addSoftkey('10',Aastra_get_label('Exit',$LANGUAGE),'SoftKey:Exit',4);
				$object->addIcon(1,'Icon:Delete');
				$object->addIcon(2,'Icon:Settings');
				$object->addIcon(3,'Icon:ArrowLeft');
				$object->addIcon(4,'Icon:CircleRed');
				$object->addIcon(5,'Icon:Search');
				}
			}
		break;
	}

# Display XML object
$object->output();
exit;
?>