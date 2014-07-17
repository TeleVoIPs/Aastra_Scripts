<?php
#############################################################################
# Image Server for the 6739i Picture Caller ID Feature
#
# Copyright Aastra Telecom 2009-2010
#
# Configuration
#    image server uri: http://<server>/<path-to-this-script>?
#    see server.conf for various directory configuration
#
# Matching rules:
#     Always look in the cache first (you need to delete a file in the cache 
#     if it's source in the picture library has changed)
#     Try to find an exact match (input number matches file name) first, then 
#     translate the number and check again
#     If multiple files with same number but different format exist in library, 
#     this is the precedence used: 
#        1. PNG, 
#        2. JPG, 
#        3. GIF
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
require_once('AastraAsterisk.php');

#############################################################################
# Private functions
#############################################################################

function getImageFromLibrary($library_path, $number) 
{
# 1st check for a .png or .PNG image
$filename=$library_path.'/'.$number.'.png';
if(file_exists($filename)) return @imagecreatefrompng($filename);
$filename = $library_path.'/'.$number.'.PNG';
if(file_exists($filename)) return @imagecreatefrompng($filename);
	
# 2nd check for a .jpg or .JPG image
$filename = $library_path.'/'.$number.'.jpg';
if (file_exists($filename)) return @imagecreatefromjpeg($filename);
$filename = $library_path.'/'.$number.'.JPG';
if (file_exists($filename)) return @imagecreatefromjpeg($filename);
$filename = $library_path.'/'.$number.'.jpeg';
if (file_exists($filename)) return @imagecreatefromjpeg($filename);
$filename = $library_path.'/'.$number.'.JPEG';
if (file_exists($filename)) return @imagecreatefromjpeg($filename);

# 3rd check for a .gif or .GIF image
$filename = $library_path.'/'.$number.'.gif';
if (file_exists($filename)) return @imagecreatefromgif($filename);
$filename = $library_path.'/'.$number.'.GIF';
if (file_exists($filename)) return @imagecreatefromgif($filename);
	
# No match
return null;
}

function scaleImage($im, $blowup) 
{
# Create emtpy output image. use true color to avoid color palette problems
$im_output = imagecreatetruecolor(150, 200);

# Get size of input image
$im_width=imagesx($im);
$im_height=imagesy($im);
	
# Check if image is smaller than 150x200 pixels and if "blow up" of images is disabled
if(($im_width < 150) && ($im_height < 200) && !$blowup) 
	{
	# Simply copy the image in the center of the output image. no scaling.
	imagecopy($im_output, $im, ((150-$im_width)/2)  ,((200-$im_height)/2), 0, 0, $im_width, $im_height);
	} 
else 
	{
	# Check aspect ratio of source image
	if ($im_width / $im_height <= 0.75) 
		{
		# "Portrait" image. scale to 200 pixel height and center horizontally
		$new_im_width = $im_width * (200 / $im_height);
		imagecopyresized($im_output, $im, ((150-$new_im_width)/2), 0, 0, 0, $new_im_width, 200, $im_width, $im_height);
		} 
	else 
		{
		# "Landscape" image. scale to 150 pixel width and center vertically
		$new_im_height = $im_height * (150 / $im_width);
		imagecopyresized($im_output, $im, 0, ((200-$new_im_height)/2), 0, 0, 150, $new_im_height, $im_width, $im_height);
		}	
	}

# Return new image
return($im_output);
}

