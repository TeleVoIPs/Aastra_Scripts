<?php
#############################################################################
# CSV directory
# Aastra SIP Phones R1.4.2 or better
#
# php source code
# Copyright Aastra Telecom 2008-2010
#
# Supported Aastra Phones
#    All Phones but 9112i, 9133i
#
# csv_directory.php?source=SOURCE&user=USER
# when
#    SOURCE 	is the name of the entry in config/directory.conf. This entry 
#             must be csv typed.
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

function prepare_number($phone,$type,$conf_dir)
{
$local=$phone;

# For display purpose
if($type==1)
	{
	# Prepare name for display
	$local=preg_replace('/ /','',$local);
	}
else
	{
	# Prepare number for dialing
	if (strstr($local,'+'.$conf_dir['country'].' ')) $local=$conf_dir['long distance'].preg_replace('/\+'.$conf_dir['country'].'/','',$local);
	else $local=preg_replace('/\+/',$conf_dir['international'],$local);

	# Remove  spaces and -
	$local=preg_replace('/ /','',$local);
	$local=preg_replace('/-/','',$local);

	# Remove (0)
	$local=preg_replace('/\(0\)/','',$local);

	# Remove ( and )
	$local=preg_replace('/\(/','',$local);
	$local=preg_replace('/\)/','',$local);

	# Extract the extension
	if(strstr($local,'x'))
		{
		$explode=explode('x',$local,2);
		$local=$explode[0];
		}

	# Local number?
	$found=False;
	if($conf_dir['local']!='')
		{
		$array_local=explode(',',$conf_dir['local']);
		foreach($array_local as $key=>$value)
			{
			$search=$conf_dir['long distance'].$value;
			$replace='/'.$conf_dir['long distance'].$value.'/';
			if (strstr($local,$search)) 
				{
				$local=preg_replace($replace,'',$local);
				$found=True;
				}
			}
		}
	if(!$found) $local=$conf_dir['outgoing'].$local;
	}

# Return processed number
return($local);
}

function starts_with($haystack,$needle)
{
$pos=strpos(strtolower($haystack),strtolower($needle));
if($pos!==False)
	{
	if($pos==0) $pos=True;
	else $pos=False;
	}
return($pos);
} 

function lookup_directory($source,$lookup,$firstn,$lastn,$company,$array_user)
{
# So far so good
$return[0]=True;

# Open and read file
$handle=@fopen($source,'r');
$index=0;
$directory=array();
if($handle)
	{	
	while($line=fgets($handle,200))
		{
		$value=explode(",",trim($line,"\n"));
		if($lookup!='')
			{
			if(stristr($value[0].' '.$value[1].' '.$value[2],$lookup)) 
				{
				if(($value[4]!='') or ($value[5]!='') or ($value[6]!=''))
					{
					$directory[$index]['first']=$value[0];
					$directory[$index]['last']=$value[1];
					$directory[$index]['company']=$value[2];
					$directory[$index]['title']=$value[3];
					$directory[$index]['work']=$value[4];
					$directory[$index]['home']=$value[5];
					$directory[$index]['mobile']=$value[6];
					$index++;
					}
				}
			}
		else
			{
			if($firstn!='') $test_firstn=starts_with($value[0],$firstn);
			else $test_firstn=True;
			if($lastn!='') $test_lastn=starts_with($value[1],$lastn);
			else $test_lastn=True;
			if($company!='') $test_company=starts_with($value[2],$company);
			else $test_company=True;
			if($test_firstn and $test_lastn and $test_company) 
				{
				if(($value[4]!='') or ($value[5]!='') or ($value[6]!=''))
					{
					$directory[$index]=Array(	'first'=>$value[0],
									'last'=>$value[1],
									'company'=>$value[2],
									'title'=>$value[3],
									'work'=>$value[4],
									'home'=>$value[5],
									'mobile'=>$value[6]
								 	);
					if($company!='')
						{
						$directory2[$value[2]][]=Array(	'first'=>$value[0],
										   	'last'=>$value[1],
											'index'=>$index
											);
						}
					$index++;
					}
				}
			}
		}
	fclose($handle);
	}
else 
	{
	Aastra_debug('Cannot open contact file '.$source);
	$return[0]=False;
	}

# Sort the data
if($return[0])
	{
	switch($array_user['sort'])
		{
		case 'first':
			if($company!='')
				{
				foreach($directory2 as $key=>$value) $directory2[$key]=Aastra_array_multi_sort($value, 'first', 'asc', TRUE);
				}
			else $directory=Aastra_array_multi_sort($directory, 'first', 'asc', TRUE);
			break;
		case 'last':
			if($company!='')
				{
				foreach($directory2 as $key=>$value) $directory2[$key]=Aastra_array_multi_sort($value, 'last', 'asc', TRUE);
				}
			else $directory=Aastra_array_multi_sort($directory, 'last', 'asc', TRUE);
			break;
		}
	$return[1]=$directory;
	if($company!='') $return[2]=$directory2;
	}

# Return results
return($return);
}

#############################################################################
# Main code
#############################################################################
# Collect parameters
$user=Aastra_getvar_safe('user');
if($user=='')
	{
	$header=Aastra_decode_HTTP_header();
	$user=$header['mac'];
	}
