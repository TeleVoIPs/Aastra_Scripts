<?php
#############################################################################
# Asterisk Startup
#
# Aastra SIP Phones 1.4.2 or better
#
# Copyright 2005-2010 Aastra Telecom Ltd
#
# Supported Aastra Phones
#   All phones
#
# script.php
#
#############################################################################

#############################################################################
# PHP customization for includes and warnings
#############################################################################
$os = strtolower(PHP_OS);
if(strpos($os, 'win') === false) ini_set('include_path',ini_get('include_path').':include:../include');
else ini_set('include_path',ini_get('include_path').';include;../include');
error_reporting(E_ERROR | E_PARSE);

#############################################################################
# Includes
#############################################################################
require_once('AastraAsterisk.php');
require_once('AastraIPPhoneTextScreen.class.php');
require_once('AastraIPPhoneTextMenu.class.php');
require_once('AastraIPPhoneFormattedTextScreen.class.php');
require_once('AastraIPPhoneInputScreen.class.php');
require_once('AastraIPPhoneExecute.class.php');
require_once('AastraIPPhoneConfiguration.class.php');

#############################################################################
# Private functions
#############################################################################
function create_mac($mac,$extension,$username,$secret,$callerid,$model,$profile,$tz_name,$tz_code,$lang_code,$lang)
{
Global $AA_XML_SERVER;
Global $AA_PROXY_SERVER;
Global $AA_REGISTRAR_SERVER;
Global $AA_XMLDIRECTORY;
Global $AA_PRESENCE_STATE;
Global $AA_SPEEDDIAL_STATE;
Global $AA_ASK_TZ;
Global $AA_FREEPBX_MODE;
Global $language;

# So far so good
$return=True;

# Read profile file
if($AA_FREEPBX_MODE=='1') $array_profile=Aastra_readCFGfile($profile.'-user.prf', '#', ':');
else $array_profile=Aastra_readCFGfile($profile.'-device-nouser.prf', '#', ':');
if(count($array_profile)==0) 
	{
	Aastra_debug('Profile file '.$profile.' not available');
	return(False);
	}

# Check if model is available
if(count($array_profile[$model])==0)
	{
	Aastra_debug('Phone model '.$model.' not configured in the Profile file ('.$profile.')');
	return(False);
	}

# Get park configuration
$park=Aastra_get_park_config_Asterisk();

# Get polling value
$polling=Aastra_get_polling_interval_Asterisk();

# Prepare replace strings
$search=array('/\$\$AA_SIPAUTHNAME_AA\$\$/',
		'/\$\$AA_SIPSECRET_AA\$\$/',
		'/\$\$AA_SIPUSERNAME_AA\$\$/',
		'/\$\$AA_SIPCALLERID_AA\$\$/',
		'/\$\$AA_TZ_NAME_AA\$\$/',
		'/\$\$AA_TZ_CODE_AA\$\$/',
		'/\$\$AA_XML_SERVER_AA\$\$/',
		'/\$\$AA_PROXY_SERVER_AA\$\$/',
		'/\$\$AA_REGISTRAR_SERVER_AA\$\$/',
		'/\$\$AA_PARKINGLOT_AA\$\$/',
		'/\$\$AA_XMLDIRECTORY_AA\$\$/',
		'/\$\$AA_INTERCOM_CODE_AA\$\$/',
		'/\$\$AA_POLLING_INT_AA\$\$/'
		);
$replace=array(	quotemeta($username),
			$secret,
			quotemeta($extension),
			quotemeta($callerid),
			$tz_name,
			$tz_code,
			$AA_XML_SERVER,
			$AA_PROXY_SERVER,
			$AA_REGISTRAR_SERVER,
			$park['parkext'],
			$AA_XMLDIRECTORY,
			Aastra_get_intercom_config_Asterisk(),
			$polling
		);

# Also use the core piece for device and user
if($AA_FREEPBX_MODE=='2')
	{
	foreach($array_profile['Core'] as $key => $value)
		{
		$line=preg_replace($search, $replace, $value);
		if(stristr($line,'$$AA_KEYPRESS_AA$$'))
			{
			$pieces=explode(' ',$key);
			$line = preg_replace('/\$\$AA_KEYPRESS_AA\$\$/', $pieces[0], $line);
			}
		$array_config[$key]=$line;
		}
	}

# Use the common piece
foreach($array_profile['Common'] as $key => $value)
	{
	$line = preg_replace($search, $replace, $value);
	if(stristr($line,'$$AA_KEYPRESS_AA$$'))
		{
		$pieces=explode(' ',$key);
		$line = preg_replace('/\$\$AA_KEYPRESS_AA\$\$/', $pieces[0], $line);
		}
	$array_config[$key]=$line;
	}

# Model exists
if($array_profile[$model]!=NULL)
	{
	# Check if full template
	if (key_exists('template',$array_profile[$model])) $template=$array_profile[$model]['template'];
	else $template=$model;

	# Use the template
	foreach($array_profile[$template] as $key => $value)
		{
		if($key!='template')
			{
			$line = preg_replace($search, $replace, $value);
			if(stristr($line,'$$AA_KEYPRESS_AA$$'))
				{
				$pieces=explode(' ',$key);
				$line = preg_replace('/\$\$AA_KEYPRESS_AA\$\$/', $pieces[0], $line);
				}
			$array_config[$key]=$line;
			}
		}

	# Use the add-ons
	if($template!=$model)
		{
		foreach($array_profile[$model] as $key => $value)
			{
			if($key!='template')
				{
				$line = preg_replace($search, $replace, $value);
				$array_config[$key]=$line;
				}
			}
		}
	}

# Read user custom configuration
$array_user=Aastra_readCFGfile('user-custom.prf', '#', ':');

# User/Device exists
if($array_user[$extension]!=NULL)
        {
        # Use the user configuration additions
        foreach($array_user[$extension] as $key => $value)
                {
                $line = preg_replace($search, $replace, $value);
                if(stristr($line,'$$AA_KEYPRESS_AA$$'))
                        {
                        $pieces=explode(' ',$key);
                        $line = preg_replace('/\$\$AA_KEYPRESS_AA\$\$/', $pieces[0], $line);
                        }
                $array_config[$key]=$line;
                }
        }

# Remove TZ if not needed
if(!$AA_ASK_TZ)
	{
	unset($array_config['time zone name']);
	unset($array_config['time zone code']);
	}

# Process language
if($lang_code!='')
	{
	$array_config['language']=$lang_code;
	if(Aastra_test_phone_version('2.0.1.','1')==0)
		{
		$array_config['web language']=$lang_code;
		$array_wl=array(	'fr'=>'French',
					'it'=>'Italian',
					'es'=>'Spanish',
					'en'=>'English',
					'de'=>'German',
					'pt'=>'Portuguese'
				);
		if($array_wl[substr($lang,0,2)]!='') $array_config['input language']=$array_wl[substr($lang,0,2)];
		}
	}

# User mode
if($AA_FREEPBX_MODE=='1')
	{
	# Retrieve user keys
	$keys=Aastra_get_user_context($username,'keys');

	# User has existing keys
	if(count($keys[$model])!=0)
		{
		# Remove all profile keys
		foreach($array_config as $key=>$value) if(stristr($key,'key')) unset($array_config[$key]);

		# Add user keys
		foreach($keys[$model] as $key=>$value) $array_config[$key]=$value;
		}
	
	# Process day/night keys
	foreach($array_config as $key=>$value) 
		{
		if(strstr($value,'daynight.php'))
			{
			$pieces=explode(' ',$key);
			if(stristr($pieces[0],'expmod')) $appli=$pieces[0].' '.$pieces[1];
			else $appli=$pieces[0];
			$url=parse_url($value);
			parse_str($url['query'],$parse);
			if($parse['index']!='') $index=$parse['index'];
			else $index='';
			if(!Aastra_is_daynight_appli_allowed_Asterisk($username,$index))
				{
				unset($array_config[$appli.' type']);
				unset($array_config[$appli.' label']);
				unset($array_config[$appli.' value']);
				unset($array_config[$appli.' states']);
				}
			}
		}

	# Process presence
	if(!$AA_PRESENCE_STATE)
		{
		foreach($array_config as $key=>$value) 
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
			unset($array_config[$appli.' type']);
			unset($array_config[$appli.' label']);
			unset($array_config[$appli.' value']);
			unset($array_config[$appli.' states']);
			}
		}

	# Process speed dial
	if(!$AA_SPEEDDIAL_STATE)
		{
		foreach($array_config as $key=>$value) 
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
			unset($array_config[$appli.' type']);
			unset($array_config[$appli.' label']);
			unset($array_config[$appli.' value']);
			unset($array_config[$appli.' states']);
			}
		}
	}