function customImage($im,$complement)
{
# Define the font
$font='../fonts/DejaVuSans-Bold';

# Split complement
$array_complement=explode('|',$complement);

# Label is 0
$array_complement[0]=str_replace("\"",'',$array_complement[0]);

# Y position is 1
if(($array_complement[1]>0) and ($array_complement[1]<200)) $y=$array_complement[1];
else $y=100;

# Font Size is 4 (default 10)
if(($array_complement[4]>=8) and ($array_complement[4]<=24)) $size=$array_complement[4];
else $size=10;

# Alignment is 2
switch($array_complement[2])
	{
	case 'left':
		$x=10;
		break;
	case 'right':
		$array_text=imagettfbbox($size,0,$font,$array_complement[0]);
		$width=$array_text[4]-$array_text[6];
		$x=150-10-$width;
		break;
	default:
		$array_text=imagettfbbox($size,0,$font,$array_complement[0]);
		$width=$array_text[4]-$array_text[6];
		$x=intval(75-($width/2));
		break;
	}

# Color is 3
switch($array_complement[3])
	{
	case 'yellow':
		$color=imagecolorallocate($im,0xFF,0xFF,0);
		break;
	case 'orange':
		$color=imagecolorallocate($im,0xFF,0xA5,0);
		break;
	case 'pink':
		$color=imagecolorallocate($im,0xFF,0xC0,0xCB);
		break;
	case 'purple':
		$color=imagecolorallocate($im,0xA0,0x20,0xF0);
		break;
	case 'black':
		$color=imagecolorallocate($im,0,0,0);
		break;
	case 'grey':
		$color=imagecolorallocate($im,0xBE,0xBE,0xBE);
		break;
	case 'red':
		$color=imagecolorallocate($im,0xFF,0,0);
		break;
	case 'brown':
		$color=imagecolorallocate($im,0xA5,0x2A,0x2A);
		break;
	case 'tan':
		$color=imagecolorallocate($im,0xD2,0xB4,0x8C);
		break;
	case 'magenta':
		$color=imagecolorallocate($im,0xFF,0,0xFF);
		break;
	case 'blue':
		$color=imagecolorallocate($im,0,0,0xFF);
		break;
	case 'green':
		$color=imagecolorallocate($im,0,0xFF,0);
		break;
	default:
		$color=imagecolorallocate($im,0xFF,0xFF,0xFF);
		break;
	}

# Display text
imagettftext($im,$size,0,$x,$y,$color,$font,$array_complement[0]);

# Return customized image
return($im);
}

function send404() 
{
Global $DEFAULT_IMAGE;
$send=True;

# Default image
if($DEFAULT_IMAGE[0]!='')
	{
	if(check_number($DEFAULT_IMAGE[0],$DEFAULT_IMAGE[1])) $send=False;
	}

# Send 404
if($send)
	{
	# Send an HTTP 404
	header('HTTP/1.0 404 Not Found'); 
	print('<html><body><h1>HTTP 404 - Image Not Found</h1></body></html>');
	}
}

function check_number($number,$complement='')
{
Global $CACHE_DIR;
Global $PICTURES_DIR;
Global $BLOWUP;

# Not found by default
$return=False;

# Look in the cache directory
$filename=$CACHE_DIR.'/'.$number.'.png';
if(file_exists($filename)) 
	{
	# Retrieve image
	$im=@imagecreatefrompng($filename);

	# Modify image if needed
	if($complement!='') $im=customImage($im,$complement);

	# Send image in the HTTP response
	header ('Content-type: image/png');
	imagepng($im);

	# Destroy image
	imagedestroy($im);

	# Found
	$return=True;
	} 

# Not found
if(!$return)
	{
	# Look in the cache
	$im=getImageFromLibrary($PICTURES_DIR,$number);

	# Image found
	if(!empty($im))
		{
		# Found
		$return=True;

		# Scale image to 150x200 pixel
		$im=scaleImage($im,$BLOWUP);
			
		# Save image to cache directory. use same number in filename as in original file in the library.
		$filename = $CACHE_DIR.'/'.$number.'.png';
		@imagepng($im,$filename);

		# Modify image if needed
		if($complement!='') $im=customImage($im,$complement);

		# Send image in the HTTP response
		header ('Content-type: image/png');
		imagepng($im);

		# Destroy image
		imagedestroy($im);
		}
	}

# Return result
return($return);
}

function remove_prefix($number,$dialplan,$type)
{
# Depending on type
switch($type)
	{
	# Local prefix
	case 'local':
		# Remove local prefixes
		$local_prefixes_array=explode(',',$dialplan['local']);

		# At least one prefix
		if(is_array($local_prefixes_array)) 
			{
			# Check each prefix
			foreach($local_prefixes_array as $prefix) 
				{
				# Empty prefix skip
				$prefix=trim($prefix);
				if($prefix=='') continue;

				# Number starts with prefix
				if(strpos($number,$prefix)===0) 
					{
					# Remove prefix
					$number=str_replace($prefix,'',$number);
					break;
					}

				# Number starts with "external prefix + prefix"
				if(strpos($number,$external_prefix.$prefix)===0) 
					{
					# Remove prefix
					$number=str_replace($dialplan['outgoing'].$prefix,'',$number);
					break;
					}
				}
			}
		break;

	# External
	case 'external':
		# Remove external prefix
		if(isset($dialplan['outgoing'])) 
			{
			# External prefix
			if (strpos($number,$dialplan['outgoing'])===0) 
				{
				# Remove prefix
				$number=substr($number,strlen($dialplan['outgoing']));
				}
			}
		break;

	# International
	case 'international':
		# Remove external/international prefix
		if(isset($dialplan['international'])) 
			{
			# External prefix
			if(strpos($number,$dialplan['outgoing'].$dialplan['international'])===0) 
				{
				# Remove prefix
				$number=substr($number,strlen($dialplan['outgoing'].$dialplan['international']));
				}
			}
		break;
	}

# Return transformed number
return($number);
}

