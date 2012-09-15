<?php 
#####################################################################
# World Time
# Aastra SIP Phones R1.4.2 or better
#
# php source code
# Copyright (c) Aastra Telecom Ltd 2007-2009
# Copyright (c) 2003 Jose Solorzano.  All rights reserved.
#
# Supported phones
#    All phones
#####################################################################

#####################################################################
# PHP customization for includes and warnings
#####################################################################
$os = strtolower(PHP_OS);
if(strpos($os, 'win') === false) ini_set('include_path',ini_get('include_path').':include:../include');
else ini_set('include_path',ini_get('include_path').';include;..\include');
error_reporting(E_ERROR | E_PARSE);

#####################################################################
# Includes
#####################################################################
require_once('AastraIPPhoneTextMenu.class.php');
require_once('AastraIPPhoneTextScreen.class.php');
require_once('AastraCommon.php');
require_once('htmlparser.class.php');

#####################################################################
# Private functions
#####################################################################
# filter_format function
function filter_format($string)
{
$parser = new HtmlParser($string);
$type=0;
$found=0;
while (($found==0) and ($parser->parse()))
	{
	if ($parser->iNodeName=='Text')
		{
		if($type==0)
			{
			if(stristr($parser->iNodeValue,'Current Time')) $type=1;
			}
		else
			{
			$value=$parser->iNodeValue;
			$found=1;
			}
		}
	}
return($value);
}

function search_index($index,$array)
{
foreach($array as $key=>$value)
	{
	if ($value['index']==$index) break;
	}
return($key);
}

######################################################################
# Beginning of the active code
######################################################################
# Collect parameters
$city=Aastra_getvar_safe('city');
$page=Aastra_getvar_safe('page','1');
$selection=Aastra_getvar_safe('selection');
$action=Aastra_getvar_safe('action','list');
$user=Aastra_getvar_safe('user');
$rank=Aastra_getvar_safe('rank');

# Trace call to the function
Aastra_trace_call('clock','city='.$city.', page='.$page.', action='.$action.', user='.$user.', selection='.$selection.', rank='.$rank);

# Test User Agent
Aastra_test_phone_version('1.4.2.',0);

# Get header info
$header=Aastra_decode_HTTP_header();

# Get Phone language
$language=Aastra_get_language();

# Set user use MAC if not set
if($user=='') $user=$header['mac'];

# Compute MaxLines
if(Aastra_is_softkeys_supported())$MaxLines=AASTRA_MAXLINES;
else $MaxLines=AASTRA_MAXLINES-2;

# Global compatibility
$nb_softkeys=Aastra_number_softkeys_supported();

# Display list of all available cities
$array=@parse_ini_file('clock.ini',True);

