<?php
#####################################################################
# SugarCRM contact access for Aastra SIP Phones R 1.4.21 or better
#
# php source code
# Provided by Aastra Telecom Ltd 2008
#
# Supported Aastra Phones
#   All but 9112i and 9133i
#
# Copyright Aastra Telecom 2008
#####################################################################

#############################################################################
# PHP customization for includes and warnings
#############################################################################
$os = strtolower(PHP_OS);
if(strpos($os, "win") === false) ini_set('include_path',ini_get('include_path').':include:../include');
else ini_set('include_path',ini_get('include_path').';include;../include');
error_reporting(E_ERROR | E_PARSE);

# Includes
require_once('AastraIPPhoneTextMenu.class.php');
require_once('AastraIPPhoneTextScreen.class.php');
require_once('AastraIPPhoneInputScreen.class.php');
require_once('AastraCommon.php');
require 'DB.php';

function prepare_number($phone,$type,$conf_dir)
{
$local=$phone;

if($type==1)
	{
	# Prepare name for display
	$local=preg_replace('/ /','',$local);
	}
else
	{
	# International dialing
	$search='+'.$conf_dir['country'].' ';
	$replace='/\+'.$conf_dir['country'].'/';
	if (strstr($local,$search)) $local=$conf_dir['long distance'].preg_replace($replace,'',$local);
	else $local=preg_replace('/\+/',$conf_dir['international'],$local);

	# Remove spaces and -
	$local=preg_replace('/ /','',$local);
	$local=preg_replace('/-/','',$local);

	# Remove optional (0)
	$local=preg_replace('/\(0\)/','',$local);

	# Remove ( and )
	$local=preg_replace('/\(/','',$local);
	$local=preg_replace('/\)/','',$local);
	}
return($local);
}

function querySQL($querytxt,$database) 
{
$db = DB::connect('mysql://root:passw0rd@localhost/'.$database);
$db->setFetchMode(DB_FETCHMODE_ASSOC);
$query = $db->query($querytxt);
return $query; 
}

function getRowSQL($querytxt,$database) 
{
$db = DB::connect('mysql://root:passw0rd@localhost/'.$database);
$db->setFetchMode(DB_FETCHMODE_ASSOC);
$query = $db->getRow($querytxt);
return $query; 
}

function getAllSQL($querytxt,$database) 
{
$db = DB::connect('mysql://root:passw0rd@localhost/'.$database);
$db->setFetchMode(DB_FETCHMODE_ASSOC);
$query = $db->getAll($querytxt);
return $query; 
}

function getRecord($index)
{
$query  = "SELECT first_name, last_name, phone_home, phone_work, phone_mobile, phone_other ";
$query .= "FROM contacts WHERE id = '".$index."' ";
$query .= "ORDER BY last_name ";
$Contact = getRowSQL($query,'sugarcrm');
return($Contact);
}

function getListRecords($lookup,$page,$MaxLines)
{
$query  = "SELECT id, first_name, last_name, phone_home, phone_work, phone_mobile, phone_other FROM contacts WHERE deleted = 0 ";
if ($lookup) $query .= "and last_name like '$lookup%' "; 
$query .= "order by last_name";
$query .= " LIMIT ".($page-1)*$MaxLines.",".$MaxLines; 
$ContactList = getAllSQL($query,'sugarcrm');

return $ContactList;
}

function countListRecords($lookup)
{
$query  = "SELECT COUNT(id) FROM contacts WHERE deleted = 0 ";
if ($lookup) $query .= "and last_name like '$lookup%' "; 
$query = getRowSQL($query,'sugarcrm');
return $query['COUNT(id)'];
}


#############################################################
# Global Variables

# Collect parameters
$lookup=Aastra_getvar_safe('lookup');
$page=Aastra_getvar_safe('page','1');
$index=Aastra_getvar_safe('index');
$selection=Aastra_getvar_safe('selection');
$action=Aastra_getvar_safe('action','input');

# Log call to the application
Aastra_trace_call('sugarCRM','lookup='.$lookup.', action='.$action);

# Test User Agent
Aastra_test_phone_version('1.4.2.',0);

# Test phone type
Aastra_test_phone_model(array('Aastra9112i','Aastra9133i'),False,0);

# Retrieve phone information
$header=Aastra_decode_HTTP_header();

# Get Language
$language=Aastra_get_language();

# Get global compatibility
$is_softkeys=Aastra_is_softkeys_supported();
$is_icons=Aastra_is_icons_supported();
$is_style_textmenu=Aastra_is_style_textmenu_supported();

# Compute MaxLines
if($is_softkeys) $MaxLines=AASTRA_MAXLINES;
else $MaxLines=AASTRA_MAXLINES-2;