# Translate the needed parameters
foreach($array_config as $key=>$value) 
	{
	$test=False;
	if(stristr($key,'key') and stristr($key,'label')) $test=True;
	if(stristr($key,'xml application title')) $test=True;
	if($test) $array_config[$key]=Aastra_get_label($value,$language);
	}

# Config file
$write=@fopen(AASTRA_TFTP_DIRECTORY.'/'.$mac.'.cfg', 'w');

# No problem with the file
if($write)
	{
	# Dump the config file
	foreach($array_config as $key=>$value) fputs($write,$key.': '.$value."\n");

	# Close the MAC.cfg file
	fclose($write);
	}
else 
	{
	# Trace
	Aastra_debug('Cannot write MAC.cfg ('.AASTRA_TFTP_DIRECTORY.'/'.$mac.'.cfg )');
	$return=False;
	}

# Return result
return($return);
}

function is_remote_dynamic_sip_supported($phone)
{
# Prepare the comparison
$piece=preg_split("/\./",$phone);	
$count=count($piece)-1;
$phone_version=$piece[0]*100;
if($count>1)$phone_version+=$piece[1]*10;
if($count>2)$phone_version+=$piece[2];
$piece=preg_split("/\./",$version);	
$count=count($piece)-1;
$test_version=250;

# Compare
if($phone_version>=$test_version) $return=True;

# Return result
return($return);
}

