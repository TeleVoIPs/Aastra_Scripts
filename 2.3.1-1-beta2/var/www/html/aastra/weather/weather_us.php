<?php 
#############################################################################
# Local weather by zip code
#
# Aastra SIP Phones 1.4.2 or better
#
# Copyright 2007-2009 Aastra Telecom Ltd
#
# Supported Aastra Phones
#   All phones but best on
#       480i
#       480iCT
#       35i
#       35i CT
#       55i
#       57i
#       57i CT
#       6739i
#
# script.php?zip=XXXXX
#    where XXXXX is the US ZIP code of the city (optional)
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
require_once('AastraIPPhoneInputScreen.class.php');
require_once('AastraIPPhoneTextMenu.class.php');
require_once('AastraIPPhoneTextScreen.class.php');
require_once('AastraIPPhoneExecute.class.php');

#############################################################################
# Private functions
#############################################################################
function array_key_exists_r($needle,$haystack)
{
$result[0]=array_key_exists($needle,$haystack);
if($result[0]) 
	{
	$result[1]=$haystack[$needle];
	return $result;
	}
foreach($haystack as $v)
	{
       if(is_array($v) || is_object($v)) $result=array_key_exists_r($needle,$v);
       if($result[0]) return $result;
    	}
return $result;
}

# trim_title function
function trim_title($string,$before,$after)
{
# Process Before
if($before!='')
	{
	$array=explode($before,$string,2);
	$string=$array[1];
	}

# Process after
if($after!='')
	{
	$array=explode($after,$string,2);
	$string=$array[0];
	}

return($string);
}

# filter_format function
function filter_format($string)
{
# Process Before
$string=preg_replace(array('/Forecast for /','/Forecast for /'), array('',''), $string);

# Remove All HTML crap
$search = array ('@<script[^>]*?>.*?</script>@si', // Strip out javascript
                 '@<[\/\!]*?[^<>]*?>@si',          // Strip out HTML tags
                 '@([\r\n])[\s]+@',                // Strip out white space
                 '@&(quot|#34);@i',                // Replace HTML entities
                 '@&(amp|#38);@i',
                 '@&(lt|#60);@i',
                 '@&(gt|#62);@i',
                 '@&(nbsp|#160);@i',
                 '@&(iexcl|#161);@i',
                 '@&(cent|#162);@i',
                 '@&(pound|#163);@i',
                 '@&(copy|#169);@i',
                 '@&#(\d+);@e'); // evaluate as php

$replace = array ('',
                 '',
                 '\1',
                 '"',
                 '&',
                 '<',
                 '>',
                 ' ',
                 chr(161),
                 chr(162),
                 chr(163),
                 chr(169),
                 'chr(\1)');

$string = preg_replace($search, $replace, $string);
$string = preg_replace($search, $replace, $string);
$string = preg_replace('/\n/',' ', $string);

return($string);
}

#############################################################################
# Beginning of the active code
#############################################################################
# Collect parameters
$header=Aastra_decode_HTTP_header();
$user=Aastra_getvar_safe('user',$header['mac']);
$index=Aastra_getvar_safe('index');
$zip=Aastra_getvar_safe('zip');

# Trace
Aastra_trace_call('weather','zip='.$zip.', index='.$index);

# Test User Agent
Aastra_test_phone_version('1.4.2.',0);

# Get Language
$language=Aastra_get_language();

# Get global compatibility
$nb_softkeys=Aastra_number_softkeys_supported();

# Callback
$XML_SERVER.='?user='.$user;

# No ZIP yet
if($zip=='')
	{
	# Input zip code
     	$object = new AastraIPPhoneInputScreen();
     	$object->setTitle(Aastra_get_label('US Local Weather',$language));
     	$object->setPrompt(Aastra_get_label('Enter ZIP code',$language));
     	$object->setParameter('zip');
     	$object->setType('number');
     	$object->setURL($XML_SERVER);
     	$object->setDestroyOnExit();
	$data=Aastra_get_user_context($user,'weather');
	if($data['last']!=NULL) $object->setDefault($data['last']);
	if($nb_softkeys)
		{
		if($nb_softkeys==6)
			{
			$object->addSoftkey('1',Aastra_get_label('Backspace',$language),'SoftKey:BackSpace');
			$object->addSoftkey('5',Aastra_get_label('Submit',$language),'SoftKey:Submit');
			$object->addSoftkey('6',Aastra_get_label('Exit',$language),'SoftKey:Exit');
			}
		else $object->addSoftkey('10',Aastra_get_label('Exit',$language),'SoftKey:Exit');
		}
	}