$action=Aastra_getvar_safe('action','input');
$source=Aastra_getvar_safe('source');
$lastn=Aastra_getvar_safe('lastn');
$firstn=Aastra_getvar_safe('firstn');
$company=Aastra_getvar_safe('company');
$lookup=Aastra_getvar_safe('lookup');
$index=Aastra_getvar_safe('index');
$page=Aastra_getvar_safe('page','1');
$speed=Aastra_getvar_safe('speed');
$back=Aastra_getvar_safe('back');
$mode=Aastra_getvar_safe('mode');
$passwd=Aastra_getvar_safe('passwd');

# Test if Asterisk mode
$asterisk=False;
if(file_exists('../include/AastraAsterisk.php'))
	{
	$asterisk=True;
	require_once('AastraAsterisk.php');
	}

# Log call to the application
if($asterisk) Aastra_trace_call('asterisk_csv_dir','action='.$action.', lookup='.$lookup.', page='.$page.', index='.$index.', lastn='.$lastn.', firstn='.$firstn.', user='.$user.', speed='.$speed);
else Aastra_trace_call('csv_directory','action='.$action.', lookup='.$lookup.', page='.$page.', index='.$index.', lastn='.$lastn.', firstn='.$firstn.', user='.$user.', speed='.$speed);

# Test User Agent
Aastra_test_phone_versions(array('1'=>'','2'=>'1.4.2.','3'=>'2.5.3.','4'=>'2.5.3.','5'=>'3.0.1.'),'0');

# Get Language
$language=Aastra_get_language();

# Get global compatibility
$is_multipleinputfields=Aastra_is_multipleinputfields_supported();
$nb_softkeys=Aastra_number_softkeys_supported();
$is_icons=Aastra_is_icons_supported();
$is_lockin=Aastra_is_lockin_supported();
$is_style_textmenu=Aastra_is_style_textmenu_supported();
$is_formatted_textscreen=Aastra_is_formattedtextscreen_supported();
$is_textmenu_wrapitem=Aastra_is_textmenu_wrapitem_supported();

# Retrieve the size of the display
$chars_supported=Aastra_size_display_line();
if($is_style_textmenu) $chars_supported--;

# To handle non softkey phones
if($nb_softkeys) $MaxLines=AASTRA_MAXLINES;
else $MaxLines=AASTRA_MAXLINES-2;

# Update URI
$XML_SERVER.='?user='.$user.'&source='.$source.'&back='.$back;

# Get user context
$ARRAY_USER=get_user_config($user);

# Get csv file location
$error=False;
$ARRAY_CONFIG=Aastra_readINIfile(AASTRA_CONFIG_DIRECTORY.'directory.conf','#','=');
if($ARRAY_CONFIG[$source]['location']!='') 
	{
	if($ARRAY_CONFIG[$source]['speed']=='0') $SPEED=False;
	else $SPEED=True;
	if($ARRAY_CONFIG[$source]['mode']=='list') 
		{
		$XML_SERVER.='&mode=list';
		$mode='list';
		if($action=='input') $action='lookup';
		}
	if($asterisk)
		{
		if(!$AA_SPEEDDIAL_STATE) $SPEED=FALSE;
		}
	$database=$ARRAY_CONFIG[$source]['location'];
	if(!file_exists($database)) $error=True;
	}
else $error=True;

# Configuration error
if($error)
	{
	require_once('AastraIPPhoneTextScreen.class.php');
	$object=new AastraIPPhoneTextScreen();
	$object->setDestroyOnExit();
	$object->setTitle(Aastra_get_label('Error',$language));
	$object->setText(Aastra_get_label('Database location not found. Please contact your administrator.',$language));
	if($nb_softkeys)
		{
		if($nb_softkeys==6) $object->addSoftkey('6',Aastra_get_label('Exit',$language),'SoftKey:Exit');
		else 
			{
			$object->addSoftkey('10',Aastra_get_label('Exit',$language),'SoftKey:Exit',1);
			$object->addIcon(1,'Icon:CircleRed');
			}
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
		$object->setTitle(Aastra_get_label('Access Denied',$language));
		$object->setText(Aastra_get_label('Wrong password.',$language));
		$object->output();
		exit;
		}
	}

# If needed test if user is authorized
if($ARRAY_CONFIG[$source]['password']!='') Aastra_test_access('csv_directory_'.$source,'passwd',$XML_SERVER,$user);

