<?php 
#############################################################################
# Mymenu
#
# Aastra SIP Phones 1.4.2 or better
#
# Copyright 2008 Aastra Telecom Ltd
#
# Supported Aastra Phones
#   All phones
#
# Usage
# 	script.php?menu_user=XXX&menu_source=YYY
# 	XXX is the extension of the phone on the platform
#	YYY is the name of the menu file to use.
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
require_once('AastraCommon.php');
require_once('AastraIPPhoneTextMenu.class.php');
require_once('AastraIPPhoneTextScreen.class.php');
require_once('AastraIPPhoneExecute.class.php');

#############################################################################
# Private functions
#############################################################################
function get_user_config($user,$menu_source,$menu_mode)
{
Global $language;

# Retrieve phone data
$update=0;
$header=Aastra_decode_HTTP_header();
$header['model']=strtolower($header['model']);
$is_softkeys_supported=Aastra_is_softkeys_supported();

# Read all menu
$all=Aastra_readINIfile($menu_source.'.menu','#','=');

# Get user config
if($is_softkeys_supported and ($menu_mode=='dynamic')) $config=Aastra_get_user_context($user,'mymenu'.'_'.$menu_source);
else $config=NULL;

# File does not exist
if($config==NULL)
	{
	unset($all['RESERVED']);
	foreach($all as $key=>$value)
		{
		if($value[$header['model']]=='no') unset($all[$key]);
		else
			{
			$all[$key]['title']=Aastra_get_label($all[$key]['title'],$language);
			}	
		}
	Aastra_natsort2d($all,'title');
	foreach($all as $key=>$value) $config['menu'][]=$key;
	if($is_softkeys_supported and ($menu_mode=='dynamic')) $update=1;
	}
else
	{
	# Read config file
	foreach($config['menu'] as $key=>$value)
		{
		if(($all[$value]==NULL) or ($all[$value][$header['model']]=='no'))
			{
			unset($config['menu'][$key]);
			$update=1;			
			}
		}
	}

# Read final file
if($update==1)
	{
	Aastra_save_user_context($user,'mymenu'.'_'.$menu_source,$config);
	unset($config);
	$config=Aastra_get_user_context($user,'mymenu'.'_'.$menu_source);
	}

# Return array
return($config);
}

###############################################################################
# Beginning of the active code
###############################################################################
# Collect parameters
$menu_source=Aastra_getvar_safe('menu_source','all');
$menu_user=Aastra_getvar_safe('menu_user');
$menu_page=Aastra_getvar_safe('menu_page','1');
$menu_page2=Aastra_getvar_safe('menu_page2','1');
$menu_action=Aastra_getvar_safe('menu_action','list');
$menu_set=Aastra_getvar_safe('menu_set','1');
$menu_pos=Aastra_getvar_safe('menu_pos');
$menu_mode=Aastra_getvar_safe('menu_mode','dynamic');
$selection=Aastra_getvar_safe('selection');

# Get MAC address and type of phone
$header=Aastra_decode_HTTP_header();
if($menu_user=='') $menu_user=$header['mac'];

# Get Language
$language=Aastra_get_language();

# Trace
Aastra_trace_call('mymenu','menu_source='.$menu_source.', menu_user='.$menu_user.', menu_action='.$menu_action.', selection='.$selection.', menu_pos='.$menu_pos.', menu_page='.$menu_page);

# Test menu_user Agent
Aastra_test_phone_version('1.4.2.',0);

# Get global compatibility
$nb_softkeys=Aastra_number_softkeys_supported();
$is_style_textmenu=Aastra_is_style_textmenu_supported();

# To handle non softkey phones
if($nb_softkeys) $MaxLines=AASTRA_MAXLINES;
else $MaxLines=AASTRA_MAXLINES-2;

# Update URI
$XML_SERVER.='?menu_source='.$menu_source.'&menu_user='.$menu_user.'&menu_mode='.$menu_mode;