function asterisk_mapping($number,$dialplan)
{
# Load asterisk mapping
$array_asterisk=Aastra_get_number_mapping_Asterisk();

# Check if number is in mapping data. 
if(count($array_asterisk)>0)
	{
	# Process each entry
	foreach($array_asterisk as $key=>$value) 
		{
		# Return mapped number as found in mapping file.
		if (($number == $key) || ($number == $dialplan['outgoing'].$key) || ($number == $dialplan['outgoing'].$dialplan['international'].$key) || ($number == $dialplan['international'].$key))
			{
			$number=$value;
			break;
			}
		}
	}

# Return number
return($number);
}

function transform_mapping($number,$dialplan,$array_mapping,$type)
{
# Not found yet
$found=False;
$complement='';

# Check if number is in mapping file. 
if(count($array_mapping)>0) 
	{
	# Local variables
	$len_outgoing=strlen($dialplan['outgoing']);
	$len_international=strlen($dialplan['international']);

	# Process each entry
	foreach($array_mapping as $keys=>$values) 
		{
		# Get all entries
		$array_keys=explode(',',$keys);

		# Get number
		$array_values=explode(',',$values,2);
		$value=$array_values[0];

		# Process all entries
		foreach($array_keys as $key)
			{
			# Not a prefix
			if(substr($key,0,1)!='p')
				{
				# Return mapped number as found in mapping file.
				if($type=='1')
					{
					if (($number == $key) || ($number == $dialplan['outgoing'].$key) || ($number == $dialplan['outgoing'].$dialplan['international'].$key) || ($number == $dialplan['international'].$key)) 
						{
						$found=True;
						$number=$value;
						$complement=$array_values[1];
						break;
						}
					}
				}
			else
				{
				# Return mapped number as found in mapping file.
				if($type=='2')
					{
					# Remove the P
					$compare=substr($key,1);
					$array_pattern=array();
					$array_pattern[]=$compare;
					if(!in_array($dialplan['outgoing'].$compare,$array_pattern)) $array_pattern[]=$dialplan['outgoing'].$compare;
					if(!in_array($dialplan['outgoing'].$dialplan['international'].$compare,$array_pattern)) $array_pattern[]=$dialplan['outgoing'].$dialplan['international'].$compare;
					if(!in_array($dialplan['international'].$compare,$array_pattern))$array_pattern[]=$dialplan['international'].$compare;

					# Return mapped number as found in mapping file.
					if (check_pattern($number,$array_pattern)) 
						{
						$found=True;
						$number=$value;
						$complement=$array_values[1];
						break;
						}
	
					}
				}

			# Break the loop if found
			if($found) break;
			}
		}
	}

# Return mapping number
return(array($number,$complement));
}

function check_pattern($number,$patterns)
{
# Not found by default
$found=False;

# Process each pattern
foreach($patterns as $pattern)
	{
	# Valid by default
	$valid=True;

	# Store lengths
	$len_pattern=strlen($pattern);
	$len_number=strlen($number);

	# Check if unlimted string
	if(strstr($pattern,'.')) 
		{
		$explode=explode('.',$pattern);
		$pattern=$explode[0].'.';
		$test=True;
		}
	else $test=($len_pattern==$len_number);

	# Do the test
	if($test and $valid)
		{
		for($i=0;($i<$len_pattern) and $valid;$i++)
			{
			switch($pattern[$i])
				{
				case 'x':
					if(($number[$i]<0) or ($number[$i]>9)) $valid=False;
					break;
				case 'z':
					if(($number[$i]<1) or ($number[$i]>9)) $valid=False;
					break;
				case 'n':
					if(($number[$i]<2) or ($number[$i]>9)) $valid=False;
					break;
				case '.':
					$i=$len_pattern;
					break;
				default:
					if($number[$i]!=$pattern[$i]) $valid=False;
					break;
				}
			}
		}
	else $valid=False;
	if($valid) 
		{
		$found=True;
		break;
		}
	}

# Return result;
return($found);
}

