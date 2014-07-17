<?php
#############################################################################
# Asterisk Meet-me
#
# Aastra SIP Phones 1.4.2 or better
#
# Copyright 2005-2010 Aastra Telecom Ltd
#
# Phones supported
#    480i
#    480iCT
#    55i
#    57i
#    57iCT
#
# Usage
# 	script.php?ext=USER
# 	USER is the user extension
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
# Private functions
#############################################################################
function is_user_connected($confno,$user)
{
# Init return
$return['connected']=False;
$return['admin']=False;

# Get an updated list
$meetmechannel=Aastra_get_meetme_list_Asterisk($confno);
if(isset($meetmechannel))
	{
	$nbuser=count($meetmechannel);
	if($nbuser>0)
		{
		foreach($meetmechannel as $value)
			{
			if($value[1]==$user)
				{
				$return['connected']=True;
				$return['admin']=$value[4];
				break;
				}
			}
		}
	}

# Return result
return($return);
}

#############################################################################
# Main code
#############################################################################

# Retrieve parameters
$confno=Aastra_getvar_safe('confno');
$action=Aastra_getvar_safe('action','select');
$page=Aastra_getvar_safe('page','1');
$user_id=Aastra_getvar_safe('user_id');
$ext=Aastra_getvar_safe('ext');
$number=Aastra_getvar_safe('number','');
$selection=Aastra_getvar_safe('selection');
if(($confno=='') and ($selection!='')) $confno=$selection;
$pin=Aastra_getvar_safe('pin');
$mode=Aastra_getvar_safe('mode');

# Local variables
$refresh=1;

# Trace
Aastra_trace_call('meetme_asterisk','confno='.$confno.', action='.$action.', user_id='.$user_id.',selection='.$selection.',ext='.$ext.', mode='.$mode.', number='.$number);

# Retrieve phone information
$header=Aastra_decode_HTTP_header();

# Test User Agent
Aastra_test_phone_versions(array('1'=>'','2'=>'1.4.2.','3'=>'','4'=>'2.5.3.','5'=>'3.0.1.'),'0');

# Get Language
$language=Aastra_get_language();

# Keep callback URI
$XML_SERVER.='?ext='.$ext;

# Authenticate user
Aastra_check_signature_Asterisk($ext);

# Compatibility
$nb_softkeys=Aastra_number_softkeys_supported();
$is_icons=Aastra_is_icons_supported();

# Pre-process action
switch($action)
	{
	# Check pin number
	case 'checkpin':
		# Retrieve pins
		$array=Aastra_get_meetme_room_details_Asterisk($confno);
		if(($pin!=$array['adminpin']) and ($pin!=$array['userpin']))
			{
			# Display error message
			require_once('AastraIPPhoneTextScreen.class.php');
			$object = new AastraIPPhoneTextScreen();
			$object->setDestroyOnExit();
			$object->setTitle(Aastra_get_label('Access denied',$language));	
			$object->setText(sprintf(Aastra_get_label('Wrong PIN number for conference %s.',$language),$confno));

			# Softkeys
			if($nb_softkeys==6) 
				{
				$object->addSoftkey(5,Aastra_get_label('Cancel',$language),$XML_SERVER.'&confno='.$confno);
				$object->addSoftkey(6,Aastra_get_label('Done',$language),$XML_SERVER.'&action=details&confno='.$confno);
				}
			else 
				{
				$object->addSoftkey(9,Aastra_get_label('Cancel',$language),$XML_SERVER.'&confno='.$confno);
				$object->addSoftkey(10,Aastra_get_label('Done',$language),$XML_SERVER.'&action=details&confno='.$confno);
				}
			}
		else
			{
			if($pin==$array['adminpin']) $mode='admin';
			else $mode='user';
			$action='display';
			}
		break;
	}

