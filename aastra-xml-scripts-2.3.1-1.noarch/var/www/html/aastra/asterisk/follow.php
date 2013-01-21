<?php
#############################################################################
# Asterisk Find-me Follow-me
#
# Aastra SIP Phones 1.4.2 or better
#
# Copyright 2005-2010 Aastra Telecom Ltd
#
# Supported Aastra Phones
#   All phones 
#	Softkey phones full features
#      Non Softkey Phones only activate/deactivate
#
# Usage
# 	script.php?user=USER
# 	USER is the extension of the phone on the Asterisk platform
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
require_once('AastraCommon.php');
require_once('AastraAsterisk.php');

#############################################################################
# Body
#############################################################################
# Retrieve parameters
$user=Aastra_getvar_safe('user');
$action=Aastra_getvar_safe('action');
$value=Aastra_getvar_safe('value');
$selection=Aastra_getvar_safe('selection');
$page=Aastra_getvar_safe('page','1');
$type=Aastra_getvar_safe('type');

# Maximum number of phones in the follow-me list
$MAX_NUMBERS=10;

# Trace
Aastra_trace_call('follow_asterisk','user='.$user.', action='.$action.', value='.$value.', selection='.$selection);

# Test User Agent
Aastra_test_phone_versions(array('1'=>'1.4.2.','2'=>'1.4.2.','3'=>'2.5.3.','4'=>'2.5.3.','5'=>'3.0.1.'),'0');

# Get global compatibility
$nb_softkeys=Aastra_number_softkeys_supported();
$is_icons=Aastra_is_icons_supported();
$is_style_textmenu=Aastra_is_style_textmenu_supported();

# Initial call
if($action=='')
	{
	if($nb_softkeys) $action='main';
	else $action='change_status2';
	}

# Get Language
$language=Aastra_get_language();

# Keep return URI
$XML_SERVER.='?user='.$user;

# Compute MaxLines
if($nb_softkeys)$MaxLines=AASTRA_MAXLINES;
else $MaxLines=AASTRA_MAXLINES-2;

# Pre-process action
switch($action)
	{
	# Modify or clear personal number
	case 'clear_info':
	case 'set_info':
		# Retrieve stored data
		$array_user=Aastra_manage_userinfo_Asterisk($user,'get');

		# Update data
		if($action=='set_info') $array_user[$type]=$value;
		else
			{
			$type=$selection;
			$array_user[$type]='';
			}
		Aastra_manage_userinfo_Asterisk($user,'set',$array_user);

		# Next action
		$action='info';
		break;
	}

