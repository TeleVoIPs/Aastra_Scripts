<?php
#############################################################################
# Asterisk Sync - Sync DND/CFWD/DAYNIGHT/PRESENCE/AGENT status
#
# Aastra SIP Phones 1.4.2 or better
#
# Copyright 2005-2010 Aastra Telecom Ltd
#
# script.php?user=USER&action=ACTION
# USER is the user extension
# ACTION is the action (register or check)
#
#############################################################################

#############################################################################
# PHP customization for includes and warnings
#############################################################################
$os = strtolower(PHP_OS);
if(strpos($os, "win") === false) ini_set('include_path',ini_get('include_path').':include:../include');
else ini_set('include_path',ini_get('include_path').';include;../include');
error_reporting(E_ERROR | E_PARSE);

#############################################################################
# Includes
#############################################################################
require_once('AastraAsterisk.php');

#############################################################################
# Private functions
#############################################################################
function generate_user_config($device,$user,$last_user)
{
Global $AA_XML_SERVER;
Global $AA_XMLDIRECTORY;
Global $AA_PRESENCE_STATE;
Global $AA_SPEEDDIAL_STATE;
Global $language;

# Get type of phone
$header=Aastra_decode_HTTP_header();
$model=$header['model'];

# Read profile file
if($user!='') $profile=Aastra_get_startup_profile_Asterisk($user);
else $profile=Aastra_get_startup_profile_Asterisk($device);
$array_user=Aastra_readCFGfile($profile.'-device-user.prf', '#', ':');
$array_nouser=Aastra_readCFGfile($profile.'-device-nouser.prf', '#', ':');
$array_custom=Aastra_readCFGfile('user-custom.prf', '#', ':');

# Language
$code=Aastra_ask_language_Asterisk();
$ask_language=$code[0];

# Retrieve user information
$device_info=Aastra_get_device_info_Asterisk($device);

# There is a user
if($user!='')
	{
	# Retrieve user information
	$user_info=Aastra_get_user_info_Asterisk($user);

	# Get park configuration
	$park=Aastra_get_park_config_Asterisk();

	# Get polling value
	$polling=Aastra_get_polling_interval_Asterisk();

	# Prepare replace strings
	$search=array(	'/\$\$AA_SIPUSERNAME_AA\$\$/',
				'/\$\$AA_SIPCALLERID_AA\$\$/',
				'/\$\$AA_XML_SERVER_AA\$\$/',
				'/\$\$AA_PARKINGLOT_AA\$\$/',
				'/\$\$AA_XMLDIRECTORY_AA\$\$/',
				'/\$\$AA_INTERCOM_CODE_AA\$\$/',
				'/\$\$AA_POLLING_INT_AA\$\$/'
			);
	$replace=array(	$user_info['cidnum'],
				$user_info['cidname'],
				$AA_XML_SERVER,
				$park['parkext'],
				$AA_XMLDIRECTORY,
				Aastra_get_intercom_config_Asterisk(),
				$polling
			);

	# Use the common piece
	foreach($array_user['Common'] as $key => $value)
		{
		if(!array_key_exists($key,$array_nouser['Core']))
			{
			$line = preg_replace($search, $replace, $value);
			if(stristr($line,'$$AA_KEYPRESS_AA$$'))
				{
				$pieces=explode(' ',$key);
				$line = preg_replace('/\$\$AA_KEYPRESS_AA\$\$/', $pieces[0], $line);
				}
			$array_out[$key]=$line;
			}
		}

	# Model exists
	if($array_user[$model]!=NULL)
		{
		# Check if full template
		if (key_exists('template',$array_user[$model])) $template=$array_user[$model]['template'];
		else $template=$model;

		# Use the template
		foreach($array_user[$template] as $key => $value)
			{
			if(($key!='template') and (!array_key_exists($key,$array_nouser['Core'])))
				{
				$line=preg_replace($search,$replace,$value);
				if(stristr($line,'$$AA_KEYPRESS_AA$$'))
					{
					$pieces=explode(' ',$key);
					$line=preg_replace('/\$\$AA_KEYPRESS_AA\$\$/', $pieces[0], $line);
					}
				$array_out[$key]=$line;
				}
			}
	
		# Use the add-ons
		if($template!=$model)
			{
			foreach($array_user[$model] as $key => $value)
				{
				if($key!='template')
					{
					$line=preg_replace($search,$replace,$value);
					$array_out[$key]=$line;
					}
				}
			}
		}

	# User customization exists
	if($array_custom[$user]!=NULL)
		{
       	# Use the user configuration additions
        	foreach($array_custom[$user] as $key => $value)
                	{
                	$line = preg_replace($search, $replace, $value);
                	if(stristr($line,'$$AA_KEYPRESS_AA$$'))
                     	{
                        	$pieces=explode(' ',$key);
                        	$line = preg_replace('/\$\$AA_KEYPRESS_AA\$\$/', $pieces[0], $line);
                        	}
                	$array_out[$key]=$line;
                	}
        	}

	# Language
	if($ask_language)
		{
		$array_language=Aastra_get_user_context($device,'language');
		$array_out['language']=$array_language['code'];
		$array_out['web language']=$array_language['code'];
		$array_out['input language']=$array_language['clear'];
		$language=$array_language['language'];
		}
	}
else
	{
	# Use the common piece
	foreach($array_user['Common'] as $key => $value)
		{
		if(!array_key_exists($key,$array_nouser['Core'])) $array_out[$key]='';
		}

	# Model exists for the user
	if($array_user[$model]!=NULL)
		{
		# Check if full template
		if (key_exists('template',$array_user[$model])) $template=$array_user[$model]['template'];
		else $template=$model;

		# Use the template
		foreach($array_user[$template] as $key => $value)
			{
			if(($key!='template') and (!array_key_exists($key,$array_nouser['Core']))) $array_out[$key]='';
			}

		# Use the add-ons
		if($template!=$model)
			{
			foreach($array_user[$model] as $key => $value)
				{
				if($key!='template') $array_out[$key]='';
				}
			}
		}

	# Device customization exists
	if($array_custom[$last_user]!=NULL)
		{
		foreach($array_custom[$last_user] as $key => $value)
			{
			if(!array_key_exists($key,$array_nouser['Core'])) $array_out[$key]='';
			}
        	}

	# Prepare replace strings
	$search=array('/\$\$AA_SIPUSERNAME_AA\$\$/','/\$\$AA_SIPCALLERID_AA\$\$/','/\$\$AA_XML_SERVER_AA\$\$/','/\$\$AA_PARKINGLOT_AA\$\$/','/\$\$AA_XMLDIRECTORY_AA\$\$/');
	$replace=array($device,Aastra_get_callerid_Asterisk($device),$AA_XML_SERVER,$park['parkext'],$AA_XMLDIRECTORY);

	# Use the common piece
	foreach($array_nouser['Common'] as $key => $value)
		{
		$line=preg_replace($search, $replace, $value);
		if(stristr($line,'$$AA_KEYPRESS_AA$$'))
			{
			$pieces=explode(' ',$key);
			$line=preg_replace('/\$\$AA_KEYPRESS_AA\$\$/', $pieces[0], $line);
			}
		$array_out[$key]=$line;
		}

	# Model exists for the device
	if($array_nouser[$model]!=NULL)
		{
		# Check if full template
		if (key_exists('template',$array_nouser[$model])) $template=$array_nouser[$model]['template'];
		else $template=$model;

		# Use the template
		foreach($array_nouser[$template] as $key => $value)
			{
			if($key!='template')
				{
				$line=preg_replace($search, $replace, $value);
				if(stristr($line,'$$AA_KEYPRESS_AA$$'))
					{
					$pieces=explode(' ',$key);
					$line=preg_replace('/\$\$AA_KEYPRESS_AA\$\$/', $pieces[0], $line);
					}
				$array_out[$key]=$line;
				}
			}

		# Use the add-ons
		if($template!=$model)
			{
			foreach($array_nouser[$model] as $key => $value)
				{
				if($key!='template')
					{
					$line=preg_replace($search, $replace, $value);
					$array_out[$key]=$line;
					}
				}
			}
		}

	# Language
	if($ask_language)
		{
		$array_out['language']='';
		$array_out['web language']='';
		$array_out['input language']='';
		$language=$code[1][$code[2]];
		}
	}

# Process day/night keys
foreach($array_out as $key=>$value) 
	{
	if(strstr($value,'daynight.php'))
		{
		$pieces=explode(' ',$key);
		if(stristr($pieces[0],'expmod')) $appli=$pieces[0].' '.$pieces[1];
		else $appli=$pieces[0];
		$url=parse_url($value);
		parse_str($url['query'],$parse);
		if($parse['index']!='') $index=$parse['index'];
		else $index='ALL';
		if(!Aastra_is_daynight_appli_allowed_Asterisk($username,$index))
			{
			unset($array_out[$appli.' type']);
			unset($array_out[$appli.' label']);
			unset($array_out[$appli.' value']);
			unset($array_out[$appli.' states']);
			}
		}
	}

# Process presence
if(!$AA_PRESENCE_STATE)
	{
	foreach($array_out as $key=>$value) 
		{
		if(strstr($value,'away.php'))
			{
			$pieces=explode(' ',$key);
			if(stristr($pieces[0],'expmod')) $appli=$pieces[0].' '.$pieces[1];
			else $appli=$pieces[0];
			break;
			}
		}
	if($appli!='')
		{
		unset($array_out[$appli.' type']);
		unset($array_out[$appli.' label']);
		unset($array_out[$appli.' value']);
		unset($array_out[$appli.' states']);
		}
	}

# Process speed dial
if(!$AA_SPEEDDIAL_STATE)
	{
	foreach($array_out as $key=>$value) 
		{
		if(strstr($value,'speed.php'))
			{
			$pieces=explode(' ',$key);
			if(stristr($pieces[0],'expmod')) $appli=$pieces[0].' '.$pieces[1];
			else $appli=$pieces[0];
			break;
			}
		}
	if($appli!='')
		{
		unset($array_out[$appli.' type']);
		unset($array_out[$appli.' label']);
		unset($array_out[$appli.' value']);
		unset($array_out[$appli.' states']);
		}
	}

# Remove 'logout' if device is fixed
if(($user!='') and ($device_info['type']=='fixed'))
	{
	foreach($array_out as $key=>$value) 
		{
		if(strstr($value,'logout.php'))
			{
			$pieces=explode(' ',$key);
			if(stristr($pieces[0],'expmod')) $appli=$pieces[0].' '.$pieces[1];
			else $appli=$pieces[0];
			break;
			}
		}
	if($appli!='')
		{
		unset($array_out[$pieces[0].' type']);
		unset($array_out[$pieces[0].' label']);
		unset($array_out[$pieces[0].' value']);
		unset($array_out[$pieces[0].' states']);
		}
	}

# Find the special keys
if($user!='') init_special_keys($device,$array_out);

# Translate labels
foreach($array_out as $key=>$value)
	{
	$test=False;
	if(stristr($key,'key') and stristr($key,'label')) $test=True;
	if(stristr($key,'xml application title')) $test=True;
	if($test) $array_out[$key]=Aastra_get_label($value,$language);
	}

# Return result
return($array_out);
}

