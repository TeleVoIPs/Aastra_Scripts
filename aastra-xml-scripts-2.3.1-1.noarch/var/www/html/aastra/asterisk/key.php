<?php
#############################################################################
# Key programming for users
#
# Aastra SIP Phones 2.5.3 or better
#
# Copyright 2010 Aastra Telecom Ltd
#
# Supported Aastra Phones
#   All phones
#
# Usage
#   script.php?user=XXX
#   XXX is the extension of the phone on the platform
#
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
require_once('AastraAsterisk.php');

#############################################################################
# Private functions
#############################################################################

# Read config file and load data
function init_config()
{
# Read config file
$array_config=Aastra_readINIfile('key.ini','#','=');

# Prepare special keys
foreach($array_config as $key=>$value)
	{
	if($value['defaulted keys']!='') $array_config[$key]['defaulted keys']=explode(',',$value['defaulted keys']);	
	}

# Return configuration
return($array_config);
}

function init_keys($user,$header)
{
Global $array_config;

# Phone supported?
if($array_config[$header['model']]!=NULL)
	{
	if($array_config[$header['model']]['top key']=='1')
		{
		for($i=1;$i<=$array_config[$header['model']]['top key number'];$i++)
			{
			$array_key[$array_config[$header['model']]['top key type'].$i]['key']=$i;
			$array_key[$array_config[$header['model']]['top key type'].$i]['internal']=$array_config[$header['model']]['top key type'];
			$array_key[$array_config[$header['model']]['top key type'].$i]['max']=$array_config[$header['model']]['top key number'];
			$array_key[$array_config[$header['model']]['top key type'].$i]['type']='';
			$array_key[$array_config[$header['model']]['top key type'].$i]['label']='';
			$array_key[$array_config[$header['model']]['top key type'].$i]['value']='';
			$array_key[$array_config[$header['model']]['top key type'].$i]['states']='';
			$array_key[$array_config[$header['model']]['top key type'].$i]['locked']='';
			}
		}
	if($array_config[$header['model']]['bottom key']=='1')
		{
		for($i=1;$i<=$array_config[$header['model']]['bottom key number'];$i++)
			{
			$array_key[$array_config[$header['model']]['bottom key type'].$i]['key']=$i;
			$array_key[$array_config[$header['model']]['bottom key type'].$i]['internal']=$array_config[$header['model']]['bottom key type'];
			$array_key[$array_config[$header['model']]['bottom key type'].$i]['max']=$array_config[$header['model']]['bottom key number'];
			$array_key[$array_config[$header['model']]['bottom key type'].$i]['type']='';
			$array_key[$array_config[$header['model']]['bottom key type'].$i]['label']='';
			$array_key[$array_config[$header['model']]['bottom key type'].$i]['value']='';
			$array_key[$array_config[$header['model']]['bottom key type'].$i]['states']='';
			$array_key[$array_config[$header['model']]['bottom key type'].$i]['locked']='';
			}
		}
	}

# Expansion modules
for($module=1;$module<=3;$module++)
	{
	$end=$array_config[$header['module'][$module]]['key number'];
	for($i=1;$i<=$end;$i++)
		{
		$array_key['expmod'.$module.' key'.$i]['key']=$i;
		$array_key['expmod'.$module.' key'.$i]['internal']='expmod'.$module.' key';
		$array_key['expmod'.$module.' key'.$i]['max']=$end;
		$array_key['expmod'.$module.' key'.$i]['type']='';
		$array_key['expmod'.$module.' key'.$i]['label']='';
		$array_key['expmod'.$module.' key'.$i]['value']='';
		$array_key['expmod'.$module.' key'.$i]['locked']='';
		}
	if($header['module'][$module]=='560M')
		{
		$array_key['expmod'.$module.'page1left']['value']=Aastra_get_label('LIST 1',$language);
		$array_key['expmod'.$module.'page1right']['value']=Aastra_get_label('LIST 2',$language);
		$array_key['expmod'.$module.'page2left']['value']=Aastra_get_label('LIST 3',$language);
		$array_key['expmod'.$module.'page2right']['value']=Aastra_get_label('LIST 4',$language);
		$array_key['expmod'.$module.'page3left']['value']=Aastra_get_label('LIST 5',$language);
		$array_key['expmod'.$module.'page3right']['value']=Aastra_get_label('LIST 6',$language);
		$array_key['expmod'.$module.'page1left']['locked']=1;
		$array_key['expmod'.$module.'page1right']['locked']=1;
		$array_key['expmod'.$module.'page2left']['locked']=1;
		$array_key['expmod'.$module.'page2right']['locked']=1;
		$array_key['expmod'.$module.'page3left']['locked']=1;
		$array_key['expmod'.$module.'page3right']['locked']=1;
		}
	}

# Retrieve user keys and page labels
$filename=AASTRA_TFTP_DIRECTORY.'/'.$header['mac'].'.cfg';
if(file_exists($filename))
	{
	$array_user=Aastra_readINIfile($filename, '#', ':');
	foreach($array_key as $key=>$value)
		{
		if(isset($array_user[''][$key.' type'])) 
			{
			$array_key[$key]['type']=$array_user[''][$key.' type'];
			if(isset($array_user[''][$key.' label'])) $array_key[$key]['label']=$array_user[''][$key.' label'];
			if(isset($array_user[''][$key.' value'])) $array_key[$key]['value']=$array_user[''][$key.' value'];
			if(isset($array_user[''][$key.' states'])) $array_key[$key]['states']=$array_user[''][$key.' states'];
			if(isset($array_user[''][$key.' locked'])) $array_key[$key]['locked']=$array_user[''][$key.' locked'];
			else $array_key[$key]['locked']='0';
			}
		if(isset($array_user[''][$key])) $array_key[$key]['value']=$array_user[''][$key];
		}
	}

# Check aastra.cfg for other defaulted keys
$filename=AASTRA_TFTP_DIRECTORY.'/aastra.cfg';
if(file_exists($filename))
	{
	$array_user=Aastra_readINIfile($filename, '#', ':');
	foreach($array_key as $key=>$value)
		{
		if(isset($array_user[''][$key.' type'])) 
			{
			if(!in_array($key,$array_config[$header['model']]['defaulted keys'])) $array_config[$header['model']]['defaulted keys'][]=$key;
			}
		}
	}

# Return current key configuration
return($array_key);
}

#######################################################################
function remove_saved($array_key,$user,$header)
{
# No update by default
$update=False;

# Retrieve saved keys
$keys_saved=Aastra_get_user_context($user,'keys_saved');

# If some saved keys
if(count($keys_saved[$header['model']])!=0)
	{
	foreach($keys_saved[$header['model']] as $key=>$value)
		{
		foreach($array_key as $key2=>$value2)
			{
			if(($value['value']==$value2['value']) and ($value['type']==$value2['type']))
				{
				unset($keys_saved[$header['model']][$key]);
				$update=True;
				break;
				}
			}
		}
	}

# Update if needed
if($update) Aastra_save_user_context($user,'keys_saved',$keys_saved);
}

#######################################################################
function update_special_keys($user,$array_config)
{
# Return array
$return=array();

# All the functions
$list=array('cfwd','dnd','daynight','away','agent','follow','parking');

# Get current data
foreach($list as $value) $array1[$value]=Aastra_get_user_context($user,$value);
$count=!Aastra_is_ledcontrol_supported();

# Remove vmail
Aastra_remove_vmail_Asterisk('',$user,$count);

# Some data
if($array_config!=NULL)
	{
	foreach($array_config as $key=>$value) 
		{
		if(strstr($value,'dnd.php')) 
			{
			$pieces=explode(' ',$key);
			if(stristr($pieces[0],'expmod')) $array2['dnd']['key']=$pieces[0].' '.$pieces[1];
			else $array2['dnd']['key']=$pieces[0];
			}
		if(strstr($value,'cfwd.php')) 
			{
			$pieces=explode(' ',$key);
			if(stristr($pieces[0],'expmod')) $array2['cfwd']['key']=$pieces[0].' '.$pieces[1];
			else $array2['cfwd']['key']=$pieces[0];
			}
		if(strstr($value,'daynight.php')) 
			{
			$pieces=explode(' ',$key);
			if(stristr($pieces[0],'expmod')) $key=$pieces[0].' '.$pieces[1];
			else $key=$pieces[0];
			$url=parse_url($value);
			parse_str($url['query'],$parse);
			if($parse['index']!='') $array2['daynight']['key'][$parse['index']]=$key;
			else $array2['daynight']['key']['ALL']=$key;
			}
		if(strstr($value,'away.php')) 
			{
			$pieces=explode(' ',$key);
			if(stristr($pieces[0],'expmod')) $array2['away']['key']=$pieces[0].' '.$pieces[1];
			else $array2['away']['key']=$pieces[0];
			}
		if(strstr($value,'agent.php')) 
			{
			$pieces=explode(' ',$key);
			if(stristr($pieces[0],'expmod')) $array2['agent']['key']=$pieces[0].' '.$pieces[1];
			else $array2['agent']['key']=$pieces[0];
			}
		if(strstr($value,'follow.php')) 
			{
			$pieces=explode(' ',$key);
			if(stristr($pieces[0],'expmod')) $array2['follow']['key']=$pieces[0].' '.$pieces[1];
			else $array2['follow']['key']=$pieces[0];
			}
		if(strstr($value,'park.php')) 
			{
			$pieces=explode(' ',$key);
			if(stristr($pieces[0],'expmod')) $array2['parking']['key']=$pieces[0].' '.$pieces[1];
			$array2['parking']['key']=$pieces[0];
			}
		if(strstr($value,'vmail.php')) 
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
					$vmail[$parse['user']]['label']=$array_config[$pieces[0].' '.$pieces[1].' label'];
					}
				else
					{
					$vmail[$parse['user']]['key']=$pieces[0];
					$vmail[$parse['user']]['label']=$array_config[$pieces[0].' label'];
					}
				}
			}
		}
	}

