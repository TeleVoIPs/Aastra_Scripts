<?php 
#############################################################################
# RSS feed display
#
# Aastra SIP Phones 1.4.2 or better
#
# Copyright 2007-2009 Aastra Telecom Ltd
#
# Supported Aastra Phones
#   All phones but
#       9112i
#       9133i
#
# script.php?feed=XXX
# XXX refers to XXX.rss which is the configuration file of the RSS feeds
# which must be in the same directory than the script.
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
function filter_format($string,$pattern,$before_s,$before_r,$after_s,$after_r)
{
# Process Before
if($pattern[0]==1) $string=preg_replace($before_s, $before_r, $string);

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

$string=preg_replace($search, $replace, $string);
$string=preg_replace($search, $replace, $string);
$string=preg_replace('/\n/',' ', $string);

# Process after
if($pattern[1]==1) $string=preg_replace($after_s, $after_r, $string);

# Return processed string
return($string);
}

#############################################################################
# Beginning of the active code
#############################################################################
# Collect parameters
$index=Aastra_getvar_safe('index');
$last_index=Aastra_getvar_safe('last_index');
$rank=Aastra_getvar_safe('rank');
$last_rank=Aastra_getvar_safe('last_rank');
$feed=Aastra_getvar_safe('feed');
$page=Aastra_getvar_safe('page','1');
$set=Aastra_getvar_safe('set','1');

# Trace
Aastra_trace_call('rss','feed='.$feed.', index='.$index.', rank='.$rank.', last_rank='.$last_rank.', page='.$page);

# Test User Agent
Aastra_test_phone_version('1.4.2.',0);

# Keep callback
$XML_SERVER.='?feed='.$feed;

# Init data
$file=$feed.'.rss';

# Get Language
$header=Aastra_decode_HTTP_header();
$language=Aastra_get_language();

# IF RSS config exists
if(file_exists($file))
	{
	$array=Aastra_readINIfile($file,'#','=');
	reset($array);
	$i=0;
	$pattern[0]=0;
	$pattern[1]=0;

	# Load config file
	while(($v=current($array)) and ($i<AASTRA_MAXLINES))
		{
		if(key($array)=='RESERVED')
			{
			$Main=$v['title'];
			if($v['copyright-'.$language]!='') $copyright=$v['copyright-'.$language];
			else $copyright=$v['copyright'];
			$copyright=' '.$copyright;
			$encoding=$v['encoding'];
			if($encoding=='') $encoding='iso';
			$static_file=AASTRA_PATH_CACHE.$v['file'];
			$search=$v['search_before'];
			$nodescription=$v['nodescription'];
			if(search!='')
				{ 
				$before_s=preg_split("/,/",$search);
				$count_s=count($before_s); # returns 1 if value is not an array
				}
			$replace=$v['replace_before'];
			$before_r=preg_split("/,/",$replace);
			$count_r=count($before_r); # returns 1 if value is not an array
			if(($search!='') and ($count_s==$count_r)) $pattern[0]=1;
			$search=$v['search_after'];
			if($search!='') 
				{
				$after_s=preg_split("/,/",$search);
				$count_s=count($after_s); # returns 1 if value is not an array
				}
			$replace=$v['replace_after'];
			$after_r=preg_split("/,/",$replace);
			$count_r=count($after_r); # returns 1 if value is not an array
			if(($search!='')  and ($count_s==$count_r)) $pattern[1]=1;
			$trim_title_after=$v['trim_title_after'];
			$trim_title_before=$v['trim_title_before'];
			}
		else
			{
			$Title[$i]=key($array);
			$ttl[$i]=$v['ttl'];
			$xml[$i]=$v['xml'];
			$i++;
			} 
		next($array);
		}

	$nb_rss=$i;
	}
else
	{
	# Trace
	Aastra_debug('Feed '.$feed.'.rss does not exist');

	# Output error
	$object=new AastraIPPhoneTextScreen();
	$object->setTitle(Aastra_get_label('Information not available',$language));
	$object->setText(Aastra_get_label('Configuration error, please contact your administrator.',$language));
	$nb=Aastra_number_softkeys_supported();
	if($nb!=0) $object->addSoftkey($nb, Aastra_get_label('Exit',$language), 'SoftKey:Exit');
	$output=$object->output();
	exit;
	}