function sync_apps($user,$action)
{
Global $XML_SERVER_PATH;
Global $AA_AUTOLOGOUT;
Global $AA_AUTOLOGOUT_MSG;
Global $AA_FREEPBX_MODE;

# Get phone information
$header=Aastra_decode_HTTP_header();

# Retrieve feature keys
$cfwd=Aastra_get_user_context($user,'cfwd');
$dnd=Aastra_get_user_context($user,'dnd');
$away=Aastra_get_user_context($user,'away');
$agent=Aastra_get_user_context($user,'agent');
$parking=Aastra_get_user_context($user,'parking');
$vmail=Aastra_get_user_context($user,'vmail');
$follow=Aastra_get_user_context($user,'follow');
$logout=Aastra_get_user_context($user,'autologout');

# Update DND, CFWD, DAYNIGHT, PRESENCE, AGENT, PARKING, VMAIL and FOLLOW-ME
require_once('AastraIPPhoneExecute.class.php');
$object = new AastraIPPhoneExecute();
if(($cfwd['key']!='') or ($header['mac']=='Aastra51i')) $object->addEntry($XML_SERVER_PATH.'cfwd.php?action='.$action.'&user='.$user);
if(($dnd['key']!='') or ($header['mac']=='Aastra51i')) $object->addEntry($XML_SERVER_PATH.'dnd.php?action='.$action.'&user='.$user);
if(Aastra_is_daynight_notify_allowed_Asterisk($user)) $object->addEntry($XML_SERVER_PATH.'daynight.php?action='.$action.'&user='.$user);
if(($away['key']!='') or ($header['mac']=='Aastra51i')) $object->addEntry($XML_SERVER_PATH.'away.php?action='.$action.'&user='.$user);
if(($agent['key']!='') or ($header['mac']=='Aastra51i')) $object->addEntry($XML_SERVER_PATH.'agent.php?action='.$action.'&agent='.$user);
if(($follow['key']!='') or ($header['mac']=='Aastra51i')) $object->addEntry($XML_SERVER_PATH.'follow.php?action='.$action.'&user='.$user);
if(($AA_FREEPBX_MODE=='1') and Aastra_is_sip_notify_supported() and $AA_AUTOLOGOUT and $AA_AUTOLOGOUT_MSG) $object->addEntry($XML_SERVER_PATH.'logout.php?action='.$action.'&user='.$user);
if(($action=='register') and Aastra_is_ledcontrol_supported()) 
	{
	if($parking['key']!='') 
		{
		$status=Aastra_get_user_context('parking','status');
		if($status=='') $status='off';
		$object->addEntry('Led: '.$parking['key'].'='.$status);
		}
	$status=Aastra_get_user_context('vmail','user');
	foreach($vmail as $box=>$key) $object->addEntry('Led: '.$key['key'].'='.$status[$box]['status']);
	}
$object->addEntry('');

# Return object
return($object);
}