# Process action
switch($action)
	{
	# Settings
	case 'settings':
		# Display selection
		require_once('AastraIPPhoneTextMenu.class.php');
		$object=new AastraIPPhoneTextMenu();
		$object->setDestroyOnExit();
		$object->setTitle(Aastra_get_label('Settings',$language));
		if($is_style_textmenu) $object->setStyle('none');
		if($index!='') $object->setDefaultIndex($index);
		$text=array(	'firstlast'=>Aastra_get_label('First Last',$language),
				'lastfirst'=>Aastra_get_label('Last First',$language)
				);
		$object->addEntry(sprintf(Aastra_get_label('Display: %s',$language),$text[$ARRAY_USER['display']]),$XML_SERVER.'&action=set_display','');
		$text=array(	'last'=>Aastra_get_label('Last Name',$language),
				'first'=>Aastra_get_label('First Name',$language)
				);
		$object->addEntry(sprintf(Aastra_get_label('Sort: %s',$language),$text[$ARRAY_USER['sort']]),$XML_SERVER.'&action=set_sort','');

		# Softkeys
		if($nb_softkeys)
			{
			if($nb_softkeys==6)
				{
				$object->addSoftkey('1',Aastra_get_label('Change',$language),'SoftKey:Select');
				$object->addSoftkey('4',Aastra_get_label('Back',$language),$XML_SERVER.'&action=input');
				$object->addSoftkey('6',Aastra_get_label('Exit',$language),'SoftKey:Exit');
				}
			else
				{
				$object->addSoftkey('9',Aastra_get_label('Back',$language),$XML_SERVER.'&action=input',1);
				$object->addSoftkey('10',Aastra_get_label('Exit',$language),'SoftKey:Exit',2);
				$object->setCancelAction($XML_SERVER.'&action=input');
				$object->addIcon(1,'Icon:ArrowLeft');
				$object->addIcon(2,'Icon:CircleRed');
				}
			}
		else 
			{
			$object->addEntry(Aastra_get_label('Back',$language),$XML_SERVER.'&action=input');
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

	# Initial input
	case 'input':
		# Title
		if($ARRAY_CONFIG[$source]['label']!='') $title=$ARRAY_CONFIG[$source]['label'];
		else $title=Aastra_get_label('Contact Lookup',$language);

		# Softkey mode
		if($nb_softkeys)
			{
			# Input screen
			require_once('AastraIPPhoneInputScreen.class.php');
			$object = new AastraIPPhoneInputScreen();
	       	$object->setTitle($title);
      			$object->setURL($XML_SERVER.'&action=lookup');
       		$object->setDestroyOnExit();

			# Multiple fields
			if($is_multipleinputfields)
				{
		       	$object->setDisplayMode('condensed');
				if(!empty($lookup)) 
					{
					$firstn='';
					$lastn='';
					$company='';
					$object->setDefaultIndex('4');
					}
				else
					{
					if (!empty($company)) $object->setDefaultIndex('1');
					if (!empty($lastn)) $object->setDefaultIndex('2');
					if (!empty($firstn))$object->setDefaultIndex('3');
					}
				$object->addField('string');
		       	$object->setFieldPrompt(Aastra_get_label('Company:',$language));
			       $object->setFieldParameter('company');
				if (!empty($company)) $object->setFieldDefault($company);
			       $object->addField('string');
       			$object->setFieldPrompt(Aastra_get_label('Last Name:',$language));
			       $object->setFieldParameter('lastn');
				if (!empty($lastn)) $object->setFieldDefault($lastn);
		       	$object->addField('string');
		       	$object->setFieldPrompt(Aastra_get_label('First Name:',$language));
			       $object->setFieldParameter('firstn');
				if (!empty($firstn)) $object->setFieldDefault($firstn);
			       $object->addField('string');
       			$object->setFieldPrompt(Aastra_get_label('Or Anywhere:',$language));
			       $object->setFieldParameter('lookup');
				if (!empty($lookup)) $object->setFieldDefault($lookup);
				}
			else
				{
				# Single input
				$object->setType('string');
				$object->setPrompt(Aastra_get_label('Letters name/company',$language));
				$object->setParameter('lookup');
				if (!empty($lookup)) $object->setDefault($lookup);
				}
			
			# Softkeys
			if($nb_softkeys==6)
				{
		      		$object->addSoftkey('1',Aastra_get_label('Backspace',$language),'SoftKey:BackSpace');
				$object->addSoftkey('2',Aastra_get_label('Reset',$language),$XML_SERVER.'&action=input');
				$object->addSoftkey('3',Aastra_get_label('ABC',$language),'SoftKey:ChangeMode');
				$object->addSoftkey('4',Aastra_get_label('Settings',$language),$XML_SERVER.'&action=settings');
				$object->addSoftkey('5',Aastra_get_label('Lookup',$language),'SoftKey:Submit');
				$object->addSoftkey('6',Aastra_get_label('Exit',$language),'SoftKey:Exit');
				}
			else
				{
				$object->addSoftkey('1',Aastra_get_label('Reset',$language),$XML_SERVER.'&action=input',1);
				$object->addSoftkey('5',Aastra_get_label('Settings',$language),$XML_SERVER.'&action=settings',2);
				$object->addSoftkey('6',Aastra_get_label('Search',$language),'SoftKey:Submit',5);
				if($back=='1')$object->addSoftkey('9',Aastra_get_label('Back',$language),$XML_SERVER_PATH.'directory.php?user='.$user,3);
				$object->addSoftkey('10',Aastra_get_label('Exit',$language),'SoftKey:Exit',4);
				$object->addIcon(1,'Icon:Delete');
				$object->addIcon(2,'Icon:Settings');
				$object->addIcon(3,'Icon:ArrowLeft');
				$object->addIcon(4,'Icon:CircleRed');
				$object->addIcon(5,'Icon:Search');
				}
			}
		else
			{
			# Textmenu
			require_once('AastraIPPhoneTextMenu.class.php');
			$object=new AastraIPPhoneTextMenu();
			$object->setDestroyOnExit();
			$object->setTitle($title);
			$object->setStyle('none');
			$object->addEntry(Aastra_get_label('Lookup',$language),$XML_SERVER.'&action=input_any');
			$object->addEntry(Aastra_get_label('Settings',$language),$XML_SERVER.'&action=settings');
			if($back=='1') $object->addEntry(Aastra_get_label('Back',$language),$XML_SERVER_PATH.'directory.php?user='.$user);
			}
		break;

	# Input single
	case 'input_any':
		# Title
		if($ARRAY_CONFIG[$source]['label']!='') $title=$ARRAY_CONFIG[$source]['label'];
		else $title=Aastra_get_label('Contact Lookup',$language);

		# Input screen
		require_once('AastraIPPhoneInputScreen.class.php');
		$object = new AastraIPPhoneInputScreen();
       	$object->setTitle($title);
      		$object->setURL($XML_SERVER.'&action=lookup');
       	$object->setDestroyOnExit();
		$object->setType('string');
		$object->setPrompt(Aastra_get_label('Letters name/company',$language));
		$object->setParameter('lookup');
		if($lookup!='') $object->setDefault($lookup);
		break;
	
	# Lookup
	case 'lookup':
		# Make the search
		$return=lookup_directory($database,$lookup,$firstn,$lastn,$company,$ARRAY_USER);

		# Search OK
		if($return[0])
			{
			# At least one result
			if(count($return[1])>0)
				{
				# Save results
				$array_directory=array('csv_directory' => base64_encode(serialize($return)));
				Aastra_save_session('csv_directory','600',$array_directory);

				# Call back with browse
				require_once('AastraIPPhoneExecute.class.php');
				$object=new AastraIPPhoneExecute();
				$object->addEntry($XML_SERVER.'&action=browse&page=1&lookup='.$lookup.'&lastn='.$lastn.'&firstn='.$firstn.'&company='.$company);
				}
			else
				{
				# Display error
				require_once('AastraIPPhoneTextScreen.class.php');
				$object=new AastraIPPhoneTextScreen();
				$object->setDestroyOnExit();
				$object->setTitle(Aastra_get_label('Directory Lookup',$language));
				$object->setText(Aastra_get_label('Sorry no match found.',$language));
				if($nb_softkeys)
					{
					if($nb_softkeys==6)
						{
						$object->addSoftkey('4',Aastra_get_label('Back',$language),$XML_SERVER.'&action=input&lookup='.$lookup.'&lastn='.$lastn.'&firstn='.$firstn.'&company='.$company);
						$object->addSoftkey('6',Aastra_get_label('Exit',$language),'SoftKey:Exit');
						}
					else
						{
						$object->addSoftkey('9',Aastra_get_label('Back',$language),$XML_SERVER.'&action=input&lookup='.$lookup.'&lastn='.$lastn.'&firstn='.$firstn.'&company='.$company,1);
						$object->addSoftkey('10',Aastra_get_label('Exit',$language),'SoftKey:Exit',2);
						$object->setCancelAction($XML_SERVER.'&action=input&lookup='.$lookup.'&lastn='.$lastn.'&firstn='.$firstn.'&company='.$company);
						$object->addIcon(1,'Icon:ArrowLeft');
						$object->addIcon(2,'Icon:CircleRed');
						}
					}
				else $object->setCancelAction($XML_SERVER.'&action=input&lookup='.$lookup.'&lastn='.$lastn.'&firstn='.$firstn.'&company='.$company);
				}
			}
		else
			{
			# Display error
			require_once('AastraIPPhoneTextScreen.class.php');
			$object=new AastraIPPhoneTextScreen();
			$object->setDestroyOnExit();
			$object->setTitle(Aastra_get_label('Contact Lookup',$language));
			$object->setText(Aastra_get_label('Cannot access Contact database. Please contact your administrator.',$language));

			# Softkeys
			if($nb_softkeys) 
				{
				if($nb_softkeys==6) $object->addSoftkey('6',Aastra_get_label('Exit',$language),'SoftKey:Exit');
				else 
					{
					$object->addSoftkey('10',Aastra_get_label('Exit',$language),'SoftKey:Exit',1);
					$object->addIcon(1,'Icon:CircleRed');
					}
				}
			}
		break;

	# Browse results
	case 'browse':
		# Retrieve session
		$array=Aastra_read_session('csv_directory');
		$temp=unserialize(base64_decode($array['csv_directory']));

		# Update global variable
		$XML_SERVER.='&lookup='.$lookup.'&lastn='.$lastn.'&firstn='.$firstn.'&company='.$company;

		# Group by company?
		if($company=='')
			{
			# Retrieve answer
			$directory=$temp[1];

			# How many pages
			$count=count($directory);
			$last=intval($count/$MaxLines);
			if(($count-$page*$MaxLines) != 0) $last++;

			# Create TextMenu
			require_once('AastraIPPhoneTextMenu.class.php');
			$object=new AastraIPPhoneTextMenu();
			$object->setDestroyOnExit();
			$object->setStyle('none');
			if($is_textmenu_wrapitem) $object->setWrapList();

			# Title
			if($last!=1) $object->setTitle(sprintf(Aastra_get_label('Results (%s/%s)',$language),$page,$last));
			else $object->setTitle(Aastra_get_label('Results',$language));

			# Default Index
			$object->setDefaultIndex(($index-(($page-1)*$MaxLines)+1));

			# Previous page for non-softkey phones			
			if(!$nb_softkeys and ($page!=1)) $object->addEntry(Aastra_get_label('Previous Page',$language),$XML_SERVER.'&page='.($page-1).'&action=browse');

			# Display items
			$object->setBase($XML_SERVER.'&action=zoom');
			for($index=($page-1)*$MaxLines;($index<($page*$MaxLines)) and ($index<$count);$index++)
				{
				if($ARRAY_USER['display']=='firstlast') $name=$directory[$index]['first'].' '.$directory[$index]['last'];
				else $name=$directory[$index]['last'].' '.$directory[$index]['first'];
				if($is_textmenu_wrapitem) 
					{
					if($nb_softkeys==10)
						{
						$display=$name.' ';
						if($directory[$index]['company']) $display.='('.substr($directory[$index]['company'],0,($chars_supported-3)).')';
						else $display.='('.Aastra_get_label('No Company',$language).')';
						}
					else
						{
						$name=substr($name,0,($chars_supported-3));
						$display=str_pad($name,$chars_supported,'-',STR_PAD_BOTH);
						if($directory[$index]['company']) $display.='('.substr($directory[$index]['company'],0,($chars_supported-3)).')';
						else $display.='('.Aastra_get_label('No Company',$language).')';
						}
					}
				else $display=$name;
				$object->addEntry($display,'&index='.$index.'&page='.$page,$index);
				}

			# Softkeys
			if($nb_softkeys)
				{
				if($nb_softkeys==6)
					{
					$object->addSoftkey('1', Aastra_get_label('Select',$language), 'SoftKey:Select');
					if($page!=1) $object->addSoftkey('2', Aastra_get_label('Previous',$language), $XML_SERVER.'&page='.($page-1).'&action=browse');
					$object->addSoftkey('3',Aastra_get_label('Settings',$language),$XML_SERVER.'&action=settings');
					if($page!=$last) $object->addSoftkey('5', Aastra_get_label('Next',$language), $XML_SERVER.'&page='.($page+1).'&action=browse');
					if($mode!='list') $object->addSoftkey('4', Aastra_get_label('Back',$language), $XML_SERVER);
					$object->addSoftkey('6', Aastra_get_label('Exit',$language), 'SoftKey:Exit');
					}
				else
					{
					$object->addSoftkey('1',Aastra_get_label('Settings',$language),$XML_SERVER.'&action=settings',5);
					if($page!=1) $object->addSoftkey('3', Aastra_get_label('Previous Page',$language), $XML_SERVER.'&page='.($page-1).'&action=browse',1);
					if($page!=$last) $object->addSoftkey('8', Aastra_get_label('Next Page',$language), $XML_SERVER.'&page='.($page+1).'&action=browse',2);
					if($mode!='list') $object->addSoftkey('9', Aastra_get_label('Back',$language), $XML_SERVER,3);
					$object->addSoftkey('10', Aastra_get_label('Exit',$language), 'SoftKey:Exit',4);
					if($mode!='list') $object->setCancelAction($XML_SERVER);
					$object->addIcon(1,'Icon:ArrowUp');
					$object->addIcon(2,'Icon:ArrowDown');
					$object->addIcon(3,'Icon:ArrowLeft');
					$object->addIcon(4,'Icon:CircleRed');
					$object->addIcon(5,'Icon:Settings');
					}
				}
			else
				{
				# Non softkey phones
				$object->resetBase();
				if($page!=$last) $object->addEntry(Aastra_get_label('Next Page',$language),$XML_SERVER.'&page='.($page+1).'&action=browse');
				$object->setCancelAction($XML_SERVER.'&action=input_any');
				}
			}
		else
			{
			# Retrieve answer
			$directory1=$temp[1];
			$directory2=$temp[2];

			# How many pages
			$MaxLines--;
			$count=count($directory1)+count($directory2);
			$last=intval($count/$MaxLines);
			if(($count-$page*$MaxLines) != 0) $last++;

			# Create TextMenu
			require_once('AastraIPPhoneTextMenu.class.php');
			$object=new AastraIPPhoneTextMenu();
			$object->setDestroyOnExit();
			$object->setStyle('none');

			# Title
			if($last!=1) $object->setTitle(sprintf(Aastra_get_label('Results (%s/%s)',$language),$page,$last));
			else $object->setTitle(Aastra_get_label('Results',$language));

			# Default Index
			if($index=='') $object->setDefaultIndex('2');

			# Previous page for non softkey phones
			if(!$nb_softkeys and ($page!=1)) $object->addEntry('Previous Page',$XML_SERVER.'&page='.($page-1).'&action=browse');

			# Display items
			$count=0;
			$rank=1;
			$first=0;
			$object->setBase($XML_SERVER);
			foreach($directory2 as $key=>$value)
				{
				if(($count>=($page-1)*$MaxLines) and ($count<($page*$MaxLines)))
					{
					$display=substr($key,0,($chars_supported-3));
					$display=str_pad($display,$chars_supported,'-',STR_PAD_BOTH);
					$object->addEntry($display,'&action=nothing');
					if($first==0) $first=1;
					$rank++;
					}
				$count++;
				foreach($value as $value2)
					{
					if(($count>=($page-1)*$MaxLines) and ($count<($page*$MaxLines)))
						{
						if($first==0)
							{
							$display=substr($key,0,($chars_supported-3));
							$display=str_pad($display,$chars_supported,'-',STR_PAD_BOTH);
							$object->addEntry($display,'&action=nothing');
							$first=1;
							$rank++;
							}
						if($ARRAY_USER['display']=='firstlast') $name=$value2['first'].' '.$value2['last'];
						else $name=$value2['last'].' '.$value2['first'];
						if($name==' ') $name=$key;
						$object->addEntry($name,'&index='.$value2['index'].'&page='.$page.'&action=zoom',$value2['index']);
						if(($index!='') and ($value2['index']==$index)) $object->setDefaultIndex($rank);
						$rank++;
						}
					$count++;
					}
				}

			# Softkeys
			if($nb_softkeys)
				{
				if($nb_softkeys==6)
					{
					$object->addSoftkey('1', Aastra_get_label('Select',$language), 'SoftKey:Select');
					if($page!=1) $object->addSoftkey('2', Aastra_get_label('Previous',$language), $XML_SERVER.'&page='.($page-1).'&action=browse');
					if($page!=$last) $object->addSoftkey('5', Aastra_get_label('Next',$language), $XML_SERVER.'&page='.($page+1).'&action=browse');
					$object->addSoftkey('4', Aastra_get_label('Back',$language), $XML_SERVER);
					$object->addSoftkey('6', Aastra_get_label('Exit',$language), 'SoftKey:Exit');
					}
				else
					{
					if($page!=1) $object->addSoftkey('3', Aastra_get_label('Previous Page',$language), $XML_SERVER.'&page='.($page-1).'&action=browse',1);
					if($page!=$last) $object->addSoftkey('8', Aastra_get_label('Next Page',$language), $XML_SERVER.'&page='.($page+1).'&action=browse',2);
					$object->addSoftkey('9', Aastra_get_label('Back',$language),$XML_SERVER,3);
					$object->addSoftkey('10', Aastra_get_label('Exit',$language),'SoftKey:Exit',4);
					$object->setCancelAction($XML_SERVER);
					$object->addIcon(1,'Icon:ArrowUp');
					$object->addIcon(2,'Icon:ArrowDown');
					$object->addIcon(3,'Icon:ArrowLeft');
					$object->addIcon(4,'Icon:CircleRed');
					}
				}
			else
				{
				# Non softkey phones
				if($page!=$last) $object->addEntry('Next Page','&page='.($page+1).'&action=browse');
				$object->setCancelAction($XML_SERVER.'&action=input_any');
				}
			}
		break;

	# Zoom on results
	case 'zoom':
		# Retrieve session
		$array=Aastra_read_session('csv_directory');
		$temp=unserialize(base64_decode($array['csv_directory']));
		$directory=$temp[1];

		# Update global variable
		$XML_SERVER.='&lookup='.$lookup.'&lastn='.$lastn.'&firstn='.$firstn.'&index='.$index.'&page='.$page.'&company='.$company;

		# Display details
		require_once('AastraIPPhoneTextMenu.class.php');
		$object=new AastraIPPhoneTextMenu();
		$object->setDestroyOnExit();
		if($is_style_textmenu) $object->setStyle('none');

		# Title
		if($ARRAY_USER['display']=='firstlast') $name=$directory[$index]['first'].' '.$directory[$index]['last'];
		else $name=$directory[$index]['last'].' '.$directory[$index]['first'];
		if($name==' ') $name=$directory[$index]['company'];
		$object->setTitle($name);

		# Work number
		if($directory[$index]['work']!='')
			{
			$display=prepare_number($directory[$index]['work'],1,$ARRAY_CONFIG['Dialplan']);
			$dial=prepare_number($directory[$index]['work'],2,$ARRAY_CONFIG['Dialplan']);
			if($nb_softkeys==0) $dial='Dial:'.$dial;
			if($is_icons) $object->addEntry($display,$dial,'',1);
			else 
				{
				if($nb_softkeys!=10) $object->addEntry(Aastra_get_label('W',$language).' '.$display,$dial,'');
				else $object->addEntry(Aastra_get_label('W',$language).' '.$display,'','','',$dial);
				}
			}

		# Cell phone number
		if($directory[$index]['mobile']!='')
			{
			$display=prepare_number($directory[$index]['mobile'],1,$ARRAY_CONFIG['Dialplan']);
			$dial=prepare_number($directory[$index]['mobile'],2,$ARRAY_CONFIG['Dialplan']);
			if($nb_softkeys==0) $dial='Dial:'.$dial;
			if($is_icons) $object->addEntry($display,$dial,'',2);
			else 
				{
				if($nb_softkeys!=10) $object->addEntry(Aastra_get_label('M',$language).' '.$display,$dial,'');
				else $object->addEntry(Aastra_get_label('M',$language).' '.$display,'','','',$dial);
				}
			}

		# Home number
		if($directory[$index]['home']!='')
			{
			$display=prepare_number($directory[$index]['home'],1,$ARRAY_CONFIG['Dialplan']);
			$dial=prepare_number($directory[$index]['home'],2,$ARRAY_CONFIG['Dialplan']);
			if($nb_softkeys==0) $dial='Dial:'.$dial;
			if($is_icons) $object->addEntry($display,$dial,'',3);
			else 
				{
				if($nb_softkeys!=10) $object->addEntry(Aastra_get_label('H',$language).' '.$display,$dial,'');
				else $object->addEntry(Aastra_get_label('H',$language).' '.$display,'','','',$dial);
				}
			}

		# Softkeys
		if($nb_softkeys>0)
			{
			if($nb_softkeys==6)
				{
				$object->addSoftkey('1',Aastra_get_label('Dial',$language),'SoftKey:Dial');
				if(($directory[$index]['company']!='') or ($directory[$index]['title']!=''))$object->addSoftkey('2',Aastra_get_label('Details',$language),$XML_SERVER.'&action=details');
				if($SPEED) $object->addSoftkey('3',Aastra_get_label('Speed Dial',$language),$XML_SERVER.'&action=speed');
				$object->addSoftkey('4',Aastra_get_label('Back',$language),$XML_SERVER.'&action=browse');
				$object->addSoftkey('6',Aastra_get_label('Exit',$language),'SoftKey:Exit');
				}
			else
				{
				if(($directory[$index]['company']!='') or ($directory[$index]['title']!=''))$object->addSoftkey('1',Aastra_get_label('Details',$language),$XML_SERVER.'&action=details&input='.$input,10);
				if($SPEED) $object->addSoftkey('6',Aastra_get_label('Add to Speed Dial',$language),$XML_SERVER.'&action=speed&input='.$input,11);
				$object->addSoftkey('9',Aastra_get_label('Back',$language),$XML_SERVER.'&action=browse&input='.$input,12);
				$object->addSoftkey('10',Aastra_get_label('Exit',$language),'SoftKey:Exit',13);
				$object->setCancelAction($XML_SERVER.'&action=browse&input='.$input);
				$object->addIcon(10,'Icon:Information');
				$object->addIcon(11,'Icon:Add');
				$object->addIcon(12,'Icon:ArrowLeft');
				$object->addIcon(13,'Icon:CircleRed');
				}
			}
		else $object->setCancelAction($XML_SERVER.'&action=browse');

		# Icons
		if($is_icons)
			{
			if(Aastra_phone_type()!=5)
				{
				$object->addIcon(1,Aastra_get_custom_icon('Office'));
				$object->addIcon(2,Aastra_get_custom_icon('Cellphone'));
				$object->addIcon(3,Aastra_get_custom_icon('Home'));
				}
			else
				{
				$object->addIcon(1,'Icon:Office');
				$object->addIcon(2,'Icon:CellPhone');
				$object->addIcon(3,'Icon:Home');
				}
			}
		break;

	# Details on results
	case 'details':
		# Retrieve session
		$array=Aastra_read_session('csv_directory');
		$temp=unserialize(base64_decode($array['csv_directory']));
		$directory=$temp[1];

		# Update global variable
		$XML_SERVER.='&lookup='.$lookup.'&lastn='.$lastn.'&firstn='.$firstn.'&index='.$index.'&page='.$page.'&company='.$company;

		# Display details
		if($ARRAY_USER['display']=='firstlast') $name=$directory[$index]['first'].' '.$directory[$index]['last'];
		else $name=$directory[$index]['last'].' '.$directory[$index]['first'];
		if($name==' ') $name=$directory[$index]['company'];
		if($is_formatted_textscreen)
			{
			require_once('AastraIPPhoneFormattedTextScreen.class.php');
			$object=new AastraIPPhoneFormattedTextScreen();
			$object->setDestroyOnExit();
			$object->addLine($name,'double');
			if($directory[$index]['company']!='') $object->addLine($directory[$index]['company']);
			else $object->addLine(Aastra_get_label('Company...',$language));
			if($directory[$index]['title']!='') $object->addLine($directory[$index]['title']);
			else $object->addLine(Aastra_get_label('Title...',$language));
			}
		else
			{
			require_once('AastraIPPhoneTextScreen.class.php');
			$object=new AastraIPPhoneTextScreen();
			$object->setDestroyOnExit();
			$object->setTitle($name);
			if($directory[$index]['company']!='') $text=$directory[$index]['company'];
			else $text=Aastra_get_label('Company...',$language);
			if($directory[$index]['title']!='') $text.=' '.$directory[$index]['title'];
			else $text.=' '.Aastra_get_label('Title...',$language);
			$object->setText($text);
			}

		# Softkeys
		if($nb_softkeys)
			{
			if($nb_softkeys==6)
				{
				$object->addSoftkey('4',Aastra_get_label('Back',$language),$XML_SERVER.'&action=zoom');
				$object->addSoftkey('6',Aastra_get_label('Exit',$language),'SoftKey:Exit');
				}
			else
				{
				$object->addSoftkey('9',Aastra_get_label('Back',$language),$XML_SERVER.'&action=zoom',1);
				$object->addSoftkey('10',Aastra_get_label('Exit',$language),'SoftKey:Exit',2);
				$object->setCancelAction($XML_SERVER.'&action=zoom');
				$object->addIcon(1,'Icon:ArrowLeft');
				$object->addIcon(2,'Icon:CircleRed');
				}
			}
		else $object->setCancelAction($XML_SERVER.'&action=zoom');
		break;


	# Speed dial
	case 'speed':
		# Update global variable
		$XML_SERVER.='&lookup='.$lookup.'&lastn='.$lastn.'&firstn='.$firstn.'&index='.$index.'&page='.$page.'&company='.$company;

		# Get user context
		if($asterisk) $conf_speed=Aastra_get_user_context(Aastra_get_userdevice_Asterisk($user),'speed');
		else $conf_speed=Aastra_get_user_context($user,'speed');
		$found=0;
		$i=0;
		while(($found==0) and ($i<$MaxLines))
			{
			if($conf_speed[$i]['name']=='') $found=1;
			$i++;
			}

		require_once('AastraIPPhoneTextMenu.class.php');
		$object=new AastraIPPhoneTextMenu();
		$object->setDestroyOnExit();
		if($found==1) $object->setDefaultIndex($i);
		$object->setTitle(Aastra_get_label('Speed Dial List',$language));
		for($i=0;$i<AASTRA_MAXLINES;$i++)
			{
			$name=$conf_speed[$i]['name'];
			if($name=='') 
				{
				if($nb_softkeys==10) $name=($i+1).'. .................................................';
				else $name='..................';
				}
			else
				{
				if($nb_softkeys==10) $name=($i+1).'. '.$name;
				}
			$object->addEntry($name,$XML_SERVER.'&action=set_speed&speed='.$i,'');
			}

		# Softkeys
		if($nb_softkeys)
			{
			if($nb_softkeys==6)
				{
				$object->addSoftkey('1',Aastra_get_label('Select',$language),'SoftKey:Select');
				$object->addSoftkey('4',Aastra_get_label('Cancel',$language),$XML_SERVER.'&action=zoom');
				$object->addSoftkey('6',Aastra_get_label('Exit',$language),'SoftKey:Exit');
				}
			else
				{
				$object->addSoftkey('9',Aastra_get_label('Cancel',$language),$XML_SERVER.'&action=zoom',1);
				$object->addSoftkey('10',Aastra_get_label('Exit',$language),'SoftKey:Exit',2);
				$object->setCancelAction($XML_SERVER.'&action=zoom');
				$object->addIcon(1,'Icon:PresenceNotAvailable');
				$object->addIcon(2,'Icon:CircleRed');
				}
			}
		break;

	# Set speed dial
	case 'set_speed':
		# Retrieve session
		$array=Aastra_read_session('csv_directory');
		$temp=unserialize(base64_decode($array['csv_directory']));
		$directory=$temp[1];

		# Update global variable
		$XML_SERVER.='&lookup='.$lookup.'&lastn='.$lastn.'&firstn='.$firstn.'&index='.$index.'&page='.$page.'&company='.$company;

		# Get user context
		if($asterisk) $conf_speed=Aastra_get_user_context(Aastra_get_userdevice_Asterisk($user),'speed');
		else $conf_speed=Aastra_get_user_context($user,'speed');

		# Save the new speed dial
		if($ARRAY_USER['display']=='firstlast') $name=$directory[$index]['first'].' '.$directory[$index]['last'];
		else $name=$directory[$index]['last'].' '.$directory[$index]['first'];
		if($name==' ') $name=$directory[$index]['company'];
		$conf_speed[$speed]['name']=$name;
		if($directory[$index]['work']!='') $conf_speed[$speed]['work']=prepare_number($directory[$index]['work'],2,$ARRAY_CONFIG['Dialplan']);
		if($directory[$index]['mobile']!='') $conf_speed[$speed]['mobile']=prepare_number($directory[$index]['mobile'],2,$ARRAY_CONFIG['Dialplan']);
		if($directory[$index]['home']!='') $conf_speed[$speed]['home']=prepare_number($directory[$index]['home'],2,$ARRAY_CONFIG['Dialplan']);
		$conf_speed[$speed]['other']='';
		if($asterisk) Aastra_save_user_context(Aastra_get_userdevice_Asterisk($user),'speed',$conf_speed);
		else Aastra_save_user_context($user,'speed',$conf_speed);
		
		# Display update
		require_once('AastraIPPhoneTextScreen.class.php');
		$object=new AastraIPPhoneTextScreen();
		$object->setDestroyOnExit();
		$object->setTitle(Aastra_get_label('List Updated',$language));
		$object->setText(sprintf(Aastra_get_label('%s stored in speed dial list at position %d.',$language),$name,$speed+1));

		# Softkeys
		if($nb_softkeys)
			{
			if($nb_softkeys==6)
				{
				$object->addSoftkey('4',Aastra_get_label('Back',$language),$XML_SERVER.'&action=zoom');
				$object->addSoftkey('6',Aastra_get_label('Exit',$language),'SoftKey:Exit');
				}
			else
				{
				$object->addSoftkey('9',Aastra_get_label('Back',$language),$XML_SERVER.'&action=zoom',1);
				$object->addSoftkey('10',Aastra_get_label('Exit',$language),'SoftKey:Exit',2);
				$object->setCancelAction($XML_SERVER.'&action=zoom');
				$object->addIcon(1,'Icon:ArrowLeft');
				$object->addIcon(2,'Icon:CircleRed');
				}
			}
		break;

	# Default
	default:
		# Do nothing
		require_once('AastraIPPhoneExecute.class.php');
		$object=new AastraIPPhoneExecute();
		$object->addEntry('');
		break;
	}

# Send XML answer
$object->output();
exit;
?>