# Depending on action
switch($action)
	{
	# Periodic update
	case 'check':
	case 'register':
		# Update needed
		$update=1;

		# Get status
		$fm=Aastra_manage_followme_Asterisk($user,'get_status');
		if($fm=='2') $fm='0';

		# Get last FOLLOW-ME status
		$data=Aastra_get_user_context($user,'follow');
		$last=$data['last'];
		$key=$data['key'];

		# Save FM status
		$data['last']=$fm;
		if($fm!=$last) Aastra_save_user_context($user,'follow',$data);

		# Update needed?
		if(($action=='check') and ($fm==$last)) $update=0;
		if(($action=='register') and ($fm=='0')) $update=0;

		# Prepare display update
		require_once('AastraIPPhoneExecute.class.php');
		$object=new AastraIPPhoneExecute();
		if($update==1)
			{
			$object->addEntry($XML_SERVER.'&action=msg&value='.$fm);
			if($key!='')
				{
				if(Aastra_is_ledcontrol_supported())
					{
					if($fm=='0')$object->AddEntry('Led: '.$key.'=off');
					else $object->AddEntry('Led: '.$key.'=on');
					}
				}
			}
		else
			{
			# Do nothing
			$object->addEntry('');
			}
		break;

	# Modify current status
	case 'change_status':
	case 'change_status2':
		# Toggle status
		$fm=Aastra_manage_followme_Asterisk($user,'change_status');

		# Prepare answer
		require_once('AastraIPPhoneExecute.class.php');
		$object=new AastraIPPhoneExecute();

		# Update LED + idle screen
		if($fm=='2') $fm='0';
		$data=Aastra_get_user_context($user,'follow');
		$last=$data['last'];
		$key=$data['key'];
		$data['last']=$fm;
		if($fm!=$last)
			{
			Aastra_save_user_context($user,'follow',$data);
			$object->setBeep();
			$object->addEntry($XML_SERVER.'&action=msg&value='.$fm);
			if($key!='')
				{
				if(Aastra_is_ledcontrol_supported())
					{
					if($fm=='0')$object->AddEntry('Led: '.$key.'=off');
					else $object->AddEntry('Led: '.$key.'=on');
					}
				}
			}

		# Send a SIP Notification if mode is device and user
		if((!$AA_FREEPBX_USEDEVSTATE) and ($AA_FREEPBX_MODE=='2')) Aastra_propagate_changes_Asterisk($user,Aastra_get_userdevice_Asterisk($user),array('follow'));

		# Back to the display
		if($action=='change_status') $object->addEntry($XML_SERVER);
		else $object->addEntry('');
		break;

	# Modify confirmation
	case 'change_grpconf':
		# Get Current value
		$fm=Aastra_manage_followme_Asterisk($user,'get_all');

		# Set new Value
		if($fm['grpconf']) $update=False;
		else $update=True;
		Aastra_manage_followme_Asterisk($user,'set_grpconf',$update);

		# Update status
		require_once('AastraIPPhoneExecute.class.php');
		$object=new AastraIPPhoneExecute();
		$object->AddEntry($XML_SERVER);
		break;

	# Change prering
	case 'change_prering':
		# Get Current value
		$fm=Aastra_manage_followme_Asterisk($user,'get_all');

		# Input screen
		require_once('AastraIPPhoneInputScreen.class.php');
		$object=new AastraIPPhoneInputScreen();
		$object->setDestroyOnExit();
		$object->setTitle(Aastra_get_label('Initial Ring Time',$language));
	     	$object->setPrompt(Aastra_get_label('Enter time (0 to 60s)',$language));
		$object->setParameter('value');
		$object->setType('number');
		$object->setURL($XML_SERVER.'&action=set_prering');
		$object->setDefault($fm['prering']);

		# Softkeys
		if($nb_softkeys)
			{
			if($nb_softkeys==6)
				{
				$object->addSoftkey('1',Aastra_get_label('Backspace',$language),'SoftKey:BackSpace');
				$object->addSoftkey('3',Aastra_get_label('Help',$language),$XML_SERVER.'&action=help_prering');
				$object->addSoftkey('5',Aastra_get_label('Cancel',$language),$XML_SERVER);
				$object->addSoftkey('6',Aastra_get_label('Submit',$language),'SoftKey:Submit');
				}
			else
				{
				$object->addSoftkey('1',Aastra_get_label('Help',$language),$XML_SERVER.'&action=help_prering');
				$object->addSoftkey('10',Aastra_get_label('Cancel',$language),$XML_SERVER);
				$object->setCancelAction($XML_SERVER);
				}
			}
		break;

	# Set prering
	case 'set_prering':
		# Check value
		if(($value<0) or ($value>60) or (!is_numeric($value)))
			{
			# Back to the input screen
			require_once('AastraIPPhoneExecute.class.php');
			$object=new AastraIPPhoneExecute();
			$object->setBeep();
			$object->AddEntry($XML_SERVER.'&action=change_prering');
			}
		else
			{
			# Update value
			Aastra_manage_followme_Asterisk($user,'set_prering',$value);
		
			# Back to the screen
			require_once('AastraIPPhoneExecute.class.php');
			$object=new AastraIPPhoneExecute();
			$object->AddEntry($XML_SERVER);
			}
		break;

	# Help on prering
	case 'help_prering':
		# Display help
		require_once('AastraIPPhoneTextScreen.class.php');
		$object=new AastraIPPhoneTextScreen();
		$object->setDestroyOnExit();
		$object->setTitle(Aastra_get_label('Initial Ring Time',$language));
		$object->setText(Aastra_get_label('This is the number of seconds to ring the primary extension prior to proceeding to the follow-me list. The extension can also be included in the follow-me list. A value of 0 will bypass this.',$language));

		# Softkeys
		if($nb_softkeys)
			{
			if($nb_softkeys==6)
				{
				$object->addSoftkey('5',Aastra_get_label('Back',$language), $XML_SERVER.'&action=change_prering');
				$object->addSoftkey('6',Aastra_get_label('Exit',$language), 'SoftKey:Exit');
				}
			else
				{
				$object->addSoftkey('9',Aastra_get_label('Back',$language), $XML_SERVER.'&action=change_prering');
				$object->addSoftkey('10',Aastra_get_label('Exit',$language), 'SoftKey:Exit');
				$object->setCancelAction($XML_SERVER.'&action=change_prering');
				}
			}
		break;

	# Change grptime
	case 'change_grptime':
		# Get Current value
		$fm=Aastra_manage_followme_Asterisk($user,'get_all');

		# Input screen
		require_once('AastraIPPhoneInputScreen.class.php');
		$object=new AastraIPPhoneInputScreen();
		$object->setDestroyOnExit();
		$object->setTitle(Aastra_get_label('Ring Time',$language));
	     	$object->setPrompt(Aastra_get_label('Enter time (1 to 60s)',$language));
		$object->setParameter('value');
		$object->setType('number');
		$object->setURL($XML_SERVER.'&action=set_grptime');
		$object->setDefault($fm['grptime']);

		# Softkeys
		if($nb_softkeys)
			{
			if($nb_softkeys==6)
				{
				$object->addSoftkey('1',Aastra_get_label('Backspace',$language),'SoftKey:BackSpace');
				$object->addSoftkey('3',Aastra_get_label('Help',$language),$XML_SERVER.'&action=help_grptime');
				$object->addSoftkey('5',Aastra_get_label('Cancel',$language),$XML_SERVER);
				$object->addSoftkey('6',Aastra_get_label('Submit',$language),'SoftKey:Submit');
				}
			else
				{
				$object->addSoftkey('1',Aastra_get_label('Help',$language),$XML_SERVER.'&action=help_grptime');
				$object->addSoftkey('10',Aastra_get_label('Cancel',$language),$XML_SERVER);
				$object->setCancelAction($XML_SERVER);
				}
			}
		break;

	# Set grptime
	case 'set_grptime':
		# Check value
		if(($value<1) or ($value>60) or (!is_numeric($value)))
			{
			# Back to the input screen
			require_once('AastraIPPhoneExecute.class.php');
			$object=new AastraIPPhoneExecute();
			$object->setBeep();
			$object->AddEntry($XML_SERVER.'&action=change_grptime');
			}
		else
			{
			# Update value
			Aastra_manage_followme_Asterisk($user,'set_grptime',$value);
		
			# Back to the screen
			require_once('AastraIPPhoneExecute.class.php');
			$object=new AastraIPPhoneExecute();
			$object->AddEntry($XML_SERVER);
			}
		break;

	# Help on grptime
	case 'help_grptime':
		# Display help
		require_once('AastraIPPhoneTextScreen.class.php');
		$object=new AastraIPPhoneTextScreen();
		$object->setDestroyOnExit();
		$object->setTitle(Aastra_get_label('Ring Time',$language));
		$object->setText(Aastra_get_label('Time in seconds that the phones will ring. For all hunt style ring strategies, this is the time for each iteration of phone(s) that are rung.',$language));

		# Softkeys
		if($nb_softkeys)
			{
			if($nb_softkeys==6)
				{
				$object->addSoftkey('5',Aastra_get_label('Back',$language), $XML_SERVER.'&action=change_grptime');
				$object->addSoftkey('6',Aastra_get_label('Exit',$language), 'SoftKey:Exit');
				}
			else
				{
				$object->addSoftkey('9',Aastra_get_label('Back',$language), $XML_SERVER.'&action=change_grptime');
				$object->addSoftkey('10',Aastra_get_label('Exit',$language), 'SoftKey:Exit');
				$object->setCancelAction($XML_SERVER.'&action=change_grptime');
				}
			}
		break;

	# Change phone numbers
	case 'change_grplist':
		# Get Current value
		$fm=Aastra_manage_followme_Asterisk($user,'get_all');

		# Retrieve personal numbers
		$array_user=Aastra_manage_userinfo_Asterisk($user,'get');
		$array_user=array_flip($array_user);
		foreach($fm['grplist'] as $i=>$number)
			{
			if($i<$MAX_NUMBERS)
				{
				if(substr($number,-1)=='#') unset($array_user[substr($number,0,-1)]);
				}
			}
		$array_user=array_flip($array_user);
		$addext_grplist='addext_grplist';
		foreach($array_user as $key=>$value)
			{
			if($value!='') $addext_grplist='select_info';
			break;
			}
		$array_user=Aastra_manage_userinfo_Asterisk($user,'get');
		$array_user=array_flip($array_user);
		$array_label=array(	'cell'=>Aastra_get_label('Cell',$language),
					'home'=>Aastra_get_label('Home',$language),
					'other'=>Aastra_get_label('Other',$language)
					);

		# Depending on the number of numbers
		if(count($fm['grplist'])>0)
			{
			# Display list
			require_once('AastraIPPhoneTextMenu.class.php');
			$object=new AastraIPPhoneTextMenu();
			$object->setDestroyOnExit();
			$object->setTitle(Aastra_get_label('Follow-me List',$language));
			if(Aastra_is_textmenu_wrapitem_supported()) $object->setWrapList();
			if($selection!='') $object->setDefaultIndex($selection+1);
			foreach($fm['grplist'] as $i=>$number)
				{
				if($i<$MAX_NUMBERS)
					{
					if($nb_softkeys!=10) $select_action=$XML_SERVER.'&action=delete_grplist&value='.$i;
					else $select_action=NULL;
					if(substr($number,-1)=='#') 
						{
						if($array_user[substr($number,0,-1)]=='') $object->addEntry(sprintf(Aastra_get_label('(E) %s',$language),substr($number,0,-1)),$select_action,$i);
						else $object->addEntry(sprintf(Aastra_get_label('(E) %s (%s)',$language),$array_label[$array_user[substr($number,0,-1)]],substr($number,0,-1)),$select_action,$i);
						}
					else 
						{
						if($number!=Aastra_get_userdevice_Asterisk($user)) $object->addEntry(sprintf(Aastra_get_label('(I) %s (%s)',$language),Aastra_get_callerid_Asterisk($number),$number),$select_action,$i);
						else $object->addEntry(sprintf(Aastra_get_label('This Phone (%s)',$language),$number),$select_action,$i);
						}
					}
				}

			# Softkeys
			if($nb_softkeys==6)
				{
				if(count($fm['grplist'])<$MAX_NUMBERS) $object->addSoftkey('1',Aastra_get_label('Add Int.',$language), $XML_SERVER.'&action=addint_grplist');
				if(count($fm['grplist'])>1) $object->addSoftkey('2',Aastra_get_label('Move Down',$language), $XML_SERVER.'&action=down_grplist');
				if(count($fm['grplist'])>1) $object->addSoftkey('3',Aastra_get_label('Delete',$language), 'SoftKey:Select');
				if(count($fm['grplist'])<$MAX_NUMBERS) $object->addSoftkey('4',Aastra_get_label('Add Ext.',$language), $XML_SERVER.'&action='.$addext_grplist);
				if(count($fm['grplist'])>1) $object->addSoftkey('5',Aastra_get_label('Move Up',$language), $XML_SERVER.'&action=up_grplist');
				$object->addSoftkey('6',Aastra_get_label('Back',$language), $XML_SERVER);
				}
			else
				{
				if(count($fm['grplist'])<$MAX_NUMBERS) $object->addSoftkey('1',Aastra_get_label('Add Internal',$language), $XML_SERVER.'&action=addint_grplist');
				if(count($fm['grplist'])>1) $object->addSoftkey('3',Aastra_get_label('Move Down',$language), $XML_SERVER.'&action=down_grplist');
				if(count($fm['grplist'])>1) $object->addSoftkey('5',Aastra_get_label('Delete',$language),$XML_SERVER.'&action=delete_grplist');
				if(count($fm['grplist'])<$MAX_NUMBERS) $object->addSoftkey('6',Aastra_get_label('Add External',$language), $XML_SERVER.'&action='.$addext_grplist);
				if(count($fm['grplist'])>1) $object->addSoftkey('8',Aastra_get_label('Move Up',$language), $XML_SERVER.'&action=up_grplist');
				$object->addSoftkey('10',Aastra_get_label('Back',$language), $XML_SERVER);
				$object->setCancelAction($XML_SERVER);
				}
			}
		else
			{
			# Display Error message
			require_once('AastraIPPhoneTextScreen.class.php');
			$object=new AastraIPPhoneTextScreen();
			$object->setDestroyOnExit();
			$object->setTitle(Aastra_get_label('Phone numbers',$language));
			$object->setText(Aastra_get_label('No phone number configured.',$language));

			# Softkeys
			if($nb_softkeys==6)
				{
				$object->addSoftkey('1',Aastra_get_label('Add Int.',$language), $XML_SERVER.'&action=addint_grplist');
				$object->addSoftkey('4',Aastra_get_label('Add Ext.',$language), $XML_SERVER.'&action='.$addext_grplist);
				$object->addSoftkey('6',Aastra_get_label('Back',$language), $XML_SERVER);
				}
			else
				{
				$object->addSoftkey('1',Aastra_get_label('Add Int.',$language), $XML_SERVER.'&action=addint_grplist');
				$object->addSoftkey('6',Aastra_get_label('Add Ext.',$language), $XML_SERVER.'&action='.$addext_grplist);
				$object->addSoftkey('10',Aastra_get_label('Back',$language), $XML_SERVER);
				$object->setCancelAction($XML_SERVER);
				}
			}
		break;

	# Delete a phone number
	case 'delete_grplist':
		# Update needed
		$update=True;
		if($value=='') $value=$selection;

		# Get Current value
		$fm=Aastra_manage_followme_Asterisk($user,'get_all');

		# Check condidions
		if(count($fm['grplist'])==1) $update=False;

		# Delete the element
		if($update)
			{
			unset($fm['grplist'][$value]);
			Aastra_manage_followme_Asterisk($user,'set_grplist',$fm['grplist']);
			}

		# Back to the screen
		require_once('AastraIPPhoneExecute.class.php');
		$object=new AastraIPPhoneExecute();
		if(!$update) $object->setBeep();
		$object->AddEntry($XML_SERVER.'&action=change_grplist&selection='.$selection);
		break;

	# Add external number
	case 'addext_grplist2':
	case 'addext_grplist':
		# Input screen
		require_once('AastraIPPhoneInputScreen.class.php');
		$object=new AastraIPPhoneInputScreen();
		$object->setDestroyOnExit();
		$object->setTitle(Aastra_get_label('External number',$language));
	     	$object->setPrompt(Aastra_get_label('Enter phone number',$language));
		$object->setParameter('value');
		$object->setType('number');
		$object->setURL($XML_SERVER.'&action=setext_grplist');

		# Softkeys
		if($nb_softkeys==6)
			{
			$object->addSoftkey('1',Aastra_get_label('Backspace',$language),'SoftKey:BackSpace');
			if($action=='addext_grplist') $object->addSoftkey('5',Aastra_get_label('Cancel',$language),$XML_SERVER.'&action=change_grplist');
			else $object->addSoftkey('5',Aastra_get_label('Cancel',$language),$XML_SERVER.'&action=select_info');
			$object->addSoftkey('6',Aastra_get_label('Submit',$language),'SoftKey:Submit');
			}
		else
			{
			if($action=='addext_grplist') 
				{
				$object->addSoftkey('10',Aastra_get_label('Cancel',$language),$XML_SERVER.'&action=change_grplist');
				$object->setCancelAction($XML_SERVER.'&action=change_grplist');
				}
			else 
				{
				$object->addSoftkey('10',Aastra_get_label('Cancel',$language),$XML_SERVER.'&action=select_info');
				$object->setCancelAction($XML_SERVER.'&action=select_info');
				}
			}
		break;

	# Add Internal number
	case 'addint_grplist':
		# Retrieve directory
		$directory=Aastra_get_user_directory_Asterisk();

		# Purge existing numbers
		$fm=Aastra_manage_followme_Asterisk($user,'get_all');
		foreach($directory as $key=>$v)
			{
			if(in_array($v['number'],$fm['grplist'])) unset($directory[$key]);
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

			# Display Page
			require_once('AastraIPPhoneTextMenu.class.php');
			$object=new AastraIPPhoneTextMenu();
			$object->setDestroyOnExit();
			if(Aastra_is_style_textmenu_supported()) $object->setStyle('none');
			if(Aastra_is_textmenu_wrapitem_supported()) $object->setWrapList();
			if($last!=1) $object->setTitle(sprintf(Aastra_get_label('Directory (%s/%s)',$language),$page,$last));
			else $object->setTitle(Aastra_get_label('Directory',$language));
			if(!$nb_softkeys)
				{
				if($page!=1) $object->addEntry(Aastra_get_label('Previous Page',$language),$XML_SERVER.'&action=addint_grplist&page='.($page-1));
				}
			$index=1;
			foreach($directory as $v) 
				{
				if(($index>=(($page-1)*$MaxLines+1)) and ($index<=$page*$MaxLines)) 
					{
					if($v['number']!=$user) $object->addEntry(sprintf('%s (%s)',$v['name'],$v['number']),$XML_SERVER.'&action=setint_grplist&value='.$v['number']);
					else $object->addEntry(sprintf(Aastra_get_label('This Phone (%s)',$language),$user),$XML_SERVER.'&action=setint_grplist&value='.$v['number']);
					}
				$index++;
				}

			if(!$nb_softkeys)
				{
				if($page!=$last) $object->addEntry(Aastra_get_label('Next Page',$language),$XML_SERVER.'&action=addint_grplist&page='.($page+1));
				}
			else
				{
				if($nb_softkeys==6)
					{
					$object->addSoftkey('1',Aastra_get_label('Select',$language),'SoftKey:Select');
					if($page!=1) $object->addSoftkey('2',Aastra_get_label('Previous',$language),$XML_SERVER.'&action=addint_grplist&page='.($page-1));
					if($page!=$last) $object->addSoftkey('5',Aastra_get_label('Next',$language),$XML_SERVER.'&action=addint_grplist&page='.($page+1));
					$object->addSoftkey('6',Aastra_get_label('Back',$language),$XML_SERVER.'&action=change_grplist');
					}
				else
					{
					if($page!=1) $object->addSoftkey('3',Aastra_get_label('Previous Page',$language),$XML_SERVER.'&action=addint_grplist&page='.($page-1));
					if($page!=$last) $object->addSoftkey('8',Aastra_get_label('Next Page',$language),$XML_SERVER.'&action=addint_grplist&page='.($page+1));
					$object->addSoftkey('10',Aastra_get_label('Back',$language),$XML_SERVER.'&action=change_grplist');
					$object->setCancelAction($XML_SERVER.'&action=change_grplist');
					}
				}
			}
		else
			{
			# Display error
			require_once('AastraIPPhoneTextScreen.class.php');
			$object=new AastraIPPhoneTextScreen();
			$object->setDestroyOnExit();
			$object->setTitle(Aastra_get_label('Empty Directory',$language));
			$object->setText(Aastra_get_label('No more available internal numbers.',$language));
			if($nb_softkeys)
				{
				if($nb_softkeys==6) $object->addSoftkey('6',Aastra_get_label('Back',$language),$XML_SERVER.'&action=change_grplist&selection='.$selection);
				else $object->addSoftkey('10',Aastra_get_label('Back',$language),$XML_SERVER.'&action=change_grplist&selection='.$selection);
				}
			}
		break;

	# Add External number
	case 'setext_grplist':
		# Back to the input screen
		require_once('AastraIPPhoneExecute.class.php');
		$object=new AastraIPPhoneExecute();

		# Check value
		if(!is_numeric($value))
			{
			$object->setBeep();
			$object->AddEntry($XML_SERVER.'&action=addext_grplist');
			}
		else
			{
			# Get Current value
			$fm=Aastra_manage_followme_Asterisk($user,'get_all');

			# Add the element
			$fm['grplist'][]=$value.'#';
			Aastra_manage_followme_Asterisk($user,'set_grplist',$fm['grplist']);

			# Back to the list
			$object->AddEntry($XML_SERVER.'&action=change_grplist&selection='.(count($fm['grplist'])-1));
			}
		break;

	# Add Internal number
	case 'setint_grplist':
		# Get Current value
		$fm=Aastra_manage_followme_Asterisk($user,'get_all');

		# Add the element
		$fm['grplist'][]=$value;
		Aastra_manage_followme_Asterisk($user,'set_grplist',$fm['grplist']);

		# Back to the list
		require_once('AastraIPPhoneExecute.class.php');
		$object=new AastraIPPhoneExecute();
		$object->AddEntry($XML_SERVER.'&action=change_grplist&selection='.(count($fm['grplist'])-1));
		break;


	# Move up
	case 'up_grplist':
		# Back to the input screen
		require_once('AastraIPPhoneExecute.class.php');
		$object=new AastraIPPhoneExecute();
		$object->AddEntry($XML_SERVER.'&action=change_grplist&selection='.($selection-1));

		# Check index
		if($selection=='0') $object->setBeep();
		else
			{
			# Get Current value
			$fm=Aastra_manage_followme_Asterisk($user,'get_all');

			# Move up the element
			$temp=$fm['grplist'][$selection-1];
			$fm['grplist'][$selection-1]=$fm['grplist'][$selection];
			$fm['grplist'][$selection]=$temp;
			Aastra_manage_followme_Asterisk($user,'set_grplist',$fm['grplist']);
			}
		break;

	# Move Down
	case 'down_grplist':
		# Back to the input screen
		require_once('AastraIPPhoneExecute.class.php');
		$object=new AastraIPPhoneExecute();
		$object->AddEntry($XML_SERVER.'&action=change_grplist&selection='.($selection+1));

		# Get Current value
		$fm=Aastra_manage_followme_Asterisk($user,'get_all');

		# Check index
		if($selection==(count($fm['grplist'])-1)) $object->setBeep();
		else
			{
			# Move down the element
			$temp=$fm['grplist'][$selection];
			$fm['grplist'][$selection]=$fm['grplist'][$selection+1];
			$fm['grplist'][$selection+1]=$temp;
			Aastra_manage_followme_Asterisk($user,'set_grplist',$fm['grplist']);
			}
		break;

	# Update idle screen message status
	case 'msg':
		# update screen message
		require_once('AastraIPPhoneStatus.class.php');
		$object=new AastraIPPhoneStatus();
		$object->setSession('aastra-xml');
		$index=Aastra_get_status_index_Asterisk('follow');
		if($value!='1') $object->addEntry($index,'');
		else 
			{
			if(Aastra_is_status_uri_supported())
				{
				$object->addEntry($index,Aastra_get_label('Follow-me activated',$language),'',NULL,$XML_SERVER.'&action=change_status2',1);
				$object->addIcon('1','Icon:World');
				}
			else $object->addEntry($index,Aastra_get_label('Follow-me activated',$language));
			}
		break;

	# Select way to add external number
	case 'select_info':
		# Get Current value
		$fm=Aastra_manage_followme_Asterisk($user,'get_all');

		# Retrieve personal numbers
		$array_user=Aastra_manage_userinfo_Asterisk($user,'get');
		$array_user=array_flip($array_user);
		foreach($fm['grplist'] as $i=>$number)
			{
			if($i<$MAX_NUMBERS)
				{
				if(substr($number,-1)=='#') unset($array_user[substr($number,0,-1)]);
				}
			}
		$array_user=array_flip($array_user);
		
		# All indexes
		$array_index=array(	'cell'=>array('1',Aastra_get_label('(M)',$language),'1'),
					'home'=>array('2',Aastra_get_label('(H)',$language),'2'),
					'other'=>array('3',Aastra_get_label('(O)',$language),'3')
					);

		# Personal phone numbers
		require_once('AastraIPPhoneTextMenu.class.php');
		$object=new AastraIPPhoneTextMenu();
		$object->setDestroyOnExit();
		if($is_style_textmenu) $object->setStyle('radio');
		$object->setTitle(Aastra_get_label('Select Destination',$language));
		
		# Numbers
		foreach($array_index as $key=>$value)
			{
			if($array_user[$key]!='') 
				{
				$label=$array_user[$key];
				if($is_icons) $icon=$value[0];
				else 
					{
					$icon='';
					$label=$value[1].' '.$label;
					}
				$object->addEntry($label,$XML_SERVER.'&action=setext_grplist&value='.$array_user[$key],'',$icon);
				}
			}

		# Manuel entry
		if($is_icons) $icon='4';
		else $icon='';
		$object->addEntry('Enter Number',$XML_SERVER.'&action=addext_grplist2','',$icon);

		# Icons
		if($is_icons)
			{
			if(Aastra_phone_type()!=5)
				{
				$object->addIcon(1,Aastra_get_custom_icon('Cellphone'));
				$object->addIcon(2,Aastra_get_custom_icon('Home'));
				$object->addIcon(3,Aastra_get_custom_icon('Phone'));
				$object->addIcon(4,Aastra_get_custom_icon('Keypad'));
				}
			else
				{
				$object->addIcon(1,'Icon:CellPhone');
				$object->addIcon(2,'Icon:Home');
				$object->addIcon(3,'Icon:PhoneOnHook');
				$object->addIcon(4,'Icon:PhoneDial');
				}
			}

		# Softkeys
		if($nb_softkeys)
			{
			if($nb_softkeys==6)
				{
				$object->addSoftkey('1',Aastra_get_label('Select',$language), 'SoftKey:Select');
				$object->addSoftkey('5',Aastra_get_label('Cancel',$language), $XML_SERVER.'&action=change_grplist');
				$object->addSoftkey('6',Aastra_get_label('Exit',$language), 'SoftKey:Exit');
				}
			else
				{
				$object->addSoftkey('9',Aastra_get_label('Cancel',$language), $XML_SERVER.'&action=change_grplist');
				$object->addSoftkey('10',Aastra_get_label('Exit',$language), 'SoftKey:Exit');
				$object->setCancelAction($XML_SERVER.'&action=change_grplist');
				}
			}
		break;

	# Edit personal numbers
	case 'edit_info':
		# Retrieve stored data
		$array_user=Aastra_manage_userinfo_Asterisk($user,'get');

		# Various labels
		$array_type=array(	'cell'=>array('Cell Phone','Cell phone number'),
					'home'=>array('Home Phone','Home phone number'),
					'other'=>array('Other Phone','Other phone number')
					);

		# Input new call forward
		require_once('AastraIPPhoneInputScreen.class.php');
		$object=new AastraIPPhoneInputScreen();
		$object->setDestroyOnExit();
		$object->setTitle($array_type[$type][0]);
		$object->setPrompt($array_type[$type][1]);
		$object->setParameter('value');
		$object->setType('number');
		$object->setURL($XML_SERVER.'&action=set_info&type='.$type);
		$object->setDefault($array_user[$type]);

		# Softkeys 
		if($nb_softkeys)
			{
			if($nb_softkeys==6)
				{
				$object->addSoftkey('1',Aastra_get_label('Backspace',$language),'SoftKey:BackSpace');
				$object->addSoftkey('5',Aastra_get_label('Cancel',$language),$XML_SERVER.'&action=info&type='.$type);
				$object->addSoftkey('6',Aastra_get_label('Submit',$language),'SoftKey:Submit');
				}
			else
				{
				$object->addSoftkey('10',Aastra_get_label('Cancel',$language),$XML_SERVER.'&action=info&type='.$type);
				$object->setCancelAction($XML_SERVER.'&action=info&type='.$type);
				}
			}
		break;

	# User information
	case 'info':
		# Retrieve stored data
		$array_user=Aastra_manage_userinfo_Asterisk($user,'get');

		# All indexes
		$array_index=array(	'cell'=>array('1',Aastra_get_label('(M)',$language),'1'),
					'home'=>array('2',Aastra_get_label('(H)',$language),'2'),
					'other'=>array('3',Aastra_get_label('(O)',$language),'3')
					);

		# Personal phone numbers
		require_once('AastraIPPhoneTextMenu.class.php');
		$object=new AastraIPPhoneTextMenu();
		$object->setDestroyOnExit();
		if($is_style_textmenu) $object->setStyle('none');
		$object->setTitle(Aastra_get_label('Personal Numbers',$language));
		if($type!='') $object->setDefaultIndex($array_index[$type][2]);
		
		# Numbers
		foreach($array_index as $key=>$value)
			{
			if($array_user[$key]!='') $label=$array_user[$key];
			else $label='.....................';
			if($is_icons) $icon=$value[0];
			else 
				{
				$icon='';
				$label=$value[1].' '.$label;
				}
			$object->addEntry($label,$XML_SERVER.'&action=edit_info&type='.$key,$key,$icon);
			}

		# Icons
		if($is_icons)
			{
			if(Aastra_phone_type()!=5)
				{
				$object->addIcon(1,Aastra_get_custom_icon('Cellphone'));
				$object->addIcon(2,Aastra_get_custom_icon('Home'));
				$object->addIcon(3,Aastra_get_custom_icon('Phone'));
				}
			else
				{
				$object->addIcon(1,'Icon:CellPhone');
				$object->addIcon(2,'Icon:Home');
				$object->addIcon(3,'Icon:PhoneOnHook');
				}
			}

		# Softkeys
		if($nb_softkeys)
			{
			if($nb_softkeys==6)
				{
				$object->addSoftkey('1',Aastra_get_label('Edit',$language), 'SoftKey:Select');
				$object->addSoftkey('2',Aastra_get_label('Clear',$language), $XML_SERVER.'&action=clear_info');
				$object->addSoftkey('5',Aastra_get_label('Back',$language), $XML_SERVER);
				$object->addSoftkey('6',Aastra_get_label('Exit',$language), 'SoftKey:Exit');
				}
			else
				{
				$object->addSoftkey('1',Aastra_get_label('Clear',$language), $XML_SERVER.'&action=clear_info');
				$object->addSoftkey('9',Aastra_get_label('Back',$language), $XML_SERVER);
				$object->addSoftkey('10',Aastra_get_label('Exit',$language), 'SoftKey:Exit');
				$object->setCancelAction($XML_SERVER);
				}
			}
		break;

	# Default is Current status
	case 'main':
		# Authenticate user
		Aastra_check_signature_Asterisk($user);

		# Display current status
		$array_fm=Aastra_manage_followme_Asterisk($user,'get_all');

		# Check if configured
		if($array_fm['status']=='2')
			{
			# Error: Not configured
			require_once('AastraIPPhoneTextScreen.class.php');
			$object=new AastraIPPhoneTextScreen();
			$object->setDestroyOnExit();
			$object->setTitle(Aastra_get_label('Follow-me',$language));
			$object->setText(Aastra_get_label('This feature is not configured for your phone. Please contact your administrator.',$language));	
			if($nb_softkeys)
				{
				if($nb_softkeys==6) $object->addSoftkey('6',Aastra_get_label('Exit',$language), 'SoftKey:Exit');
				else $object->addSoftkey('10',Aastra_get_label('Exit',$language), 'SoftKey:Exit');
				}
			}
		else
			{
			# Configured: List of options
			require_once('AastraIPPhoneTextMenu.class.php');
			$object=new AastraIPPhoneTextMenu();
			$object->setDestroyOnExit();
			$object->setStyle('none');
			$object->setTitle(sprintf(Aastra_get_label('Follow-me for %s',$language),Aastra_get_userdevice_Asterisk($user)));
			if($array_fm['status']=='1') $object->addEntry(Aastra_get_label('Status: Activated',$language),$XML_SERVER.'&action=change_status');
			else $object->addEntry(Aastra_get_label('Status: Deactivated',$language),$XML_SERVER.'&action=change_status');
			$object->addEntry(sprintf(Aastra_get_label('Initial Ring Time: %ss',$language),$array_fm['prering']),$XML_SERVER.'&action=change_prering');
			$object->addEntry(sprintf(Aastra_get_label('Ring Time: %ss',$language),$array_fm['grptime']),$XML_SERVER.'&action=change_grptime');
			if($array_fm['grpconf']) $object->addEntry(Aastra_get_label('Confirm Calls: Yes',$language),$XML_SERVER.'&action=change_grpconf');
			else $object->addEntry(Aastra_get_label('Confirm Calls: No',$language),$XML_SERVER.'&action=change_grpconf');
			$object->addEntry(sprintf(Aastra_get_label('%s phone number(s)',$language),count($array_fm['grplist'])),$XML_SERVER.'&action=change_grplist');

			# Softkeys
			if($nb_softkeys)
				{
				if($nb_softkeys==6)
					{
					$object->addSoftkey('1',Aastra_get_label('Change',$language),'SoftKey:Select');
					$object->addSoftkey('2',Aastra_get_label('My Numbers',$language),$XML_SERVER.'&action=info');
					$object->addSoftkey('6',Aastra_get_label('Exit',$language),'SoftKey:Exit');
					}
				else
					{
					$object->addSoftkey('1',Aastra_get_label('My Numbers',$language),$XML_SERVER.'&action=info');
					$object->addSoftkey('10',Aastra_get_label('Exit',$language),'SoftKey:Exit');
					}
				}
			}
		break;

	# Default
	default:
		# Debug
		Aastra_debug('Unexpected action:'.$action);
		require_once('AastraIPPhoneExecute.class.php');
		$object=new AastraIPPhoneExecute();
		$object->addEntry('');
		break;
	}

# Display answer
$object->output();
exit;
?>