function init_special_keys($user,$array_config=NULL)
{
# Mode
if($array_config==NULL)
	{
	# Get MAC address and type of phone
	$header=Aastra_decode_HTTP_header();

	# Read config file
	$array_temp=Aastra_readCFGfile(AASTRA_TFTP_DIRECTORY.'/'.$header['mac'].'.cfg','#',':');
	$array_config=$array_temp[''];
	}

# Global parameters
$is_sip_notify_supported=Aastra_is_sip_notify_supported();

# Remove the special keys
$cfwd['key']='';
$dnd['key']='';
$daynight['key']=array();
$away['key']='';
$agent['key']='';
$follow['key']='';
$parking['key']='';
$vmail=array();
Aastra_remove_parking_Asterisk($user);
$count=!Aastra_is_ledcontrol_supported();
Aastra_remove_vmail_Asterisk('',$user,$count);

# Some data
if($array_config!=NULL)
	{
	foreach($array_config as $key=>$value) 
		{
		if(strstr($value,'dnd.php')) 
			{
			$pieces=explode(' ',$key);
			if(stristr($pieces[0],'expmod')) $dnd['key']=$pieces[0].' '.$pieces[1];
			else $dnd['key']=$pieces[0];
			}
		if(strstr($value,'cfwd.php')) 
			{
			$pieces=explode(' ',$key);
			if(stristr($pieces[0],'expmod')) $cfwd['key']=$pieces[0].' '.$pieces[1];
			else $cfwd['key']=$pieces[0];
			}
		if(strstr($value,'daynight.php')) 
			{
			$pieces=explode(' ',$key);
			if(stristr($pieces[0],'expmod')) $key=$pieces[0].' '.$pieces[1];
			else $key=$pieces[0];
			$url=parse_url($value);
			parse_str($url['query'],$parse);
			if(isset($parse['index']))
				{			
				if($parse['index']!='') $daynight['key'][$parse['index']]=$key;
				else $daynight['key']['ALL']=$key;
				}
			else $daynight['key']['ALL']=$key;
			}
		if(strstr($value,'away.php')) 
			{
			$pieces=explode(' ',$key);
			if(stristr($pieces[0],'expmod')) $away['key']=$pieces[0].' '.$pieces[1];
			else $away['key']=$pieces[0];
			}
		if(strstr($value,'agent.php')) 
			{
			$pieces=explode(' ',$key);
			if(stristr($pieces[0],'expmod')) $agent['key']=$pieces[0].' '.$pieces[1];
			else $agent['key']=$pieces[0];
			}
		if(strstr($value,'follow.php')) 
			{
			$pieces=explode(' ',$key);
			if(stristr($pieces[0],'expmod')) $follow['key']=$pieces[0].' '.$pieces[1];
			else $follow['key']=$pieces[0];
			}
		if(strstr($value,'park.php') and $is_sip_notify_supported) 
			{
			$pieces=explode(' ',$key);
			if(stristr($pieces[0],'expmod')) $parking['key']=$pieces[0].' '.$pieces[1];
			else $parking['key']=$pieces[0];
			Aastra_add_parking_Asterisk($user);
			}
		if(strstr($value,'vmail.php') and $is_sip_notify_supported) 
			{
			$url=parse_url($value);
			parse_str($url['query'],$parse);
			if($parse['user']!='') 
				{
				Aastra_add_vmail_Asterisk($parse['user'],$user,$count);
				$pieces=explode(' ',$key);
				if(stristr($pieces[0],'expmod'))
					{
					$vmail[$parse['user']]['key']=$pieces[0].' '.$pieces[1];
					if(isset($array_config[$pieces[0].' '.$pieces[1].' label'])) $vmail[$parse['user']]['label']=$array_config[$pieces[0].' '.$pieces[1].' label'];
					else $vmail[$parse['user']]['label']='';
					}
				else
					{
					if(stristr($pieces[0],'key'))
						{
						$vmail[$parse['user']]['key']=$pieces[0];
						if(isset($array_config[$pieces[0].' label'])) $vmail[$parse['user']]['label']=$array_config[$pieces[0].' label'];
						else $vmail[$parse['user']]['label']='';
						}
					}
				}
			}
		}
	}

# Cache them for the device not the user
Aastra_save_user_context($user,'cfwd',$cfwd);
Aastra_save_user_context($user,'dnd',$dnd);
Aastra_save_user_context($user,'daynight',$daynight);
Aastra_save_user_context($user,'away',$away);
Aastra_save_user_context($user,'agent',$agent);
Aastra_save_user_context($user,'follow',$follow);
Aastra_save_user_context($user,'parking',$parking);
Aastra_save_user_context($user,'vmail',$vmail);
}

