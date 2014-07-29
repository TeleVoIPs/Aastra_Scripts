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
#    6867i with firmware 2.5.3 or better
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
$action=Aastra_getvar_safe('action','init');
$speed=Aastra_getvar_safe('speed');
$selection=Aastra_getvar_safe('selection');
$origin=Aastra_getvar_safe('origin');
$orig_s=Aastra_getvar_safe('orig_s');
$menu_set=Aastra_getvar_safe('menu_set','1');
$lookup=Aastra_getvar_safe('lookup','');

# Trace
Aastra_trace_call('directory_6_asterisk','user='.$user.', action='.$action.', speed='.$speed.', selection='.$selection);

# Test User Agent
Aastra_test_phone_versions(array('1'=>'','2'=>'','3'=>'','4'=>'2.5.3.','5'=>''),'0');

# Get Language
$language=Aastra_get_language();

# Save return URI
$XML_SERVER.='?user='.$user.'&origin='.$origin;

# Compute MaxLines
$MaxLines=AASTRA_MAXLINES;

# Initial call
if($action=='init')
	{
	# Retrieve current configuration
	$mode=Aastra_get_user_context(Aastra_get_userdevice_Asterisk($user),'pbx_directory');
	if($mode=='')
		{
		$mode='1';
		Aastra_save_user_context(Aastra_get_userdevice_Asterisk($user),'pbx_directory',$mode);
		}

	# Initial action
	if($mode=='1') $action='list';
	else $action='search';
	}

# Pre-process action
switch($action)
	{
	# Set Preferences
	case 'set_prefs':
		# Update user context
		Aastra_save_user_context(Aastra_get_userdevice_Asterisk($user),'pbx_directory',$speed);

		# Next action
		$action='list';
		break;
	}

