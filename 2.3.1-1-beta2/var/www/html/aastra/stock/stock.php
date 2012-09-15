<?php
#############################################################################
# Internet Stock Quote
#
# Aastra SIP Phones 1.4.2 or better
#
# Copyright 2007-2009 Aastra Telecom Ltd
#
# Supported Aastra Phones
#   All phones but best on large display phones
#
# script.php
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
require_once('AastraIPPhoneTextScreen.class.php');
require_once('AastraIPPhoneFormattedTextScreen.class.php');
require_once('AastraIPPhoneTextMenu.class.php');
require_once('AastraIPPhoneInputScreen.class.php');
require_once('AastraCommon.php');

#############################################################################
# Private functions
#############################################################################

###################################################################################################
# get_quote(array)
#
# This function retrieves stock information for the list of stock symbols.
#
# Parameters
#   array		Array of stock symbols
#
# Returns an array
#   0 			Boolean to indicate success or failure
#   1 			Array for the results for each symbol
#   			0 Symbol 
#			1 Name
#			2 Last Trade (Price Only) 
#			3 52-week Low 
#			4 52-week High 
#			5 Change 
#			6 Change in Percent 
#			7 Volume
#			8 Error Indication (returned for symbol changed / invalid)
###################################################################################################
function get_quote($array)
{
# OK so far
$return[0]=True;

# Create the HTTP request
$src = 'http://finance.yahoo.com/d/quotes.csv?s=';
foreach ($array as $key=>$value) $src.=$value.'+';

# Complete the request
$src=substr($src,0,-1).'&f=snl1jkc1p2ve1';

# Launch the request
$fp = @fopen ($src, 'r');
if($fp)
	{
	while (($result[] = fgetcsv($fp,1000)) !== FALSE);
	fclose($fp);
	}
else $return[0]=False;

# Return result
$return[1]=$result;
return($return);
}

###################################################################################################
# format_line(nb_carac,title,value)
#
# This function format the result line.
#
# Parameters
#   nb_carac		Number of characters on the display
#   title		Title to be displayed
#   value		Value to be displayed
#
# Returns a formatted string
###################################################################################################
function format_line($nb_carac,$title,$value)
{
# Compute string
$string=$title.str_repeat('.',$nb_carac-strlen($title)-strlen($value)-1).$value;

# Return string
return($string);
}


##########################################
# Retrieve parameters
$user=Aastra_getvar_safe('user');
$action=Aastra_getvar_safe('action','input');
$page=Aastra_getvar_safe('page','1');
$selection=Aastra_getvar_safe('selection');
$symbol=strtoupper(Aastra_getvar_safe('symbol'));

# Get user if not provided
$header=Aastra_decode_HTTP_header();
if($user=='') $user=$header['mac'];

# Update URI
$XML_SERVER.='?user='.$user;

# Trace
Aastra_trace_call('stock','user='.$user.', symbol='.$symbol);

# Test User Agent
Aastra_test_phone_version('1.4.2.',0);

# Get Language
$language=Aastra_get_language();

# Global compatibility
$nb_softkeys=Aastra_number_softkeys_supported();

