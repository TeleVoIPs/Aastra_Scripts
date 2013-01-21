<?php
#####################################################################
# Server side speed dial
#
# Aastra SIP Phones R1.4.2 or better
# Copyright 2008 Aastra Telecom Ltd
#
# Supported Aastra Phones
#    All Phones 
#
# Usage
# 	script.php?user=XXX&mode=MODE
# 	XXX is the extension of the phone on the platform. If the user
# 	is not provided, the MAC address is used instead.
#      MODE is the behavior mode, static or dynamic (optional)
#
#####################################################################

#############################################################################
# PHP customization for includes and warnings
#############################################################################
$os = strtolower(PHP_OS);
if(strpos($os, 'win') === false) ini_set('include_path',ini_get('include_path').':include:../include');
else ini_set('include_path',ini_get('include_path').';include;..\include');
error_reporting(E_ERROR | E_PARSE);

#####################################################################
# Includes
#####################################################################
require_once('AastraCommon.php');

#####################################################################
# Beginning of the active code
#####################################################################

# Retrieve parsed data
$user=Aastra_getvar_safe('user');
if($user=='')
	{
	$header=Aastra_decode_HTTP_header();
	$user=$header['mac'];
	}
$action=Aastra_getvar_safe('action','list');
$selection=Aastra_getvar_safe('selection');
$value=Aastra_getvar_safe('value');
$step=Aastra_getvar_safe('step','1');
$input1=Aastra_getvar_safe('input1');
$input2=Aastra_getvar_safe('input2');
$input3=Aastra_getvar_safe('input3');
$input4=Aastra_getvar_safe('input4');
$input5=Aastra_getvar_safe('input5');
$mode=Aastra_getvar_safe('mode','dynamic');

# Check if in Asterisk mode
$asterisk=False;
if(file_exists('../include/AastraAsterisk.php'))
	{
	$asterisk=True;
	require_once('AastraAsterisk.php');
	}

# Log call to the application
if($asterisk) Aastra_trace_call('asterisk_speed','user='.$user.', action='.$action.', selection='.$selection.', value='.$value.', step='.$step);
else Aastra_trace_call('speed','user='.$user.', action='.$action.', selection='.$selection.', value='.$value.', step='.$step);

# Test User Agent
Aastra_test_phone_versions(array('1'=>'1.4.2.','2'=>'1.4.2.','3'=>'2.5.3.','4'=>'2.5.3.','5'=>'3.0.1.'),'0');

# Global data
$XML_SERVER.='?user='.$user.'&mode='.$mode;

# Get Language
$language=Aastra_get_language();

# Init data
if($asterisk) $data=Aastra_get_user_context(Aastra_get_userdevice_Asterisk($user),'speed');
else $data=Aastra_get_user_context($user,'speed');

# Get global compatibility
$is_multipleinputfields=Aastra_is_multipleinputfields_supported();
$nb_softkeys=Aastra_number_softkeys_supported();
$is_icons=Aastra_is_icons_supported();
$is_style_textmenu=Aastra_is_style_textmenu_supported();

# Pre-Process action
switch($action)
	{
	# UP
	case 'up':
		# Next action
		$action='nothing';

		# Entry must be real
		if($data[$selection]['name']!='')
			{
			# Not the first one
			if($selection!=0)
				{
				# Switch inputs
				$temp=$data[$selection-1];
				$data[$selection-1]=$data[$selection];
				$data[$selection]=$temp;
				if($data[$selection]['name']=='') unset($data[$selection]);

				# Save update
				if($asterisk) Aastra_save_user_context(Aastra_get_userdevice_Asterisk($user),'speed',$data);
				else Aastra_save_user_context($user,'speed',$data);

				# Next step
				$action='list';
				$selection=$selection-1;
				}
			}	
		break;

	# DOWN
	case 'down':
		# Next action
		$action='nothing';

		# Entry must be real
		if($data[$selection]['name']!='')
			{
			# Not the last one
			if($selection!=(AASTRA_MAXLINES-1))
				{
				# Switch inputs
				$temp=$data[$selection+1];
				$data[$selection+1]=$data[$selection];
				$data[$selection]=$temp;
				if($data[$selection]['name']=='') unset($data[$selection]);

				# Save update
				if($asterisk) Aastra_save_user_context(Aastra_get_userdevice_Asterisk($user),'speed',$data);
				else Aastra_save_user_context($user,'speed',$data);

				# Next step
				$action='list';
				$selection=$selection+1;
				}
			}
		break;

	# Page up
	case 'pgup':
		# Next action
		$action='nothing';

		# Compute next index
		if($selection>3) 
			{
			$selection-=4;
			$action='list';
			}
		else
			{
			if($selection!=0) 
				{
				$selection=0;
				$action='list';
				}
			}
		break;


	# Page down
	case 'pgdn':
		# Next action
		$action='nothing';

		# Compute next index
		if($selection<(AASTRA_MAXLINES-4)) 
			{
			$selection+=4;
			$action='list';
			}
		else
			{
			if($selection!=(AASTRA_MAXLINES-1)) 
				{
				$selection=AASTRA_MAXLINES-1;
				$action='list';
				}
			}
		break;

	# CLEAR
	case 'clear':
		# Next action
		$action='nothing';

		# Entry must be real
		if($data[$selection]['name']!='')
			{
			# Clear selection
			unset($data[$selection]);

			# Update user data
			if($asterisk) $data=Aastra_save_user_context(Aastra_get_userdevice_Asterisk($user),'speed',$data);
			else Aastra_save_user_context($user,'speed',$data);

			# Next action
			$action='list';
			}
		break;
	}

