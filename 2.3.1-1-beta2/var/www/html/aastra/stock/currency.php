<?php
#############################################################################
# Internet Quote for International Currencies
#
# Aastra SIP Phones 1.4.2 or better
#
# Copyright 2008 Aastra Telecom Ltd
#
# Supported Aastra Phones
#   55i
#   57i
#   57iCT
#   9480i
#   9480iCT	 
#
# script.php
#
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
require_once('AastraIPPhoneFormattedTextScreen.class.php');
require_once('AastraIPPhoneTextScreen.class.php');
require_once('AastraIPPhoneTextMenu.class.php');
require_once('AastraIPPhoneInputScreen.class.php');
require_once('AastraCommon.php');

#############################################################################
# Private functions
#############################################################################

###################################################################################################
# get_currency(array)
#
# This function retrieves currency values
#
# Parameters
#   Array
#        source		source currency
#        target		target currency
#
# Returns an array
#   0 			Boolean to indicate success or failure
#   1 			Array for the results for each symbol
#			0 Name
#			1 Last Trade (Price Only) 
#			2 Date
#			3 Time
#			4 Ask
#			5 Bid
###################################################################################################
function get_currency($array)
{
# OK so far
$return[0]=True;

# Create the HTTP request
$src = 'http://finance.yahoo.com/d/quotes.csv?s=';
foreach ($array as $key=>$value) $src.=$array[$key]['source'].$array[$key]['target'].'=X+';

# Complete the request with the flags
$src=substr($src,0,-1).'&f=nl1d1t1ab';

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

##################################################################################################
# format_line(nb_carac,title,value)
#
# This function format the screen for a FormattedTextScreen with Title....Value
#
# Parameters
#   nb_carac		Number of characters of the line
#   title		Title to be displayed
#   value		Value to be displayed
#
# Returns a string
###################################################################################################
function format_line($nb_carac,$title,$value)
{
# Compute string
$string=$title.str_repeat('.',$nb_carac-strlen($title)-strlen($value)-1).$value;

# Return string
return($string);
}

##################################################################################################
# find_default(default)
#
# This function finds the location of the currency in the pages
#
# Parameters
#   default		Currency to look for
#
# Returns an array
#   page		Page of the currency (0=main)
#   index		Index in the page
###################################################################################################
function find_default($default)
{
Global $currency;
Global $main;
$found=False;

# Try first with the main currencies
$index=1;
foreach($main as $key=>$value)
	{
	if($key==$default) 
		{
		$found=True;
		break;
		}
	else $index++;
	}

# Trey with all of them
if($found)
	{
	$return['page']=0;
	$return['index']=$index;
	}
else
	{
	$index=1;
	foreach($currency as $key=>$value)
		{
		if($key==$default) 
			{
			$found=True;
			break;
			}
		else $index++;
		}
	if($found)
		{
		$return['page']=intval($index/AASTRA_MAXLINES);
		if(($index-$return['page']*AASTRA_MAXLINES) != 0) $return['page']++;
		$return['index']=$index%AASTRA_MAXLINES;
		}
	else
		{
		$return['page']=0;
		$return['index']=1;
		}
	}

# Return results
return($return);
}

##########################################
# Retrieve parameters
$user=Aastra_getvar_safe('user');
$action=Aastra_getvar_safe('action','init');
$input=Aastra_getvar_safe('input');
$choice=Aastra_getvar_safe('choice');
$page=Aastra_getvar_safe('page','0');
$selection=Aastra_getvar_safe('selection');

# Get user if not provided
$header=Aastra_decode_HTTP_header();
if($user=='') $user=$header['mac'];

# Update URI
$XML_SERVER.='?user='.$user;

# Trace
Aastra_trace_call('currency','user='.$user.', action='.$action.', selection='.$selection.', input='.$input);

# Test User Agent
Aastra_test_phone_versions(array('1'=>'','2'=>'','3'=>'','4'=>'2.5.3.','5'=>'3.0.1.'),'0');

# Get Language
$language=Aastra_get_language();

# Retrieve user context
$data=Aastra_get_user_context($user,'currency');
if($data['last']['source']=='') $data['last']['source']='USD';
if($data['last']['target']=='') $data['last']['target']='EUR';
Aastra_save_user_context($user,'currency',$data);

# Global compatibility
$nb_softkeys=Aastra_number_softkeys_supported();

# Create the table with the currencies if needed
if(($action=='input1') or ($action=='input2') or ($action=='inputfav1') or ($action=='inputfav2'))
	{
	# Currency
	$main=array(	'AUD'=>'Australian Dollar','GBP'=>'British Pound','CAD'=>'Canadian Dollar',
			'CNY'=>'Chinese Yuan','EUR'=>'Euro','XAU'=>'Gold Ounces',
			'JPY'=>'Japanese Yen','MXN'=>'Mexican Peso','XPD'=>'Palladium Ounces',
			'XPT'=>'Platinum Ounces','SEK'=>'Swedish Krona','CHF'=>'Swiss Franc',
			'USD'=>'US Dollar');

	$currency=array(	'AFA'=>'Afghanistan Afghani', 	'ALL'=>'Albanian Lek', 	'DZD'=>'Algerian Dinar',
			'ADF'=>'Andorran Franc', 	'ADP'=>'Andorran Peseta', 	'ARS'=>'Argentine Peso',
			'AWG'=>'Aruba Florin', 	'AUD'=>'Australian Dollar', 	'ATS'=>'Austrian Schilling',
			'BSD'=>'Bahamian Dollar', 	'BHD'=>'Bahraini Dinar', 	'BDT'=>'Bangladesh Taka',
			'BBD'=>'Barbados Dollar', 	'BEF'=>'Belgian Franc', 	'BZD'=>'Belize Dollar',
			'BMD'=>'Bermuda Dollar', 	'BTN'=>'Bhutan Ngultrum', 	'BOB'=>'Bolivian Boliviano',
			'BWP'=>'Botswana Pula', 	'BRL'=>'Brazilian Real', 	'GBP'=>'British Pound',
			'BND'=>'Brunei Dollar', 	'BIF'=>'Burundi Franc', 	'XOF'=>'CFA Franc (BCEAO)',
			'XAF'=>'CFA Franc (BEAC)', 	'KHR'=>'Cambodia Riel', 	'CAD'=>'Canadian Dollar',
			'CVE'=>'Cape Verde Escudo', 'KYD'=>'Cayman Islands Dollar', 	'CLP'=>'Chilean Peso',
			'CNY'=>'Chinese Yuan', 	'COP'=>'Colombian Peso', 	'KMF'=>'Comoros Franc',
			'CRC'=>'Costa Rica Colon', 	'HRK'=>'Croatian Kuna', 	'CUP'=>'Cuban Peso',
			'CYP'=>'Cyprus Pound', 	'CZK'=>'Czech Koruna', 	'DKK'=>'Danish Krone',
			'DJF'=>'Dijibouti Franc', 	'DOP'=>'Dominican Peso', 	'NLG'=>'Dutch Guilder',
			'XCD'=>'East Caribbean Dollar', 	'ECS'=>'Ecuadorian Sucre', 	'EGP'=>'Egyptian Pound',
			'SVC'=>'El Salvador Colon', 	'EEK'=>'Estonian Kroon', 	'ETB'=>'Ethiopian Birr',
			'EUR'=>'Euro', 		'FKP'=>'Falkland Islands Pound', 	'FJD'=>'Fiji Dollar',
			'FIM'=>'Finnish Mark', 	'FRF'=>'French Franc', 	'GMD'=>'Gambian Dalasi',
			'DEM'=>'German Mark', 	'GHC'=>'Ghanian Cedi', 	'GIP'=>'Gibraltar Pound',
			'XAU'=>'Gold Ounces', 	'GRD'=>'Greek Drachma', 	'GTQ'=>'Guatemala Quetzal',
			'GNF'=>'Guinea Franc', 	'GYD'=>'Guyana Dollar', 	'HTG'=>'Haiti Gourde',
			'HNL'=>'Honduras Lempira', 	'HKD'=>'Hong Kong Dollar', 	'HUF'=>'Hungarian Forint',
			'ISK'=>'Iceland Krona', 	'INR'=>'Indian Rupee', 	'IDR'=>'Indonesian Rupiah',
			'IQD'=>'Iraqi Dinar', 	'IEP'=>'Irish Punt', 	'ILS'=>'Israeli Shekel',
			'ITL'=>'Italian Lira', 	'JMD'=>'Jamaican Dollar', 	'JPY'=>'Japanese Yen',
			'JOD'=>'Jordanian Dinar', 	'KZT'=>'Kazakhstan Tenge', 	'KES'=>'Kenyan Shilling',
			'KRW'=>'Korean Won', 	'KWD'=>'Kuwaiti Dinar', 	'LAK'=>'Lao Kip', 	'LVL'=>'Latvian Lat',
			'LBP'=>'Lebanese Pound', 	'LSL'=>'Lesotho Loti', 	'LRD'=>'Liberian Dollar',
			'LYD'=>'Libyan Dinar', 	'LTL'=>'Lithuanian Lita', 	'LUF'=>'Luxembourg Franc',
			'MOP'=>'Macau Pataca', 	'MKD'=>'Macedonian Denar', 	'MGF'=>'Malagasy Franc',
			'MWK'=>'Malawi Kwacha', 	'MYR'=>'Malaysian Ringgit', 	'MVR'=>'Maldives Rufiyaa',
			'MTL'=>'Maltese Lira', 	'MRO'=>'Mauritania Ougulya', 	'MUR'=>'Mauritius Rupee',
			'MXN'=>'Mexican Peso', 	'MDL'=>'Moldovan Leu', 	'MNT'=>'Mongolian Tugrik',
			'MAD'=>'Moroccan Dirham', 	'MZM'=>'Mozambique Metical', 	'MMK'=>'Myanmar Kyat',
			'NAD'=>'Namibian Dollar', 	'NPR'=>'Nepalese Rupee', 	'ANG'=>'Neth Antilles Guilder',
			'NZD'=>'New Zealand Dollar', 	'NIO'=>'Nicaragua Cordoba', 	'NGN'=>'Nigerian Naira',
			'KPW'=>'North Korean Won', 	'NOK'=>'Norwegian Krone', 	'OMR'=>'Omani Rial',
			'XPF'=>'Pacific Franc', 	'PKR'=>'Pakistani Rupee', 	'XPD'=>'Palladium Ounces',
			'PAB'=>'Panama Balboa', 	'PGK'=>'Papua New Guinea Kina', 	'PYG'=>'Paraguayan Guarani',
			'PEN'=>'Peruvian Nuevo Sol', 	'PHP'=>'Philippine Peso', 	'XPT'=>'Platinum Ounces',
			'PLN'=>'Polish Zloty', 	'PTE'=>'Portuguese Escudo', 	'QAR'=>'Qatar Rial',
			'ROL'=>'Romanian Leu', 	'RUB'=>'Russian Rouble', 	'WST'=>'Samoa Tala',
			'STD'=>'Sao Tome Dobra', 	'SAR'=>'Saudi Arabian Riyal', 	'SCR'=>'Seychelles Rupee',
			'SLL'=>'Sierra Leone Leone', 	'XAG'=>'Silver Ounces', 	'SGD'=>'Singapore Dollar',
			'SKK'=>'Slovak Koruna', 	'SIT'=>'Slovenian Tolar', 	'SBD'=>'Solomon Islands Dollar',
			'SOS'=>'Somali Shilling', 	'ZAR'=>'South African Rand', 	'ESP'=>'Spanish Peseta',
			'LKR'=>'Sri Lanka Rupee', 	'SHP'=>'St Helena Pound', 	'SDD'=>'Sudanese Dinar',
			'SRG'=>'Surinam Guilder', 	'SZL'=>'Swaziland Lilageni', 	'SEK'=>'Swedish Krona',
			'CHF'=>'Swiss Franc', 	'SYP'=>'Syrian Pound', 	'TWD'=>'Taiwan Dollar',
			'TZS'=>'Tanzanian Shilling', 	'THB'=>'Thai Baht', 	'TOP'=>'Tonga Pa\'anga',
			'TTD'=>'Trinida and Tobago Dollar', 	'TND'=>'Tunisian Dinar', 	'TRL'=>'Turkish Lira',
			'USD'=>'US Dollar', 	'AED'=>'UAE Dirham', 	'UGX'=>'Ugandan Shilling',
			'UAH'=>'Ukraine Hryvnia', 	'UYU'=>'Uruguayan New Peso', 	'VUV'=>'Vanuatu Vatu',
			'VEB'=>'Venezuelan Bolivar', 	'VND'=>'Vietnam Dong', 	'YER'=>'Yemen Riyal',
			'YUM'=>'Yugoslav Dinar', 	'ZMK'=>'Zambian Kwacha', 	'ZWD'=>'Zimbabwe Dollar'
			);
	}

# Retrieve last page
$last=intval(count($currency)/AASTRA_MAXLINES);
if((count($currency)-$last*AASTRA_MAXLINES) != 0) $last++;

# Process special sctions
switch($action)
	{
	# Reverse Source/target
	case 'reverse':
		# Reverse currencies
		$temp=$data['last']['source'];
		$data['last']['source']=$data['last']['target'];
		$data['last']['target']=$temp;
		Aastra_save_user_context($user,'currency',$data);
		$action='init';
		break;

	# Reset favorite
	case 'clear':
		# Retrieve favorites
		$data=Aastra_get_user_context($user,'currency');
		$data['favorites'][$selection]['source']='';
		$data['favorites'][$selection]['target']='';
		Aastra_save_user_context($user,'currency',$data);
		$action='favorites';
		$default=$selection+1;
		break;

	# Up
	case 'up':
		# Retrieve favorites
		$data=Aastra_get_user_context($user,'currency');
		if(($data['favorites'][$selection]!='') and ($selection!=0))
			{
			$temp=$data['favorites'][$selection-1];
			$data['favorites'][$selection-1]=$data['favorites'][$selection];
			$data['favorites'][$selection]=$temp;
			Aastra_save_user_context($user,'currency',$data);
			$default=$selection;
			}
		else $default=$selection+1;
		$action='favorites';
		break;

	# Down
	case 'down':
		# Retrieve favorites
		$data=Aastra_get_user_context($user,'currency');
		if(($data['favorites'][$selection]!='') and ($selection!=(AASTRA_MAXLINES-1)))
			{
			$temp=$data['favorites'][$selection+1];
			$data['favorites'][$selection+1]=$data['favorites'][$selection];
			$data['favorites'][$selection]=$temp;
			Aastra_save_user_context($user,'currency',$data);
			$default=$selection+2;
			}
		else $default=$selection+1;
		$action='favorites';
		break;

	# Favorites
	case 'favorites':
		$default=$selection+1;
		break;

	# Set source
	case 'set_1':
		if($input!='')
			{
			$data['last']['source']=$input;
			Aastra_save_user_context($user,'currency',$data);
			}
		$action='init';
		break;

	# Set Target
	case 'set_2':
		if($input!='')
			{
			$data['last']['target']=$input;
			Aastra_save_user_context($user,'currency',$data);
			}
		$action='init';
		break;
	}

# Depending on action
switch($action)
	{
	# Initial screen
	case 'init':
		# Prepare result screen
		$object = new AastraIPPhoneInputScreen();
		$object->setTitle(Aastra_get_label('Currency converter',$language));
		$object->setURL($XML_SERVER.'&action=display');
		$object->setDisplayMode('condensed');
		$object->addField('empty');
		$object->addField('string');
		$object->setFieldEditable('no');
		$object->setFieldPrompt(Aastra_get_label('Source',$language));
		$object->setFieldDefault($data['last']['source']);
		$object->addField('string');
		$object->setFieldEditable('no');
		$object->setFieldPrompt(Aastra_get_label('Target',$language));
		$object->setFieldDefault($data['last']['target']);

		# Softkeys
		if($nb_softkeys==6)
			{
			$object->addSoftkey('1', Aastra_get_label('Source',$language), $XML_SERVER.'&action=input1&choice='.$data['last']['source']);
			$object->addSoftkey('2', Aastra_get_label('Target',$language), $XML_SERVER.'&action=input2&choice='.$data['last']['target']);
			$object->addSoftkey('3', Aastra_get_label('Reverse',$language), $XML_SERVER.'&action=reverse');
			$object->addSoftkey('4', Aastra_get_label('Watch List',$language), $XML_SERVER.'&action=favorites');
			$object->addSoftkey('5', Aastra_get_label('Convert',$language), 'Softkey:Submit');
			$object->addSoftkey('6', Aastra_get_label('Exit',$language), 'SoftKey:Exit');
			}
		else
			{
			$object->addSoftkey('1', Aastra_get_label('Change Source',$language), $XML_SERVER.'&action=input1&choice='.$data['last']['source']);
			$object->addSoftkey('2', Aastra_get_label('Change Target',$language), $XML_SERVER.'&action=input2&choice='.$data['last']['target']);
			$object->addSoftkey('3', Aastra_get_label('Reverse',$language), $XML_SERVER.'&action=reverse');
			$object->addSoftkey('6', Aastra_get_label('Watch List',$language), $XML_SERVER.'&action=favorites');
			$object->addSoftkey('10', Aastra_get_label('Exit',$language), 'SoftKey:Exit');
			}
		break;

	# Input Source and target
	case 'input1':
	case 'input2':
	case 'inputfav1':
	case 'inputfav2':
		# Save last request
		if($action=='input2')
			{
			unset($main[$data['last']['source']]);
			unset($currency[$data['last']['source']]);
			}

		# Save last request
		if($action=='inputfav2')
			{
			if($input!='')
				{
				$data['favorites'][$selection]['source']=$input;
				Aastra_save_user_context($user,'currency',$data);
				}
			unset($main[$data['favorites'][$selection]['source']]);
			unset($currency[$data['favorites'][$selection]['source']]);
			}

		# Set choice for fav1
		if(($action=='inputfav1') and ($choice!='')) $choice=$data['favorites'][$selection]['source'];
		
		# Create the Textmenu
		$object = new AastraIPPhoneTextMenu();

		# Position on the right page and Index
		if($choice!='')
			{
			$array=find_default($choice);
			$page=$array['page'];
			$object->setDefaultIndex($array['index']);
			}
	
		# Display the list
		if($page==0)
			{
			$index=1;
			$start='';
			foreach($main as $key=>$value)
				{
				switch($action)
					{
					case 'input1':
						$object->addEntry($value,$XML_SERVER.'&action=set_1&input='.$key);
						break;
					case 'input2':
						$object->addEntry($value,$XML_SERVER.'&action=set_2&input='.$key);
						break;
					case 'inputfav1':
						$object->addEntry($value,$XML_SERVER.'&action=inputfav2&input='.$key.'&choice='.$data['favorites'][$selection]['target'].'&selection='.$selection);
						break;
					case 'inputfav2':
						$object->addEntry($value,$XML_SERVER.'&action=favorites&input='.$key.'&selection='.$selection);
						break;
					}
				}

			# Title displaying first and last element of the list
			switch($action)
				{
				case 'input1':
					$object->setTitle(Aastra_get_label('Source Currency (Main)',$language));
					break;
				case 'input2':
					$object->setTitle(Aastra_get_label('Target Currency (Main)',$language));
					break;
				case 'inputfav1':
					$object->setTitle(sprintf(Aastra_get_label('Source Fav #%s (Main)',$language),$selection+1));
					break;
				case 'inputfav2':
					$object->setTitle(sprintf(Aastra_get_label('Target Fav #%s (Main)',$language),$selection+1));
					break;
				}
			}
		else
			{
			$index=1;
			$start='';
			foreach($currency as $key=>$value)
				{
				if(($index>=($page-1)*AASTRA_MAXLINES+1) and ($index<=($page*AASTRA_MAXLINES))) 
					{
					switch($action)
						{
						case 'input1':
							$object->addEntry($value,$XML_SERVER.'&action=set_1&input='.$key);
							break;
						case 'input2':
							$object->addEntry($value,$XML_SERVER.'&action=set_2&input='.$key);
							break;
						case 'inputfav1':
							$object->addEntry($value,$XML_SERVER.'&action=inputfav2&input='.$key.'&choice='.$data['favorites'][$selection]['target'].'&selection='.$selection);
							break;
						case 'inputfav2':
							$object->addEntry($value,$XML_SERVER.'&action=favorites&input='.$key.'&selection='.$selection);
							break;
						}
					if($start=='') $start=substr($value,0,2);
					$end=substr($value,0,2);
					}
				$index++;
				}

			# Title displaying first and last element of the list
			switch($action)
				{
				case 'input1':
					$object->setTitle(sprintf(Aastra_get_label('Source Currency (%s-%s)',$language),$start,$end));
					break;
				case 'input2':
					$object->setTitle(sprintf(Aastra_get_label('Target Currency (%s-%s)',$language),$start,$end));
					break;
				case 'inputfav1':
					$object->setTitle(sprintf(Aastra_get_label('Source Fav #%s (%s-%s)',$language),$selection+1,$start,$end));
					break;
				case 'inputfav2':
					$object->setTitle(sprintf(Aastra_get_label('Target Fav #%s (%s-%s)',$language),$selection+1,$start,$end));
					break;
				}
			}

		# Softkeys
		if($nb_softkeys==6)
			{
			# Phone with 6 softkeys
			$object->addSoftkey('1', Aastra_get_label('Select',$language), 'SoftKey:Select');
			if($page!=0) $object->addSoftkey('2', Aastra_get_label('Previous',$language), $XML_SERVER.'&action='.$action.'&page='.($page-1).'&selection='.$selection);
			switch($action)
				{
				case 'input1':
				case 'input2':
					$object->addSoftkey('4', Aastra_get_label('Cancel',$language), $XML_SERVER.'&action=init');
					break;
				case 'inputfav1':
					$object->addSoftkey('4', Aastra_get_label('Cancel',$language), $XML_SERVER.'&action=favorites&selection='.$selection);
					break;
				case 'inputfav2':
					$object->addSoftkey('4', Aastra_get_label('Back',$language), $XML_SERVER.'&action=inputfav1&choice='.$data['favorites'][$selection]['source'].'&selection='.$selection);
					break;
				}
			if($page!=$last) $object->addSoftkey('5', Aastra_get_label('Next',$language), $XML_SERVER.'&action='.$action.'&page='.($page+1).'&selection='.$selection);
			$object->addSoftkey('6', Aastra_get_label('Exit',$language), 'SoftKey:Exit');
			}
		else
			{
			# 6739i
			if($page!=0) $object->addSoftkey('3', Aastra_get_label('Previous',$language), $XML_SERVER.'&action='.$action.'&page='.($page-1).'&selection='.$selection);
			switch($action)
				{
				case 'input1':
				case 'input2':
					$object->addSoftkey('9', Aastra_get_label('Cancel',$language), $XML_SERVER.'&action=init');
					$object->setCancelAction($XML_SERVER.'&action=init');
					break;
				case 'inputfav1':
					$object->addSoftkey('9', Aastra_get_label('Cancel',$language), $XML_SERVER.'&action=favorites&selection='.$selection);
					break;
				case 'inputfav2':
					$object->addSoftkey('9', Aastra_get_label('Back',$language), $XML_SERVER.'&action=inputfav1&choice='.$data['favorites'][$selection]['source'].'&selection='.$selection);
					break;
				}
			if($page!=$last) $object->addSoftkey('8', Aastra_get_label('Next',$language), $XML_SERVER.'&action='.$action.'&page='.($page+1).'&selection='.$selection);
			$object->addSoftkey('10', Aastra_get_label('Exit',$language), 'SoftKey:Exit');
			}
		break;

	# Display favorites
	case 'favorites':
		# Save last request
		if($input!='')
			{
			$data['favorites'][$selection]['target']=$input;
			Aastra_save_user_context($user,'currency',$data);
			}

		# Create list
		$object = new AastraIPPhoneTextMenu();
		$summary=False;
		$object->setTitle(Aastra_get_label('Watch List',$language));
		for($i=0;$i<AASTRA_MAXLINES;$i++)
			{
			if(($data['favorites'][$i]['source']=='') or ($data['favorites'][$i]['target']==''))
				{
				$object->addEntry('...........................',$XML_SERVER.'&action=inputfav1&selection='.$i,$i);
				$data['favorites'][$i]['source']='';
				$data['favorites'][$i]['target']='';
				}
			else 
				{
				$object->addEntry(sprintf(Aastra_get_label('%s to %s',$language),$data['favorites'][$i]['source'],$data['favorites'][$i]['target']),$XML_SERVER.'&action=displayfav&selection='.$i,$i);
				$summary=True;
				}
			}

		# Update user favorites
		Aastra_save_user_context($user,'currency',$data);

		# Set default index
		if($default!='') $object->setDefaultIndex($default);

		# Add softkeys
		if($nb_softkeys==6)
			{
			# Regular phone
			if($page=='0')
				{
				$object->addSoftkey('1', Aastra_get_label('Select',$language), 'SoftKey:Select');
				$object->addSoftkey('2', Aastra_get_label('Move Up',$language), $XML_SERVER.'&action=up');
				if($summary) $object->addSoftkey('3', Aastra_get_label('Summary',$language), $XML_SERVER.'&action=summary');
				$object->addSoftkey('4', Aastra_get_label('Edit',$language), $XML_SERVER.'&action=inputfav1&choice=fav1');
				$object->addSoftkey('5', Aastra_get_label('Move Down',$language), $XML_SERVER.'&action=down');
				$object->addSoftkey('6', Aastra_get_label('More',$language), $XML_SERVER.'&action=favorites&page=1&selection='.$selection);
				}
			else
				{
				$object->addSoftkey('2', Aastra_get_label('Clear',$language), $XML_SERVER.'&action=clear&page=1');
				if($summary) $object->addSoftkey('3', Aastra_get_label('Summary',$language), $XML_SERVER.'&action=summary');
				$object->addSoftkey('4', Aastra_get_label('Back',$language), $XML_SERVER.'&action=init');
				$object->addSoftkey('5', Aastra_get_label('Exit',$language), 'SoftKey:Exit');
				$object->addSoftkey('6', Aastra_get_label('More',$language), $XML_SERVER.'&action=favorites&page=0&selection='.$selection);
				}
			}
		else
			{
			$object->addSoftkey('3', Aastra_get_label('Move Up',$language), $XML_SERVER.'&action=up');
			$object->addSoftkey('5', Aastra_get_label('Clear',$language), $XML_SERVER.'&action=clear&page=1');
			$object->addSoftkey('6', Aastra_get_label('Edit',$language), $XML_SERVER.'&action=inputfav1&choice=fav1');
			$object->addSoftkey('8', Aastra_get_label('Move Down',$language), $XML_SERVER.'&action=down');
			$object->addSoftkey('9', Aastra_get_label('Back',$language), $XML_SERVER.'&action=init');
			$object->addSoftkey('10', Aastra_get_label('Exit',$language), 'SoftKey:Exit');
			if($summary) $object->addSoftkey('1', Aastra_get_label('Summary',$language), $XML_SERVER.'&action=summary');
			}
		break;

	# Summary
	case 'summary':
		# Retrieve favorites
		foreach($data['favorites'] as $key=>$value) 
			{
			if($value['target']!='') $array[]=$value;
			}
		$return=get_currency($array);

		# Return OK
		if($return[0])
			{
			# Create the object
			$object = new AastraIPPhoneFormattedTextScreen();

			# Process the results
			if($nb_softkeys==6)
				{
				# Regular phone
				$nb_carac=Aastra_size_display_line();	
				$object->addLine(Aastra_get_label('Watch List',$language),NULL,'center');
				$object->addLine('');
				$object->setScrollStart(Aastra_size_formattedtextscreen()-2);
				foreach($return[1] as $key=>$value)
					{
					if($value!=NULL) 
						{
						if(!strstr($value[0],'=X')) $object->addLine(format_line($nb_carac,$value[0],$value[1]));
						else $object->addLine(format_line($nb_carac,sprintf(Aastra_get_label('%s to %s',$language),substr($value[0],0,3),substr($value[0],3,3)),'N/A'));
						}
					}
				$object->addLine(Aastra_get_label('Powered by Yahoo',$language),'','center');
				$object->setScrollEnd();
				}
			else
				{
				# 6739i
				$object->addLine(Aastra_get_label('Watch List',$language),'double','center');
				$object->addLine('');
				$object->setScrollStart();
				foreach($return[1] as $key=>$value)
					{
					if($value!=NULL) 
						{
						if(!strstr($value[0],'=X')) $object->addLine($value[0].': '.$value[1]);
						else $object->addLine(sprintf(Aastra_get_label('%s to %s',$language),substr($value[0],0,3),substr($value[0],3,3).': N/A'));
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
			$object->setTitle(Aastra_get_label('Currency conversion',$language));
			$object->setText(Aastra_get_label('Information not available at this time. Please try again later.',$language));
			}
	
		# Add remaining softkeys
		if($nb_softkeys==6)
			{
			$object->addSoftkey('5',Aastra_get_label('Back',$language), $XML_SERVER.'&action=favorites&selection='.$selection);
			$object->addSoftkey('6',Aastra_get_label('Exit',$language), 'SoftKey:Exit');
			}
		else
			{
			$object->addSoftkey('9',Aastra_get_label('Back',$language), $XML_SERVER.'&action=favorites&selection='.$selection);
			$object->addSoftkey('10',Aastra_get_label('Exit',$language), 'SoftKey:Exit');
			}
		break;

	# Display result
	case 'display':
	case 'displayfav':
		# Retrieve quote
		if($action=='display') $array[0]=$data['last'];
		else $array[0]=$data['favorites'][$selection];
		$return=get_currency($array);

		# Return OK
		if($return[0])
			{
			# Create the object
			$object = new AastraIPPhoneFormattedTextScreen();

			# Display results
			if($nb_softkeys==6)
				{
				# Regular phone
				if($action=='display') $object->addLine($data['last']['source'].' to '.$data['last']['target'],NULL,'center');
				else $object->addLine($data['favorites'][$selection]['source'].' to '.$data['favorites'][$selection]['target'],NULL,'center');
				$object->setScrollStart(Aastra_size_formattedtextscreen()-1);
				$nb_carac=Aastra_size_display_line();
				$object->addLine('');
				$object->addLine(format_line($nb_carac,Aastra_get_label('Last Trade',$language),$return[1][0][1]));
				$object->addLine(format_line($nb_carac,Aastra_get_label('Ask',$language),$return[1][0][4]));
				$object->addLine(format_line($nb_carac,Aastra_get_label('Bid',$language),$return[1][0][5]));
				$object->addLine(format_line($nb_carac,Aastra_get_label('Date',$language),$return[1][0][2]));
				$object->addLine(format_line($nb_carac,Aastra_get_label('Time',$language),$return[1][0][3]));
				$object->addLine(Aastra_get_label('Powered by Yahoo',$language),'','center');
				$object->setScrollEnd();
				}
			else
				{
				# 6739i
				if($action=='display') $object->addLine($data['last']['source'].' to '.$data['last']['target'],'double','center');
				else $object->addLine($data['favorites'][$selection]['source'].' to '.$data['favorites'][$selection]['target'],'double','center');
				$object->setScrollStart();
				$object->addLine('');
				$object->addLine(Aastra_get_label('Last Trade',$language).': '.$return[1][0][1]);
				$object->addLine(Aastra_get_label('Ask',$language).': '.$return[1][0][4]);
				$object->addLine(Aastra_get_label('Bid',$language).': '.$return[1][0][5]);
				$object->addLine(Aastra_get_label('Date',$language).': '.$return[1][0][2]);
				$object->addLine(Aastra_get_label('Time',$language).': '.$return[1][0][3]);
				$object->setScrollEnd();
				$object->addLine(Aastra_get_label('Powered by Yahoo',$language),'','center');
				}
			}
		else
			{
			# Prepare result screen
			$object = new AastraIPPhoneTextScreen();
			$object->setTitle(Aastra_get_label('Currency converter',$language));
			$object->setText(Aastra_get_label('Information not available at this time. Please try again later.',$language));
			}
	
		# Add remaining softkeys
		if($nb_softkeys==6)
			{
			if($action=='display') $object->addSoftkey('4',Aastra_get_label('New Lookup',$language), $XML_SERVER.'&action=init');
			else $object->addSoftkey('4',Aastra_get_label('Back',$language), $XML_SERVER.'&action=favorites&selection='.$selection);
			$object->addSoftkey('6',Aastra_get_label('Exit',$language), 'SoftKey:Exit');
			}
		else
			{
			if($action=='display') $object->addSoftkey('6',Aastra_get_label('New Lookup',$language), $XML_SERVER.'&action=init');
			else $object->addSoftkey('9',Aastra_get_label('Back',$language), $XML_SERVER.'&action=favorites&selection='.$selection);
			$object->addSoftkey('10',Aastra_get_label('Exit',$language), 'SoftKey:Exit');
			}
		break;
	}

# Display object
$object->setDestroyOnExit();
$object->output();
exit;
?>