# Pre-Process special actions
switch($action)
	{
	# Store favorite
	case 'set':
		# Retrieve favorites
		$data=Aastra_get_user_context($user,'stock');
		$data['favorites'][$selection]=$symbol;
		Aastra_save_user_context($user,'stock',$data);
		$action='favorites';
		$default=$selection+1;
		break;

	# Reset favorite
	case 'clear':
		# Retrieve favorites
		$data=Aastra_get_user_context($user,'stock');
		$data['favorites'][$selection]='';
		Aastra_save_user_context($user,'stock',$data);
		$action='favorites';
		$default=$selection+1;
		break;

	# Up
	case 'up':
		# Retrieve favorites
		$data=Aastra_get_user_context($user,'stock');
		if(($data['favorites'][$selection]!='') and ($selection!=0))
			{
			$temp=$data['favorites'][$selection-1];
			$data['favorites'][$selection-1]=$data['favorites'][$selection];
			$data['favorites'][$selection]=$temp;
			Aastra_save_user_context($user,'stock',$data);
			$default=$selection;
			}
		else $default=$selection+1;
		$action='favorites';
		break;

	# Down
	case 'down':
		# Retrieve favorites
		$data=Aastra_get_user_context($user,'stock');
		if(($data['favorites'][$selection]!='') and ($selection!=(AASTRA_MAXLINES-1)))
			{
			$temp=$data['favorites'][$selection+1];
			$data['favorites'][$selection+1]=$data['favorites'][$selection];
			$data['favorites'][$selection]=$temp;
			Aastra_save_user_context($user,'stock',$data);
			$default=$selection+2;
			}
		else $default=$selection+1;
		$action='favorites';
		break;

	# Favorites
	case 'favorites':
		$default=$selection+1;
		break;
	}