# Compare before and after
$notify=Aastra_get_user_context($user,'notify');
foreach($list as $value) 
	{
	if($value!='daynight')
		{
		if($array1[$value]['key']!=$array2[$value]['key'])
			{
			Aastra_save_user_context($user,$value,$array2[$value]);
			$notify[$value]='1';
			if($value=='parking')
				{
				Aastra_remove_parking_Asterisk($user);
				if($array2[$value]['key']!='') Aastra_add_parking_Asterisk($user);
				}
			else $return[]=$value;
			}
		}
	else
		{
		$list2=array('0','1','2','3','4','5','6','7','8','9','ALL');
		$diff=False;
		foreach($list2 as $value2)
			{
			if($array1[$value]['key'][$value2]!=$array2[$value]['key'][$value2])
				{
				$diff=True;
				break;
				}
			}
		if($diff) 
			{
			Aastra_save_user_context($user,$value,$array2[$value]);
			$notify[$value]='1';
			$return[]=$value;
			}
		}
	}

# Update vmail anyways
Aastra_save_user_context($user,'vmail',$vmail);

# Prepare notification
$notify['vmail']='1';
Aastra_save_user_context($user,'notify',$notify);

# Return changes
return($return);
}

#######################################################################
function update_user_config($user,$array_key,$header,$selection1,$selection2=NULL,$type='key')
{
# No special key yet
$special=False;

# Retrieve current configuration
$array_temp=Aastra_readINIfile(AASTRA_TFTP_DIRECTORY.'/'.$header['mac'].'.cfg', '#', ':');
$array_user=$array_temp[''];

# Prepare process of both keys
if($selection2) $array_selection=array($selection1,$selection2);
else $array_selection=array($selection1);

# Process both changes
foreach($array_selection as $selection)
	{
	# Key update
	if($type=='key')
		{
		# Update selection 
		if(($array_key[$selection]['type']!='') and ($array_key[$selection]['type']!='empty'))
			{
			# XML?
			if($array_key[$selection]['type']=='xml') $special=True;

			# Update configuration (MAC.cfg)
			$array_user[$selection.' type']=$array_key[$selection]['type'];
			$array_user[$selection.' value']=$array_key[$selection]['value'];
			$array_user[$selection.' label']=$array_key[$selection]['label'];
			if($array_key[$selection]['states']!='') $array_user[$selection.' states']=$array_key[$selection]['states'];
			else unset($array_user[$selection.' states']);
			if($array_key[$selection]['locked']!='') $array_user[$selection.' locked']=$array_key[$selection]['locked'];
			else unset($array_user[$selection.' locked']);
		
			# Update configuration (dynamic)
			$array_update[]=array('param'=>$selection.' type','value'=>$array_user[$selection.' type']);
			$array_update[]=array('param'=>$selection.' value','value'=>$array_user[$selection.' value']);
			$array_update[]=array('param'=>$selection.' label','value'=>$array_user[$selection.' label']);
			if($array_key[$selection]['states']!='') $array_update[]=array('param'=>$selection.' states','value'=>$array_user[$selection.' states']);
			if($array_key[$selection]['locked']!='') $array_update[]=array('param'=>$selection.' locked','value'=>$array_user[$selection.' locked']);
			}
		else
			{
			# Maybe we changed a special key
			if($array_user[$selection.' type']=='xml') $special=True;
	
			# Key is not programmed but maybe locked
			if($array_key[$selection]['type']=='') unset($array_user[$selection.' type']);
			else $array_user[$selection.' type']='empty';
			if($array_key[$selection]['locked']=='1') $array_user[$selection.' locked']=$array_key[$selection]['locked'];
			else unset($array_user[$selection.' locked']);
			unset($array_user[$selection.' value']);
			unset($array_user[$selection.' label']);
			unset($array_user[$selection.' states']);

			# Update array
			$array_update[]=array('param'=>$selection.' type','value'=>$array_user[$selection.' type']);
			$array_update[]=array('param'=>$selection.' value','value'=>'');
			$array_update[]=array('param'=>$selection.' label','value'=>'');
			$array_update[]=array('param'=>$selection.' locked','value'=>$array_user[$selection.' locked']);
			$array_update[]=array('param'=>$selection.' states','value'=>'');
			}
		}
	else
		{
		# Update parameter
		$array_user[$selection]=$array_key[$selection]['value'];
		$array_update[]=array('param'=>$selection,'value'=>$array_key[$selection]['value']);
		}
	}

# Update MAC.cfg
$write=@fopen(AASTRA_TFTP_DIRECTORY.'/'.$header['mac'].'.cfg', 'w');
if($write)
	{
	# Dump the config file
	foreach($array_user as $key=>$value) fputs($write,$key.': '.$value."\n");

	# Close the MAC.cfg file
	fclose($write);

	# Update the user keys
	$keys=Aastra_get_user_context($user,'keys');
	foreach($array_user as $key=>$value) 
		{
		if(stristr($key,'key')) $array_keys[$key]=$value;
		if(stristr($key,'expmod') and stristr($key,'page')) $array_keys[$key]=$value;
		}
	$keys[$header['model']]=$array_keys;
	Aastra_save_user_context($user,'keys',$keys);
	}

# Update special keys
if($special) $array_special=update_special_keys($user,$array_user);

# Save Configuration update
if(!$special) 
	{
	$array_selection=NULL;
	$array_special_keys=NULL;
	}
$array_session=array('keys' => base64_encode(serialize(array($array_selection,$array_update,$array_special))));
Aastra_save_session('key','120',$array_session);

# Return the need for notify
return($special);
}

function extra_keys($user,$header,$array_saved,$array_user)
{
Global $AA_XML_SERVER,$AA_PROXY_SERVER,$AA_REGISTRAR_SERVER,$AA_XMLDIRECTORY;

# Retrieve user profile
$profile=Aastra_get_startup_profile_Asterisk($user);
$array_profile=Aastra_readCFGfile($profile.'-user.prf', '#', ':');

# Retrieve extra keys
$array_extra=array();
foreach($array_profile['Extra'] as $key=>$value)
	{
	if(strstr($key,'extrakey'))
		{
		$explode=explode(' ',$key);
		switch($explode[1])
			{
			case 'type':
			case 'label':
			case 'value':
			case 'states':
			case 'exclude':
				$array_extra[$explode[0]][$explode[1]]=$value;
				break;
			}
		}
	}

# Prepare replace strings
$search=array('/\$\$AA_XML_SERVER_AA\$\$/',
		'/\$\$AA_PROXY_SERVER_AA\$\$/',
		'/\$\$AA_REGISTRAR_SERVER_AA\$\$/',
		'/\$\$AA_XMLDIRECTORY_AA\$\$/'
		);
$replace=array($AA_XML_SERVER,
		$AA_PROXY_SERVER,
		$AA_REGISTRAR_SERVER,
		$AA_XMLDIRECTORY
		);

# Filter extra keys 
foreach($array_extra as $key=>$value)
	{
	if(!isset($value['type']) or !isset($value['label']) or !isset($value['value'])) unset($array_extra[$key]);
	else 
		{
		if($array_extra[$key]['type']=='xml')$array_extra[$key]['value']=preg_replace($search,$replace,$array_extra[$key]['value']);
		if(isset($value['exclude']))
			{
			$explode=explode(',',$value['exclude']);
			if(in_array($header['model'],$explode)) unset($array_extra[$key]);
			} 
		}
	}

# Filter compare to deleted keys
if(isset($array_saved[$header['model']]))
	{
	foreach($array_saved[$header['model']] as $key=>$value) 
		{
		foreach($array_extra as $key2=>$value2)
			{
			if(($value['type']==$value2['type']) and ($value['value']==$value2['value']))
				{
				unset($array_extra[$key2]);
				break;
				}
			}
		}
	}

# Filter compare to current keys
if(isset($array_user))
	{
	foreach($array_user as $key=>$value) 
		{
		foreach($array_extra as $key2=>$value2)
			{
			if(($value['type']==$value2['type']) and ($value['value']==$value2['value']))
				{
				unset($array_extra[$key2]);
				break;
				}
			}
		}
	}

# Return extra keys
return($array_extra);
}