function update_user_keys($user)
{
# Get MAC address and type of phone
$header=Aastra_decode_HTTP_header();

# Read config file
$array_temp=Aastra_readCFGfile(AASTRA_TFTP_DIRECTORY.'/'.$header['mac'].'.cfg','#',':');
$array_config=$array_temp[''];

# Update user keys
$keys=Aastra_get_user_context($user,'keys');
foreach($array_config as $key=>$value) 
	{
	if(stristr($key,'key')) $array_key[$key]=$value;
	}
$keys[$header['model']]=$array_key;
Aastra_save_user_context($user,'keys',$keys);
}

#############################################################################
# Main code
#############################################################################
# Retrieve parameters
$user=Aastra_getvar_safe('user');
$action=Aastra_getvar_safe('action','check');
$page=Aastra_getvar_safe('page');

# Trace
Aastra_trace_call('sync_asterisk','user='.$user.', action='.$action);

# Test User Agent
if($AA_FREEPBX_MODE=='1') Aastra_test_phone_version('1.4.2.',0);
else Aastra_test_phone_version('2.5.3.',0);

# Get Language
$language=Aastra_get_language();

# Get header information
$header=Aastra_decode_HTTP_header();

# Depending on action
switch($action)
	{
	# First Registration
	case 'register':
		# Bug 6739i on first registration
		if($header['model']=='Aastra6739i')
			{
			$register=Aastra_get_user_context($user,'register');
			$time=time();
			if(($time-$register)<2)
				{
				require_once('AastraIPPhoneExecute.class.php');
				$object=new AastraIPPhoneExecute();
				$object->addEntry('');
				$object->output();
				exit;
				}
			else Aastra_save_user_context($user,'register',$time);
			}

		# User and extension mode
		if($AA_FREEPBX_MODE=='1')
			{
			# Store the signature and update phone data
			Aastra_store_signature($user);
			Aastra_update_HDconfig_file(AASTRA_PATH_CACHE.'startup_asterisk.cfg',$user,$header);

			# Init keys from MAC.cfg
			init_special_keys($user);

			# Update user keys
			update_user_keys($user);

			# Sync applications
			$object=sync_apps($user,'register');
			}
		else
			{
			# Retrieve device and user
			$device=$user;
			$user=Aastra_get_userdevice_Asterisk($device);

			# Get device context
			$data=Aastra_get_user_context($device,'login_out');
			$last_user=$data['user'];

			# Generate the dynamic configuration
			$config=generate_user_config($device,$user,$last_user);

			# Save device context
			$data['user']=$user;
			$data['config']=$config;
			Aastra_save_user_context($device,'login_out',$data);

			# Create the XML object
			require_once('AastraIPPhoneExecute.class.php');
			$object=new AastraIPPhoneExecute();

			# Real configuration
			if($config!=NULL)
				{
				# Send a check
				if($user!='') $object->addEntry($XML_SERVER.'?user='.$device.'&action=check2');
				if($user!=$last_user)
					{
					# Clear display
					if(Aastra_is_local_reset_supported())
						{
						$object->addEntry('Command: ClearCallersList');
						$object->addEntry('Command: ClearDirectory');
						$object->addEntry('Command: ClearRedialList');
						}
					$object->addEntry($XML_SERVER.'?action=purge_msg');
					}
				if($user=='')
					{
					if(Aastra_is_ledcontrol_supported())
						{
						$cfwd=Aastra_get_user_context($device,'cfwd');
						if($cfwd['key']!='') $object->AddEntry('Led: '.$cfwd['key'].'=off');
						$dnd=Aastra_get_user_context($device,'dnd');
						if($dnd['key']!='') $object->AddEntry('Led: '.$dnd['key'].'=off');
						$daynight=Aastra_get_user_context($device,'daynight');
						foreach($daynight['key'] as $value) $object->AddEntry('Led: '.$value.'=off');
						$away=Aastra_get_user_context($device,'away');
						if($away['key']!='') $object->AddEntry('Led: '.$away['key'].'=off');
						$agent=Aastra_get_user_context($device,'agent');
						if($agent['key']!='') $object->AddEntry('Led: '.$agent['key'].'=off');
						$follow=Aastra_get_user_context($device,'follow');
						if($follow['key']!='') $object->AddEntry('Led: '.$follow['key'].'=off');
						$parking=Aastra_get_user_context($device,'parking');
						if($parking['key']!='') $object->AddEntry('Led: '.$parking['key'].'=off');
						$vmail=Aastra_get_user_context($device,'vmail');
						foreach($vmail as $box=>$value) if($value['key']!='') $object->AddEntry('Led: '.$value['key'].'=off');
						}

					# Clear the critical keys
					Aastra_save_user_context($device,'cfwd',NULL);
					Aastra_save_user_context($device,'dnd',NULL);
					Aastra_save_user_context($device,'daynight',NULL);
					Aastra_save_user_context($device,'away',NULL);
					Aastra_save_user_context($device,'agent',NULL);
					Aastra_save_user_context($device,'follow',NULL);
					Aastra_save_user_context($device,'parking',NULL);
					Aastra_save_user_context($device,'vmail',NULL);
					}

				# How many pages?
				$last=intval(count($config)/AASTRA_MAXCONFIGURATIONS);
				if((count($config)-$last*AASTRA_MAXCONFIGURATIONS) != 0) $last++;

				# Prepare the request for the configuration
				for($i=1;$i<=$last;$i++) $object->addEntry($XML_SERVER.'?user='.$device.'&action=configuration&page='.$i);
				}
			else
				{
				# Do nothing
				$object->addEntry('');
				}
			}
		break;

	# Polling
	case 'check':
	case 'check2':
		# User and extension mode
		if($AA_FREEPBX_MODE=='1')
			{
			# Sync applications
			$object=sync_apps($user,'check');
			}
		else
			{
			# Retrieve device and user
			$device=$user;
			$user=Aastra_get_userdevice_Asterisk($device);

			# Retrieve new configuration
			$data=Aastra_get_user_context($device,'login_out');
			$last_user=$data['user'];

			# New user?
			if($user!=$last_user) 
				{
				require_once('AastraIPPhoneExecute.class.php');
				$object=new AastraIPPhoneExecute();
				$object->addEntry($XML_SERVER.'?user='.$device.'&action=register');
				}
			else
				{
				# Sync the applications
				if($user!='')	
					{
					if($action=='check') $object=sync_apps($device,'check');
					else $object=sync_apps($device,'register');
					}
				else 
					{
					# Do nothing
					require_once('AastraIPPhoneExecute.class.php');
					$object=new AastraIPPhoneExecute();
					$object->addEntry('');
					}
				}
			}
		break;

	# Notify
	case 'notify':
		# Retrieve Notify to-do list
		$data=Aastra_get_user_context($user,'notify');
		require_once('AastraIPPhoneExecute.class.php');
		$object=new AastraIPPhoneExecute();
		if(isset($data['keys']))
			{
			if($data['keys']=='1') 
				{
				$object->addEntry($XML_SERVER_PATH.'key.php?action=msg&user='.$user);
				if(Aastra_is_ledcontrol_supported()) $object->addEntry($XML_SERVER_PATH.'key.php?action=led&user='.$user);
				$object->addEntry($XML_SERVER_PATH.'key.php?action=configuration&user='.$user);
				$data['keys']='0';
				}
			}
		if(isset($data['dnd']))
			{
			if($data['dnd']=='1')
				{
				$dnd=Aastra_get_user_context($user,'dnd');
				if(($dnd['key']!='') or ($header['mac']=='Aastra51i')) $object->addEntry($XML_SERVER_PATH.'dnd.php?action=check&user='.$user);
				$data['dnd']='0';
				}
			}
		if(isset($data['cfwd']))
			{
			if($data['cfwd']=='1')
				{
				$cfwd=Aastra_get_user_context($user,'cfwd');
				if(($cfwd['key']!='') or ($header['mac']=='Aastra51i')) $object->addEntry($XML_SERVER_PATH.'cfwd.php?action=check&user='.$user);
				$data['cfwd']='0';
				}
			}
		if(isset($data['daynight']))
			{
			if($data['daynight']=='1')
				{
				$daynight=Aastra_get_user_context($user,'daynight');
				if((Aastra_is_daynight_notify_allowed_Asterisk($user)) or ($header['mac']=='Aastra51i')) $object->addEntry($XML_SERVER_PATH.'daynight.php?action=check&user='.$user);
				$data['daynight']='0';
				}
			}
		if(isset($data['away']))
			{
			if($data['away']=='1')
				{
				$away=Aastra_get_user_context($user,'away');
				if(($away['key']!='') or ($header['mac']=='Aastra51i')) $object->addEntry($XML_SERVER_PATH.'away.php?action=check&user='.$user);
				$data['away']='0';
				}
			}
		if(isset($data['agent']))
			{
			if($data['agent']=='1')
				{
				$agent=Aastra_get_user_context($user,'agent');
				if(($agent['key']!='') or ($header['mac']=='Aastra51i')) $object->addEntry($XML_SERVER_PATH.'agent.php?action=check&agent='.$user);
				$data['agent']='0';
				}
			}
		if(isset($data['follow']))
			{
			if($data['follow']=='1')
				{
				$agent=Aastra_get_user_context($user,'follow');
				if(($agent['key']!='') or ($header['mac']=='Aastra51i')) $object->addEntry($XML_SERVER_PATH.'follow.php?action=check&user='.$user);
				$data['follow']='0';
				}
			}
		if(isset($data['message']))
			{
			if($data['message']=='1') 
				{
				$object->addEntry($XML_SERVER.'?action=send&user='.$user);
				$data['message']='0';
				}
			}
		if(isset($data['parking']))
			{
			if($data['parking']=='1') 
				{
				$parking=Aastra_get_user_context($user,'parking');
				if(($parking['key']!='') and Aastra_is_ledcontrol_supported()) $object->addEntry('Led: '.$parking['key'].'='.Aastra_get_user_context('parking','status'));
				$data['parking']='0';
				}
			}
		if(isset($data['vmail']))
			{
			if($data['vmail']=='1') 
				{
				$vmail=Aastra_get_user_context($user,'vmail');
				$status=Aastra_get_user_context('vmail','user');
				foreach($vmail as $box=>$value) 
					{
					if(Aastra_is_ledcontrol_supported()) $object->addEntry('Led: '.$value['key'].'='.$status[$box]['status']);
					else 
						{
						if($status[$box]['msg']==0) $label=$value['label'];
						else $label=sprintf('%s(%s)',$value['label'],$status[$box]['msg']);
						$array_config[$value['key'].' label']=$label;
						}
					}
				if(isset($array_config))
					{
					if(count($array_config)>0)
						{
						Aastra_save_user_context($user,'vm_label',$array_config);
						$object->addEntry($XML_SERVER.'?user='.$user.'&action=conf_vm');
						}
					}
				$data['vmail']='0';
				}
			}
		if(isset($data['logout']))
			{
			if($data['logout']=='1') 
				{
				$object->addEntry($XML_SERVER_PATH.'logout.php?action=logout&user='.$user);
				$data['logout']='0';
				}
			}
		if(isset($data['forced_logout']))
			{
			if($data['forced_logout']=='1') 
				{
				$object->addEntry($XML_SERVER_PATH.'logout.php?action=forced_logout&user='.$user);
				$data['forced_logout']='0';
				}
			}
		if(isset($data['auto_logout']))
			{
			if($data['auto_logout']=='1') 
				{
				$object->addEntry($XML_SERVER_PATH.'logout.php?action=check&user='.$user);
				$data['auto_logout']='0';
				}
			}
		if(isset($data['userdevice']))
			{
			if($data['userdevice']=='1') 
				{
				if($AA_FREEPBX_MODE=='2') $object->addEntry($XML_SERVER.'?user='.$user.'&action=register');
				$data['userdevice']='0';
				}
			}
		if(isset($data['meetmejoin']))
			{
			if(($data['meetmejoin']=='1') and ($AA_FREEPBX_MODE=='1') and (Aastra_is_softkeys_supported()))
				{
				$object->addEntry($XML_SERVER_PATH.'meetme.php?action=select&number=$$REMOTENUMBER$$&ext='.$user);
				$data['meetmejoin']='0';
				}
			}
		if(isset($data['meetmeleave']))
			{
			if(($data['meetmeleave']=='1') and ($AA_FREEPBX_MODE=='1') and (Aastra_is_softkeys_supported())) 
				{
				$object->addEntry('');
				$object->setTriggerDestroyOnExit('');
				$data['meetmeleave']='0';
				}
			}
		Aastra_save_user_context($user,'notify',$data);
		break;

	# Message
	case 'send':
		# Retrieve Message
		$data=Aastra_get_user_context($user,'message');

		# Send message
		if(($data['long']!='') and ($data['short']!=''))
			{
			require_once('AastraIPPhoneStatus.class.php');
			$object=new AastraIPPhoneStatus();
			$object->setBeep();
			$object->setSession('message');
			if(Aastra_size_display_line()>20) 
				{
				if(Aastra_is_status_uri_supported() and ($data['uri']!='')) $object->addEntry('0',$data['long'],'alert','10',$data['uri']);
				else $object->addEntry('0',$data['long'],'alert','10');
				}
			else $object->addEntry('0',$data['short'],'alert','10');

			# Delete message
			Aastra_save_user_context($user,'message',NULL);
			}
		else
			{
			require_once('AastraIPPhoneExecute.class.php');
			$object=new AastraIPPhoneExecute();
			$object->addEntry('');
			}
		break;

	# Purge Message status
	case 'purge_msg':
		require_once('AastraIPPhoneStatus.class.php');
		$object=new AastraIPPhoneStatus();
		$object->setSession('aastra-xml');
		$array=Aastra_get_status_index_Asterisk('');
		foreach($array as $key=>$value) $object->addEntry($key,'');
		break;

	# Conf VM (9480i/9480iCT)
	case 'conf_vm':
		# Retrieve new configuration
		$data=Aastra_get_user_context($user,'vm_label');

		# Configuration XML object
		require_once('AastraIPPhoneConfiguration.class.php');
		$object=new AastraIPPhoneConfiguration();

		# Send the configuration
		foreach($data as $key=>$value) $object->addEntry($key,$value);
		break;

	# Configuration
	case 'configuration':
		# Retrieve device and user
		$device=$user;
		$user=Aastra_get_userdevice_Asterisk($device);

		# Retrieve new configuration
		$data=Aastra_get_user_context($device,'login_out');
		$config=$data['config'];

		# Configuration XML object
		require_once('AastraIPPhoneConfiguration.class.php');
		$object=new AastraIPPhoneConfiguration();

		# Send the partial configuration
		$index=1;
		foreach($config as $key=>$value)
			{
			if(($index>=(($page-1)*AASTRA_MAXCONFIGURATIONS+1)) and ($index<=$page*AASTRA_MAXCONFIGURATIONS)) $object->addEntry($key,$value);
			$index++;
			}
		break;

	# Unexpected
	default:
		Aastra_debug('Unexpected action='.$action);
		require_once('AastraIPPhoneExecute.class.php');
		$object=new AastraIPPhoneExecute();
		$object->addEntry('');
		break;
	}

# Send XML command
$object->output();
exit;
?>