else
	{
	# Save last value requested
	$data['last']=$zip;
	Aastra_save_user_context($user,'weather',$data);

	# Test if we need to regenerate the static pages
	$generate=0;
	$file_name=AASTRA_PATH_CACHE.'w-'.$zip.'.rss';
	if(!file_exists($file_name)) $generate=1;
	else 
		{	
		if(time()-filemtime($file_name)>(4*3600)) $generate=1;
		}

	# If need to generate
	if($generate==1)
		{
		# Check if target directory is present
		if (!is_dir(AASTRA_PATH_CACHE))@mkdir(AASTRA_PATH_CACHE);

		# Open and retrieve RSS XML file
		$array=Aastra_xml2array('http://www.rssweather.com/zipcode/'.$zip.'/rss.php',0);
		$search=array_key_exists_r('item',$array);
		if($search[0])
			{
			if(is_array($search[1][0]))
				{
				$allItems=$search[1]; 
				$itemCount=count($allItems); 
				}
			else
				{
				$allItems[0]=$search[1]; 
				$itemCount=1; 
				}
			}
		else 
			{
			Aastra_debug('rss','Cannot open '.'http://www.rssweather.com/zipcode/'.$zip.'/rss.php'.' in read mode');
			$object=new AastraIPPhoneTextScreen();
			$object->setTitle(Aastra_get_label('Information not available',$language));
			$object->setText(Aastra_get_label('The information you are looking for is not available at this time. Try again later.',$language));
			$output=$object->output();
			exit;
			}

		# ZIP code OK?
		if($itemCount!=0)
			{
			# Prepare static file
			$handle = @fopen($file_name,'w');
			if($handle)
				{
				for($y=0;$y<$itemCount;$y++) 
					{
					# Format data
					fputs($handle,'['.$y."]\n");
					$allItems[$y]['title']= filter_format($allItems[$y]['title']);
					$allItems[$y]['title']= trim_title($allItems[$y]['title'],'',' :: ');
					if($y==0)
						{
						$main=$allItems[$y]['title'];
						$allItems[$y]['title']='Currently';
						}
					fputs($handle,'title='.$allItems[$y]['title']."\n");
					$description=filter_format($allItems[$y]['description']);
					if(trim($description,' ')=='') $description=Aastra_get_label('No data provided',$language);
					$allItems[$y]['description']= $description.' '.Aastra_get_label('RSS feed provided by rssweather.com and brought to you by Aastra Telecom.',$language);
					fputs($handle,'description='.$allItems[$y]['description']."\n");
					fputs($handle,'uri='.$XML_SERVER.'&zip='.$zip.'&index='.($y+1)."\n");
					}
				fputs($handle,"[99]\n");
				fputs($handle,'title='.$main."\n");
				fclose($handle);
				}
			else 
				{
				Aastra_debug('Can not open '.$file_name.' in write mode');
				$object = new AastraIPPhoneTextScreen();
				$object->setTitle(Aastra_get_label('Information not available',$language));
				$object->setText(Aastra_get_label('The information you are looking for is not available at this time. Try again later.',$language));
				$output=$object->output();
				exit;
				}
			}
		else
			{
			Aastra_debug('RSS feed empty, wrong zip code');
			$object=new AastraIPPhoneTextScreen();
			$object->setTitle(Aastra_get_label('Error',$language));
			$object->setText(Aastra_get_label('No data available, please check the ZIP code you entered.',$language));
			$output=$object->output();
			exit;
			}
		}

	# Display the requested article or list
	$array_rss=Aastra_readINIfile($file_name,'#','=');
	if(empty($index))
		{
		# Reguler phone?
		if($nb_softkeys<7)
			{
			# Display list
			$object=new AastraIPPhoneTextMenu();
			$object->setDestroyOnExit();
			if(Aastra_is_style_textmenu_supported()) $object->setStyle('radio');

			# Title
			$object->setTitle($array_rss[99]['title']);

			# Display topics
			foreach($array_rss as $key=>$value) 
				{
				if($key!='99') $object->addEntry($value['title'],$value['uri'].'&user='.$user);
				}

			# Softkeys
			if($nb_softkeys)
				{
				$object->addSoftkey('1', Aastra_get_label('Select',$language), 'SoftKey:Select');
				$object->addSoftkey('5', Aastra_get_label('Back',$language), $XML_SERVER);
				$object->addSoftkey('6', Aastra_get_label('Exit',$language), 'SoftKey:Exit');
				}
			}
		else
			{
			# Display first item
			$object=new AastraIPPhoneExecute();
			$object->addEntry($XML_SERVER.'&zip='.$zip.'&index=1');
			}
		}
	else
		{
		# shift $index
		$index--;

		# Display article
		$object=new AastraIPPhoneTextScreen();
		$object->setDestroyOnExit();
		if(Aastra_is_wrap_title_supported())$object->setTitleWrap();

		# Title
		if(Aastra_phone_type()==5) $object->setTitle($array_rss[$index]['title']);
		else $object->setTitle($array_rss[99]['title'].' - '.$array_rss[$index]['title']);

		# Content
		$object->setText($array_rss[$index]['description']);

		# Softkeys
		if($nb_softkeys) 
			{
			if($nb_softkeys==6)
				{
				if($index!=0) $object->addSoftkey('2', Aastra_get_label('Previous',$language), $XML_SERVER.'&zip='.$zip.'&index='.$index);
				$object->addSoftkey('4', Aastra_get_label('Back',$language), $XML_SERVER.'&zip='.$zip);
				if($index!=(count($array_rss)-2))$object->addSoftkey('5', Aastra_get_label('Next',$language), $XML_SERVER.'&zip='.$zip.'&index='.($index+2));
				$object->addSoftkey('6', Aastra_get_label('Exit',$language), 'SoftKey:Exit');
				}
			else
				{
				$softkey=1;
				foreach($array_rss as $key=>$value) 
					{
					if($key!='99') 
						{
						$object->addSoftkey($softkey,$value['title'],$value['uri'].'&user='.$user);
						$softkey++;
						}
					$object->addSoftkey('9', Aastra_get_label('Back',$language), $XML_SERVER);
					$object->addSoftkey('10', Aastra_get_label('Exit',$language), 'SoftKey:Exit');
					}
				$object->setCancelAction($XML_SERVER);
				}
			}
		else $object->setCancelAction($XML_SERVER);
		}
	}

# Display output
$object->output();
exit;
?>