#############################################################################
# Active code
#############################################################################

# Retrieve parameters
$user=Aastra_getvar_safe('user');
$action=Aastra_getvar_safe('action','init');
$selection=Aastra_getvar_safe('selection');
$step=Aastra_getvar_safe('step','1');
$input1=Aastra_getvar_safe('input1');
$input2=Aastra_getvar_safe('input2');
$input3=Aastra_getvar_safe('input3');
$type=Aastra_getvar_safe('type','bottom');
$page=Aastra_getvar_safe('page');
$set=Aastra_getvar_safe('set','1');
$default=Aastra_getvar_safe('default');
if(($default=='') and ($page=='')) $default=$selection;
if($page=='') $page='1';

# Trace
Aastra_trace_call('key_asterisk','user='.$user.', action='.$action.', selection='.$selection.', step='.$step.', input1='.$input1.', input2='.$input2.', type='.$type.', page='.$page.', default='.$default);

# Test User Agent
Aastra_test_phone_versions(array('1'=>'','2'=>'','3'=>'2.5.3.','4'=>'2.5.3.','5'=>'3.0.1.'),'0');

# Authenticate user
Aastra_check_signature_Asterisk($user);

# Get language
$language=Aastra_get_language();

# Get MAC address and type of phone
$header=Aastra_decode_HTTP_header();

# Global parameter
$is_multipleinputfields=Aastra_is_multipleinputfields_supported();
$nb_softkeys=Aastra_number_softkeys_supported();
$is_icons=Aastra_is_icons_supported();

# TEST for freePBX mode
if($AA_FREEPBX_MODE=='2')
	{
	# Display error
	require_once('AastraIPPhoneTextScreen.class.php');
	$object=new AastraIPPhoneTextScreen();
	$object->setDestroyOnExit();
	$object->setTitle(Aastra_get_label('Configuration Error',$language));
	$object->setText(Aastra_get_label('This application is not supported in device/user mode, please contact your administrator.',$language));
	if($nb_softkeys)
		{
		if($nb_softkeys==6) $object->addSoftkey('6',Aastra_get_label('Exit',$language),'SoftKey:Exit');
		else $object->addSoftkey('10',Aastra_get_label('Exit',$language),'SoftKey:Exit');
		}
	$object->output();
	exit;
	}

# Check user mode
if($user=='')
	{
	# Display error
	require_once('AastraIPPhoneTextScreen.class.php');
	$object=new AastraIPPhoneTextScreen();
	$object->setDestroyOnExit();
	$object->setTitle(Aastra_get_label('Configuration Error',$language));
	$object->setText(Aastra_get_label('Missing parameter in the command line, please contact your administrator.',$language));
	if($nb_softkeys)
		{
		if($nb_softkeys==6) $object->addSoftkey('6',Aastra_get_label('Exit',$language),'SoftKey:Exit');
		else $object->addSoftkey('10',Aastra_get_label('Exit',$language),'SoftKey:Exit');
		}
	$object->output();
	exit;
	}
else 
	{
	# Keep callback
	$XML_SERVER.='?user='.$user;
	}

# Get MAC address and type of phone
$header=Aastra_decode_HTTP_header();

# Compute MaxLines
if($nb_softkeys)$MaxLines=AASTRA_MAXLINES;
else $MaxLines=AASTRA_MAXLINES-3;

# INIT keys
if($action!='update')
	{
	# Read configuration files
	$array_config=init_config();
	$array_key=init_keys($user,$header);
	}

# Process pre-action
switch($action)
	{
	# INIT
	case 'init':
		# Remove saved keys
		remove_saved($array_key,$user,$header);
		$action='main';
		break;

	# SET LABEL
	case 'set_label':
		# Label must be set
		if($input1!='')
			{
			# Keep new label
			$array_key[$selection]['label']=$input1;

			# Update everything
			update_user_config($user,$array_key,$header,$selection);
			$update_notify=False;
			$update=1;
			$update_key1=$selection;
			$default=$selection;
			}
		else $action='label';
		break;	

	# SET TITLE
	case 'set_title':
		# Title must be set
		if($input1!='')
			{
			# Keep new label
			$array_key[$selection]['value']=$input1;

			# Update everything
			update_user_config($user,$array_key,$header,$selection,NULL,'parameter');
			$update_notify=False;
			$update=1;
			}
		else $action='title';
		break;	

	# EXTRA KEYS
	case 'extra':
		# Retrieve extra keys
		$array_extra=extra_keys($user,$header,NULL,NULL);

		# Retrieve new values
		$array_key[$selection]=$array_extra[$input1];
		if(isset($array_key[$selection]['exclude'])) unset($array_key[$selection]['exclude']);

		# Save changes
		update_user_config($user,$array_key,$header,$selection);
		$update_notify=True;
		$update=1;
		$update_key1=$selection;
		$default=$selection;
		break;	

	# RESTORE
	case 'restore':
		# Retrieve saved keys
		$keys=Aastra_get_user_context($user,'keys_saved');

		# Retrieve new values
		$array_key[$selection]=$keys[$header['model']][$input1];

		# Remove the key
		unset($keys[$header['model']][$input1]);
		Aastra_save_user_context($user,'keys_saved',$keys);

		# Save changes
		update_user_config($user,$array_key,$header,$selection);
		$update_notify=True;
		$update=1;
		$update_key1=$selection;
		$default=$selection;
		break;	

	# SET
	case 'set':
		# Label must not be empty
		if($input3!='')
			{
			# Retrieve new values
			$array_key[$selection]['type']=$input1;
			$array_key[$selection]['value']=$input2;
			$array_key[$selection]['label']=$input3;

			# Save changes
			update_user_config($user,$array_key,$header,$selection);
			$update_notify=False;
			$update=1;
			$update_key1=$selection;
			$default=$selection;
			}
		else
			{
			$action='edit';
			$step=3;
			}
		break;	
	
	# DOWN
	case 'down':
		# Initial key must be valid and not locked, target key must not be locked
		if(($array_key[$selection]['key']<$array_key[$selection]['max']) and ($array_key[$selection]['locked']!='1'))
			{
			# Find target key
			$selection2='';
			$index=1;
			while($selection2=='')
				{
				# Find target key
				$temp=$array_key[$selection]['internal'].($array_key[$selection]['key']+$index);
				if($array_key[$temp]['key']<=$array_key[$selection]['max'])
					{
					if($array_key[$temp]['locked']!='1') $selection2=$temp;
					}
				else break;
				if($selection2=='') $index++;
				}

			# Key found
			if($selection2!='')
				{
				# switch keys
				$temp=$array_key[$selection2];
				$array_key[$selection2]=$array_key[$selection];
				$array_key[$selection2]['key']+=$index;
				$array_key[$selection]=$temp;
				$array_key[$selection]['key']-=$index;

				# Save changes
				$update_notify=update_user_config($user,$array_key,$header,$selection,$selection2);
				$update=1;
				$update_key1=$selection;
				$update_key2=$selection2;

				# Set default;
				$default=$selection2;
				}
			else $action='nothing';
			}
		else $action='nothing';
		break;

	# UP
	case 'up':
		# Initial key must be valid and not locked, target key must not be locked
		if(($array_key[$selection]['key']>1) and ($array_key[$selection]['locked']!='1'))
			{
			# Find target key
			$selection2='';
			$index=1;
			while($selection2=='')
				{
				$temp=$array_key[$selection]['internal'].($array_key[$selection]['key']-$index);
				if($array_key[$temp]['key']>'0')
					{
					if($array_key[$temp]['locked']!='1') $selection2=$temp;
					}
				else break;
				if($selection2=='') $index++;
				}

			# Found a match
			if($selection2!='')
				{
				# Switch keys
				$temp=$array_key[$selection2];
				$array_key[$selection2]=$array_key[$selection];
				$array_key[$selection2]['key']-=$index;
				$array_key[$selection]=$temp;
				$array_key[$selection]['key']+=$index;

				# Save changes
				$update_notify=update_user_config($user,$array_key,$header,$selection,$selection2);
				$update=1;
				$update_key1=$selection;
				$update_key2=$selection2;

				# Set default;
				$default=$selection2;
				}
			else $action='nothing';
			}
		else $action='nothing';
		break;

	# CLEAR/EMPTY
	case 'clear':
	case 'empty':
		# Depending on key type
		switch($array_key[$selection]['type'])
			{
			# Available for user
			case 'blf':
			case 'blfxfer':
			case 'speeddial':
			case 'speeddialxfer':
			case 'speeddialconf':
				# Do nothing
				break;

			# Other keys
			default:
				# Update the user stored keys
				$keys=Aastra_get_user_context($user,'keys_saved');
				$return=Aastra_search2d($keys[$header['model']],$array_key[$selection]['value'],'value');
				if(!$return[0])
					{
					$array_temp['type']=$array_key[$selection]['type'];
					$array_temp['label']=$array_key[$selection]['label'];
					$array_temp['value']=$array_key[$selection]['value'];
					$array_temp['states']=$array_key[$selection]['states'];
					$keys[$header['model']][]=$array_temp;
					Aastra_save_user_context($user,'keys_saved',$keys);
					}
				break;
			}

		# Reset the key
		if($action=='clear') $array_key[$selection]['type']='';
		else $array_key[$selection]['type']='empty';
		$array_key[$selection]['label']='';
		$array_key[$selection]['value']='';
		$array_key[$selection]['states']='';
		$array_key[$selection]['locked']='';
	
		# Save changes
		$update_notify=update_user_config($user,$array_key,$header,$selection);
		$update=1;
		$update_key1=$selection;
		$default=$selection;
		break;
	}