#############################################################################
# Active code
#############################################################################
# Retrieve parameters
$extension=Aastra_getvar_safe('extension');
$password=Aastra_getvar_safe('password');
$tz_code=Aastra_getvar_safe('tz_code');
$tz_name=Aastra_getvar_safe('tz_name');
$action=Aastra_getvar_safe('action');
$step=Aastra_getvar_safe('step','1');
$page=Aastra_getvar_safe('page','1');
$lang=Aastra_getvar_safe('lang');
$cl=Aastra_getvar_safe('cl');

# No action yet
if($action=='')
	{
	# Select the right action
	$code=Aastra_ask_language_Asterisk();
	if($code[0]) $action='language';
	else $action='input';
	}

# Trace
Aastra_trace_call('startup_asterisk','extension='.$extension.', password='.$password.', action='.$action.', page='.$page.', cl='.$cl);

# Get MAC address and type of phone
$header=Aastra_decode_HTTP_header();

# To handle non softkey phones
$nb_softkeys=Aastra_number_softkeys_supported();
if($nb_softkeys) $MaxLines=AASTRA_MAXLINES;
else $MaxLines=AASTRA_MAXLINES-2;
$is_doneAction=Aastra_is_doneAction_supported();

# Test User Agent
if($AA_FREEPBX_MODE=='1') Aastra_test_phone_versions(array('1'=>'1.4.2.','2'=>'1.4.2.','3'=>'2.5.3.','4'=>'2.5.3.','5'=>'3.0.1.'),'0');
else Aastra_test_phone_versions(array('1'=>'','2'=>'','3'=>'2.5.3.','4'=>'2.5.3.','5'=>'3.0.1.'),'0');

# Get Language
if($lang!='') $language=$lang;
else $language=Aastra_get_language();

# Callback
if($action!='language') $XML_SERVER.='?lang='.$lang.'&cl='.$cl;