# Process action
switch($action)
	{
	# Search the directory
	case 'search':
		# InputScreen
		require_once('AastraIPPhoneInputScreen.class.php');
		$object = new AastraIPPhoneInputScreen();
	      	$object->setTitle(Aastra_get_label('Directory Lookup',$language));
		$object->setURL($XML_SERVER.'&action=list');
	      	$object->setDestroyOnExit();
		$object->setType('string');
		$object->setPrompt(Aastra_get_label('Letters in name',$language));
		$object->setParameter('lookup');
		if($lookup!='') $object->setDefault($lookup);

		# Softkeys
		$object->addSoftkey('1',Aastra_get_label('Backspace',$language),'SoftKey:BackSpace');
		$object->addSoftkey('2','ABC','SoftKey:ChangeMode');
		$object->addSoftkey('3',Aastra_get_label('Submit',$language),'SoftKey:Submit');
		$object->addSoftkey('5',Aastra_get_label('Exit',$language),'SoftKey:Exit');
		if($origin!='presence') $object->addSoftkey('4',Aastra_get_label('Cancel',$language),$XML_SERVER.'&action=list');
		else
			{
			$object->addSoftkey('6',Aastra_get_label('List Mode',$language),$XML_SERVER.'&action=list');
			$object->addSoftkey('4',Aastra_get_label('Back',$language),$XML_SERVER_PATH.'/away.php?user='.$user);
			}
		break;

	# Preferences
	case 'prefs':
		# Retrieve current configuration
		$mode=Aastra_get_user_context(Aastra_get_userdevice_Asterisk($user),'pbx_directory');

		# Display options
		require_once('AastraIPPhoneTextMenu.class.php');
		$object=new AastraIPPhoneTextMenu();
		$object->setDestroyOnExit();
		$object->setDefaultIndex($mode);
		$object->setStyle('radio');
		$object->setTitle(Aastra_get_label('Directory Mode',$language));
		$object->addEntry(Aastra_get_label('Entire List',$language),$XML_SERVER.'&action=set_prefs&lookup='.$lookup.'&speed=1'.'&page='.$page);
		$object->addEntry(Aastra_get_label('Lookup',$language),$XML_SERVER.'&action=set_prefs&lookup='.$lookup.'&speed=2'.'&page='.$page);

		# Softkeys
		$object->addSoftkey('1',Aastra_get_label('Select',$language),'SoftKey:Select');
		$object->addSoftkey('5',Aastra_get_label('Cancel',$language),$XML_SERVER.'&action=list&lookup='.$lookup.'&page='.$page);
		$object->addSoftkey('6',Aastra_get_label('Exit',$language),'SoftKey:Exit');
		break;

	# Intercom
	case 'intercom':
		require_once('AastraIPPhoneExecute.class.php');
		$object=new AastraIPPhoneExecute();
		$object->addEntry('Dial:'.Aastra_get_intercom_config_Asterisk().$selection);
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
			$object->addEntry($name,$XML_SERVER.'&action=set&speed='.$i.'&selection='.$selection.'&page='.$page.'&orig_s='.$orig_s.'&lookup='.$lookup,'');
			}

		# Softkeys
		$object->addSoftkey('1',Aastra_get_label('Select',$language),'SoftKey:Select');
		$object->addSoftkey('4',Aastra_get_label('Cancel',$language),$XML_SERVER.'&action='.$orig_s.'&page='.$page.'&selection='.$selection.'&lookup='.$lookup);
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
		$object->addSoftkey('6',Aastra_get_label('Close',$language),$XML_SERVER.'&action='.$orig_s.'&page='.$page.'&selection='.$selection.'&lookup='.$lookup);
		break;

	# Display status
	case 'zoom':
		# Retrieve user status
		$away=Aastra_manage_presence_Asterisk($selection,'get','status');

		# Labels for status
		$status_text=Aastra_status_config_Asterisk();

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

		# Get display size
		$size=Aastra_size_display_line();

		# Use a FormattedTextScreen
		require_once('AastraIPPhoneFormattedTextScreen.class.php');
		$object=new AastraIPPhoneFormattedTextScreen();
		$object->setDestroyOnExit();
		$object->addLine(Aastra_get_callerid_Asterisk($selection).' ('.$selection.')');
		$object->setScrollStart(Aastra_size_formattedtextscreen()-1);
		$line=sprintf(Aastra_get_label('Currently %s',$language),$status_text[$away['status']]['label']);
		if(strlen($line)>$size) $line=sprintf('%s',$status_text[$away['status']]['label']);
		$object->addLine($line);
		if($away['status']!=AA_PRESENCE_AVAILABLE)
			{
			$line=Aastra_format_presence_dt_Asterisk($away['return']);
			foreach($line as $data) $object->addLine($data);
			if($notify) $object->addLine(Aastra_get_label('Notification on return',$language));
			}
		else
			{
			if($hint=='Idle') $object->addLine(Aastra_get_label('Phone is idle',$language));
			else
				{
				if($hint=='Ringing') $object->addLine(Aastra_get_label('Phone is ringing',$language));
				else $object->addLine(Aastra_get_label('Phone is in use',$language));
				}
			}
		$object->setScrollEnd();

		# Softkeys
		/* $object->addSoftKey('1',Aastra_get_label('Dial',$language),'Dial:'.$selection); */
		/* if(($away['status']!=AA_PRESENCE_AVAILABLE) and ($away['status']!=AA_PRESENCE_DISCONNECTED)) */
		/* 	{ */
		/* 	if(!$notify) $object->addSoftKey('2',Aastra_get_label('Notify me',$language),$XML_SERVER.'&action=notify&page='.$page.'&selection='.$selection.'&lookup='.$lookup); */
		/* 	else $object->addSoftKey('2',Aastra_get_label('Unnotify',$language),$XML_SERVER.'&action=unnotify&page='.$page.'&selection='.$selection.'&lookup='.$lookup); */
		/* 	} */
		/* if($AA_SPEEDDIAL_STATE) $object->addSoftkey('3',Aastra_get_label('+Speed',$language),$XML_SERVER.'&action=select&page='.$page.'&selection='.$selection.'&orig_s=zoom'.'&lookup='.$lookup); */
		/* $intercom=Aastra_get_intercom_config_Asterisk(); */
		/* if($intercom!='') $object->addSoftKey('4',Aastra_get_label('Icom',$language),'Dial:'.$intercom.$selection); */
		$object->addSoftKey('1',Aastra_get_label('Back',$language),$XML_SERVER.'&action=list&page='.$page.'&selection='.$selection.'&lookup='.$lookup);
		/* $object->addSoftKey('6',Aastra_get_label('Exit',$language),'SoftKey:Exit'); */
		break;

	# Notify
	case 'notify':
		# Display choice
		require_once('AastraIPPhoneTextMenu.class.php');
		$object=new AastraIPPhoneTextMenu();
		$object->setDestroyOnExit();
		if(Aastra_is_style_textmenu_supported()) $object->setStyle('none');
		$object->setTitle(Aastra_get_label('Notification type',$language));
		$object->addEntry(Aastra_get_label('Message',$language),$XML_SERVER.'&action=set_notifym&page='.$page.'&selection='.$selection.'&lookup='.$lookup);
		$object->addEntry(Aastra_get_label('Phone Call',$language),$XML_SERVER.'&action=set_notifyv&page='.$page.'&selection='.$selection.'&lookup='.$lookup);

		# Softkeys
		$object->addSoftkey('1',Aastra_get_label('Select',$language),'SoftKey:Select');
		$object->addSoftkey('4',Aastra_get_label('Cancel',$language),$XML_SERVER.'&action=zoom&page='.$page.'&selection='.$selection.'&lookup='.$lookup);
		$object->addSoftkey('6',Aastra_get_label('Exit',$language),'SoftKey:Exit');
		break;

	# Set Notify
	case 'set_notifym':
	case 'set_notifyv':
		# Add the user to the list
		if($action=='set_notifym') Aastra_manage_presence_Asterisk($selection,'set','notifym',$user);
		else Aastra_manage_presence_Asterisk($selection,'set','notifyv',$user);

		# Display text
		require_once('AastraIPPhoneTextScreen.class.php');
		$object=new AastraIPPhoneTextScreen();
		$object->setDestroyOnExit();
		$object->setTitle(Aastra_get_label('Notification Set',$language));
		$object->setText(sprintf(Aastra_get_label('You will be notified when %s returns.',$language),Aastra_get_callerid_Asterisk($selection)));

		# Softkey
		$object->addSoftkey('6',Aastra_get_label('Close',$language),$XML_SERVER.'&action=zoom&page='.$page.'&selection='.$selection.'&lookup='.$lookup);
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
		$object->addSoftkey('6',Aastra_get_label('Close',$language),$XML_SERVER.'&action=zoom&page='.$page.'&selection='.$selection.'&lookup='.$lookup);
		break;

	# Display directory
	case 'list':
		# Get list of users
		$directory=Aastra_get_user_directory_Asterisk();

		# Remove current user
		unset($directory[Aastra_get_userdevice_Asterisk($user)]);

		# Search?
		if($lookup!='')
			{
			# Filter the search
			foreach($directory as $key=>$value)
				{
				if(!stristr($value['name'],$lookup)) unset($directory[$key]);
				}
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

			# Get status if needed
			if($AA_PRESENCE_STATE) $directory=Aastra_get_hints_asterisk($directory);

			# Display Page
			require_once('AastraIPPhoneTextMenu.class.php');
			$object=new AastraIPPhoneTextMenu();
			$object->setDestroyOnExit();
			$object->setStyle('none');
			$object->setWrapList();

			# Display List
			$index=1;
			$rank=1;
			$min='';
			$max='';
			foreach ($directory as $v)
				{
				if(($index>=(($page-1)*$MaxLines+1)) and ($index<=$page*$MaxLines))
					{
					if($min=='') $min=substr($v['name'],0,2);
					if($v['number']==$selection) $object->setDefaultIndex($rank);
					$name=$v['name'];
					$icon='';
					if($AA_PRESENCE_STATE)
						{
						if($v['status']==AA_PRESENCE_AVAILABLE)
							{
							if($v['hint']=='Idle') $icon='1';
							else
								{
								if($v['hint']=='Ringing') $icon='3';
								else $icon='4';
								}
							}
						else $icon='2';
						}
					$object->addEntry($name,'Dial:'.$v['number'],$v['number'],$icon,$v['number']);
					$rank++;
					$max=substr($v['name'],0,2);
					}
				$index++;
				}

			# Title
			if($lookup=='')
				{
				if($last!=1) $object->setTitle(sprintf(Aastra_get_label('Directory (%s-%s)',$language),$min,$max));
				else $object->setTitle(Aastra_get_label('Directory',$language));
				}
			else
				{
				if($last!=1) $object->setTitle(sprintf(Aastra_get_label('Results (%s-%s)',$language),$min,$max));
				else $object->setTitle(Aastra_get_label('Results',$language));
				}

			# Softkeys
			$intercom=Aastra_get_intercom_config_Asterisk();

			# How many pages
			if($last==1)
				{
				# First menu set
				if($menu_set=='1')
					{
					# Single page list
					if($intercom!='') $object->addSoftkey('6',Aastra_get_label('Icom',$language),$XML_SERVER.'&action=intercom');
					if(!$AA_PRESENCE_STATE)
						{
						$object->addSoftkey('1',Aastra_get_label('Dial',$language),'SoftKey:Dial2');
						if($AA_SPEEDDIAL_STATE) $object->addSoftkey('3',Aastra_get_label('+Speed',$language),$XML_SERVER.'&action=select&orig_s=list'.'&lookup='.$lookup);
						}
					else
						{
						$object->addSoftkey('1',Aastra_get_label('Status',$language),$XML_SERVER.'&action=zoom'.'&lookup='.$lookup);
						$object->addSoftkey('2',Aastra_get_label('Dial',$language),'SoftKey:Dial2');
       					$object->addSoftkey('3',Aastra_get_label('Exit',$language),'SoftKey:Exit');
						if($origin!='presence')
							{
							if($AA_SPEEDDIAL_STATE) $object->addSoftkey('4',Aastra_get_label('+Speed',$language),$XML_SERVER.'&action=select&orig_s=list'.'&lookup='.$lookup);
							}
						else $object->addSoftkey('4',Aastra_get_label('Back',$language),$XML_SERVER_PATH.'/away.php?user='.$user);
						}
					$object->addSoftkey('5',Aastra_get_label('Lookup',$language),$XML_SERVER.'&action=search');
					/* $object->addSoftkey('6',Aastra_get_label('More',$language),$XML_SERVER.'&action=list&menu_set=2&lookup='.$lookup); */
					}
				else
					{
					if(!$AA_PRESENCE_STATE)
						{
						$object->addSoftkey('1',Aastra_get_label('Dial',$language),'SoftKey:Dial2');
						if($AA_SPEEDDIAL_STATE) $object->addSoftkey('3',Aastra_get_label('+Speed',$language),$XML_SERVER.'&action=select&orig_s=list'.'&lookup='.$lookup);
						}
					else
						{
						$object->addSoftkey('1',Aastra_get_label('Status',$language),$XML_SERVER.'&action=zoom'.'&lookup='.$lookup);
						$object->addSoftkey('2',Aastra_get_label('Dial',$language),'SoftKey:Dial2');
                        $object->addSoftkey('3',Aastra_get_label('Exit',$language),'SoftKey:Exit');
						}
					if($lookup!='') $object->addSoftkey('2',Aastra_get_label('List',$language),$XML_SERVER.'&action=list');
					$object->addSoftkey('4',Aastra_get_label('Exit',$language),'SoftKey:Exit');
					$object->addSoftkey('5',Aastra_get_label('Prefs',$language),$XML_SERVER.'&action=prefs&lookup='.$lookup.'&page='.$page);
					$object->addSoftkey('6',Aastra_get_label('More',$language),$XML_SERVER.'&action=list&menu_set=1&lookup='.$lookup);
					}
				}
			else
				{
				# First menu set
				if($menu_set=='1')
					{
					# Multiple pages
					if(!$AA_PRESENCE_STATE)
						{
						$object->addSoftkey('1',Aastra_get_label('Dial',$language),'SoftKey:Dial2');
						$object->addSoftkey('3',Aastra_get_label('Lookup',$language),$XML_SERVER.'&action=search');
						}
					else
						{
						$object->addSoftkey('1',Aastra_get_label('Select',$language),$XML_SERVER.'&action=zoom&page='.$page);
						$object->addSoftkey('2',Aastra_get_label('Dial',$language),'SoftKey:Dial2');
						if($origin!='presence') $object->addSoftkey('4',Aastra_get_label('Lookup',$language),$XML_SERVER.'&action=search');
						else $object->addSoftkey('4',Aastra_get_label('Back',$language),$XML_SERVER_PATH.'/away.php?user='.$user);
						}
					if($page!=1) $object->addSoftkey('2',Aastra_get_label('Previous',$language),$XML_SERVER.'&action=list&page='.($page-1));
					if($page!=$last) $object->addSoftkey('5',Aastra_get_label('Next',$language),$XML_SERVER.'&action=list&page='.($page+1));
					$object->addSoftkey('6',Aastra_get_label('More',$language),$XML_SERVER.'&action=list&menu_set=2&lookup='.$lookup);
					}
				else
					{
					if(!$AA_PRESENCE_STATE)
						{
						if($AA_SPEEDDIAL_STATE) $object->addSoftkey('1',Aastra_get_label('+Speed',$language),$XML_SERVER.'&action=select&page='.$page.'&orig_s=list');
						}
					else
						{
						if($origin!='presence')
							{
							if($AA_SPEEDDIAL_STATE) $object->addSoftkey('1',Aastra_get_label('+Speed',$language),$XML_SERVER.'&action=select&page='.$page.'&orig_s=list');
							}
						else $object->addSoftkey('1',Aastra_get_label('Lookup',$language),$XML_SERVER.'&action=search');
						}
					if($page!=1) $object->addSoftkey('2',Aastra_get_label('Previous',$language),$XML_SERVER.'&action=list&page='.($page-1));
					$object->addSoftkey('3',Aastra_get_label('Prefs',$language),$XML_SERVER.'&action=prefs&lookup='.$lookup.'&page='.$page);
					if($page!=$last) $object->addSoftkey('5',Aastra_get_label('Next',$language),$XML_SERVER.'&action=list&page='.($page+1));
					if($lookup!='') $object->addSoftkey('4',Aastra_get_label('List',$language),$XML_SERVER.'&action=list');
					else $object->addSoftkey('4',Aastra_get_label('Exit',$language),'SoftKey:Exit');
					$object->addSoftkey('6',Aastra_get_label('More',$language),$XML_SERVER.'&action=list&menu_set=1&lookup='.$lookup);
					}
				}

			# Icons
			$object->addIcon('1',Aastra_get_custom_icon('Available'));
			$object->addIcon('2',Aastra_get_custom_icon('DND'));
			$object->addIcon('3',Aastra_get_custom_icon('Bell'));
			$object->addIcon('4',Aastra_get_custom_icon('Offhook'));
			}
		else
			{
			# Display error
			require_once('AastraIPPhoneTextScreen.class.php');
			$object=new AastraIPPhoneTextScreen();
			$object->setDestroyOnExit();
			if($lookup=='')
				{
				$object->setTitle(Aastra_get_label('Directory error',$language));
				$object->setText(Aastra_get_label('Directory list is empty. Please contact your administrator.',$language));
				}
			else
				{
				$object->setTitle(Aastra_get_label('Lookup error',$language));
				$object->setText(Aastra_get_label('Sorry no match.',$language));
				}

			# Softkey
			if($lookup!='')
				{
				$object->addSoftkey('4',Aastra_get_label('List Mode',$language),$XML_SERVER.'&action=list');
				$object->addSoftkey('5',Aastra_get_label('Back',$language),$XML_SERVER.'&action=search&lookup='.$lookup);
				}
			$object->addSoftkey('6',Aastra_get_label('Exit',$language),'SoftKey:Exit');
			}
		break;
	}

# Output XML object
$object->output();
exit();
?>