# Process request
if(empty($index))
	{
	if(($nb_rss>1) and (Aastra_number_softkeys_supported()<7))
		{
		# Display list of topics
		$object=new AastraIPPhoneTextMenu();
		$object->setDestroyOnExit();
		if($last_index!='') $object->setDefaultIndex($last_index);
		if(Aastra_is_style_textmenu_supported()) $object->setStyle('radio');
		if(Aastra_is_textmenu_wrapitem_supported()) $object->setWrapList();

		# Title
		$object->setTitle($Main);
	
		# Items
		for($y=0;$y<$nb_rss;$y++) $object->addEntry($Title[$y],$XML_SERVER.'&index='.($y+1));

		# Softkeys
		if(Aastra_is_softkeys_supported())
			{
			$object->addSoftkey('1', Aastra_get_label('Select',$language), 'SoftKey:Select');
			$object->addSoftkey('6', Aastra_get_label('Exit',$language), 'SoftKey:Exit');
			}
		}
	else
		{
		# Display first item
		$object=new AastraIPPhoneExecute();
		$object->addEntry($XML_SERVER.'&index=1');
		}

	# Display object
	$object->output();
	exit;
	}
else
	{
	# Shift Index
	$index--;

	# Test if we need to regenerate the static pages
	$generate=0;
	$file_name=$static_file.'-'.$index.'.rss';
	if(!file_exists($file_name)) $generate=1;
	else 
		{	
		if($ttl[$index]=='daily')
			{
			if(strftime('%D',time())!=strftime('%D',filemtime($file_name))) $generate=1;
			}
		else
			{
			if(time()-filemtime($file_name)>$ttl[$index]) $generate=1;
			}
		}

	# If need to generate
	if($generate==1)
		{
		# Check if target directory is present
		if (!is_dir(AASTRA_PATH_CACHE))@mkdir(AASTRA_PATH_CACHE);

		# Open and retrieve RSS XML file
		$array=Aastra_xml2array($xml[$index],0);
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
			Aastra_debug('rss','Cannot open '.$xml[$index].' in read mode');
			$object=new AastraIPPhoneTextScreen();
			$object->setTitle(Aastra_get_label('Information not available',$language));
			$object->setText(Aastra_get_label('The information you are looking for is not available at this time. Try again later.',$language));
			$nb=Aastra_number_softkeys_supported();
			if($nb!=0) $object->addSoftkey($nb, Aastra_get_label('Exit',$language), 'SoftKey:Exit');
			$output=$object->output();
			exit;
			}

		# At least one item
		if($itemCount>0)
			{
			# Prepare static file
			$handle = @fopen($file_name,'w');
			if($handle)
				{
				for($y=0;$y<$itemCount;$y++) 
					{
					# Format data
					fputs($handle,'['.$y.']'."\n");
					$allItems[$y]['title']= filter_format($allItems[$y]['title'],$pattern,$before_s,$before_r,$after_s,$after_r);
					$allItems[$y]['title']= trim_title($allItems[$y]['title'],$trim_title_before,$trim_title_after);
					$description=filter_format($allItems[$y]['description'],$pattern,$before_s,$before_r,$after_s,$after_r);
					if(trim($description,' ')=='') $description=$nodescription;
					$allItems[$y]['description']= $description.$copyright;
					switch($encoding)
						{
						case 'utf8':
							fputs($handle,'title='.utf8_decode(ereg_replace("’","'",$allItems[$y]['title']))."\n");
							fputs($handle,'description='.utf8_decode(ereg_replace("’","'",$allItems[$y]['description']))."\n");
							break;
						default:
							fputs($handle,'title='.$allItems[$y]['title']."\n");
							fputs($handle,'description='.$allItems[$y]['description']."\n");
							break;
						}
					fputs($handle,'uri='.$XML_SERVER.'&index='.($index+1).'&rank='.($y+1)."\n");
					}
				fclose($handle);
				}
			else 
				{
				Aastra_debug('Cannot open '.$file_name.' in write mode');
				$object=new AastraIPPhoneTextScreen();
				$object->setTitle(Aastra_get_label('Information not available',$language));
				$object->setText(Aastra_get_label('The information you are looking for is not available at this time. Try again later.',$language));
				if(Aastra_is_softkeys_supported()) $object->addSoftkey('6', Aastra_get_label('Exit',$language), 'SoftKey:Exit');
				$output=$object->output();
				exit;
				}
			}
		else
			{
			Aastra_debug('No item in the RSS feed');
			$object = new AastraIPPhoneTextScreen();
			$object->setTitle(Aastra_get_label('Information not available',$language));
			$object->setText(Aastra_get_label('The information you are looking for is not available at this time. Try again later.',$language));
			if(Aastra_is_softkeys_supported()) $object->addSoftkey('6', Aastra_get_label('Exit',$language), 'SoftKey:Exit');
			$output=$object->output();
			exit;
			}
		}

	# Display the requested article or list
	$array_rss=Aastra_readINIfile($file_name,'#','=');
	if(empty($rank))
		{
		# Special for no softkey phones
		if(Aastra_is_softkeys_supported()) $MaxLines=AASTRA_MAXLINES;
		else $MaxLines=AASTRA_MAXLINES-2;

		# Retrieve last page
		$count=count($array_rss);
		$last=intval($count/$MaxLines);
		if(($count-$last*$MaxLines) != 0) $last++;

		# More than one item
		if($count>1)
			{
			# Display list
			$object = new AastraIPPhoneTextMenu();
			$object->setDestroyOnExit();
			if($last_rank!='') 
				{
				$object->setDefaultIndex((($last_rank-1)%$MaxLines)+1);
				$page=intval($last_rank/$MaxLines);
				if(($last_rank-$page*$MaxLines) != 0) $page++;
				}
			if($last=='1') $object->setTitle($Title[$index]);
			else $object->setTitle(sprintf($Title[$index].' (%d/%d)',$page,$last));
			if(Aastra_is_style_textmenu_supported()) $object->setStyle('radio');
			if(Aastra_is_textmenu_wrapitem_supported()) $object->setWrapList();
			if(Aastra_is_wrap_title_supported()) $object->setTitleWrap();
			if((!Aastra_is_softkeys_supported()) and ($page!=1)) $object->addEntry(Aastra_get_label('Previous Page',$language),$XML_SERVER.'&index='.($index+1).'&page='.($page-1));
			$i=1;
			foreach ($array_rss as $key=>$value) 
				{
				if(($i>=(($page-1)*$MaxLines+1)) and ($i<=$page*$MaxLines)) $object->addEntry($value['title'],$value['uri'].'&set='.$set);
				$i++;
				}
			if(Aastra_is_softkeys_supported())
				{
				if(Aastra_number_softkeys_supported()<7) 
					{
					$object->addSoftkey('1', Aastra_get_label('Select',$language), 'SoftKey:Select');
					if($page!=1) $object->addSoftkey('2', Aastra_get_label('Previous',$language), $XML_SERVER.'&index='.($index+1).'&page='.($page-1));
					if($page!=$last) $object->addSoftkey('5', Aastra_get_label('Next',$language), $XML_SERVER.'&index='.($index+1).'&page='.($page+1));
					if($nb_rss>1)
						{
						$object->addSoftkey('4', Aastra_get_label('Back',$language), $XML_SERVER.'&last_index='.($index+1));
						$object->setCancelAction($XML_SERVER.'&last_index='.($index+1));
						}
					$object->addSoftkey('6', Aastra_get_label('Exit',$language), 'SoftKey:Exit');
					}
				else
					{
					$last_set=intval($nb_rss/4);
					if(($nb_rss-$last_set*4) != 0) $last_set++;
					$softkey=1;
					for($y=0;$y<$nb_rss;$y++) 
						{
						if(($y>=($set-1)*4) and ($y<$set*4))
							{
							$object->addSoftkey($softkey,$Title[$y],$XML_SERVER.'&index='.($y+1).'&set='.$set);
							$softkey++;
							}
						}
					if($last_set!=1)
						{
						if($set!=$last_set) $object->addSoftkey('5',Aastra_get_label('More...',$language), $XML_SERVER.'&index='.($index+1).'&rank='.($rank+1).'&set='.($set+1));
						else $object->addSoftkey('5',Aastra_get_label('More...',$language), $XML_SERVER.'&index='.($index+1).'&rank='.($rank+1).'&set=1');
						}
					if($page!=1) $object->addSoftkey('8', Aastra_get_label('Previous',$language), $XML_SERVER.'&index='.($index+1).'&page='.($page-1));
					if($page!=$last) $object->addSoftkey('9', Aastra_get_label('Next',$language), $XML_SERVER.'&index='.($index+1).'&page='.($page+1));
					$object->addSoftkey('10',Aastra_get_label('Exit',$language), 'SoftKey:Exit');
					}
				}
			else 
				{
				if($page!=$last) $object->addEntry(Aastra_get_label('Next Page',$language),$XML_SERVER.'&index='.($index+1).'&page='.($page+1));
				}
			}
		else
			{
			$object=new AastraIPPhoneExecute();
			$object->addEntry($XML_SERVER.'&index='.($index+1).'&rank=1');
			}
		}
	else
		{
		# shift $rank
		$rank--;

		# Display article
		$object = new AastraIPPhoneTextScreen();
		$object->setDestroyOnExit();
		if(Aastra_is_wrap_title_supported())$object->setTitleWrap();
		if(Aastra_number_softkeys_supported()<7) $object->setTitle($array_rss[$rank]['title']);
		else 
			{
			if(count($array_rss)>1) $object->setTitle($Title[$index].' ('.($rank+1).'/'.count($array_rss).') - '.$array_rss[$rank]['title']);
			else $object->setTitle($Title[$index].' - '.$array_rss[$rank]['title']);
			}
		$object->setText($array_rss[$rank]['description']);
		if(($rank!=0) or ($rank!=(count($array_rss)-1))) $back=$XML_SERVER.'&index='.($index+1).'&last_rank='.($rank+1);
		else $back=$XML_SERVER.'&last_index='.($index+1);
		if(Aastra_is_softkeys_supported())
			{
			if(Aastra_number_softkeys_supported()<7)
				{
				if($rank!=0) $object->addSoftkey('2',Aastra_get_label('Previous',$language), $XML_SERVER.'&index='.($index+1).'&rank='.$rank);
				$object->addSoftkey('4',Aastra_get_label('Back',$language),$back);
				if($rank!=(count($array_rss)-1))$object->addSoftkey('5',Aastra_get_label('Next',$language),$XML_SERVER.'&index='.($index+1).'&rank='.($rank+2));
				$object->addSoftkey('6',Aastra_get_label('Exit',$language),'SoftKey:Exit');
				}
			else
				{
				$last_set=intval($nb_rss/4);
				if(($nb_rss-$last_set*4) != 0) $last_set++;
				$softkey=1;
				for($y=0;$y<$nb_rss;$y++) 
					{
					if(($y>=($set-1)*4) and ($y<$set*4))
						{
						$object->addSoftkey($softkey,$Title[$y],$XML_SERVER.'&index='.($y+1).'&rank=1'.'&set='.$set);
						$softkey++;
						}
					}
				if($last_set!=1)
					{
					if($set!=$last_set) $object->addSoftkey('5',Aastra_get_label('More...',$language), $XML_SERVER.'&index='.($index+1).'&rank='.($rank+1).'&set='.($set+1));
					else $object->addSoftkey('5',Aastra_get_label('More...',$language), $XML_SERVER.'&index='.($index+1).'&rank='.($rank+1).'&set=1');
					}
				if($rank!=0) $object->addSoftkey('8',Aastra_get_label('Previous',$language), $XML_SERVER.'&index='.($index+1).'&rank='.$rank.'&set='.$set);
				if($rank!=(count($array_rss)-1))$object->addSoftkey('9',Aastra_get_label('Next',$language),$XML_SERVER.'&index='.($index+1).'&rank='.($rank+2).'&set='.$set);
				$object->addSoftkey('10',Aastra_get_label('Exit',$language),'SoftKey:Exit');
				$object->setCancelAction($back);
				}
			}
		else $object->setDoneAction($back);
		}

	# Display output
	$object->output();
	exit;
	}
?>