# Process action
switch($action)
	{
	# List of cities
	case 'list':
	case 'select':
		# Retrieve last page
		$nb_pages=count($array);
		$last=intval($nb_pages/$MaxLines);
		if(($nb_pages-$last*$MaxLines) != 0) $last++;

		# Display object
		$object=new AastraIPPhoneTextMenu();
		$object->setDestroyOnExit();
		if(Aastra_is_style_textmenu_supported) $object->setStyle('none');
		if(($selection!='') and ($action=='list')) $object->setDefaultIndex($selection);
		$index=0;
		if($action=='list') $selection=1;
		$start='';
		if((!Aastra_is_softkeys_supported()) and ($page!=1)) $object->addEntry(Aastra_get_label('Previous Page',$language),$XML_SERVER.'?page='.($page-1).'&user='.$user.'&action='.$action.'&rank='.$rank);
		while(($index<$nb_pages) and ($index<($page*$MaxLines)))
			{
			if($index >= ($page-1)*$MaxLines) 	
				{
				if($action=='list') $object->addEntry($array[$index]['name'],$XML_SERVER.'?action=zoom&city='.$array[$index]['index'].'&user='.$user.'&page='.$page.'&selection='.$selection,'');
				else $object->addEntry($array[$index]['name'],$XML_SERVER.'?action=set&city='.$array[$index]['index'].'&user='.$user.'&rank='.$rank,'');
				if($start=='') $start=substr($array[$index]['name'],0,2);
				$end=substr($array[$index]['name'],0,2);
				$selection++;
				}
			$index++;
			}
		$object->setTitle(sprintf(Aastra_get_label('World Clock (%s-%s)',$language),$start,$end));
		if((!Aastra_is_softkeys_supported()) and ($page!=$last)) $object->addEntry(Aastra_get_label('Next Page',$language),$XML_SERVER.'?page='.($page+1).'&user='.$user.'&action='.$action.'&rank='.$rank);

		# Softkeys
		if($nb_softkeys>0)
			{
			if($nb_softkeys==6)
				{
				$object->addSoftkey('1', Aastra_get_label('Select',$language), 'SoftKey:Select');
				if($page!=1) $object->addSoftkey('2', Aastra_get_label('Previous',$language), $XML_SERVER.'?page='.($page-1).'&user='.$user.'&action='.$action.'&rank='.$rank);
				if($page!=$last) $object->addSoftkey('5', Aastra_get_label('Next',$language), $XML_SERVER.'?page='.($page+1).'&user='.$user.'&action='.$action.'&rank='.$rank);
				if($action=='list') $object->addSoftkey('3', Aastra_get_label('Favorites',$language), $XML_SERVER.'?page='.$page.'&user='.$user.'&action=favorites');
				$object->addSoftkey('6', Aastra_get_label('Exit',$language), 'SoftKey:Exit');
				}
			else
				{
				if($page!=1) $object->addSoftkey('3', Aastra_get_label('Previous',$language), $XML_SERVER.'?page='.($page-1).'&user='.$user.'&action='.$action.'&rank='.$rank);
				if($page!=$last) $object->addSoftkey('8', Aastra_get_label('Next',$language), $XML_SERVER.'?page='.($page+1).'&user='.$user.'&action='.$action.'&rank='.$rank);
				if($action=='list') $object->addSoftkey('5', Aastra_get_label('Favorites',$language), $XML_SERVER.'?page='.$page.'&user='.$user.'&action=favorites');
				$object->addSoftkey('10', Aastra_get_label('Exit',$language), 'SoftKey:Exit');
				}
			}
		break;

	# Action on favorites
	case 'favorites':
	case 'up':
	case 'down':
	case 'clear':
	case 'set':
		# Retrieve favorites
		$array_fav=Aastra_get_user_context($user,'clock');
		
		# Process moves
		if($action=='up')
			{
			if($selection!=0)
				{
				$temp['index']=$array_fav[$selection-1]['index'];
				$temp['name']=$array_fav[$selection-1]['name'];
				$array_fav[$selection-1]['index']=$array_fav[$selection]['index'];
				$array_fav[$selection-1]['name']=$array_fav[$selection]['name'];
				$array_fav[$selection]['index']=$temp['index'];
				$array_fav[$selection]['name']=$temp['name'];
				Aastra_save_user_context($user,'clock',$array_fav);
				}
			else $selection=1;
			}
		if($action=='down')
			{
			if($selection!=$MaxLines-1)
				{
				$temp['index']=$array_fav[$selection]['index'];
				$temp['name']=$array_fav[$selection]['name'];
				$array_fav[$selection]['index']=$array_fav[$selection+1]['index'];
				$array_fav[$selection]['name']=$array_fav[$selection+1]['name'];
				$array_fav[$selection+1]['index']=$temp['index'];
				$array_fav[$selection+1]['name']=$temp['name'];
				Aastra_save_user_context($user,'clock',$array_fav);
				$selection+=2;
				}
			else $selection=$MaxLines;
			}
		if($action=='clear')
			{
			$array_fav[$selection]['index']='';
			$array_fav[$selection]['name']='';
			Aastra_save_user_context($user,'clock',$array_fav);
			$selection++;
			}
		if($action=='set')
			{
			$key=search_index($city,$array);
			$array_fav[$rank]['index']=$array[$key]['index'];
			$array_fav[$rank]['name']=$array[$key]['name'];
			Aastra_save_user_context($user,'clock',$array_fav);
			$selection=$rank+1;
			}

		# Display the favorites
		$object=new AastraIPPhoneTextMenu();
		$object->setDestroyOnExit();
		$object->setTitle(Aastra_get_label('Favorite Cities',$language));
		$object->setDefaultIndex($selection);
		if(Aastra_is_style_textmenu_supported) $object->setStyle('none');
		for($i=0;$i<$MaxLines;$i++)
			{
			if($array_fav[$i]['name']!='')
				{
				$object->addEntry($array_fav[$i]['name'],$XML_SERVER.'?action=zoomfav&city='.$array_fav[$i]['index'].'&user='.$user.'&selection='.($i+1),$i);
				}
			else
				{
				$array[$i]['name']='';
				$array[$i]['index']='';
				$object->addEntry('..........................',$XML_SERVER.'?action=select'.'&user='.$user.'&rank='.$i,'');
				}
			}

		# Softkeys
		if($nb_softkeys>0)
			{
			if($nb_softkeys==6)
				{
				$object->addSoftkey('1',Aastra_get_label('Select',$language), 'SoftKey:Select');
				$object->addSoftkey('2',Aastra_get_label('Move Up',$language), $XML_SERVER.'?user='.$user.'&action=up');
				$object->addSoftkey('3',Aastra_get_label('Clear',$language), $XML_SERVER.'?user='.$user.'&action=clear');
				$object->addSoftkey('4',Aastra_get_label('Back',$language), $XML_SERVER.'?user='.$user.'&action=list');
				$object->addSoftkey('5',Aastra_get_label('Move Down',$language), $XML_SERVER.'?user='.$user.'&action=down');
				$object->addSoftkey('6',Aastra_get_label('Exit',$language), 'SoftKey:Exit');
				}
			else
				{
				$object->addSoftkey('3',Aastra_get_label('Move Up',$language), $XML_SERVER.'?user='.$user.'&action=up');
				$object->addSoftkey('8',Aastra_get_label('Move Down',$language), $XML_SERVER.'?user='.$user.'&action=down');
				$object->addSoftkey('6',Aastra_get_label('Clear',$language), $XML_SERVER.'?user='.$user.'&action=clear');
				$object->addSoftkey('9',Aastra_get_label('Back',$language), $XML_SERVER.'?user='.$user.'&action=list');
				$object->setCancelAction($XML_SERVER.'?user='.$user.'&action=list');
				$object->addSoftkey('10',Aastra_get_label('Exit',$language), 'SoftKey:Exit');
				}
			}
		break;

	case 'zoom':
	case 'zoomfav':
		# Retrieve city index
		$key=search_index($city,$array);

		# Open and retrieve RSS XML file
		$http='http://timeanddate.com/worldclock/city.html?n='.$city;
		$handle=@fopen($http, 'r');
		$found=0;
		$title=$array[$key]['name'];
		if($handle)
			{	
			while (($line=fgets($handle,1000)) and ($found!=2))
				{
				switch($found)
					{
					case '0':
						$value=trim($previous_line,'\\n').trim($line,'\\n');
						if (stristr($value,'Current Time')) $found=1;
						break;
					case '1':
						$value.=$line;
						$current=filter_format($value);
						$found=2;
						break;
					}
				$previous_line=$line;
				}
			fclose($handle);

			if($found==2)
				{
				if(Aastra_is_formattedtextscreen_supported())
					{
					sscanf($current,'%s %s %d, %d at %s %[^$]s',$day,$month,$day2,$year,$time,$AMPM);
					require_once('AastraIPPhoneFormattedTextScreen.class.php');
					$object=new AastraIPPhoneFormattedTextScreen();
					$object->setDestroyOnExit();
					$size=Aastra_size_formattedtextscreen();
					if($size>5) $font='double';
					else $font=NULL;
					$object->addLine($title,$font,'center');
					if($size<4) $object->setScrollStart($size-1);
					if(Aastra_size_formattedtextscreen()>4)$object->addLine('');
					if(Aastra_size_formattedtextscreen()>5)$object->addLine('');
					$object->addLine($day,$font,'center');
					$object->addLine($month.' '.$day2.' '.$year,$font,'center');
					$object->addLine($time.' '.$AMPM,$font,'center');
					if($size<4) $object->setScrollEnd();
					}
				else
					{
					$object = new AastraIPPhoneTextScreen();
					$object->setDestroyOnExit();
					$object->setTitle($title);
					$object->setText($current);
					}

				# Softkeys
				if($nb_softkeys>0)
					{
					if($nb_softkeys==6)
						{
						if($action=='zoom') $object->addSoftkey('4', Aastra_get_label('Back',$language), $XML_SERVER.'?selection='.$selection.'&user='.$user.'&action=list'.'&page='.$page);
						else $object->addSoftkey('4', Aastra_get_label('Back',$language), $XML_SERVER.'?selection='.$selection.'&user='.$user.'&action=favorites');
						$object->addSoftkey('6', Aastra_get_label('Exit',$language), 'SoftKey:Exit');
						}
					else
						{
						if($action=='zoom') 
							{
							$object->addSoftkey('9', Aastra_get_label('Back',$language), $XML_SERVER.'?selection='.$selection.'&user='.$user.'&action=list'.'&page='.$page);
							$object->setCancelAction($XML_SERVER.'?selection='.$selection.'&user='.$user.'&action=list'.'&page='.$page);
							}
						else 
							{
							$object->addSoftkey('9', Aastra_get_label('Back',$language), $XML_SERVER.'?selection='.$selection.'&user='.$user.'&action=favorites');
							$object->setCancelAction($XML_SERVER.'?selection='.$selection.'&user='.$user.'&action=favorites');
							}
						$object->addSoftkey('10', Aastra_get_label('Exit',$language), 'SoftKey:Exit');
						}
					}
				}
			else
				{
				# Display error
				$object = new AastraIPPhoneTextScreen();
				$object->setDestroyOnExit();
				$object->setTitle(Aastra_get_label('Application error',$language));
				$object->setText(Aastra_get_label('The information you are looking for is not available at this time. Try again later.',$language));
				}
			}
		else
			{
			# Display error
			$object = new AastraIPPhoneTextScreen();
			$object->setDestroyOnExit();
			$object->setTitle(Aastra_get_label('Application error',$language));
			$object->setText(Aastra_get_label('The information you are looking for is not available at this time. Try again later.',$language));
			}
		break;
	}

# Display object
$object->output();
exit;
?>