# Depending on action
switch($action)
	{
	# Input symbol
	case 'input':
	case 'inputfav':
		# Input sticker
		$object = new AastraIPPhoneInputScreen();
		$object->setType('string');
		if($action=='input') $object->setTitle(Aastra_get_label('Get Stock quotes',$language));
		else $object->setTitle(sprintf(Aastra_get_label('Favorite #%s',$language),($selection+1)));
		$object->setPrompt(Aastra_get_label('Enter Ticker',$language));
		$object->setParameter('symbol');
		if($action=='input') $object->setURL($XML_SERVER.'&action=display');
		else $object->setURL($XML_SERVER.'&action=set&selection='.$selection);
		$data=Aastra_get_user_context($user,'stock');
		if($action=='input')
			{
			if($data['last']!=NULL) $default=$data['last'];
			else $default='AAH.TO';
			}
		else $default=$data['favorites'][$selection];
		$object->setDefault($default);

		# Softkeys
		if($nb_softkeys>0)
			{
			if($nb_softkeys==6)
				{
				$object->addSoftkey('1', Aastra_get_label('Backspace',$language), 'SoftKey:BackSpace');
				$object->addSoftkey('2', '.', 'SoftKey:Dot');
				if($action=='input') 
					{
					$object->addSoftkey('3', Aastra_get_label('Watch List',$language), $XML_SERVER.'&action=favorites');
					$object->addSoftkey('5', Aastra_get_label('Lookup',$language), 'SoftKey:Submit');
					}
				else 
					{
					$object->addSoftkey('4', Aastra_get_label('Cancel',$language), $XML_SERVER.'&action=favorites&selection='.$selection);
					$object->addSoftkey('5', Aastra_get_label('Enter',$language), 'SoftKey:Submit');
					}
				$object->addSoftkey('6', Aastra_get_label('Exit',$language), 'SoftKey:Exit');
				}
			else
				{
				if($action=='input') 
					{
					$object->addSoftkey('5', Aastra_get_label('Watch List',$language), $XML_SERVER.'&action=favorites');
					}
				else 
					{
					$object->addSoftkey('8', Aastra_get_label('Cancel',$language), $XML_SERVER.'&action=favorites&selection='.$selection);
					$object->setCancelAction($XML_SERVER.'&action=favorites&selection='.$selection);
					}
				$object->addSoftkey('10', Aastra_get_label('Exit',$language), 'SoftKey:Exit');
				}
			}
		break;

	# Display favorites
	case 'favorites':
		# Retrieve favorites
		$data=Aastra_get_user_context($user,'stock');

		# Create list
		$object = new AastraIPPhoneTextMenu();
		$summary=False;
		$object->setTitle(Aastra_get_label('Watch List',$language));
		for($i=0;$i<AASTRA_MAXLINES;$i++)
			{
			if($data['favorites'][$i]=='') $object->addEntry('...........................',$XML_SERVER.'&action=inputfav&selection='.$i,$i);
			else 
				{
				$object->addEntry($data['favorites'][$i],$XML_SERVER.'&action=displayfav&selection='.$i,$i);
				$summary=True;
				}
			}
		# Set default index
		if($default!='') $object->setDefaultIndex($default);

		# Add softkeys
		if($nb_softkeys>0)
			{
			if($nb_softkeys==6)
				{
				if($page==1)
					{
					$object->addSoftkey('1', Aastra_get_label('Select',$language), 'SoftKey:Select');
					$object->addSoftkey('2', Aastra_get_label('Move Up',$language), $XML_SERVER.'&action=up');
					if($summary and Aastra_is_formattedtextscreen_supported()) $object->addSoftkey('3', Aastra_get_label('Summary',$language), $XML_SERVER.'&action=summary');
					$object->addSoftkey('4', Aastra_get_label('Edit',$language), $XML_SERVER.'&action=inputfav');
					$object->addSoftkey('5', Aastra_get_label('Move Down',$language), $XML_SERVER.'&action=down');
					$object->addSoftkey('6', Aastra_get_label('More',$language), $XML_SERVER.'&action=favorites&page=2&selection='.$selection);
					}
				else
					{
					$object->addSoftkey('2', Aastra_get_label('Clear',$language), $XML_SERVER.'&action=clear');
					if($summary and Aastra_is_formattedtextscreen_supported()) $object->addSoftkey('3', Aastra_get_label('Summary',$language), $XML_SERVER.'&action=summary');
					$object->addSoftkey('4', Aastra_get_label('Back',$language), $XML_SERVER.'&action=input');
					$object->addSoftkey('5', Aastra_get_label('Exit',$language), 'SoftKey:Exit');
					$object->addSoftkey('6', Aastra_get_label('More',$language), $XML_SERVER.'&action=favorites&page=1&selection='.$selection);
					}
				}
			else
				{
				if($summary and Aastra_is_formattedtextscreen_supported()) $object->addSoftkey('1', Aastra_get_label('Summary',$language), $XML_SERVER.'&action=summary');
				$object->addSoftkey('2', Aastra_get_label('Clear',$language), $XML_SERVER.'&action=clear');
				$object->addSoftkey('3', Aastra_get_label('Move Up',$language), $XML_SERVER.'&action=up');
				$object->addSoftkey('6', Aastra_get_label('Edit',$language), $XML_SERVER.'&action=inputfav');
				$object->addSoftkey('8', Aastra_get_label('Move Down',$language), $XML_SERVER.'&action=down');
				$object->addSoftkey('9', Aastra_get_label('Back',$language), $XML_SERVER.'&action=input');
				$object->setCancelAction($XML_SERVER.'&action=input');
				$object->addSoftkey('10', Aastra_get_label('Exit',$language), 'SoftKey:Exit');
				}
			}
		break;

	# Display result
	case 'display':
	case 'displayfav':
		# Retrieve cached data
		$data=Aastra_get_user_context($user,'stock');

		# Simple lookup
		if($action=='display')
			{
			# Save the last request
			$data['last']=$symbol;
			Aastra_save_user_context($user,'stock',$data);

			# Prepare request
			$array[0]=$symbol;
			}
		else
			{
			# Prepare request
			$array[0]=$data['favorites'][$selection];
			}

		# Process request
		$return=get_quote($array);

		# Return OK
		if($return[0])
			{
			# Can we use a formatted text screen
			if(Aastra_is_formattedtextscreen_supported())
				{
				# Get size of the screen
				$nb_carac=Aastra_size_display_line();

				# Create the object
				$object = new AastraIPPhoneFormattedTextScreen();

				# Symbol not found
				if($return[1][0][8]!='N/A')
					{
					# Display error message
					$object->addLine(Aastra_get_label('Stock results',$language),null,'center');
					if(Aastra_size_formattedtextscreen()>3) $object->addLine('');
					if($nb_carac>20)
						{
						$object->addLine(sprintf(Aastra_get_label('Symbol %s not found',$language),$symbol));
						$object->addLine(Aastra_get_label('Powered by Yahoo',$language),'','center');
						}
					else
						{
						$object->addLine(Aastra_get_label('Symbol not found',$language));
						$object->addLine('www.yahoo.com','','center');
						}
					}
				else 
					{
					# Color FormattedTextScreen
					if(!Aastra_is_formattedtextscreen_color_supported())
						{
						# Display results
						$object->addLine($return[1][0][1],NULL,'center');
						$object->setScrollStart(Aastra_size_formattedtextscreen()-1);
					
						if($nb_carac>20) 
							{
							$object->addLine(format_line($nb_carac,Aastra_get_label('Last Trade',$language),$return[1][0][2]));
							$object->addLine(format_line($nb_carac,Aastra_get_label('52-week Low',$language),$return[1][0][3]));
							$object->addLine(format_line($nb_carac,Aastra_get_label('52-week High',$language),$return[1][0][4]));
							$object->addLine(format_line($nb_carac,Aastra_get_label('Change',$language),$return[1][0][5]));
							$object->addLine(format_line($nb_carac,Aastra_get_label('Change (%)',$language),$return[1][0][6]));
							$object->addLine(format_line($nb_carac,Aastra_get_label('Volume',$language),$return[1][0][7]));
							}
						else
							{
							$object->addLine(format_line($nb_carac,Aastra_get_label('Last',$language),$return[1][0][2]));
							$object->addLine(format_line($nb_carac,Aastra_get_label('52W Low',$language),$return[1][0][3]));
							$object->addLine(format_line($nb_carac,Aastra_get_label('52W High',$language),$return[1][0][4]));
							$object->addLine(format_line($nb_carac,Aastra_get_label('Chge',$language),$return[1][0][5]));
							$object->addLine(format_line($nb_carac,Aastra_get_label('Chge %',$language),$return[1][0][6]));
							$object->addLine(format_line($nb_carac,Aastra_get_label('Vol.',$language),$return[1][0][7]));
							}
						$object->addLine('');
						$object->addLine(Aastra_get_label('Powered by Yahoo',$language),'','center');
						$object->setScrollEnd();
						}
					else
						{
						# Prepare color
						if($return[1][0][5]<0) $color='red';
						else $color='green';

						# Display results
						$object->addLine($return[1][0][1],'double','center',$color);
						$object->addLine('');
						$object->setScrollStart();
						$object->addLine(Aastra_get_label('Last Trade',$language).': '.$return[1][0][2],'double');
						$object->addLine(Aastra_get_label('52-week Low',$language).': '.$return[1][0][3],'double');
						$object->addLine(Aastra_get_label('52-week High',$language).': '.$return[1][0][4],'double');
						$object->addLine(Aastra_get_label('Change',$language).': '.$return[1][0][5],'double',NULL,$color);
						$object->addLine(Aastra_get_label('Change (%)',$language).': '.$return[1][0][6],'double',NULL,$color);
						$object->addLine(Aastra_get_label('Volume',$language).': '.$return[1][0][7],'double');
						$object->setScrollEnd();
						$object->addLine(Aastra_get_label('Powered by Yahoo',$language),'','center');
						}
					}
				}
			else
				{
				$object = new AastraIPPhoneTextScreen();
				$object->setTitle(Aastra_get_label('Stock results',$language));
				if ($return[1][0][8]!='N/A')$object->setText(sprintf(Aastra_get_label('Symbol %s not found (www.yahoo.com)',$language),$symbol));
				else $object->setText(sprintf(Aastra_get_label('Latest trade for %s is %s (www.yahoo.com)',$language),$symbol,$return[1][0][2]));
				}
			}
		else
			{
			# Prepare result screen
			$object = new AastraIPPhoneTextScreen();
			$object->setTitle(Aastra_get_label('Stock results',$language));
			$object->setText(Aastra_get_label('Information not available at this time. Please try again later.',$language));
			}
	
		# Add remaining softkeys
		if($nb_softkeys>0)
			{
			if($nb_softkeys==6)
				{
				if($action=='display') $object->addSoftkey('4',Aastra_get_label('New Lookup',$language), $XML_SERVER.'&action=input');
				else $object->addSoftkey('5',Aastra_get_label('Back',$language), $XML_SERVER.'&action=favorites&selection='.$selection);
				$object->addSoftkey('6',Aastra_get_label('Exit',$language), 'SoftKey:Exit');
				}
			else
				{
				if($action=='display') 
					{
					$object->addSoftkey('5', Aastra_get_label('Watch List',$language), $XML_SERVER.'&action=favorites');
					$object->addSoftkey('9',Aastra_get_label('New lookup',$language), $XML_SERVER.'&action=input');
					$object->setCancelAction($XML_SERVER.'&action=input');
					}
				else 
					{
					$object->addSoftkey('8',Aastra_get_label('Back',$language), $XML_SERVER.'&action=favorites&selection='.$selection);
					$object->setCancelAction($XML_SERVER.'&action=favorites&selection='.$selection);
					}
				$object->addSoftkey('10',Aastra_get_label('Exit',$language), 'SoftKey:Exit');
				}
			}
		break;

	# Summary
	case 'summary':
		# Retrieve favorites
		$data=Aastra_get_user_context($user,'stock');
		foreach($data['favorites'] as $key=>$value) 
			{
			if($value!='') $array[]=$value;
			}
		$return=get_quote($array);

		# Return OK
		if($return[0])
			{
			# Create the object
			$object = new AastraIPPhoneFormattedTextScreen();

			# No color FTS
			if(!Aastra_is_formattedtextscreen_color_supported())
				{
				# Process the results
				$nb_carac=Aastra_size_display_line();
				$object->setScrollStart(Aastra_size_formattedtextscreen());
				foreach($return[1] as $key=>$value)
					{
					if($value!=NULL)
						{
						if($value[8]!='N/A') $last='Not Found';
						else $last=$value[2];
						$object->addLine(format_line($nb_carac,$value[0],$last));
						}
					}
				$object->addLine(Aastra_get_label('Powered by Yahoo',$language),'','center');
				$object->setScrollEnd();
				}
			else
				{
				# Color FTS
				$object->setScrollStart();
				foreach($return[1] as $key=>$value)
					{
					if($value!=NULL)
						{
						if($value[8]!='N/A') $last='Not Found';
						else $last=$value[2];
						if($value[6]<0) $color='red';
						else $color='green';
						$object->addLine($value[0].': '.$last.' ('.$value[6].')',NULL,NULL,$color);
						}
					}
				$object->setScrollEnd();
				$object->addLine(Aastra_get_label('Powered by Yahoo',$language),'','center');
				}
			}
		else 
			{
			# Prepare result screen
			$object = new AastraIPPhoneTextScreen();
			$object->setTitle(Aastra_get_label('Stock results',$language));
			$object->setText(Aastra_get_label('Information not available at this time. Please try again later.',$language));
			}
	
		# Add remaining softkeys
		if($nb_softkeys>0)
			{
			if($nb_softkeys==6)
				{
				$object->addSoftkey('5',Aastra_get_label('Back',$language), $XML_SERVER.'&action=favorites&selection='.$selection);
				$object->addSoftkey('6',Aastra_get_label('Exit',$language), 'SoftKey:Exit');
				}
			else
				{
				$object->addSoftkey('8',Aastra_get_label('Back',$language), $XML_SERVER.'&action=favorites&selection='.$selection);
				$object->addSoftkey('10',Aastra_get_label('Exit',$language), 'SoftKey:Exit');
				$object->setCancelAction($XML_SERVER.'&action=favorites&selection='.$selection);
				}
			}
		break;
	}

# Display object
$object->setDestroyOnExit();
$object->output();
exit;
?>