#############################################################################
# Body
#############################################################################
# Variables
$found=False;

# Script was called with query string ?number=xxxx
$number_original=preg_replace('/[^0-9]/','',$_GET['number']);

# Script was called with query string ?/xxxx.png or similar match xxxx.png pattern in the query string
if(empty($number_original)) 
	{
	if(preg_match('/([0-9*#]+)\.png/',$_SERVER['QUERY_STRING'],$matches)) $number_original = $matches[1];
	}

# No number
if(empty($number_original)) 
	{
	# Send a 404 and exit
	send404();
	exit;
	}

# Trace
Aastra_trace_call('pcallerID_asterisk','number='.$number_original);

# Retrieve directory configuration
$array_config=Aastra_readINIfile(AASTRA_CONFIG_DIRECTORY.'pictureID.conf','#','=');

# Absolute or relative path to the cache directory. Must be writable by the Web server.
if($array_config['General']['cache']!='') $CACHE_DIR=$array_config['General']['cache'];
else $CACHE_DIR=AASTRA_PATH_CACHE.'imagecache';

# Absolute or relative path to the picture library directory. This is read-only.
if($array_config['General']['pictures']!='') $PICTURES_DIR=$array_config['General']['pictures'];
else $PICTURES_DIR=AASTRA_PATH_CACHE.'pictures';

# Rescale image smaller than 150x200
$BLOWUP=False;
if($array_config['General']['blowup']=='1') $BLOWUP=True;

# Default image
$DEFAULT_IMAGE=array();
if($array_config['General']['default']!='') $DEFAULT_IMAGE=explode(',',$array_config['General']['default']);

# Retrieve local dial plan
$array_temp=Aastra_readINIfile(AASTRA_CONFIG_DIRECTORY.'directory.conf','#','=');

# Maybe an intercom
$intercom=Aastra_get_intercom_config_Asterisk();
if($intercom!='')
	{
	if(strpos($number_original,$intercom)===0) $number_original=str_replace($intercom,'',$number_original);
	}

# Check original number
$found=check_number($number_original);

# Check asterisk mapping
if(!$found)
	{
	$number_transformed=asterisk_mapping($number_original,$array_temp['Dialplan']);
	if($number_transformed!=$number_original) 
		{
		$found=check_number($number_transformed);
		if(!$found) 
			{
			$array_number=transform_mapping($number_transformed,$array_temp['Dialplan'],$array_config['Numbers'],'1');
			if($array_number[0]!=$number_original) $found=check_number($array_number[0],$array_number[1]);
			}
		}
	}

# Remove local prefixes
if(!$found)
	{
	$number_transformed=remove_prefix($number_original,$array_temp['Dialplan'],'local');
	if($number_transformed!=$number_original) $found=check_number($number_transformed);
	}

# Check basic mapping
if(!$found)
	{
	$array_number=transform_mapping($number_original,$array_temp['Dialplan'],$array_config['Numbers'],'1');
	if($array_number[0]!=$number_original) $found=check_number($array_number[0],$array_number[1]);
	}

# Check advanced mapping
if(!$found)
	{
	$array_number=transform_mapping($number_original,$array_temp['Dialplan'],$array_config['Numbers'],'2');
	if($array_number[0]!=$number_original) $found=check_number($array_number[0],$array_number[1]);
	}

# Remove external prefix
if(!$found)
	{
	$number_transformed=remove_prefix($number_original,$array_temp['Dialplan'],'external');
	if($number_transformed!=$number_original) $found=check_number($number_transformed);
	}

# Remove international prefix
if(!$found)
	{
	$number_transformed=remove_prefix($number_original,$array_temp['Dialplan'],'international');
	if($number_transformed!=$number_original) $found=check_number($number_transformed);
	}

# No number
if(!$found) 
	{
	# Send a 404
	send404();
	}

# Clean exit
exit;
?>