# Process menu_action
switch($menu_action)
	{
	# List menus
	case 'list':
	case 'listr':
		# Display MENU
		if ((empty($menu_source)) || (!file_exists($menu_source.'.menu'))) 
			{
			$object = new AastraIPPhoneTextScreen();
			$object->setDestroyOnExit();
			$object->setTitle(Aastra_get_label('Configuration error',$language));
			$object->setText(Aastra_get_label('Please check your configuration or contact your administrator.',$language));
			if($nb_softkeys)
				{
				if($nb_softkeys==6) $object->addSoftkey('6', Aastra_get_label('Exit',$language), 'SoftKey:Exit');
				else $object->addSoftkey('10', Aastra_get_label('Exit',$language), 'SoftKey:Exit');
				}
			}
		else
			{
			# Load menu config file
			$all=Aastra_readINIfile($menu_source.'.menu','#','=');
			foreach($all as $key=>$value)
				{
				if($value[$header['model']]=='no') unset($all[$key]);
				else
					{
					$all[$key]['title']=Aastra_get_label($all[$key]['title'],$language);
					}				
				}

			# Load menu_user config file
			$config=get_user_config($menu_user,$menu_source,$menu_mode);

			# At least one menu?
			if(count($config['menu'])>0)
				{
				# Retrieve last menu_page
				$nb_menu_pages=count($config['menu']);
				$last=intval($nb_menu_pages/$MaxLines);
				if(($nb_menu_pages-$last*$MaxLines) != 0) $last++;
				if($menu_page>$last) $menu_page=$last;

				# Display menu
				$object=new AastraIPPhoneTextMenu();
				$object->setDestroyOnExit();
				if(($menu_action=='list') and ($menu_pos!='')) $object->setDefaultIndex($menu_pos);
				$title=$all['RESERVED']['title'];
				if($last!=1) $title.=' ('.$menu_page.'/'.$last.')';
				$object->setTitle($title);
				if($is_style_textmenu) $object->setStyle('none');
				$index=0;
				$menu_pos=1;
				if((!$nb_softkeys) and ($menu_page!=1)) $object->addEntry(Aastra_get_label('Previous Page',$language),$XML_SERVER.'&menu_page='.($menu_page-1));
				$search=array('/\$\$AA_XML_SERVER_AA\$\$/','/\$\$AA_XMLDIRECTORY_AA\$\$/');
				$replace=array($AA_XML_SERVER,$AA_XMLDIRECTORY);
				foreach($config['menu'] as $key=>$value)
					{
					$uri=preg_replace($search, $replace, $all[$value]['uri']);
					if(($index >= ($menu_page-1)*$MaxLines) and ($index < $menu_page*$MaxLines)) 
						{
						if($all[$value]['param']!='')
							{
							$split=explode(',',$all[$value]['param']);
							foreach($split as $key2) $uri.=((strpos($uri,'?')===false) ? '?':'&').$key2.'='.$_GET[$key2];
							}
						$object->addEntry($all[$value]['title'],$uri,$key.'&menu_pos='.$menu_pos);
						$menu_pos++;
						}
					$index++;
					}
				
				# Check if some menus are missing
				$header['model']=strtolower($header['model']);
				unset($all['RESERVED']);
				foreach($all as $key=>$value)
					{
					if($value[$header['model']]=='no') unset($all[$key]);
					}
				foreach($config['menu'] as $key=>$value) unset($all[$value]);

				# Softkeys
				if($nb_softkeys)
					{
					if($menu_mode=='dynamic')
						{
						if($nb_softkeys==6)
							{
							if($menu_set==1)
								{
								$object->addSoftkey('1', Aastra_get_label('Select',$language), 'SoftKey:Select');
								if($menu_page!=1) $object->addSoftkey('2', Aastra_get_label('Previous',$language), $XML_SERVER.'&menu_action=listr&menu_page='.($menu_page-1));
								if(count($all)>0) $object->addSoftkey('3', Aastra_get_label('Add',$language), $XML_SERVER.'&menu_action=add&menu_page='.$menu_page);
								$object->addSoftkey('4', Aastra_get_label('Remove',$language), $XML_SERVER.'&menu_action=remove&menu_page='.$menu_page);
								if($menu_page!=$last) $object->addSoftkey('5', Aastra_get_label('Next',$language), $XML_SERVER.'&menu_action=listr&menu_page='.($menu_page+1).'&menu_pos=');
								$object->addSoftkey('6', Aastra_get_label('More',$language), $XML_SERVER.'&menu_set=2&menu_page='.$menu_page);
								}
							else
								{
								$object->addSoftkey('1', Aastra_get_label('Select',$language), 'SoftKey:Select');
								$object->addSoftkey('2', Aastra_get_label('Move Up',$language), $XML_SERVER.'&menu_action=up&menu_page='.$menu_page.'&menu_set='.$menu_set);
								$object->addSoftkey('3', Aastra_get_label('Sort A-Z',$language), $XML_SERVER.'&menu_action=sortA&menu_set='.$menu_set);
								$object->addSoftkey('4', Aastra_get_label('Exit',$language), 'SoftKey:Exit');
								$object->addSoftkey('5', Aastra_get_label('Move Down',$language), $XML_SERVER.'&menu_action=down&menu_page='.$menu_page.'&menu_set='.$menu_set);
								$object->addSoftkey('6', Aastra_get_label('More',$language), $XML_SERVER.'&menu_set=1&menu_page='.$menu_page);
								}
							}
						else
							{
							if(count($all)>0) $object->addSoftkey('1', Aastra_get_label('Add',$language), $XML_SERVER.'&menu_action=add&menu_page='.$menu_page);
							$object->addSoftkey('2', Aastra_get_label('Remove',$language), $XML_SERVER.'&menu_action=remove&menu_page='.$menu_page);
							if($menu_page!=1) $object->addSoftkey('3', Aastra_get_label('Previous',$language), $XML_SERVER.'&menu_action=listr&menu_page='.($menu_page-1));
							if($menu_page!=$last) $object->addSoftkey('8', Aastra_get_label('Next',$language), $XML_SERVER.'&menu_action=listr&menu_page='.($menu_page+1).'&menu_pos=');
							$object->addSoftkey('4', Aastra_get_label('Move Up',$language), $XML_SERVER.'&menu_action=up&menu_page='.$menu_page.'&menu_set='.$menu_set);
							$object->addSoftkey('6', Aastra_get_label('Sort A-Z',$language), $XML_SERVER.'&menu_action=sortA&menu_set='.$menu_set);
							$object->addSoftkey('9', Aastra_get_label('Move Down',$language), $XML_SERVER.'&menu_action=down&menu_page='.$menu_page.'&menu_set='.$menu_set);
							$object->addSoftkey('10', Aastra_get_label('Exit',$language), 'SoftKey:Exit');
							}
						}
					else
						{
						if($nb_softkeys==6)
							{
							$object->addSoftkey('1', Aastra_get_label('Select',$language), 'SoftKey:Select');
							if($menu_page!=1) $object->addSoftkey('2', Aastra_get_label('Previous',$language), $XML_SERVER.'&menu_action=listr&menu_page='.($menu_page-1));
							if($menu_page!=$last) $object->addSoftkey('5', Aastra_get_label('Next',$language), $XML_SERVER.'&menu_action=listr&menu_page='.($menu_page+1).'&menu_pos=');
							$object->addSoftkey('6', Aastra_get_label('Exit',$language), 'SoftKey:Exit');
							}
						else
							{
							if($menu_page!=1) $object->addSoftkey('3', Aastra_get_label('Previous',$language), $XML_SERVER.'&menu_action=listr&menu_page='.($menu_page-1));
							if($menu_page!=$last) $object->addSoftkey('8', Aastra_get_label('Next',$language), $XML_SERVER.'&menu_action=listr&menu_page='.($menu_page+1).'&menu_pos=');
							$object->addSoftkey('10', Aastra_get_label('Exit',$language), 'SoftKey:Exit');
							}
						}
					}
				else
					{
					if($menu_page!=$last) $object->addEntry(Aastra_get_label('Next Page',$language),$XML_SERVER.'&menu_page='.($menu_page+1));
					}
				}
			else
				{
				# Display error
				$object = new AastraIPPhoneTextScreen();
				$object->setDestroyOnExit();
				$object->setTitle($all['RESERVED']['title']);
				$object->setText(Aastra_get_label('No application configured.',$language));
				if($nb_softkeys)
					{
					if($nb_softkeys==6)
						{
						if(count($all)>0) $object->addSoftkey('3', Aastra_get_label('Add',$language), $XML_SERVER.'&menu_action=add');
						$object->addSoftkey('6', Aastra_get_label('Exit',$language), 'SoftKey:Exit');
						}
					else
						{
						if(count($all)>0) $object->addSoftkey('1', Aastra_get_label('Add',$language), $XML_SERVER.'&menu_action=add');
						$object->addSoftkey('10', Aastra_get_label('Exit',$language), 'SoftKey:Exit');
						}
					}
				}
			}
		break;

	# Sort A-Z
	case 'sortA':
		# Load menu_user config file
		$config=Aastra_get_user_context($menu_user,'mymenu'.'_'.$menu_source);

		# Load menu config file
		$all=Aastra_readINIfile($menu_source.'.menu','#','=');
		foreach($all as $key=>$value)
			{
			$all[$key]['title']=Aastra_get_label($all[$key]['title'],$language);
			}

		# Prepare the sort
		$i=0;
		foreach($config['menu'] as $key=>$value)
			{
			$sort[$i]['index']=$value;
			$sort[$i]['sort']=$all[$value]['title'];
			$i++;
			}
		unset($config);

		# Sort A-Z on the localized label
		Aastra_natsort2d($sort,'sort');
		foreach($sort as $key=>$value) $config['menu'][]=$sort[$key]['index'];

		# Update user file
		Aastra_save_user_context($menu_user,'mymenu'.'_'.$menu_source,$config);

		# Display list   
     		$object=new AastraIPPhoneExecute();
		$object->addEntry($XML_SERVER.'&menu_action=list&menu_page=1&menu_set='.$menu_set.'&menu_pos=1');
		break;

	# Move up
	case 'up':
		# Not the first entry?
		if($selection!='0')
			{
			# Load menu_user config file
			$config=Aastra_get_user_context($menu_user,'mymenu'.'_'.$menu_source);
	
			# Up
			$temp=$config['menu'][$selection];
			$config['menu'][$selection]=$config['menu'][($selection-1)];
			$config['menu'][($selection-1)]=$temp;
			Aastra_save_user_context($menu_user,'mymenu'.'_'.$menu_source,$config);
			$menu_pos--;

			# Change page if needed
			if($selection%$MaxLines==0)
				{
				if($menu_page!='1')
					{
					$menu_page--;
					$menu_pos=$MaxLines;
					}
				}

			# Display list   
     			$object=new AastraIPPhoneExecute();
			$object->addEntry($XML_SERVER.'&menu_action=list&menu_page='.$menu_page.'&menu_set='.$menu_set.'&menu_pos='.$menu_pos);
			}
		else
			{
			# Do Nothing
     			$object=new AastraIPPhoneExecute();
			$object->setBeep();
			$object->addEntry('');
			}
		break;

	# Move down
	case 'down':
		# Load menu_user config file
		$config=Aastra_get_user_context($menu_user,'mymenu'.'_'.$menu_source);

		# Not the last entry?
		if($selection!=count($config['menu']))
			{
			# Down
			$temp=$config['menu'][($selection+1)];
			$config['menu'][($selection+1)]=$config['menu'][$selection];
			$config['menu'][$selection]=$temp;
			Aastra_save_user_context($menu_user,'mymenu'.'_'.$menu_source,$config);
			$menu_pos++;

			# Change page if needed
			if($selection%$MaxLines==($MaxLines-1))
				{
				$menu_page++;
				$menu_pos=1;
				}
	
			# Display list   
	     		$object=new AastraIPPhoneExecute();
			$object->addEntry($XML_SERVER.'&menu_action=list&menu_page='.$menu_page.'&menu_set='.$menu_set.'&menu_pos='.$menu_pos);
			}
		else
			{
			# Do Nothing
     			$object=new AastraIPPhoneExecute();
			$object->setBeep();
			$object->addEntry('');
			}
		break;

	# Remove a menu
	case 'remove':
		# Load menu_user config file
		$config=Aastra_get_user_context($menu_user,'mymenu'.'_'.$menu_source);

		# Remove Menu
		unset($config['menu'][$selection]);

		# Display menu
		Aastra_save_user_context($menu_user,'mymenu'.'_'.$menu_source,$config);

		# Display list   
     		$object = new AastraIPPhoneExecute();
		$object->addEntry($XML_SERVER.'&menu_action=list&menu_page='.$menu_page);
		break;

	# Add a menu in the list
	case 'update':
		# Load menu_user config file
		$config=Aastra_get_user_context($menu_user,'mymenu'.'_'.$menu_source);

		# Remove Menu
		$config['menu'][]=$selection;

		# Update configuration
		Aastra_save_user_context($menu_user,'mymenu'.'_'.$menu_source,$config);

		# Display list   
     		$object=new AastraIPPhoneExecute();
		$object->addEntry($XML_SERVER.'&menu_action=add&menu_page2='.$menu_page2);
		break;

	# Info on a menu
	case 'details':
		# Load menu config file
		$all=Aastra_readINIfile($menu_source.'.menu','#','=');
		foreach($all as $key=>$value)
			{
			$all[$key]['info']=Aastra_get_label($all[$key]['info'],$language);
			}

		# Display Help
		$object = new AastraIPPhoneTextScreen();
		$object->setDestroyOnExit();
		$object->setTitle($selection);
		if($all[$selection]['info']!='') $object->setText($all[$selection]['info']);
		else $object->setText(Aastra_get_label('No information available',$language));

		# Softkeys
		if($nb_softkeys)
			{
			if($nb_softkeys==6)
				{
				$object->addSoftkey('5', Aastra_get_label('Back',$language), $XML_SERVER.'&menu_action=add&menu_page2='.$menu_page2);
				$object->addSoftkey('6', Aastra_get_label('Exit',$language), 'SoftKey:Exit');
				}
			else
				{
				$object->addSoftkey('9', Aastra_get_label('Back',$language), $XML_SERVER.'&menu_action=add&menu_page2='.$menu_page2);
				$object->addSoftkey('10', Aastra_get_label('Exit',$language), 'SoftKey:Exit');
				$object->setCancelAction($XML_SERVER.'&menu_action=add&menu_page2='.$menu_page2);
				}
			}
		break;

	# Add a menu screen
	case 'add':
		# Load menu config file
		$header['model']=strtolower($header['model']);
		$all=Aastra_readINIfile($menu_source.'.menu','#','=');
		foreach($all as $key=>$value)
			{
			if($value[$header['model']]=='no') unset($all[$key]);
			else
				{
				$all[$key]['title']=Aastra_get_label($all[$key]['title'],$language);
				}	
			}
		Aastra_natsort2d($all,'title');

		# Load menu_user config file
		$config=Aastra_get_user_context($menu_user,'mymenu'.'_'.$menu_source);

		# Filter existing menus
		unset($all['RESERVED']);
		foreach($config['menu'] as $key=>$value) unset($all[$value]);

		# At least one to add
		if(count($all)>0)
			{
			# Retrieve last menu_page
			$nb_menu_pages=count($all);
			$last=intval($nb_menu_pages/$MaxLines);
			if(($nb_menu_pages-$last*$MaxLines) != 0) $last++;

			# Display menu
			$object=new AastraIPPhoneTextMenu();
			$object->setDestroyOnExit();
			$title=Aastra_get_label('Add Application',$language);
			if($last!=1) $title.=' ('.$menu_page.'/'.$last.')';
			$object->setTitle($title);
			if($is_style_textmenu) $object->setStyle('none');
			$index=0;
			foreach($all as $key=>$value)
				{
				if(($index >= ($menu_page2-1)*$MaxLines) and ($index < $menu_page2*$MaxLines)) $object->addEntry($value['title'],$XML_SERVER.'&menu_action=update&selection='.$key.'&menu_page2='.$menu_page2,$key);
				$index++;
				}

			# Softkeys
			if($nb_softkeys)
				{
				if($nb_softkeys==6)
					{
					$object->addSoftkey('1', Aastra_get_label('Add',$language), 'SoftKey:Select');
					if($menu_page2!=1) $object->addSoftkey('2', Aastra_get_label('Previous',$language), $XML_SERVER.'&menu_action=add&menu_page2='.($menu_page2-1));
					if($menu_page2!=$last) $object->addSoftkey('5', Aastra_get_label('Next',$language), $XML_SERVER.'&menu_action=add&menu_page2='.($menu_page2+1));
					$object->addSoftkey('3', Aastra_get_label('Details',$language), $XML_SERVER.'&menu_action=details&menu_page2='.$menu_page2);
					$object->addSoftkey('4', Aastra_get_label('Back',$language), $XML_SERVER.'&menu_page='.$menu_page);
					$object->addSoftkey('6', Aastra_get_label('Exit',$language), 'SoftKey:Exit');
					}
				else
					{
					$object->addSoftkey('1', Aastra_get_label('Add',$language), 'SoftKey:Select');
					if($menu_page2!=1) $object->addSoftkey('3', Aastra_get_label('Previous Page',$language), $XML_SERVER.'&menu_action=add&menu_page2='.($menu_page2-1));
					if($menu_page2!=$last) $object->addSoftkey('8', Aastra_get_label('Next Page',$language), $XML_SERVER.'&menu_action=add&menu_page2='.($menu_page2+1));
					$object->addSoftkey('6', Aastra_get_label('Details',$language), $XML_SERVER.'&menu_action=details&menu_page2='.$menu_page2);
					$object->addSoftkey('9', Aastra_get_label('Back',$language), $XML_SERVER.'&menu_page='.$menu_page);
					$object->addSoftkey('10', Aastra_get_label('Exit',$language), 'SoftKey:Exit');
					$object->setCancelAction('9', Aastra_get_label('Back',$language), $XML_SERVER.'&menu_page='.$menu_page);
					}
				}
			}
		else
			{
			# Display list   
     			$object=new AastraIPPhoneExecute();
			$object->addEntry($XML_SERVER.'&menu_action=list');
			}
		break;
	}

# Display object
$object->output();
exit;
?>