# Perform requested action
switch($action)
	{
	# Select conference room
	case 'select':
		# Erase refresh
		@unlink(AASTRA_PATH_CACHE.$header['mac'].'.meetme');

		# Open configured conferences
		$conf_array=Aastra_get_meetme_rooms_Asterisk();

		# Retrieve last page
		$index=count($conf_array);
		$last=intval($index/AASTRA_MAXLINES);
		if(($index-$last*AASTRA_MAXLINES) != 0) $last++;

		# Display list
		if($index==0)
			{
			# Display error message
			require_once('AastraIPPhoneTextScreen.class.php');
			$object = new AastraIPPhoneTextScreen();
			$object->setDestroyOnExit();
			$object->setTitle(Aastra_get_label('Configuration error',$language));	
			$object->setText(Aastra_get_label('No conference room configured on this platform. Please contact your administrator.',$language));

			# Softkeys
			if($nb_softkeys==6) $object->addSoftkey(6,Aastra_get_label('Exit',$language),'SoftKey:Exit');
			else $object->addSoftkey(10,Aastra_get_label('Exit',$language),'SoftKey:Exit');
			}
		else
			{
			# Ongoing conference?
			if(($number!='') and array_key_exists($number,$conf_array))
				{
				# Jump to details
				require_once('AastraIPPhoneExecute.class.php');
				$object = new AastraIPPhoneExecute();
				$object->addEntry($XML_SERVER.'&action=details&confno='.$number);
				}
			else
				{
				# Create list of conference rooms
				require_once('AastraIPPhoneTextMenu.class.php');
				$object = new AastraIPPhoneTextMenu();
				$object->setDestroyOnExit();
				if($last==1) $object->setTitle(Aastra_get_label('Conference Rooms',$language));
				else $object->setTitle(sprintf(Aastra_get_label('Conference Rooms (%s/%s)',$language),$page,$last));
				if(Aastra_is_style_textmenu_supported()) $object->setStyle('none');

				# Find default position
				$default='';
				if($confno!='')
					{
					$index=1;
					$found=False;
					foreach($conf_array as $key=>$value)
						{
						if($confno==$key) 
							{
							$found=True;
							break;
							}
						$index++;
						}
					if($found)
						{
						$default=$index%AASTRA_MAXLINES;
						$page=(($index-$default)/AASTRA_MAXLINES)+1;
						}
					}

				# Display list
				$index=1;
				foreach($conf_array as $key=>$value)
					{
					if(($index>=(($page-1)*AASTRA_MAXLINES+1)) and ($index<=$page*AASTRA_MAXLINES)) 
						{
						if(Aastra_is_dial2key_supported()) 
							{
							if($nb_softkeys==10) $object->addEntry($value['name'].' ('.$value['parties'].')',$XML_SERVER.'&action=details&selection='.$key,$key,'',$key);
							else $object->addEntry($value['name'].' ('.$value['parties'].')','Dial:'.$key,$key,'',$key);
							}
						else $object->addEntry($value['name'].' ('.$value['parties'].')',$key,$key);
						}
					$index++;
					}

				# Default position
				if($default!='') $object->setDefaultIndex($default);

				# Softkeys
				if($nb_softkeys==6)
					{
					$object->addSoftkey(1,Aastra_get_label('Details',$language),$XML_SERVER.'&action=details');
					if($page!=1) $object->addSoftkey(2,Aastra_get_label('Previous',$language),$XML_SERVER.'&page='.($page-1));
					if(Aastra_is_dial2key_supported()) $object->addSoftkey(4,Aastra_get_label('Join',$language),'SoftKey:Select');
					else $object->addSoftkey(4,Aastra_get_label('Join',$language),'SoftKey:Dial');
					if($page!=$last) $object->addSoftkey(5,Aastra_get_label('Next',$language),$XML_SERVER.'&page='.($page+1));
					$object->addSoftkey(6,Aastra_get_label('Exit',$language),'SoftKey:Exit');
					}
				else
					{
					$object->addSoftkey(1,Aastra_get_label('Details',$language),$XML_SERVER.'&action=details');
					if($page!=1) $object->addSoftkey(3,Aastra_get_label('Previous Page',$language),$XML_SERVER.'&page='.($page-1));
					if($page!=$last) $object->addSoftkey(8,Aastra_get_label('Next Page',$language),$XML_SERVER.'&page='.($page+1));
					$object->addSoftkey(10,Aastra_get_label('Exit',$language),'SoftKey:Exit');
					}
				}
			}
		break;

	# Check if password needed
	case 'details':
		# Retrieve pins
		$array=Aastra_get_meetme_room_details_Asterisk($confno);

		# No PIN
		if(($array['adminpin']=='') and ($array['userpin']==''))
			{
			# Straight to details
			require_once('AastraIPPhoneExecute.class.php');
			$object=new AastraIPPhoneExecute();
			$object->addEntry($XML_SERVER.'&action=display&mode=admin&confno='.$confno);
			}
		else
			{
			# Check if user connected
			$user=Aastra_get_userdevice_Asterisk($ext);
			$return=is_user_connected($confno,$user);
			
			# User connected
			if($return['connected'])
				{
				# Straight to details
				require_once('AastraIPPhoneExecute.class.php');
				$object=new AastraIPPhoneExecute();
				if($return['admin']) $object->addEntry($XML_SERVER.'&action=display&mode=admin&confno='.$confno);
				else $object->addEntry($XML_SERVER.'&action=display&mode=user&confno='.$confno);
				}
			else
				{
				# Enter PIN
				require_once('AastraIPPhoneInputScreen.class.php');
				$object=new AastraIPPhoneInputScreen();
				$object->setDestroyOnExit();
				$object->setTitle(sprintf(Aastra_get_label('Conference Access (%s)',$language),$confno));
				$object->setPrompt(Aastra_get_label('Enter Password',$language));
				$object->setParameter('pin');
				$object->setType('number');
				$object->setURL($XML_SERVER.'&action=checkpin&confno='.$confno);
				$object->setPassword();

				# Softkeys
				if($nb_softkeys)
					{
					if($nb_softkeys==6)
						{
						$object->addSoftkey('1',Aastra_get_label('Backspace',$language),'SoftKey:BackSpace');
						$object->addSoftkey('4',Aastra_get_label('Cancel',$language),$XML_SERVER.'&confno='.$confno);
						$object->addSoftkey('5',Aastra_get_label('Submit',$language),'SoftKey:Submit');
						$object->addSoftkey('6',Aastra_get_label('Exit',$language),'SoftKey:Exit');
						}
					else
						{
						$object->addSoftkey('9',Aastra_get_label('Cancel',$language),$XML_SERVER.'&confno='.$confno);
						$object->addSoftkey('10',Aastra_get_label('Exit',$language),'SoftKey:Exit');
						}
					}
				}
			}
		break;

	# Mute/unmute and kick
	case 'mute':
	case 'kick':
		# Perform the action
		$meetmechannel=Aastra_get_meetme_list_Asterisk($confno);
		$nbuser=count($meetmechannel);
		$send=True;
		for($index=0;$index<$nbuser;$index++)
			{
			if($meetmechannel[$index][0]==intval($user_id))
				{
				if(($action=='mute') and ($meetmechannel[$index][3])) $action='unmute';
				if(strstr($meetmechannel[$index][4],'SIP/'.$ext)) $send=False;
				break;
				}
			}
		if($send) Aastra_meetme_action_Asterisk($confno,$action,$user_id);
		unset($meetmechannel);

		# Come back to the display
		require_once('AastraIPPhoneExecute.class.php');
		$object=new AastraIPPhoneExecute();
		$object->addEntry($XML_SERVER.'&action=display&confno='.$confno.'&mode='.$mode);
		break;
	
	# Display conference room
	case 'refresh':
		$refresh=0;
	case 'display':
		# Get an updated list
		$meetmechannel=Aastra_get_meetme_list_Asterisk($confno);
		$nbuser=count($meetmechannel);

		# Display list
		if($nbuser==0)
			{
			# No user
			require_once('AastraIPPhoneTextScreen.class.php');
			$object = new AastraIPPhoneTextScreen();
			$object->setDestroyOnExit();
			$object->setTitle(sprintf(Aastra_get_label('Conference %s',$language),$confno));	
			$object->setText(Aastra_get_label('No user in this conference room.',$language));

			# Softkeys
			if($nb_softkeys==6) 
				{
				$object->addSoftkey('5',Aastra_get_label('Back',$language),$XML_SERVER.'&confno='.$confno);
				$object->addSoftkey('6',Aastra_get_label('Exit',$language),'SoftKey:Exit');
				}
			else 
				{
				$object->addSoftkey('9',Aastra_get_label('Back',$language),$XML_SERVER.'&confno='.$confno);
				$object->addSoftkey('10',Aastra_get_label('Exit',$language),'SoftKey:Exit');
				$object->setCancelAction($XML_SERVER.'&confno='.$confno);
				}
			}
		else
			{
			# Retrieve last page
			$last=intval($nbuser/AASTRA_MAXLINES);
			if(($nbuser-$last*AASTRA_MAXLINES)!=0) $last++;

			# Sort by name
			Aastra_natsort2d($meetmechannel,'2');

			# Prepare object
			require_once('AastraIPPhoneTextMenu.class.php');
			$object = new AastraIPPhoneTextMenu();
			$object->setDestroyOnExit();
			if(Aastra_is_style_textmenu_supported())$object->setStyle('none');
			if($last==1) $object->setTitle(sprintf(Aastra_get_label('Conference %s',$language),$confno));
			else $object->setTitle(sprintf(Aastra_get_label('Conference %s (%s/%s)',$language),$confno,$page,$last));
			$new_selection=1;
			$index=0;
			$user=Aastra_get_userdevice_Asterisk($ext);
			foreach($meetmechannel as $value)
				{
				if(($index>=(($page-1)*AASTRA_MAXLINES)) and ($index<$page*AASTRA_MAXLINES))
					{
					if($value[1]==$user) $name=sprintf(Aastra_get_label('%s (YOU)',$language),$value[2]);
					else $name=$value[2];
					$icon='';
					if($value[3]) 
						{
						if(!$is_icons) $name='('.$name.')';
						else $icon=2; 
						}
					else
						{
						if($is_icons) $icon=1; 
						}
					$object->addEntry($name,$XML_SERVER.'&action=refresh&page='.$page.'&confno='.$confno.'&mode='.$mode,$new_selection.'&user_id='.$value[0],$icon);
					$new_selection++;
					}
				$index++;
				}

			# Timeout
			$object->setTimeout('120');

			# Default value
			if(isset($selection)) $object->setDefaultIndex($selection);

			# Icons
			if($is_icons)
				{
				if(Aastra_phone_type()!=5)
					{
					$object->addIcon('1',Aastra_get_custom_icon('Speaker'));
					$object->addIcon('2',Aastra_get_custom_icon('Muted'));
					}
				else
					{
					$object->addIcon(1,'Icon:Speaker');
					$object->addIcon(2,'Icon:Mute');
					}
				}

			# Softkeys
			if($nb_softkeys==6)
				{
				if($mode=='admin')
					{
					$object->addSoftkey(1,Aastra_get_label('(Un)Mute',$language),$XML_SERVER.'&action=mute&page='.$page.'&confno='.$confno.'&mode='.$mode);
					$object->addSoftkey(3,Aastra_get_label('Kick',$language),$XML_SERVER.'&action=kick&page='.$page.'&confno='.$confno.'&mode='.$mode);
					}
				if($page!=1) $object->addSoftkey(2,Aastra_get_label('Previous',$language),$XML_SERVER.'&action=display&page='.($page-1).'&confno='.$confno.'&mode='.$mode);
				$object->addSoftkey('4',Aastra_get_label('Back',$language),$XML_SERVER.'&confno='.$confno);
				if($page!=$last) $object->addSoftkey(5,Aastra_get_label('Next',$language),$XML_SERVER.'&action=display&page='.($page+1).'&confno='.$confno.'&mode='.$mode);
				$object->addSoftkey(6,Aastra_get_label('Exit',$language),'SoftKey:Exit');
				}
			else
				{
				if($mode=='admin')
					{
					$object->addSoftkey(1,Aastra_get_label('(Un)Mute',$language),$XML_SERVER.'&action=mute&page='.$page.'&confno='.$confno.'&mode='.$mode);
					$object->addSoftkey(2,Aastra_get_label('Kick',$language),$XML_SERVER.'&action=kick&page='.$page.'&confno='.$confno.'&mode='.$mode);
					}
				if($page!=1) $object->addSoftkey(3,Aastra_get_label('Previous',$language),$XML_SERVER.'&action=display&page='.($page-1).'&confno='.$confno.'&mode='.$mode);
				if($page!=$last) $object->addSoftkey(8,Aastra_get_label('Next',$language),$XML_SERVER.'&action=display&page='.($page+1).'&confno='.$confno.'&mode='.$mode);
				$object->addSoftkey('9',Aastra_get_label('Back',$language),$XML_SERVER.'&confno='.$confno);
				$object->addSoftkey(10,Aastra_get_label('Exit',$language),'SoftKey:Exit');
				}
			}

		# Test the refresh
		if(Aastra_is_Refresh_supported()) 
			{
			$object->setRefresh('5',$XML_SERVER.'&action=refresh&page='.$page.'&confno='.$confno.'&mode='.$mode);
			$new=$object->generate();
			}
		else 
			{
			$refresh=1;
			if($nb_softkeys==6) $object->addSoftkey(3,Aastra_get_label('Refresh',$language),$XML_SERVER.'&action=display&page='.$page.'&confno='.$confno.'&mode='.$mode);
			else $object->addSoftkey(6,Aastra_get_label('Refresh',$language),$XML_SERVER.'&action=display&page='.$page.'&confno='.$confno.'&mode='.$mode);
			}

		# Test refresh
		if($refresh==0)
			{
			$old=@file_get_contents(AASTRA_PATH_CACHE.$header['mac'].'.meetme');
			if($new!=$old) $refresh=1;
			}

		# Save last screen
		$stream=@fopen(AASTRA_PATH_CACHE.$header['mac'].'.meetme','w');
		@fwrite($stream,$new);
		@fclose($stream);
		break;
	}


# Display object
if($refresh==1) $object->output();
else
	{
	# Do nothing
	require_once('AastraIPPhoneExecute.class.php');
	$object2 = new AastraIPPhoneExecute();
	$object2->addEntry('');
	$object2->output();
	}
exit;
?>