# PROCESS Action
switch($action)
	{
	# Input lookup
	case 'input':
		# Input object
		$object = new AastraIPPhoneInputScreen();
		$object->setTitle(Aastra_get_label('SugarCRM Lookup',$language));
		$object->setPrompt(Aastra_get_label('First letters of the name',$language));
		$object->setParameter('lookup');
		$object->setType('string');
		$object->setURL($XML_SERVER.'?action=list');
		$object->setDestroyOnExit();
		if(!empty($lookup)) $object->setDefault($lookup);
		if($is_softkeys)
			{
			$object->addSoftkey('1',Aastra_get_label('Backspace',$language),'SoftKey:BackSpace');
			$object->addSoftkey('3',Aastra_get_label('ABC',$language),'SoftKey:ChangeMode');
			$object->addSoftkey('5',Aastra_get_label('Lookup',$language),'SoftKey:Submit');
			$object->addSoftkey('6',Aastra_get_label('Exit',$language),'SoftKey:Exit');
			}
		break;

	# Display contact list
	case 'list':
		# Retrieve Contact list
		$ContactList=getListRecords($lookup,$page,$MaxLines);
		$count=countListRecords($lookup);

		# List empty
		if($count==0)
			{
			$object=new AastraIPPhoneTextScreen();
			$object->setDestroyOnExit();
			$object->setTitle(sprintf(Aastra_get_label('Results for %s',$language),$lookup));
			$object->setText(Aastra_get_label('Sorry no match found.',$language));
			}
		else
			{
			# Retrieve last page
			$last=intval($count/$MaxLines);
			if(($count-$last*$MaxLines) != 0) $last++;

			# Display List
			$object=new AastraIPPhoneTextMenu();
			$object->setDestroyOnExit();
			if($lookup!='') 
				{
				if($last==1) $object->setTitle(sprintf(Aastra_get_label('Results for %s',$language),$lookup));
				else $object->setTitle(sprintf(Aastra_get_label('Results for %s (%s/%s)',$language),$lookup,$page,$last));
				}
			else 
				{
				if($last==1) $object->setTitle(sprintf(Aastra_get_label('Contacts  %s',$language),$lookup));
				else $object->setTitle(sprintf(Aastra_get_label('Contacts  %s (%s/%s)',$language),$lookup,$page,$last));
				}
			if(!empty($selection)) $object->setDefaultIndex($selection);
			if(!$is_softkeys)
				{
				if($page!=1) $object->addEntry(Aastra_get_label('Previous Page',$language),$XML_SERVER.'?action=list&lookup='.$lookup.'&page='.($page-1));
				}
			$index=1;
			foreach($ContactList as $item)
				{
				$object->addEntry($item['last_name'].' '.$item['first_name'],$XML_SERVER.'?action=zoom&index='.$item['id'].'&page='.$page.'&lookup='.$lookup.'&selection='.$index);
				$index++;
				}
			if($is_softkeys)
				{
				$object->addSoftkey('1',Aastra_get_label('Select',$language),'SoftKey:Select');
				if($page!=1) $object->addSoftkey('2',Aastra_get_label('Previous',$language),$XML_SERVER.'?action=list&lookup='.$lookup.'&page='.($page-1));
				if($page!=$last) $object->addSoftkey('5',Aastra_get_label('Next',$language),$XML_SERVER.'?action=list&lookup='.$lookup.'&page='.($page+1));
				}
			else
				{
				if($page!=$last) $object->addEntry(Aastra_get_label('Next Page',$language),$XML_SERVER.'?action=list&lookup='.$lookup.'&page='.($page+1));
				}
			}
		if($is_softkeys)
			{
			$object->addSoftkey('4',Aastra_get_label('Back',$language),$XML_SERVER.'?action=input&lookup='.$lookup);
			$object->addSoftkey('6',Aastra_get_label('Exit',$language),'SoftKey:Exit');
			}
		break;

	# Contact details
	case 'zoom':
		# Retrieve Contact 
		$Contact=getRecord($index);

		# Any number at all?
		if(($Contact['phone_home']=='') AND ($Contact['phone_work']!='') AND ($Contact['phone_mobile']!='') AND ($Contact['phone_other']!=''))
			{
			# Error Message
			$object=new AastraIPPhoneTextScreen();
			$object->setDestroyOnExit();
			$object->setTitle($Contact['last_name'].' '.$Contact['first_name']);
			$object->setText(Aastra_get_label('No attached phone number.',$language));
			}
		else
			{
			# Retrieve configuration
			$array_config_asterisk=Aastra_readINIfile(AASTRA_CONFIG_DIRECTORY.'directory.conf','#','=');
			$conf=$array_config_asterisk['Dialplan'];

			# Prepare display			
			if($Contact['phone_home']!='') 
				{
				$display['home']=prepare_number($Contact['phone_home'],1,$conf);
				$dial['home']=prepare_number($Contact['phone_home'],0,$conf);
				}
			if($Contact['phone_work']!='')
				{
				$display['work']=prepare_number($Contact['phone_work'],1,$conf);
				$dial['work']=prepare_number($Contact['phone_work'],0,$conf);
				}
			if($Contact['phone_mobile']!='') 
				{
				$display['mobile']=prepare_number($Contact['phone_mobile'],1,$conf);
				$dial['mobile']=prepare_number($Contact['phone_mobile'],0,$conf);
				}
			if($Contact['phone_other']!='') 
				{
				$display['other']=prepare_number($Contact['phone_other'],1,$conf);
				$dial['other']=prepare_number($Contact['phone_other'],0,$conf);
				}

			# Dialable object
			$object=new AastraIPPhoneTextMenu();
			$object->setDestroyOnExit();
			$object->setTitle($Contact['last_name'].' '.$Contact['first_name']);
			if($is_style_textmenu) $object->setStyle('none');
			$title=array(	'W'=>Aastra_get_label('W',$language),
					'M'=>Aastra_get_label('M',$language),
					'H'=>Aastra_get_label('H',$language),
					'O'=>Aastra_get_label('O',$language));
			switch($header['model'])
				{
				case 'Aastra480i':
				case 'Aastra480i Cordless':
					if($display['work']!='') $object->addEntry($title['W'].' '.$display['work'],$dial['work']);
					if($display['mobile']!='') $object->addEntry($title['M'].' '.$display['mobile'],$dial['mobile']);
					if($display['home']!='') $object->addEntry($title['H'].' '.$display['home'],$dial['home']);
					if($display['other']!='') $object->addEntry($title['O'].' '.$display['other'],$dial['other']);
					$object->addSoftkey('1',Aastra_get_label('Dial',$language),'SoftKey:Dial');
					break;

				case 'Aastra9143i':
				case 'Aastra51i':
				case 'Aastra53i':
					if($display['work']!='') $object->addEntry($title['W'].' '.$display['work'],'Dial:'.$dial['work'],'','',$dial['work']);
					if($display['mobile']!='') $object->addEntry($title['M'].' '.$display['mobile'],'Dial:'.$dial['mobile'],'','',$dial['mobile']);
					if($display['home']!='') $object->addEntry($title['H'].' '.$display['home'],'Dial:'.$dial['home'],'','',$dial['home']);
					if($display['other']!='') $object->addEntry($title['O'].' '.$display['other'],'Dial:'.$dial['other'],'','',$dial['other']);
					break;
				default:
					if($is_icons)
						{
						if($display['work']!='') $object->addEntry($display['work'],$dial['work'],'','1',$dial['work']);
						if($display['mobile']!='') $object->addEntry($display['mobile'],$dial['mobile'],'','2',$dial['mobile']);
						if($display['home']!='') $object->addEntry($display['home'],$dial['home'],'','3',$dial['home']);
						if($display['other']!='') $object->addEntry($display['other'],$dial['other'],'','4',$dial['other']);
						}
					else
						{
						if($display['work']!='') $object->addEntry($title['W'].' '.$display['work'],$dial['work'],'','',$dial['work']);
						if($display['mobile']!='') $object->addEntry($title['M'].' '.$display['mobile'],$dial['mobile'],'','',$dial['mobile']);
						if($display['home']!='') $object->addEntry($title['H'].' '.$display['home'],$dial['home'],'','',$dial['home']);
						if($display['other']!='') $object->addEntry($title['O'].' '.$display['other'],$dial['other'],'','',$dial['other']);
						}
					$object->addSoftkey('1',Aastra_get_label('Dial',$language),'SoftKey:Dial2');
					if($is_icons)
						{
						$object->addIcon(1,'000000FEAEFAAEFE00000000');
						$object->addIcon(2,'000000007E565AFE00000000');
						$object->addIcon(3,'000000103E7A3E1000000000');
						$object->addIcon(4,'000000664E5A4E6600000000');
						}
					break;
				}
			}

		if($is_softkeys)
			{
			$object->addSoftkey('4',Aastra_get_label('Back',$language),$XML_SERVER.'?action=list&lookup='.$lookup.'&page='.$page.'&selection='.$selection);
			$object->addSoftkey('6',Aastra_get_label('Exit',$language),'SoftKey:Exit');
			}
		break;
	}

# Display XML object
$object->output();
exit;
?>