# Depending on action
switch($action)
	{
	# Reboot
	case 'reboot':
		# Reboot needed
		$object=new AastraIPPhoneExecute();
		if(Aastra_is_fastreboot_supported()) $object->AddEntry('Command: FastReboot');
		else $object->AddEntry('Command: Reset');
		break;

	# Update display
	case 'display':
		$object=new AastraIPPhoneTextScreen();
		$object->setDestroyOnExit();
		$object->setTitle(Aastra_get_label('REBOOT',$language));
		$object->setText(Aastra_get_label('Reboot',$language));
		if($nb_softkeys==6) $object->addSoftkey('6','','SoftKey:Exit');
		break;

	# Language Selection
	case 'language':
		# Associated labels
		$array_lang=array(	'en'=>Aastra_get_label('English',$language),
					'fr'=>Aastra_get_label('French (Europe)',$language),
					'fr_ca'=>Aastra_get_label('French (Canada)',$language),
					'de'=>Aastra_get_label('German',$language),
					'it'=>Aastra_get_label('Italian',$language),
					'es'=>Aastra_get_label('Spanish (Europe)',$language),
					'es_mx'=>Aastra_get_label('Spanish (Mexico)',$language),
					'pt'=>Aastra_get_label('Portuguese (Europe)',$language),
					'pt_br'=>Aastra_get_label('Portuguese (Brazil)',$language)
				);

		# Create TextMenu
		$object=new AastraIPPhoneTextMenu();
		$object->setDestroyOnExit();
		if(Aastra_is_lockin_supported()) $object->setLockIn();
		$object->setTitle(Aastra_get_label('Select Language',$language));
		$index=1;
		foreach($code[1] as $key=>$value) 
			{
			$object->addEntry($array_lang[$value],$XML_SERVER.'?action=input&lang='.$value.'&cl='.$key);
			if($key==$code[2]) $object->setDefaultIndex($index);
			$index++;
			}
		if($nb_softkeys==6) $object->addSoftkey('1',Aastra_get_label('Select',$language),'SoftKey:Select');
		break;

	# Input data
	case 'input':
		switch($step)
			{
			case '1':
				if(Aastra_is_multipleinputfields_supported())
					{
				       $object=new AastraIPPhoneInputScreen();
			       	$object->setTitle(Aastra_get_label('Initial Startup',$language));
				       $object->setDisplayMode('condensed');
			       	$object->setURL($XML_SERVER.'&action=input&step=3');
				       $object->setDestroyOnExit();
			       	$object->addField('empty');
				       $object->addField('number');
			       	if($AA_FREEPBX_MODE=='1') $object->setFieldPrompt(Aastra_get_label('Extension:',$language));
					else $object->setFieldPrompt(Aastra_get_label('Device ID:',$language));
				       $object->setFieldParameter('extension');
				       if($AA_FREEPBX_MODE=='1') $object->addField('number');
					else $object->addField('string');
			       	$object->setFieldPrompt(Aastra_get_label('Password:',$language));
				       $object->setFieldPassword();
				       $object->setFieldParameter('password');
				       if($AA_FREEPBX_MODE=='2') $object->addFieldSoftkey('2',Aastra_get_label('ABC',$language),'SoftKey:ChangeMode');
					}
				else
					{
					$object=new AastraIPPhoneInputScreen();
					$object->setTitle(Aastra_get_label('Initial Startup',$language));
					if($AA_FREEPBX_MODE=='1') $object->setPrompt(Aastra_get_label('Enter extension',$language));
					else $object->setPrompt(Aastra_get_label('Enter Device ID',$language));
					$object->setParameter('extension');
					$object->setType('number');
					$object->setURL($XML_SERVER.'&action=input&step=2');
					$object->setDestroyOnExit();
					if($extension!='') $object->setDefault($extension);
					}
				if(Aastra_is_lockin_supported()) 
					{
					$object->setLockIn();
					if($nb_softkeys==6) $object->addSoftkey('3',Aastra_get_label('Reboot',$language),$XML_SERVER.'&action=reboot');
					else $object->addSoftkey('6',Aastra_get_label('Reboot',$language),$XML_SERVER.'&action=reboot');
					}
				if($header['model']=='Aastra6867i') {
					$object->addSoftkey('1',Aastra_get_label('Backspace',$language),'SoftKey:BackSpace');
					$object->addSoftkey('2',Aastra_get_label('Submit',$language),'SoftKey:Submit');
                                }
				elseif($nb_softkeys==6) 
				{
					$object->addSoftkey('1',Aastra_get_label('Backspace',$language),'SoftKey:BackSpace');
					$object->addSoftkey('4',Aastra_get_label('Submit',$language),'SoftKey:Submit');
				}
				break;

			case '2':
				$object=new AastraIPPhoneInputScreen();
				$object->setTitle(Aastra_get_label('Initial Startup',$language));
				$object->setPrompt(Aastra_get_label('Enter password',$language));
				$object->setParameter('password');
				if($AA_FREEPBX_MODE=='1') $object->setType('number');
				else $object->setType('string');
				$object->setPassword();
				$object->setURL($XML_SERVER.'&action=input&step=3&extension='.$extension);
				$object->setDestroyOnExit();
				if(Aastra_is_lockin_supported()) 
					{
					$object->setLockIn();
					if($nb_softkeys==6) $object->addSoftkey('3',Aastra_get_label('Reboot',$language),$XML_SERVER.'&action=reboot');
					else $object->addSoftkey('6',Aastra_get_label('Reboot',$language),$XML_SERVER.'&action=reboot');
					}
				if($header['model']=='Aastra6867i') {
					$object->addSoftkey('1',Aastra_get_label('Backspace',$language),'SoftKey:BackSpace');
					$object->addSoftkey('2',Aastra_get_label('Submit',$language),'SoftKey:Submit');
				}
				if($nb_softkeys==6) 
					{
					$object->addSoftkey('1',Aastra_get_label('Backspace',$language),'SoftKey:BackSpace');
				       if($AA_FREEPBX_MODE=='2') $object->addSoftkey('2',Aastra_get_label('ABC',$language),'SoftKey:ChangeMode');
					$object->addSoftkey('4',Aastra_get_label('Submit',$language),'SoftKey:Submit');
					}
				break;

			case '3':
				# Init continue
				$continue=True;

				# Check credentials
				if($AA_FREEPBX_MODE=='1') $mode='login';
				else $mode='sip';
				if(!Aastra_verify_user_Asterisk($extension,$password,$mode))
					{
					# Display error
					$object = new AastraIPPhoneTextScreen();
					$object->setDestroyOnExit();
					if(Aastra_is_lockin_supported()) $object->setLockIn();
					$object->setTitle(Aastra_get_label('Authentication failed',$language));
					$object->setText(Aastra_get_label('Wrong credentials.',$language));
					if($nb_softkeys) 
						{
						if($nb_softkeys==6) $object->addSoftkey('6',Aastra_get_label('Close',$language),$XML_SERVER.'&action=input&step=1&extension='.$extension);
						else $object->addSoftkey('10',Aastra_get_label('Close',$language),$XML_SERVER.'&action=input&step=1&extension='.$extension);
						}
					else
						{
						if($is_doneAction) $object->setDoneAction($XML_SERVER.'&action=input&step=1&extension='.$extension);
						}
					$continue=False;
					}

				# Test MAC address
				if($continue)
					{
					# Read config file
					$ext_array=Aastra_read_HDconfig_file(AASTRA_PATH_CACHE.'startup_asterisk.cfg');
	
					# Test MAC address
					if(isset($ext_array[$extension]['mac']))
						{
						if($ext_array[$extension]['mac']==$header['mac'])
							{
							# Should not happen so let's clean up
							Aastra_update_HDconfig_file(AASTRA_PATH_CACHE.'startup_asterisk.cfg',$extension,NULL);
							Aastra_delete_mac($header['mac']);
							}
						}
					}


				# Test MAC address
				if($continue)
					{
					# If TZ must be asked
					if($AA_ASK_TZ)
						{
						# Get TZ data
						$array_ini=Aastra_readINIfile(AASTRA_CONFIG_DIRECTORY.'timezone.conf','#','=');
						$last=intval(count($array_ini['tz'])/$MaxLines);
						if((count($array_ini['tz'])- $last*$MaxLines) != 0) $last++;

						# Create TextMenu
						$object=new AastraIPPhoneTextMenu();
						$object->setDestroyOnExit();
						if(Aastra_is_lockin_supported()) $object->setLockIn();
						if(Aastra_size_display_line()>16) $object->setTitle(sprintf(Aastra_get_label('Select Timezone (%d/%d)',$language),$page,$last));
						else $object->setTitle(sprintf(Aastra_get_label('Timezone (%d/%d)',$language),$page,$last));

						# Display current TZ page
						$i=0;
						if((!$nb_softkeys) and ($page!=1)) $object->addEntry(Aastra_get_label('Previous Page',$language),$XML_SERVER.'&action=input&step=3&extension='.$extension.'&password='.$password.'&page='.($page-1));
						foreach($array_ini['tz'] as $key=>$value)
							{
							if(($i>=($page-1)*$MaxLines) and ($i<$page*$MaxLines))
								{
								$tzname=strtoupper(substr($key,0,4)).substr($key,-(strlen($key)-4));
								$object->addEntry($tzname,$XML_SERVER.'&action=submit&extension='.$extension.'&password='.$password.'&tz_code='.$value.'&tz_name='.$tzname);
								}
							$i++;
							}
						if($nb_softkeys)
							{
							if($nb_softkeys==6)
								{
								$object->addSoftkey('1',Aastra_get_label('Select',$language),'SoftKey:Select');
								if($page!=1) $object->addSoftkey('2',Aastra_get_label('Previous',$language),$XML_SERVER.'&action=input&step=3&extension='.$extension.'&password='.$password.'&page='.($page-1));
								if($page!=$last) $object->addSoftkey('5',Aastra_get_label('Next',$language),$XML_SERVER.'&action=input&step=3&extension='.$extension.'&password='.$password.'&page='.($page+1));
								}
							else
								{
								if($page!=1) $object->addSoftkey('3',Aastra_get_label('Previous',$language),$XML_SERVER.'&action=input&step=3&extension='.$extension.'&password='.$password.'&page='.($page-1));
								if($page!=$last) $object->addSoftkey('8',Aastra_get_label('Next',$language),$XML_SERVER.'&action=input&step=3&extension='.$extension.'&password='.$password.'&page='.($page+1));
								}
							}
						else 
							{
							if($page!=$last)$object->addEntry(Aastra_get_label('Next Page',$language),$XML_SERVER.'&action=input&step=3&extension='.$extension.'&password='.$password.'&page='.($page+1));
							}
						}
					else
						{
						# Skip this phase
						$object=new AastraIPPhoneExecute();
						$object->addEntry($XML_SERVER.'&action=submit&extension='.$extension.'&password='.$password);
						}
					}
				break;
			}
		break;

	case 'submit':
	case 'override':
		# Read config file
		$ext_array=Aastra_read_HDconfig_file(AASTRA_PATH_CACHE.'startup_asterisk.cfg');

		# Test MAC address
		if(($ext_array[$extension]['mac']!='') and ($action!='override'))
			{
			if($nb_softkeys)
				{
				# Display error as a TextScreen
				$object=new AastraIPPhoneTextScreen();
				$object->setDestroyOnExit();
				$object->setTitle(Aastra_get_label('Warning',$language));
				if(!Aastra_is_user_registered_Asterisk($extension) and ((time()-$ext_array[$extension]['time'])<180))
					{
					$object->setText(sprintf(Aastra_get_label('Extension already in use on a %s at %s but not registered yet. Please try again later.',$language),$ext_array[$extension]['model'],$ext_array[$extension]['ip']));
					if($nb_softkeys==6) $object->addSoftkey('6',Aastra_get_label('Close',$language),$XML_SERVER);
					else
						{
						$object->addSoftkey('10',Aastra_get_label('Close',$language),$XML_SERVER);
						$object->setCancelAction($XML_SERVER);
						}
					}
				else
					{
					$object->setText(sprintf(Aastra_get_label('Extension already in use on a %s at %s.',$language),$ext_array[$extension]['model'],$ext_array[$extension]['ip']));
					if($nb_softkeys==6)
						{
						$object->addSoftkey('5',Aastra_get_label('Override',$language),$XML_SERVER.'&action=override&extension='.$extension.'&password='.$password.'&tz_code='.$tz_code.'&tz_name='.$tz_name);
						$object->addSoftkey('6',Aastra_get_label('Close',$language),$XML_SERVER);
						}
					else
						{
						$object->addSoftkey('9',Aastra_get_label('Override',$language),$XML_SERVER.'&action=override&extension='.$extension.'&password='.$password.'&tz_code='.$tz_code.'&tz_name='.$tz_name);
						$object->addSoftkey('10',Aastra_get_label('Close',$language),$XML_SERVER);
						$object->setCancelAction($XML_SERVER);
						}
					}
				}
			else
				{
				# Display error as a TextMenu
				$object = new AastraIPPhoneTextMenu();
				$object->setDestroyOnExit();
				$object->setTitle(Aastra_get_label('Extension in Use',$language));
				if(Aastra_is_user_registered_Asterisk($extension) or ((time()-$ext_array[$extension]['time'])>180)) $object->addEntry(Aastra_get_label('Override',$language),$XML_SERVER.'&action=override&extension='.$extension.'&password='.$password.'&tz_code='.$tz_code.'&tz_name='.$tz_name);
				$object->addEntry(Aastra_get_label('Cancel',$language),$XML_SERVER);
				}
			}
		else
			{
			# Collect user data
			if($AA_FREEPBX_MODE=='1')
				{
				$username=Aastra_get_username_Asterisk($extension);
				$secret=Aastra_get_secret_Asterisk($extension);
				}
			else
				{
				$username=$extension;
				$secret=$password;
				}
			$callerid=Aastra_get_callerid_Asterisk($extension);

			# Get user/device profile
			$profile=Aastra_get_startup_profile_Asterisk($extension);

			# Create mac.cfg
			if(create_mac($header['mac'],$extension,$username,$secret,$callerid,$header['model'],$profile,$tz_name,$tz_code,$cl,$lang))
				{
				# If override
				if($action=='override')
					{
					# Send a SIP notify
					if(Aastra_is_user_registered_Asterisk($extension)) 
						{
						if(Aastra_is_dynamic_sip_supported($ext_array[$extension]) or ($ext_array[$extension]['model']=='Aastra8000i'))
							{
							$notify_type='aastra-xml';
							$notify=Aastra_get_user_context($extension,'notify');
							$notify['forced_logout']='1';
							Aastra_save_user_context($extension,'notify',$notify);
							}
						else 
							{
							Aastra_delete_mac($ext_array[$extension]['mac']);
							$notify_type='aastra-check-cfg';
							}
						Aastra_send_SIP_notify_Asterisk($notify_type,array($extension));
						}
					else
						{ 
						if($ext_array[$extension]['model']!='Aastra8000i') Aastra_delete_mac($ext_array[$extension]['mac']);
						}

					# Send an email
					Aastra_send_HDmail($ext_array[$extension],$callerid,'FORCED LOGOUT',$AA_EMAIL,$AA_SENDER);
					}

				# Update config file
				Aastra_update_HDconfig_file(AASTRA_PATH_CACHE.'startup_asterisk.cfg',$extension,$header);

				# Save signature
				Aastra_store_signature($extension);
	
				# Send an email
				Aastra_send_HDmail($header,$callerid.' '.$extension,'LOGIN',$AA_EMAIL,$AA_SENDER);

				# Set status to Available
				if(($AA_FREEPBX_MODE=='1') and ($AA_PRESENCE_STATE))
					{
					if(Aastra_manage_presence_Asterisk($extension,'status')==AA_PRESENCE_DISCONNECTED) 
						{
						Aastra_manage_presence_Asterisk($extension,'set','status',AA_PRESENCE_AVAILABLE);
						$away=Aastra_manage_presence_Asterisk($extension,'action');
						switch($away['action'][AA_PRESENCE_DISCONNECTED])
							{
							case AA_PRESENCE_ACT_FM:
								Aastra_manage_followme_Asterisk($extension,'disable');
								break;
							case AA_PRESENCE_ACT_CFWD:
								Aastra_manage_cf_Asterisk($extension,'cancel');
								break;
							}
						}
					}

				# Depending on dynamic SIP
				if(Aastra_is_dynamic_sip_supported())
					{
					# Read MAC.cfg
					$array_temp=Aastra_readCFGfile(AASTRA_TFTP_DIRECTORY.'/'.$header['mac'].'.cfg','#',':');
					$array_temp['']['sip line1 password']=preg_replace('/"/','',$array_temp['']['sip line1 password']);

					# How many pages?
					$last=intval(count($array_temp[''])/AASTRA_MAXCONFIGURATIONS);
					if((count($array_temp[''])-$last*AASTRA_MAXCONFIGURATIONS)!=0) $last++;

					# 3.2.1 bug double all the configuration messages
					$last=$last*2;

					# Prepare the requests for the configuration
					$object=new AastraIPPhoneExecute();
					$object->setTriggerDestroyOnExit();
					for($i=1;$i<=$last;$i++) $object->addEntry($XML_SERVER.'&extension='.$extension.'&action=configuration&page='.$i);

					# Special case for 6739i before 3.2.1
					if(($header['model']=='Aastra6739i') and !Aastra_test_phone_version('3.2.1,',1)) $object->addEntry($XML_SERVER_PATH.'/sync.php?action=register&user='.$extension);
					}
				else
					{
					# Reboot needed
					$object=new AastraIPPhoneExecute();
					$object->addEntry($XML_SERVER.'&action=display');
					if(Aastra_is_fastreboot_supported()) $object->AddEntry('Command: FastReboot');
					else $object->AddEntry('Command: Reset');
					}
				}
			else
				{
				# Display error as a TextScreen
				$object=new AastraIPPhoneTextScreen();
				$object->setDestroyOnExit();
				$object->setTitle(Aastra_get_label('Configuration error',$language));
				$object->setText(Aastra_get_label('Configuration file cannot be generated. Please contact your administrator.',$language));

				# Softkeys
				if($nb_softkeys)
					{
					if($nb_softkeys==6) $object->addSoftkey('6',Aastra_get_label('Exit',$language),'SoftKey:Exit');
					else $object->addSoftkey('10',Aastra_get_label('Exit',$language),'SoftKey:Exit');
					}
				}
			}
		break;

	# Configuration
	case 'configuration':
		# Read MAC.cfg
		$array_temp=Aastra_readCFGfile(AASTRA_TFTP_DIRECTORY.'/'.$header['mac'].'.cfg','#',':');
		$array_temp['']['sip line1 password']=preg_replace('/"/','',$array_temp['']['sip line1 password']);
		if(isset($array_temp['']['action uri registered']))
			{
			$array_reg=array('action uri registered'=>$array_temp['']['action uri registered']);
			unset($array_temp['']['action uri registered']);
			$array_temp['']=array_merge($array_reg,$array_temp['']);
			}

		# Configuration XML object
		$object=new AastraIPPhoneConfiguration();

		# Send the partial configuration
		$index=1;
		# Bug 3.2.1 send all the configuration twice
		$page=intval(($page-1)/2)+1;
		foreach($array_temp[''] as $key=>$value)
			{
			if(($index>=(($page-1)*AASTRA_MAXCONFIGURATIONS+1)) and ($index<=$page*AASTRA_MAXCONFIGURATIONS)) $object->addEntry($key,preg_replace('/"/','',$value));
			$index++;
			}
		break;
	}

# Display XML Object
$object->output();
exit;
?>