# Process action
switch($action)
	{
	# DELETE KEYS
	case 'delete':
		# Key must be programmed and not locked
		if(($array_key[$selection]['type']!='') and ($array_key[$selection]['locked']!='1') and ($array_key[$selection]['type']!='empty'))
			{
			# Defaulted key?
			if(in_array($selection,$array_config[$header['model']]['defaulted keys']))
				{
				# No choice force "empty"
				require_once('AastraIPPhoneExecute.class.php');
				$object=new AastraIPPhoneExecute();
				$object->AddEntry($XML_SERVER.'&action=empty&type='.$type.'&page='.$page.'&set='.$set.'&selection='.$selection);
				}
			else
				{
				# No choice force "clear"
				require_once('AastraIPPhoneExecute.class.php');
				$object=new AastraIPPhoneExecute();
				$object->AddEntry($XML_SERVER.'&action=clear&type='.$type.'&page='.$page.'&set='.$set.'&selection='.$selection);
				}
			}
		else
			{
			# Do Nothing
			require_once('AastraIPPhoneExecute.class.php');
			$object=new AastraIPPhoneExecute();
			$object->AddEntry($XML_SERVER.'&action=nothing');
			}
		break;

	# SELECT KEYS
	case 'select':
		# Count the options
		$option[0]='bottom';
		if($array_config[$header['model']]['top key']=='1') $option[]='top';
		if($header['module'][1]!='') $option[]='expmod1';
		if($header['module'][2]!='') $option[]='expmod2';
		if($header['module'][3]!='') $option[]='expmod3';

		# More than 2 options?
		if(count($option)>2)
			{
			# Base screen
			require_once('AastraIPPhoneTextMenu.class.php');
			$object=new AastraIPPhoneTextMenu();
			$object->setStyle('radio');
			$object->setDestroyOnExit();
			switch($type)
				{
				case 'top':
					$default=1;
					break;
				case 'bottom':
					$default=2;
					break;
				case 'expmod1':
					$default=3;
					break;
				case 'expmod2':
					$default=4;
					break;
				case 'expmod3':
					$default=5;
					break;
				}
			$object->setDefaultIndex($default);
     			$object->setTitle(Aastra_get_label('Select Keys',$language));

			# Add all the key types
			if($array_config[$header['model']]['top key']=='1') $object->addEntry(Aastra_get_label('Phone Top Keys',$language),$XML_SERVER.'&type=top');
			if($array_config[$header['model']]['top key']!='1') $label=Aastra_get_label('Phone Keys',$language);
			else $label=Aastra_get_label('Phone Bottom Keys',$language);
			$object->addEntry($label,$XML_SERVER.'&type=bottom');
			if($header['module'][1]!='') $object->addEntry(Aastra_get_label('Expansion Module 1',$language),$XML_SERVER.'&type=expmod1');
			if($header['module'][2]!='') $object->addEntry(Aastra_get_label('Expansion Module 2',$language),$XML_SERVER.'&type=expmod2');
			if($header['module'][3]!='') $object->addEntry(Aastra_get_label('Expansion Module 3',$language),$XML_SERVER.'&type=expmod3');

			# Softkeys
			if($nb_softkeys)
				{
				if($nb_softkeys==6)
					{
					$object->addSoftkey('1',Aastra_get_label('Select',$language),'SoftKey:Select');
					$object->addSoftkey('5',Aastra_get_label('Cancel',$language),$XML_SERVER.'&type='.$type.'&page='.$page.'&default='.$selection);
					$object->addSoftkey('6',Aastra_get_label('Exit',$language),'SoftKey:Exit');
					}
				else
					{
					$object->addSoftkey('9',Aastra_get_label('Cancel',$language),$XML_SERVER.'&type='.$type.'&page='.$page.'&default='.$selection);
					$object->addSoftkey('10',Aastra_get_label('Exit',$language),'SoftKey:Exit');
					$object->setCancelAction($XML_SERVER.'&type='.$type.'&page='.$page.'&default='.$selection);
					}
				}
			else $object->setCancelAction($XML_SERVER.'&type='.$type.'&page='.$page.'&default='.$selection);
			}
		else
			{
			# Find next set of keys
			if($type==$option[0]) $next=$option[1];
			else $next=$option[0];
	
			# Launch next action
			require_once('AastraIPPhoneExecute.class.php');
			$object = new AastraIPPhoneExecute();
			$object->addEntry($XML_SERVER.'&type='.$next);
			}
		break;

	# LABEL CHANGE
	case 'label':
		# Input label
		require_once('AastraIPPhoneInputScreen.class.php');
		$object = new AastraIPPhoneInputScreen();
		$object->setDestroyOnExit();
		$object->setTitle(Aastra_get_label('Key Configuration',$language));
	    	$object->setType('string');
		$object->setPrompt(Aastra_get_label('Enter key label',$language));
		$object->setDefault($array_key[$selection]['label']);
		$object->setURL($XML_SERVER.'&selection='.$selection.'&action=set_label&type='.$type.'&page='.$page.'&set='.$set);
		$object->setParameter('input1');

		# Softkeys
		if($nb_softkeys)
			{
			if($nb_softkeys==6)
				{
				$object->addSoftkey('1',Aastra_get_label('Backspace',$language),'SoftKey:BackSpace');
				$object->addSoftkey('3',Aastra_get_label('ABC',$language),'SoftKey:ChangeMode');
				$object->addSoftkey('4',Aastra_get_label('NextSpace',$language),'SoftKey:NextSpace');
				$object->addSoftkey('5',Aastra_get_label('Submit',$language),'SoftKey:Submit');
				$object->addSoftkey('6',Aastra_get_label('Cancel',$language),$XML_SERVER.'&default='.$selection.'&action=main&type='.$type.'&page='.$page.'&set='.$set);
				}
			else
				{
				$object->addSoftkey('10',Aastra_get_label('Cancel',$language),$XML_SERVER.'&default='.$selection.'&action=main&type='.$type.'&page='.$page.'&set='.$set);
				$object->setCancelAction($XML_SERVER.'&default='.$selection.'&action=main&type='.$type.'&page='.$page.'&set='.$set);
				}
			}
		else $object->setCancelAction($XML_SERVER.'&selection='.$selection.'&action=menu&type='.$type.'&page='.$page.'&set='.$set);
		break;

	# TITLE CHANGE
	case 'title':
		# Input label
		require_once('AastraIPPhoneInputScreen.class.php');
		$object=new AastraIPPhoneInputScreen();
		$object->setDestroyOnExit();
		$object->setTitle(Aastra_get_label('560M Page title',$language));
	    	$object->setType('string');
		$object->setPrompt(Aastra_get_label('Enter page title',$language));
		$object->setDefault($array_key[$selection]['value']);
		$object->setURL($XML_SERVER.'&selection='.$selection.'&action=set_title&type='.$type.'&page='.$page.'&set='.$set);
		$object->setParameter('input1');

		# Softkeys
		if($nb_softkeys)
			{
			if($nb_softkeys==6)
				{
				$object->addSoftkey('1',Aastra_get_label('Backspace',$language),'SoftKey:BackSpace');
				$object->addSoftkey('3',Aastra_get_label('ABC',$language),'SoftKey:ChangeMode');
				$object->addSoftkey('4',Aastra_get_label('NextSpace',$language),'SoftKey:NextSpace');
				$object->addSoftkey('5',Aastra_get_label('Submit',$language),'SoftKey:Submit');
				$object->addSoftkey('6',Aastra_get_label('Cancel',$language),$XML_SERVER.'&action=main&type='.$type.'&page='.$page.'&set='.$set);
				}
			else
				{
				$object->addSoftkey('10',Aastra_get_label('Cancel',$language),$XML_SERVER.'&action=main&type='.$type.'&page='.$page.'&set='.$set);
				$object->setCancelAction($XML_SERVER.'&action=main&type='.$type.'&page='.$page.'&set='.$set);
				}
			}
		else $object->setCancelAction($XML_SERVER.'&selection='.$selection.'&action=menu&type='.$type.'&page='.$page.'&set='.$set);
		break;

	# KEY TYPE SELECTION
	case 'edit':
		# Choices
		$array_title=array(		'blfxfer'=>Aastra_get_label('Busy Lamp Field',$language),
						'speeddialxfer'=>Aastra_get_label('Speed dial',$language),
					);
		# Depending on step
		switch($step)
			{
			# First step
			case '1':
				# Count items
				$items=0;

				# Default index
				$array_default=array(	'1'=>'blf',
		                                       	'1'=>'blfxfer',
							       '2'=>'speeddial',
							       '2'=>'speeddialxfer',
							       '2'=>'speeddialconf'
							        );
				$default_index=array_search($array_key[$selection]['type'],$array_default);
	
				# TextMenu
				require_once('AastraIPPhoneTextMenu.class.php');
				$object=new AastraIPPhoneTextMenu();
				$object->setDestroyOnExit();
				$object->setStyle('radio');
				if($default_index) $object->setDefaultIndex($default_index);
				$object->setTitle(Aastra_get_label('Key type',$language));
				foreach($array_title as $key=>$value) 
					{
					$object->addEntry($value,$XML_SERVER.'&action=edit&step=2&input1='.$key.'&selection='.$selection.'&type='.$type,'');
					$items++;
					if($items==AASTRA_MAXLINES) break;
					}
				
				# Add previously deleted XML keys
				$keys=Aastra_get_user_context($user,'keys_saved');
				foreach($keys[$header['model']] as $key=>$value) 
					{
					$object->addEntry($value['label'],$XML_SERVER.'&action=restore&input1='.$key.'&selection='.$selection.'&type='.$type,'');
					$items++;
					if($items==AASTRA_MAXLINES) break;
					}

				# Add Extra keys
				$array_extra=extra_keys($user,$header,$keys,$array_key);
				foreach($array_extra as $key=>$value) 
					{
					$object->addEntry($value['label'],$XML_SERVER.'&action=extra&input1='.$key.'&selection='.$selection.'&type='.$type,'');
					$items++;
					if($items==AASTRA_MAXLINES) break;
					}

				# Softkeys
				if($nb_softkeys)
					{
					if($nb_softkeys==6)
						{
						$object->addSoftkey('1',Aastra_get_label('Select',$language),'SoftKey:Select');
						$object->addSoftkey('5',Aastra_get_label('Cancel',$language),$XML_SERVER.'&action=main&default='.$selection.'&type='.$type);
						$object->addSoftkey('6',Aastra_get_label('Exit',$language),'SoftKey:Exit');
						}
					else
						{
						$object->addSoftkey('9',Aastra_get_label('Cancel',$language),$XML_SERVER.'&action=main&default='.$selection.'&type='.$type);
						$object->addSoftkey('10',Aastra_get_label('Exit',$language),'SoftKey:Exit');
						$object->setCancelAction($XML_SERVER.'&action=main&default='.$selection.'&type='.$type);
						}
					}
				else $object->setCancelAction($XML_SERVER.'&action=menu&selection='.$selection.'&page='.$page.'&type='.$type);
				break;

			# Second step
			case '2':
				# Depending on key type
				switch($input1)
					{
					# BLF
					case 'blf':
					case 'blfxfer':
						# Get list of users
						$directory=Aastra_get_user_directory_Asterisk();

						# Remove existing BLF
						foreach($array_key as $key=>$value)
							{
							if(($value['type']=='blf') or ($value['type']=='blfxfer'))
								{
								if($key!=$selection) 
									{
									$number=Aastra_search2d($directory,$value['value'],'number');
									if($number[0]) unset($directory[$number[1]]);
									}
								}
							}

						# Remove user as blf
						$number=Aastra_search2d($directory,$user,'number');
						if($number[0]) unset($directory[$number[1]]);

						# Sort Directory by name
						Aastra_natsort2d($directory,'name');
						$directory=array_values($directory);

						# Number of records
						$count=count($directory);

						# At least one record
						if($count>0)
							{
							# Retrieve last page
							$last=intval($count/$MaxLines);
							if(($count-$last*$MaxLines)!=0) $last++;

							# Find default if needed
							switch($array_key[$selection]['type'])
								{
								case 'blf':
								case 'blfxfer':
									if($array_key[$selection]['value']!='')
										{
										$number=Aastra_search2d($directory,$array_key[$selection]['value'],'number');
										if($number[0]) 
											{
											$rank=$number[1]+1;
											$page=intval($rank/$MaxLines);
											if(($rank-($rank*$MaxLines))!=0) $page++;
											$default=$rank%$MaxLines;
											}
										}
									break;
								}
													
							# Display Page
							require_once('AastraIPPhoneTextMenu.class.php');
							$object=new AastraIPPhoneTextMenu();
							$object->setDestroyOnExit();
							$object->setStyle('none');
							if($last!=1) $object->setTitle(sprintf(Aastra_get_label('Directory (%s/%s)',$language),$page,$last));
							else $object->setTitle(Aastra_get_label('Directory',$language));

							# Default Index
							if($default) $object->setDefaultIndex($default);

							# Previous page for non softkey phones
							if(!$nb_softkeys)
								{
								if($page!='1') $object->addEntry(Aastra_get_label('Previous Page',$language),$XML_SERVER.'&selection='.$selection.'&action=edit&step=2&type='.$type.'&page='.($page-1).'&set='.$set.'&input1='.$input1);
								}

							# Set Base for the items
							$object->setBase($XML_SERVER.'&selection='.$selection.'&action=edit&step=3&type='.$type.'&set='.$set.'&input1='.$input1);

							# Display elements
							$index=1;
							foreach ($directory as $v) 
								{
								if(($index>=(($page-1)*$MaxLines+1)) and ($index<=$page*$MaxLines)) $object->addEntry($v['name'],'&input2='.$v['number']);
								$index++;
								}

							# Reset Base
							$object->resetBase();

							# Softkeys
							if($nb_softkeys)
								{
								if($nb_softkeys==6)
									{
									$object->addSoftkey('1',Aastra_get_label('Select',$language),'SoftKey:Select');
									if($page!='1') $object->addSoftKey('2',Aastra_get_label('Previous',$language),$XML_SERVER.'&selection='.$selection.'&action=edit&step=2&type='.$type.'&page='.($page-1).'&set='.$set.'&input1='.$input1);
									$object->addSoftkey('4',Aastra_get_label('Back',$language),$XML_SERVER.'&selection='.$selection.'&action=edit&step=1&type='.$type.'&set='.$set);
									if($page!=$last) $object->addSoftKey('5',Aastra_get_label('Next',$language),$XML_SERVER.'&selection='.$selection.'&action=edit&step=2&type='.$type.'&page='.($page+1).'&set='.$set.'&input1='.$input1);
									$object->addSoftkey('6',Aastra_get_label('Exit',$language),'SoftKey:Exit');
									}
								else
									{
									if($page!='1') $object->addSoftKey('3',Aastra_get_label('Previous',$language),$XML_SERVER.'&selection='.$selection.'&action=edit&step=2&type='.$type.'&page='.($page-1).'&set='.$set.'&input1='.$input1);
									if($page!=$last) $object->addSoftKey('8',Aastra_get_label('Next',$language),$XML_SERVER.'&selection='.$selection.'&action=edit&step=2&type='.$type.'&page='.($page+1).'&set='.$set.'&input1='.$input1);
									$object->addSoftkey('9',Aastra_get_label('Back',$language),$XML_SERVER.'&selection='.$selection.'&action=edit&step=1&type='.$type.'&set='.$set);
									$object->addSoftkey('10',Aastra_get_label('Exit',$language),'SoftKey:Exit');
									}
								}
							else 
								{
								$object->setCancelAction($XML_SERVER.'&selection='.$selection.'&action=edit&step=1&type='.$type.'&set='.$set);
								if($page!=$last) $object->addEntry(Aastra_get_label('Next Page',$language),$XML_SERVER.'&selection='.$selection.'&action=edit&step=2&type='.$type.'&page='.($page+1).'&set='.$set.'&input1='.$input1);
								}
							}
						else
							{
							# Display error
							require_once('AastraIPPhoneTextScreen.class.php');
							$object=new AastraIPPhoneTextScreen();
							$object->setDestroyOnExit();
							$object->setTitle(Aastra_get_label('BLF error',$language));
							$object->setText(Aastra_get_label('You already have a BLF on every possible user of the platform.',$language));
							$object->addSoftkey('6',Aastra_get_label('Close',$language),$XML_SERVER.'&selection='.$selection.'&action=edit&step=1&type='.$type.'&set='.$set);
							}
						break;

					# SPEEDDIAL
					case 'speeddial':
					case 'speeddialconf':
					case 'speeddialxfer':
						# Input number
						require_once('AastraIPPhoneInputScreen.class.php');
						$object=new AastraIPPhoneInputScreen();
						$object->setDestroyOnExit();
						$object->setTitle($array_title[$input1]);
						$object->setType('number');
						$object->setPrompt(Aastra_get_label('Enter phone number',$language));
						$object->setURL($XML_SERVER.'&selection='.$selection.'&action=edit&step=3&type='.$type.'&set='.$set.'&input1='.$input1);
						$object->setParameter('input2');

						# Default value
						switch($array_key[$selection]['type'])
							{
							case 'speeddial':
							case 'speeddialconf':
							case 'speeddialxfer': 
								if($array_key[$selection]['value']!='') $object->setDefault($array_key[$selection]['value']);
								break;
							}

						# Softkeys
						$object->addSoftkey('1',Aastra_get_label('Backspace',$language),'SoftKey:BackSpace');
						$object->addSoftkey('5',Aastra_get_label('Submit',$language),'SoftKey:Submit');
						$object->addSoftkey('6',Aastra_get_label('Back',$language),$XML_SERVER.'&selection='.$selection.'&action=edit&step=1&type='.$type.'&set='.$set.'&input1='.$input1);
						break;
					}
				break;

			# Third step: label
			case '3':
				# Check if step 2 is properly completed
				if($input2!='')
					{
					# Input label
					require_once('AastraIPPhoneInputScreen.class.php');
					$object=new AastraIPPhoneInputScreen();
					$object->setDestroyOnExit();
					$object->setTitle($array_title[$input1]);
					$object->setType('string');
					$object->setPrompt(Aastra_get_label('Enter key label',$language));
					$object->setURL($XML_SERVER.'&selection='.$selection.'&action=set&type='.$type.'&set='.$set.'&input1='.$input1.'&input2='.$input2);
					$object->setParameter('input3');

					# Default value
					switch($input1)
						{
						case 'speeddial':
						case 'speeddialconf':
						case 'speeddialxfer': 
							switch($array_key[$selection]['type'])
								{
								case 'speeddial':
								case 'speeddialconf':
								case 'speeddialxfer': 
									if(($input2==$array_key[$selection]['value']) and ($array_key[$selection]['label']!='')) $object->setDefault($array_key[$selection]['label']);
									break;
								}
							break;
						case 'blf':
						case 'blfxfer':
							switch($array_key[$selection]['type'])
								{
								case 'blf':
								case 'blfxfer': 
									if($input2==$array_key[$selection]['value'])
										{
										if($array_key[$selection]['label']!='') $object->setDefault($array_key[$selection]['label']);
										}
									else $object->setDefault(Aastra_get_callerid_Asterisk($input2));
									break;
								default:
									$object->setDefault(Aastra_get_callerid_Asterisk($input2));
									break;
								}
							break;
						}

					# Softkeys
					if($nb_softkeys)
						{
						if($nb_softkeys==6)
							{
							$object->addSoftkey('1',Aastra_get_label('Backspace',$language),'SoftKey:BackSpace');
							$object->addSoftkey('2',Aastra_get_label('ABC',$language),'SoftKey:ChangeMode');
							$object->addSoftkey('3',Aastra_get_label('NextSpace',$language),'SoftKey:NextSpace');
							$object->addSoftkey('4',Aastra_get_label('Back',$language),$XML_SERVER.'&selection='.$selection.'&action=edit&step=2&type='.$type.'&set='.$set.'&input1='.$input1.'&input2='.$input2);
							$object->addSoftkey('5',Aastra_get_label('Submit',$language),'SoftKey:Submit');
							$object->addSoftkey('6',Aastra_get_label('Exit',$language),'SoftKey:Exit');
							}
						else
							{
							$object->addSoftkey('9',Aastra_get_label('Back',$language),$XML_SERVER.'&selection='.$selection.'&action=edit&step=2&type='.$type.'&set='.$set.'&input1='.$input1.'&input2='.$input2);
							$object->addSoftkey('10',Aastra_get_label('Exit',$language),'SoftKey:Exit');
							$object->setCancelAction($XML_SERVER.'&selection='.$selection.'&action=edit&step=2&type='.$type.'&set='.$set.'&input1='.$input1.'&input2='.$input2);
							}
						}
					else $object->setCancelAction($XML_SERVER.'&selection='.$selection.'&action=edit&step=2&type='.$type.'&set='.$set.'&input1='.$input1.'&input2='.$input2);
					}
				else
					{
					require_once('AastraIPPhoneExecute.class.php');
					$object=new AastraIPPhoneExecute();
					$object->AddEntry($XML_SERVER.'&action=edit&step=2&input1='.$input1.'&type='.$type.'&set='.$set.'&selection='.$selection);
					}
				break;
			}
		break;

	# UPDATE LED
	case 'led':
		# Retrieve session
		$array=Aastra_read_session('key');
		$temp=unserialize(base64_decode($array['keys']));
		require_once('AastraIPPhoneExecute.class.php');
		$object=new AastraIPPhoneExecute();
		foreach($temp[0] as $value) $object->AddEntry('Led: '.$value.'=off');
		$object->AddEntry('');
		break;

	# UPDATE CONFIGURATION
	case 'configuration':
		# Retrieve session
		$array=Aastra_read_session('key');
		$temp=unserialize(base64_decode($array['keys']));

		# Update configuration 
		if(count($temp[1])!=0)
			{
			require_once('AastraIPPhoneConfiguration.class.php');
			$object=new AastraIPPhoneConfiguration();
			foreach($temp[1] as $value) $object->addEntry($value['param'],$value['value']);
			}
		else
			{
			require_once('AastraIPPhoneExecute.class.php');
			$object=new AastraIPPhoneExecute();
			$object->AddEntry('');
			}
		break;

	# UPDATE MSG
	case 'msg':
		# Retrieve session
		$array=Aastra_read_session('key');
		$temp=unserialize(base64_decode($array['keys']));

		# Update configuration 
		if(count($temp[2])!=0)
			{
			require_once('AastraIPPhoneStatus.class.php');
			$object=new AastraIPPhoneStatus();
			$object->setSession('aastra-xml');
			foreach($temp[2] as $value) $object->addEntry(Aastra_get_status_index_Asterisk($value),'');
			}
		else
			{
			require_once('AastraIPPhoneExecute.class.php');
			$object=new AastraIPPhoneExecute();
			$object->AddEntry('');
			}
		break;

	# DO NOTHING
	case 'nothing':
		# Do Nothing
		require_once('AastraIPPhoneExecute.class.php');
		$object=new AastraIPPhoneExecute();
		$object->setBeep();
		$object->AddEntry('');
		break;

	# Key menu for non softkey phones
	case 'menu':
		# Base screen
		require_once('AastraIPPhoneTextMenu.class.php');
		$object=new AastraIPPhoneTextMenu();
		$object->setDestroyOnExit();
		$object->setStyle('radio');
		$object->setCancelAction($XML_SERVER.'&action=main&type='.$type.'&default='.$selection.'&page='.$page);

		# Labels for the title
		$array_title=Array( 	'top'=>Aastra_get_label('Top Key %d',$language),
					'bottom'=>Aastra_get_label('Bottom Key %d',$language),
					'expmod1'=>Aastra_get_label('Module 1 Key %d',$language),
					'expmod2'=>Aastra_get_label('Module 2 Key %d',$language),
					'expmod3'=>Aastra_get_label('Module 3 Key %d',$language));
		if(($type=='bottom') and $array_config[$header['model']]['top key']!='1') $title=sprintf(Aastra_get_label('Key %d',$language),$array_key[$selection]['key']);
		else $title=sprintf($array_title[$type],$array_key[$selection]['key']);
		$object->setTitle($title);

		# Locked keys
		if($array_key[$selection]['locked']=='1')
			{
			# Only label change
			$object->addEntry(Aastra_get_label('Change Label',$language),$XML_SERVER.'&action=label&type='.$type.'&selection='.$selection.'&page='.$page.'&set='.$set);
			}
		else
			{
			# Move up
			if($array_key[$selection]['key']>1) $object->addEntry(Aastra_get_label('Move Up',$language),$XML_SERVER.'&action=up&type='.$type.'&selection='.$selection);

			# Depending on type
			switch($array_key[$selection]['type'])
				{
				# No key
				case '':
				case 'empty':
					$object->addEntry(Aastra_get_label('Edit Key',$language),$XML_SERVER.'&action=edit&type='.$type.'&selection='.$selection.'&page='.$page);
					break;
  				case 'blf':
				case 'blfxfer':
				case 'speeddial':
				case 'speeddialxfer':
				case 'speeddialconf':
					$object->addEntry(Aastra_get_label('Edit Key',$language),$XML_SERVER.'&action=edit&type='.$type.'&selection='.$selection.'&page='.$page);
					$object->addEntry(Aastra_get_label('Remove Key',$language),$XML_SERVER.'&action=delete&type='.$type.'&selection='.$selection.'&page='.$page);
					break;
				default:
					$object->addEntry(Aastra_get_label('Change label',$language),$XML_SERVER.'&action=label&type='.$type.'&selection='.$selection.'&page='.$page);
					$object->addEntry(Aastra_get_label('Remove Key',$language),$XML_SERVER.'&action=delete&type='.$type.'&selection='.$selection.'&page='.$page);
					break;
				}

			# Move down
			if($array_key[$selection]['key']<$array_key[$selection]['max']) $object->addEntry(Aastra_get_label('Move Down',$language),$XML_SERVER.'&action=down&type='.$type.'&selection='.$selection);
			}
		# Back
		$object->addEntry(Aastra_get_label('Back',$language),$XML_SERVER.'&action=main&type='.$type.'&default='.$selection.'&page='.$page);
		break;

	# Notification
	case 'notify':
		# Send notification
		Aastra_send_SIP_notify_Asterisk('aastra-xml',array($user));

		# Do nothing
		require_once('AastraIPPhoneExecute.class.php');
		$object=new AastraIPPhoneExecute();
		$object->addEntry('');
		break;

	# DEFAULT
	case 'main':
	default:
		# IF update needed
		if($update==1)
			{
			# Process the update
			require_once('AastraIPPhoneExecute.class.php');
			$object=new AastraIPPhoneExecute();

			# Update LED if needed
			if(Aastra_is_ledcontrol_supported()) $object->AddEntry($XML_SERVER.'&action=led');

			# Update status messages if needed
			$object->AddEntry($XML_SERVER.'&action=msg');

			# Update configuration
			$object->AddEntry($XML_SERVER.'&action=configuration');

			# Back to the main menu
			$object->AddEntry($XML_SERVER.'&default='.$default.'&type='.$type.'&page='.$page.'&set='.$set);

			# Notify the phone if needed
			if($update_notify) $object->AddEntry($XML_SERVER.'&action=notify');

			# Workaround for 6739i
			if($header['model']=='Aastra6739i') $object->AddEntry($XML_SERVER_PATH.'sync.php?user='.$user.'&action=register');
			}
		else
			{
			# More than just current keys?
			if(($array_config[$header['model']]['top key']=='1') or ($header['module'][1]!='')) $otherkeys=True;
			else $otherkeys=False;

			# Subtitle?
			$subtitle=False;

			# Get display values for the phone
			switch($type)
				{
				case 'top':
				case 'bottom':
					$nb_keys=$array_config[$header['model']][$type.' key number'];
					$keys_per_page=$array_config[$header['model']][$type.' key per page'];
					$search=$array_config[$header['model']][$type.' key type'];
					break;
	
				case 'expmod1':
					$keys_per_page=$array_config[$header['module'][1]]['key per page'];
					$nb_keys=$array_config[$header['module'][1]]['key number'];
					$search=$type.' key';
					$subtitle=True;
					$module=1;
					break;

				case 'expmod2':
					$keys_per_page=$array_config[$header['module'][2]]['key per page'];
					$nb_keys=$array_config[$header['module'][2]]['key number'];
					$search=$type.' key';
					$subtitle=True;
					$module=2;
					break;

				case 'expmod3':
					$keys_per_page=$array_config[$header['module'][3]]['key per page'];
					$nb_keys=$array_config[$header['module'][3]]['key number'];
					$search=$type.' key';
					$subtitle=True;
					$module=3;
					break;
				}

	
			# How many pages?
			$last=intval($nb_keys/$keys_per_page);
			if(($nb_keys-($last*$keys_per_page)) != 0) $last++;

			# Base screen
			require_once('AastraIPPhoneTextMenu.class.php');
			$object=new AastraIPPhoneTextMenu();
			$object->setDestroyOnExit();
			$object->setWrapList();
			$object->setStyle('none');
			$object->setTitleWrap();

			# Default selection
			if($default!='')
				{
				sscanf($default,$search.'%d',$value);
				$page=intval(($value-1)/$keys_per_page);
				if(($value-($value*$keys_per_page)) != 0) $page++;
				$default_index=(($value-1)%$keys_per_page)+1;
				}

			# Labels for the title
			$array_title=Array( 	'top'=>Aastra_get_label('Top Keys',$language),
						'bottom'=>Aastra_get_label('Bottom Keys',$language),
						'expmod1'=>Aastra_get_label('Exp. Module 1',$language),
						'expmod2'=>Aastra_get_label('Exp. Module 2',$language),
						'expmod3'=>Aastra_get_label('Exp. Module 3',$language));
			if(($type=='bottom') and $array_config[$header['model']]['top key']!='1') $title=Aastra_get_label('Keys',$language);
			else $title=$array_title[$type];
			if($last!=1) $title.=' ('.$page.'/'.$last.')';
			if($subtitle)
				{
				$title.=' '.$header['module'][$module].'-';
				if(($page%2)==1) $side=array(Aastra_get_label('Left',$language),'left');
				else $side=array(Aastra_get_label('Right',$language),'right');
				$pagem=intval($page/2);
				if(($page-($pagem*2)) != 0) $pagem++;
				if($header['module'][$module]=='560M') 
					{
					$title.=sprintf(Aastra_get_label('Page %d %s',$language),$pagem,$side[0]);
					$parameter=$type.'page'.$pagem.$side[1];
					$object->addEntry(sprintf(Aastra_get_label('Title: %s',$language),$array_key[$parameter]['value']),$XML_SERVER.'&action=title&type='.$type.'&selection='.$parameter.'&page='.$page.'&set='.$set,$parameter);
					if($default_index) $default_index++;
					}
				else $title.=$side;
				}
			$object->setTitle($title);

			# Default position
			if($default_index) $object->setDefaultIndex($default_index);

			# Previous page for non-softkey phones
			if(!$nb_softkeys)
				{
				if($page!=1) $object->addEntry(Aastra_get_label('Previous Page',$language),$XML_SERVER.'&type='.$type.'&page='.($page-1).'&set='.$set);
				}

			# Set menu item base
			$object->setBase($XML_SERVER.'&type='.$type.'&page='.$page.'&set='.$set);

			# Labels for the types
			$array_type=array(	''=>Aastra_get_label('Not configured',$language),
						'empty'=>Aastra_get_label('Empty',$language),
						'blf'=>Aastra_get_label('BLF',$language),
						'blfxfer'=>Aastra_get_label('BLF',$language),
						'speeddial'=>Aastra_get_label('Speed Dial',$language),
						'speeddialxfer'=>Aastra_get_label('Speed Dial',$language),
						'speeddialconf'=>Aastra_get_label('Speed Dial',$language),
						'dnd'=>Aastra_get_label('Phone DND',$language),
						'callforward'=>Aastra_get_label('Phone CFWD',$language),
						'park'=>Aastra_get_label('Park',$language),
						'dir'=>Aastra_get_label('Phone Directory',$language),
						'callers'=>Aastra_get_label('Callers List',$language),
						'icom'=>Aastra_get_label('Intercom',$language),
						'services'=>Aastra_get_label('Services',$language),
						'phonelock'=>Aastra_get_label('Lock/Unlock',$language),
						'paging'=>Aastra_get_label('Paging',$language),
						'xml'=>Aastra_get_label('Application',$language)
					);

			# Display items
			for ($i=1;$i<=$nb_keys;$i++)
				{
				# Key in the page?
				if(($i>($keys_per_page*($page-1))) and ($i<=($keys_per_page*$page)))
					{
					# Format key number
					if($nb_keys>9) $key=sprintf('%02s',$array_key[$search.$i]['key']);
					else $key=$array_key[$search.$i]['key'];
					
					# Check "locked" state
					if($array_key[$search.$i]['locked']=='1') $locked=True;
					else $locked=False;

					# Depending on the feature type
					switch($array_key[$search.$i]['type'])
						{
						# Not configured/empty
						case 'empty':
						case '':
							if($is_icons)
								{
								if($locked) $icon='1';
								else $icon='3';
								$label=$key;
								}
							else
								{
								$icon='';
								if($locked) $label=$key.'.'.Aastra_get_label('(L)',$language);
								else $label=$key.'.'.Aastra_get_label('(U)',$language);
								}
							if($locked) $next_action='&action=nothing';
							else $next_action='&action=edit&selection='.$search.$i;
							if($nb_softkeys) 
								{
								if($nb_softkeys==6) $object->addEntry(array($label,'',0,'.'),'&action=edit&selection='.$search.$i,$search.$i,$icon);
								else 
									{
									if($array_key[$search.$i]['type']=='') $object->addEntry($label.' '.Aastra_get_label('NOT CONFIGURED',$language),'&action=edit&selection='.$search.$i,$search.$i,$icon);
									else $object->addEntry($label.' '.Aastra_get_label('NOT CONFIGURED',$language),'&action=edit&selection='.$search.$i,$search.$i,$icon);
									}
								}
							else $object->addEntry(array($label,'',0,'.'),'&action=menu&selection='.$search.$i,$search.$i,$icon);
							break;

						# User available
						case 'blf':
						case 'blfxfer':
						case 'speeddial':
						case 'speeddialxfer':
						case 'speeddialconf':
							if($is_icons)
								{
								if($locked) $icon='1';
								else $icon='3';
								$label=$key.'.'.$array_key[$search.$i]['label'];
								}
							else
								{
								$icon='';
								if($locked) $label=$key.'.'.Aastra_get_label('(L)',$language).' '.$array_key[$search.$i]['label'];
								else $label=$key.'.'.Aastra_get_label('(U)',$language).' '.$array_key[$search.$i]['label'];
								}
							if($locked) $next_action='&action=nothing';
							else $next_action='&action=edit&selection='.$search.$i;
							if($nb_softkeys) 
								{
								if($nb_softkeys==6) $object->addEntry(array($label,'('.$array_type[$array_key[$search.$i]['type']].')',4,' '),$next_action,$search.$i,$icon);
								else $object->addEntry($label.' ('.$array_type[$array_key[$search.$i]['type']].')',$next_action,$search.$i,$icon);
								}
							else $object->addEntry(array($label,'('.$array_type[$array_key[$search.$i]['type']].')',0,' '),'&action=menu&selection='.$search.$i,$search.$i,$icon,$icon);
							break;

						# Others
						default:
							if($is_icons)
								{
								if($locked) $icon='1';
								else $icon='2';
								$label=$key.'.'.$array_key[$search.$i]['label'];
								}
							else
								{
								$icon='';
								if($locked) $label=$key.'.'.Aastra_get_label('(L)',$language).' '.$array_key[$search.$i]['label'];
								$label=$key.'.'.Aastra_get_label('(P)',$language).' '.$array_key[$search.$i]['label'];
								}
							if($locked) $next_action='&action=nothing';
							else $next_action='&action=label&selection='.$search.$i;
							if($nb_softkeys) 
								{
								if($array_type[$array_key[$search.$i]['type']]!='') $display_type=$array_type[$array_key[$search.$i]['type']];
								else $display_type=Aastra_get_label('System',$language);
								if($nb_softkeys==6) $object->addEntry(array($label,'('.$display_type.')',4,' '),$next_action,$search.$i,$icon);
								else $object->addEntry($label.' ('.$display_type.')',$next_action,$search.$i,$icon);
								}
							else 
								{
								#$object->addEntry($label,'&action=menu&selection='.$search.$i,$search.$i,$icon);
								if($array_type[$array_key[$search.$i]['type']]!='') $display_type=$array_type[$array_key[$search.$i]['type']];
								else $display_type=Aastra_get_label('System',$language);
								$object->addEntry(array($label,'('.$display_type.')',0,' '),'&action=menu&selection='.$search.$i,$search.$i,$icon);
								}
							break;
						}
					}
				}

			# Reset menu item base
			$object->resetBase();

			# Softkeys
			if($nb_softkeys)
				{
				if($nb_softkeys==6)
					{
					if($set=='1')
						{
						# First page of menu
						$object->addSoftkey('1',Aastra_get_label('Select',$language),'SoftKey:Select');
						$object->addSoftkey('2',Aastra_get_label('Move Up',$language),$XML_SERVER.'&action=up&type='.$type.'&set='.$set);
						$object->addSoftkey('3',Aastra_get_label('Remove',$language),$XML_SERVER.'&action=delete&type='.$type.'&set='.$set);
						if($otherkeys) $object->addSoftkey('4',Aastra_get_label('Other Keys',$language),$XML_SERVER.'&action=select&type='.$type.'&set='.$set);
						$object->addSoftkey('5',Aastra_get_label('Move Down',$language),$XML_SERVER.'&action=down&type='.$type.'&set='.$set);
						if($last!=1) $object->addSoftkey('6',Aastra_get_label('More',$language),$XML_SERVER.'&set=2&type='.$type.'&page='.$page);
						else $object->addSoftkey('6',Aastra_get_label('Exit',$language),'SoftKey:Exit');
						}
					else
						{
						# Second Page of menu
						$object->addSoftkey('1',Aastra_get_label('Select',$language),'SoftKey:Select');
						if($page!=1) $object->addSoftkey('2',Aastra_get_label('Previous',$language),$XML_SERVER.'&type='.$type.'&page='.($page-1).'&set='.$set);
						$object->addSoftkey('4',Aastra_get_label('Exit',$language),'SoftKey:Exit');
						if($page!=$last) $object->addSoftkey('5',Aastra_get_label('Next',$language),$XML_SERVER.'&type='.$type.'&page='.($page+1).'&set='.$set);
						$object->addSoftkey('6',Aastra_get_label('More',$language),$XML_SERVER.'&set=1&type='.$type.'&page='.$page);
						}
					}
				else
					{
					# Menu
					$object->addSoftkey('2',Aastra_get_label('Move Up',$language),$XML_SERVER.'&action=up&type='.$type.'&set='.$set);
					if($page!=1) $object->addSoftkey('3',Aastra_get_label('Previous Page',$language),$XML_SERVER.'&type='.$type.'&page='.($page-1).'&set='.$set);
					$object->addSoftkey('6',Aastra_get_label('Remove',$language),$XML_SERVER.'&action=delete&type='.$type.'&set='.$set);
					$object->addSoftkey('7',Aastra_get_label('Move Down',$language),$XML_SERVER.'&action=down&type='.$type.'&set='.$set);
					if($page!=$last) $object->addSoftkey('8',Aastra_get_label('Next Page',$language),$XML_SERVER.'&type='.$type.'&page='.($page+1).'&set='.$set);
					if($otherkeys) $object->addSoftkey('9',Aastra_get_label('Other Keys',$language),$XML_SERVER.'&action=select&type='.$type.'&set='.$set);
					$object->addSoftkey('10',Aastra_get_label('Exit',$language),'SoftKey:Exit');
					}
				}
			else
				{
				# Non softkey phones
				if($otherkeys) $object->addEntry(Aastra_get_label('Other Keys',$language),$XML_SERVER.'&action=select&type='.$type.'&page='.$page.'&set='.$set);
				if($page!=$last) $object->addEntry(Aastra_get_label('Next Page',$language),$XML_SERVER.'&type='.$type.'&page='.($page+1).'&set='.$set);
				}
			}

		# Icons
		if($is_icons)
			{
			if(Aastra_phone_type()!=5)
				{
				$object->addIcon(1,'3E4E4A4E3E00');
				$object->addIcon(2,'4E8E8A8E7E00');
				$object->addIcon(3,'32167C163200');
				}
			else
				{
				$object->addIcon(1,'Icon:Lock');
				$object->addIcon(2,'Icon:UnLock');
				$object->addIcon(3,'Icon:World');
				}
			}
		break;
	}

# Display object
$object->output();
exit;
?>