# Pre-Process action
switch($action)
	{
	# SET (Single input field)
	case 'set':
		# No error so far
		$error=0;

		# Name is mandatory
		if(($value=='') and ($step=='1')) $error=1;

		# At least one phone number
		if(($step=='4') and ($data['temp']['work']=='') and ($data['temp']['mobile']=='') and ($data[$selection]['home']=='') and ($data[$selection]['other']=='')) $error=2;

		# Still OK
		if($error==0)
			{
			# Next step
			require_once('AastraIPPhoneExecute.class.php');
			$object=new AastraIPPhoneExecute();
			$array=array('0'=>'name','1'=>'work','2'=>'mobile','3'=>'home','4'=>'other');
			$data['temp'][$array[$step-1]]=$value;
			if($step<5)
				{
				$step++;
				$object->addEntry($XML_SERVER.'&action=edit&selection='.$selection.'&step='.$step);
				}
			else 
				{
				$data[$selection]=$data['temp'];
				unset($data['temp']);
				$object->addEntry($XML_SERVER.'&action=list&selection='.$selection);
				}

			# Update user data
			if($asterisk) $data=Aastra_save_user_context(Aastra_get_userdevice_Asterisk($user),'speed',$data);
			else Aastra_save_user_context($user,'speed',$data);
			}
		else
			{
			# Display error
			require_once('AastraIPPhoneTextScreen.class.php');
			$object=new AastraIPPhoneTextScreen();
			$object->setTitle(Aastra_get_label('User error',$language));
			if($error=='1') $object->setText(Aastra_get_label('The name is a mandatory field.',$language));
			else $object->setText(Aastra_get_label('At least one phone number is needed for this application.',$language));

			# Softkeys
			if($nb_softkeys) 
				{
				if($nb_softkeys==6) $object->addSoftkey('6',Aastra_get_label('Done',$language),$XML_SERVER.'&action=edit&selection='.$selection);
				else $object->addSoftkey('10',Aastra_get_label('Done',$language),$XML_SERVER.'&action=edit&selection='.$selection);
				}
			else $object->setDoneAction($XML_SERVER.'&action=edit&selection='.$selection);
			}
		break;

	# SET (Multiple input fields)
	case 'set2':
		# All inputs empty
		if(($input1=='') or (($input2=='') and ($input3=='') and ($input4=='') and ($input5=='')))
			{
			# Display error
			require_once('AastraIPPhoneTextScreen.class.php');
			$object=new AastraIPPhoneTextScreen();
			$object->setTitle(Aastra_get_label('User error',$language));
			$object->setText(Aastra_get_label('The name and at least one phone number are mandatory fields.',$language));
			if($nb_softkeys) 
				{
				if($nb_softkeys==6) 
					{
					$object->addSoftkey('5',Aastra_get_label('Cancel',$language),$XML_SERVER.'&selection='.$selection);
					$object->addSoftkey('6',Aastra_get_label('Close',$language),$XML_SERVER.'&action=edit&selection='.$selection.'&input1='.$input1.'&input2='.$input2.'&input3='.$input3.'&input4='.$input4.'&input5='.$input5);
					}
				else 
					{
					$object->addSoftkey('9',Aastra_get_label('Cancel',$language),$XML_SERVER.'&selection='.$selection);
					$object->addSoftkey('10',Aastra_get_label('Close',$language),$XML_SERVER.'&action=edit&selection='.$selection.'&input1='.$input1.'&input2='.$input2.'&input3='.$input3.'&input4='.$input4.'&input5='.$input5);
					}
				}
			else $object->setDoneAction($XML_SERVER.'&action=edit&selection='.$selection);
			}
		else
			{
			# Update user data
			$data[$selection]['name']=$input1;
			$data[$selection]['work']=$input2;
			$data[$selection]['mobile']=$input3;
			$data[$selection]['home']=$input4;
			$data[$selection]['other']=$input5;
			if($asterisk) $data=Aastra_save_user_context(Aastra_get_userdevice_Asterisk($user),'speed',$data);
			else Aastra_save_user_context($user,'speed',$data);

			# Back to the list
			require_once('AastraIPPhoneExecute.class.php');
			$object=new AastraIPPhoneExecute();
			$object->addEntry($XML_SERVER.'&action=list&selection='.$selection);
			}
		break;

	# EDIT
	case 'edit':
		# Multiple input fields?
		if(Aastra_is_multipleinputfields_supported())
			{
			# Input Screen
			require_once('AastraIPPhoneInputScreen.class.php');
			$object = new AastraIPPhoneInputScreen();
			$object->setDestroyOnExit();

			# Title
			$object->setTitle(sprintf(Aastra_get_label('Speed Dial %d',$language),$selection+1));

			# Target URL
			$object->setURL($XML_SERVER.'&action=set2&selection='.$selection.'&step='.$step);

			# Field Name
			$object->addField('string');
			$object->setFieldPrompt(Aastra_get_label('Name',$language));
			$object->setFieldParameter('input1');
			if($data[$selection]['name']!='') $default=$data[$selection]['name'];
			else $default=$input1;
			$object->setFieldDefault($default);
			if($nb_softkeys==6)
				{
				$object->addFieldSoftkey('3',Aastra_get_label('ABC',$language),'SoftKey:ChangeMode');
				$object->addFieldSoftkey('4',Aastra_get_label('NextSpace',$language),'SoftKey:NextSpace');
				}

			# Field Work Number
			$object->addField('number');
			$object->setFieldPrompt(Aastra_get_label('Work number',$language));
			$object->setFieldParameter('input2');
			if($data[$selection]['work']!='') $default=$data[$selection]['work'];
			else $default=$input2;
			$object->setFieldDefault($default);

			# Field Mobile Number
			$object->addField('number');
			$object->setFieldPrompt(Aastra_get_label('Mobile number',$language));
			$object->setFieldParameter('input3');
			if($data[$selection]['mobile']!='') $default=$data[$selection]['mobile'];
			else $default=$input3;
			$object->setFieldDefault($default);

			# Field Home number
			$object->addField('number');
			$object->setFieldPrompt(Aastra_get_label('Home number',$language));
			$object->setFieldParameter('input4');
			if($data[$selection]['home']!='') $default=$data[$selection]['home'];
			else $default=$input4;
			$object->setFieldDefault($default);

			# Field Other Number
			$object->addField('number');
			$object->setFieldPrompt(Aastra_get_label('Other number',$language));
			$object->setFieldParameter('input5');
			if($data[$selection]['other']!='') $default=$data[$selection]['other'];
			else $default=$input5;
			$object->setFieldDefault($default);

			# Common Softkeys
			if($nb_softkeys==6)
				{
				$object->addSoftkey('1',Aastra_get_label('Backspace',$language),'SoftKey:BackSpace');
				$object->addSoftkey('5',Aastra_get_label('Submit',$language),'SoftKey:Submit');
				$object->addSoftkey('6',Aastra_get_label('Cancel',$language),$XML_SERVER.'&selection='.$selection);
				}
			else $object->addSoftkey('9',Aastra_get_label('Cancel',$language),$XML_SERVER.'&selection='.$selection);
			}
		else
			{
			# Input Screen
			require_once('AastraIPPhoneInputScreen.class.php');
			$object=new AastraIPPhoneInputScreen();
			$object->setDestroyOnExit();

			# Name or number
			if($step==1) $object->setType('string');
			else $object->setType('number');

			# Title
			$object->setTitle(sprintf(Aastra_get_label('Speed Dial %d',$language),$selection+1));

			# Prompts
			if($step==1) $object->setPrompt(Aastra_get_label('Enter Name',$language));
			if($step==2) $object->setPrompt(Aastra_get_label('Enter Work Number',$language));
			if($step==3) $object->setPrompt(Aastra_get_label('Enter Mobile Number',$language));
			if($step==4) $object->setPrompt(Aastra_get_label('Enter Home Number',$language));
			if($step==5) $object->setPrompt(Aastra_get_label('Enter Other Number',$language));
			$object->setParameter('value');
			$object->setURL($XML_SERVER.'&action=set&selection='.$selection.'&step='.$step);
			$array=array('0'=>'name','1'=>'work','2'=>'mobile','3'=>'home','4'=>'other');
			$object->setDefault($data[$selection][$array[$step-1]]);
	
			# Softkeys
			if($nb_softkeys)
				{
				$object->addSoftkey('1',Aastra_get_label('Backspace',$language),'SoftKey:BackSpace');
				if($step==1)
					{
					$object->addSoftkey('3', Aastra_get_label('ABC',$language),'SoftKey:ChangeMode');
					$object->addSoftkey('4', Aastra_get_label('NextSpace',$language),'SoftKey:NextSpace');
					}
				$object->addSoftkey('5',Aastra_get_label('Done',$language),'SoftKey:Submit');
				if($step!=1) 
					{
					$step--;
					$object->addSoftkey('6',Aastra_get_label('Back',$language),$XML_SERVER.'&action=edit&selection='.$selection.'&step='.$step);
					}
				else $object->addSoftkey('6',Aastra_get_label('Back',$language),$XML_SERVER.'&action=list&selection='.$selection);
				}
			else
				{
				if($step!=1) 
					{
					$step--;
					$object->setCancelAction($XML_SERVER.'&action=edit&selection='.$selection.'&step='.$step);
					}
				else $object->setCancelAction($XML_SERVER.'&action=list&selection='.$selection);
				}
			}
		break;

	# VIEW
	case 'view':
		# Real input
		if($data[$selection]['name']!='')
			{
			# At least one number
			if(($data[$selection]['work']!='') || ($data[$selection]['mobile']!='') || ($data[$selection]['home']!='') || ($data[$selection]['other']!=''))
				{
				# Display list as TextMenu
				require_once('AastraIPPhoneTextMenu.class.php');
				$object=new AastraIPPhoneTextMenu();
				$object->setDestroyOnExit();
				if($is_style_textmenu) $object->setStyle('none');

				# Title
				$object->setTitle($data[$selection]['name']);
				$title=array(	'1'=>Aastra_get_label('W',$language),
						'2'=>Aastra_get_label('M',$language),
						'3'=>Aastra_get_label('H',$language),
						'4'=>Aastra_get_label('O',$language));
				$array=array('1'=>'work','2'=>'mobile','3'=>'home','4'=>'other');
				for($i=1;$i<5;$i++)
					{
					$name=$data[$selection][$array[$i]];
					if($name!='')
						{
						if(!$is_icons) $name=$title[$i].' '.$data[$selection][$array[$i]];
						else $name=$data[$selection][$array[$i]];
						$number=$data[$selection][$array[$i]];
						if((!$nb_softkeys) or ($nb_softkeys==10)) $number='Dial:'.$number;
						if(!$is_icons) 
							{
							if($nb_softkeys==10) $object->addEntry($name,$number,'','',$data[$selection][$array[$i]]);
							else $object->addEntry($name,$number,'');
							}
						else $object->addEntry($name,$number,'',$i);
						}
					}

				# Softkeys
				if($nb_softkeys)
					{
					if($nb_softkeys==6)
						{
						$object->addSoftkey('1',Aastra_get_label('Dial',$language),'SoftKey:Dial');
						$object->addSoftkey('5',Aastra_get_label('Back',$language),$XML_SERVER.'&selection='.$selection);
						$object->addSoftkey('6',Aastra_get_label('Exit',$language),'SoftKey:Exit');
						}
					else
						{
						$object->addSoftkey('9',Aastra_get_label('Back',$language),$XML_SERVER.'&selection='.$selection);
						$object->addSoftkey('10',Aastra_get_label('Exit',$language),'SoftKey:Exit');
						}
					}
				else
					{
					$object->addEntry('Clear',$XML_SERVER.'&action=clear&selection='.$selection);
					$object->addEntry('Edit',$XML_SERVER.'&action=edit&step=1&selection='.$selection);
					}

				# Icons
				if($is_icons)
					{
					if(Aastra_phone_type()!=5)
						{
						$object->addIcon(1,Aastra_get_custom_icon('Office'));
						$object->addIcon(2,Aastra_get_custom_icon('Cellphone'));
						$object->addIcon(3,Aastra_get_custom_icon('Home'));
						$object->addIcon(4,Aastra_get_custom_icon('Phone'));
						}
					else
						{
						$object->addIcon(1,'Icon:Office');
						$object->addIcon(2,'Icon:CellPhone');
						$object->addIcon(3,'Icon:Home');
						$object->addIcon(4,'Icon:PhoneOnHook');
						}
					}
				}
			else
				{
				# Display error
				require_once('AastraIPPhoneTextScreen.class.php');
				$object=new AastraIPPhoneTextScreen();
				$object->setTitle($data[$selection]['name']);
				$object->setText(Aastra_get_label('No phone number associated to this name.',$language));
				$object->addSoftkey('6',Aastra_get_label('Done',$language),$XML_SERVER.'&action=list&selection='.$selection);
				}
			}
		else
			{
			# Back to the list
			require_once('AastraIPPhoneExecute.class.php');
			$object=new AastraIPPhoneExecute();
			$object->addEntry($XML_SERVER.'&action=list&selection='.$selection);
			}
		break;

	# List of speed dial
	case 'list':
		# Display speeddials as a list
		require_once('AastraIPPhoneTextMenu.class.php');
		$object=new AastraIPPhoneTextMenu();
		$object->setDestroyOnExit();

		# Title
		$object->setTitle(Aastra_get_label('Speed Dial List',$language));

		# Default index
		if($selection!='') $object->setDefaultIndex($selection+1);

		# Display items
		$nb_entries=0;
		$object->setBase($XML_SERVER);
		for($i=0;$i<AASTRA_MAXLINES;$i++)
			{
			$name=$data[$i]['name'];
			if($name=='') 
				{
				if($mode=='dynamic')
					{
					if($nb_softkeys==10) $name=($i+1).'. .................................................';
					else $name='..................';
					$object->addEntry($name,'&action=edit&selection='.$i,$i);
					}
				}
			else 
				{
				if($nb_softkeys==10) 
					{
					$name=($i+1).'. '.$name;
					$array_test=array('work','mobile','home','other');
					$dial='';
					$count=0;
					foreach($array_test as $value)
						{
						if($data[$i][$value]!='')
							{
							if($dial=='') $dial=$data[$i][$value];
							$count++;
							}
						}
					if($count==1) $object->addEntry($name,'&action=view&selection='.$i,$i,'',$dial);
					else $object->addEntry($name,'&action=view&selection='.$i,$i);
					}
				else $object->addEntry($name,'&action=view&selection='.$i,$i);
				$nb_entries++;
				}
			}

		# At least one item in static mode
		if(($mode!='dynamic') and ($nb_entries==0)) $object->addEntry(Aastra_get_label('NO ENTRY',$language),'');

		# Add Softkeys
		if($nb_softkeys)
			{
			if($nb_softkeys==6)
				{
				$object->addSoftkey('1',Aastra_get_label('Select',$language),'SoftKey:Select');
				if($mode=='dynamic')
					{
					$object->addSoftkey('2',Aastra_get_label('Move Up',$language),$XML_SERVER.'&action=up');
					$object->addSoftkey('3',Aastra_get_label('Clear',$language),$XML_SERVER.'&action=clear');
					$object->addSoftkey('4',Aastra_get_label('Edit',$language),$XML_SERVER.'&action=edit');
					$object->addSoftkey('5',Aastra_get_label('Move Down',$language),$XML_SERVER.'&action=down');
					}
				$object->addSoftkey('6',Aastra_get_label('Exit',$language),'SoftKey:Exit');
				}
			else
				{
				if($mode=='dynamic')
					{
					$object->addSoftkey('3',Aastra_get_label('Move Up',$language),$XML_SERVER.'&action=up');
					$object->addSoftkey('5',Aastra_get_label('Clear',$language),$XML_SERVER.'&action=clear');
					$object->addSoftkey('6',Aastra_get_label('Edit',$language),$XML_SERVER.'&action=edit');
					$object->addSoftkey('8',Aastra_get_label('Move Down',$language),$XML_SERVER.'&action=down');
					$object->addSoftkey('4',Aastra_get_label('Page Up',$language),$XML_SERVER.'&action=pgup');
					$object->addSoftkey('9',Aastra_get_label('Page Down',$language),$XML_SERVER.'&action=pgdn');
					}
				$object->addSoftkey('10',Aastra_get_label('Exit',$language),'SoftKey:Exit');
				}
			}
		break;

	# Nothing
	case 'nothing':
		# Back to the list
		require_once('AastraIPPhoneExecute.class.php');
		$object=new AastraIPPhoneExecute();
		$object->addEntry('');
		break;
	}

# Display object
$object->output();
exit;
?>