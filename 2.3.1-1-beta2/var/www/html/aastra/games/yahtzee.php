<?php
#############################################################################
# Yahtzee
#
# Aastra SIP Phones 2.2.0 or better
#
# Copyright 2008 Aastra Telecom Ltd
#
# Supported Aastra Phones
#   55i, 57i and 57iCT
#
# Usage
# 	script.php
#
# Note
#      PHP-GD extension is needed for this script
#
#############################################################################

#############################################################################
# PHP customization for includes and warnings
#############################################################################
$os = strtolower(PHP_OS);
if(strpos($os, "win") === false) ini_set('include_path',ini_get('include_path').':include:../include');
else ini_set('include_path',ini_get('include_path').';include;..\include');
error_reporting(E_ERROR | E_WARNING | E_PARSE);

#############################################################################
# Includes
#############################################################################
require_once('AastraCommon.php');
require_once('AastraIPPhoneTextMenu.class.php');
require_once('AastraIPPhoneFormattedTextScreen.class.php');
require_once('AastraIPPhoneGDImage.class.php');
require_once('AastraIPPhoneImageScreen.class.php');
require_once('AastraIPPhoneImageMenu.class.php');
require_once('AastraIPPhoneExecute.class.php');

#############################################################################
# Private functions
#############################################################################
function draw_display($GDImage,$yahtzee,$action)
{
# Depending on action
if($action=='status')
	{
	for ($line=0;$line<5;$line++)
		{
		switch($line)
			{
			case '0':
				for($i=0;$i<6;$i++) 
					{
					if($yahtzee['score'][($i+1)]==(-1)) $filled=False;
					else $filled=True;
					$GDImage->rectangle(12+($i*22),0,12+8+($i*22),8, 1,$filled);
					}
				if($yahtzee['score'][1]==(-1)) $color=1;
				else $color=0;
				$GDImage->line(16,4,16,4,$color);
				if($yahtzee['score'][2]==(-1)) $color=1;
				else $color=0;
				$GDImage->line(36,2,36,2,$color);
				$GDImage->line(40,6,40,6,$color);
				if($yahtzee['score'][3]==(-1)) $color=1;
				else $color=0;
				$GDImage->line(58,6,58,6,$color);
				$GDImage->line(60,4,60,4,$color);
				$GDImage->line(62,2,62,2,$color);
				if($yahtzee['score'][4]==(-1)) $color=1;
				else $color=0;
				$GDImage->line(80,2,80,2,$color);
				$GDImage->line(80,6,80,6,$color);
				$GDImage->line(84,2,84,2,$color);
				$GDImage->line(84,6,84,6,$color);
				if($yahtzee['score'][5]==(-1)) $color=1;
				else $color=0;
				$GDImage->line(102,2,102,2,$color);
				$GDImage->line(102,6,102,6,$color);
				$GDImage->line(104,4,104,4,$color);
				$GDImage->line(106,2,106,2,$color);
				$GDImage->line(106,6,106,6,$color);
				if($yahtzee['score'][6]==(-1)) $color=1;
				else $color=0;
				$GDImage->line(124,2,124,2,$color);
				$GDImage->line(124,4,124,4,$color);
				$GDImage->line(124,6,124,6,$color);
				$GDImage->line(128,2,128,2,$color);
				$GDImage->line(128,4,128,4,$color);
				$GDImage->line(128,6,128,6,$color);
				break;
			case '1':
			case '2':
			case '3':
				$GDImage->rectangle(0,(12+($line-1)*9),61,(21+($line-1)*9), 1);
				$GDImage->rectangle(81,(12+($line-1)*9),143,(21+($line-1)*9), 1);
				break;
			}
		}
	if($yahtzee['score']['3Kind']!=(-1))
		{
		$color=0;
		$GDImage->rectangle(1,13,61,20, 1,True);
		}
	else $color=1;
	$GDImage->drawtext(1, 2, 13, '3 of a Kind', $color);
	if($yahtzee['score']['4Kind']!=(-1))
		{
		$color=0;
		$GDImage->rectangle(1,22,61,29, 1,True);
		}
	else $color=1;
	$GDImage->drawtext(1, 2, 22, '4 of a Kind', $color);
	if($yahtzee['score']['FHouse']!=(-1))
		{
		$color=0;
		$GDImage->rectangle(1,31,61,38, 1,True);
		}
	else $color=1;
	$GDImage->drawtext(1, 2, 31, 'Full House', $color);
	if($yahtzee['score']['SmStraight']!=(-1))
		{
		$color=0;
		$GDImage->rectangle(82,13,142,20, 1,True);
		}
	else $color=1;
	$GDImage->drawtext(1, 83, 13, 'Sm. Straight', $color);
	if($yahtzee['score']['LgStraight']!=(-1))
		{
		$color=0;
		$GDImage->rectangle(82,22,142,29, 1,True);
		}
	else $color=1;
	$GDImage->drawtext(1, 83, 22, 'Lg. Straight', $color);
	if($yahtzee['score']['Chance']!=(-1))
		{
		$color=0;
		$GDImage->rectangle(82,31,142,38, 1,True);
		}
	else $color=1;
	$GDImage->drawtext(1, 83, 31, 'Chance', $color);
	if($yahtzee['score']['Yahtzee']!=(-1))
		{
		$GDImage->rectangle(65,12,77,39, 1,True);
		$GDImage->drawtext(1, 69, 13, 'Y',0);
		$GDImage->drawtext(1, 69, 22, 'Y',0);
		$GDImage->drawtext(1, 69, 31, 'Y',0);
		}
	else
		{
		$GDImage->rectangle(65,12,77,39, 1);
		$GDImage->drawtext(1, 69, 13, 'Y', 1);
		$GDImage->drawtext(1, 69, 22, 'Y', 1);
		$GDImage->drawtext(1, 69, 31, 'Y', 1);
		}
	}
else
	{
	# Draw the dices
	for($i=0;$i<5;$i++)
		{
		if($yahtzee['dice'][$i]['hold']) 
			{
			$color=0;
			$GDImage->rectangle(1+$i*22,0,15+$i*22, 14, 1, True);	
			}
		else 
			{
			$color=1;
			$GDImage->rectangle(1+$i*22,0,15+$i*22, 14, 1);
			}
		if(($yahtzee['dice'][$i]['value']==2) or ($yahtzee['dice'][$i]['value']==4) or ($yahtzee['dice'][$i]['value']==5) or ($yahtzee['dice'][$i]['value']==6)) 
			{
			$GDImage->rectangle(3+$i*22,2,5+$i*22,4,$color,true);	
			$GDImage->rectangle(11+$i*22,10,13+$i*22,12,$color,true);	
			}
		if($yahtzee['dice'][$i]['value']==6) 
			{
			$GDImage->rectangle(3+$i*22,6,5+$i*22,8,$color,true);	
			$GDImage->rectangle(11+$i*22,6,13+$i*22,8,$color,true);	
			}
		if(($yahtzee['dice'][$i]['value']==3) or ($yahtzee['dice'][$i]['value']==4) or ($yahtzee['dice'][$i]['value']==5) or ($yahtzee['dice'][$i]['value']==6)) 
			{
			$GDImage->rectangle(3+$i*22,10,5+$i*22,12,$color,true);
			$GDImage->rectangle(11+$i*22,2,13+$i*22,4,$color,true);	
			}					
		if(($yahtzee['dice'][$i]['value']==1) or ($yahtzee['dice'][$i]['value']==3) or ($yahtzee['dice'][$i]['value']==5)) $GDImage->rectangle(7+$i*22,6,9+$i*22,8,$color,true);
		if($yahtzee['dice'][$i]['hold']) $GDImage->rectangle(1+$i*22,16,15+$i*22, 17, 1, True);	
		}

	# Subtotal
	if($yahtzee['score']['Bonus35']!=(-1)) $GDImage->drawtext(1, 0, 19, 'Bonus 35', 1);
	else
		{
		$one=False;
		$display=0;
		for($i=1;$i<=6;$i++)
			{
			if($yahtzee['score'][$i]!=(-1)) 
				{
				$sub+=($yahtzee['score'][$i]-3*$i);
				$display++;
				}
			}
		if(($display!=0) and ($display!=6))
			{
			if($sub>0) $subT='+'.$sub;
			else $subT=$sub;
			$GDImage->drawtext(1, 0, 19, 'SubT'.' '.$subT, 1);
			}
		}

	$GDImage->drawtext(1, 94, 19, sprintf('Score %04d',$yahtzee['score']['Total']), 1);
	$GDImage->drawtext(1, 69, 26, sprintf('Round %02d',$yahtzee['round']), 1);
	$GDImage->drawtext(1, 114, 26, sprintf('Roll %d',$yahtzee['roll']), 1);

	# Game Over
	if(last_score($yahtzee)=='')
		{
		$GDImage->rectangle(1,27,144,33, 1, True);
		if($yahtzee['score']['Total']>=$yahtzee['hiscore']) $GDImage->drawtext(1, 8, 26, 'GAME OVER - NEW HIGH SCORE', 0);
		else $GDImage->drawtext(1, 50, 26, 'GAME OVER', 0);
		}

	# Display Yahtzee
	if(is_yahtzee($yahtzee))
		{
		$GDImage->rectangle(1,34,144,40, 1, True);	
		if($yahtzee['score']['Yahtzee']==50) $GDImage->drawtext(1, 22, 33, 'YAHTZEE     BONUS 100', 0);
		else $GDImage->drawtext(1, 55, 33, 'YAHTZEE', 0);
		}

	# Commands
	if(($yahtzee['roll']!=0) and ($yahtzee['roll']<=2)) $GDImage->drawtext(1, 109, 1, '1-5 H/R', 1);
	if($yahtzee['roll']<=2) $GDImage->drawtext(1, 109, 8, ' # Roll', 1);
	}
}

function init_context(&$yahtzee)
{
for ($i=0;$i<5;$i++)	
	{
	$yahtzee['dice'][$i]['value']=(-1);
	$yahtzee['dice'][$i]['hold']=false;
	}
$yahtzee['score']['1']=(-1);
$yahtzee['score']['2']=(-1);
$yahtzee['score']['3']=(-1);
$yahtzee['score']['4']=(-1);
$yahtzee['score']['5']=(-1);
$yahtzee['score']['6']=(-1);
$yahtzee['score']['3Kind']=(-1);
$yahtzee['score']['4Kind']=(-1);
$yahtzee['score']['FHouse']=(-1);
$yahtzee['score']['SmStraight']=(-1);
$yahtzee['score']['LgStraight']=(-1);
$yahtzee['score']['Chance']=(-1);
$yahtzee['score']['Yahtzee']=(-1);
$yahtzee['score']['Bonus35']=(-1);
$yahtzee['score']['Bonus100']=(-1);
$yahtzee['score']['Total']=0;
$yahtzee['roll']=0;
$yahtzee['set']=False;
$yahtzee['round']=1;
}

function get_user_context($user)
{
# Get cached context
$yahtzee=Aastra_get_user_context($user,'yahtzee');

# New game
if($yahtzee==NULL)
	{
	init_context($yahtzee);
	$yahtzee['hiscore']=0;
	$yahtzee['games']=0;
	$yahtzee['average']=0;
	}

# Return context
return($yahtzee);
}

function is_yahtzee($yahtzee)
{
$return=False;

# Create an array with the number for each value
$array=array();
for($i=0;$i<5;$i++) if($yahtzee['dice'][$i]['value']!=(-1)) $array[$yahtzee['dice'][$i]['value']]++;

# Test yahtzee
if(array_search('5',$array)) $return=True;

# Return test
return($return);
}

function compute_score($yahtzee,$index)
{
# Compute the sum
$sum=0;
$score=0;
for($i=0;$i<5;$i++) $sum+=$yahtzee['dice'][$i]['value'];

# Create an array with the number for each value
$array=array();
for($i=0;$i<5;$i++) $array[$yahtzee['dice'][$i]['value']]++;

# Create a sorted array
for($i=0;$i<5;$i++) $sorted[$i]=$yahtzee['dice'][$i]['value'];
$sorted=array_unique($sorted);
sort($sorted);

# Process index
switch($index)
	{
	case '1':
	case '2':
	case '3':
	case '4':
	case '5':
	case '6':
		if($yahtzee['score'][$index]==(-1))
			{
			for($j=0;$j<5;$j++)
				{
				if($yahtzee['dice'][$j]['value']==$index) $score+=$index;
				}
			}
		break;

	case '3Kind':
		if($yahtzee['score'][$index]==(-1))
			{
			if(array_search('3',$array) or array_search('4',$array) or array_search('5',$array)) $score=$sum;
			}
		break;

	case '4Kind':
		if($yahtzee['score'][$index]==(-1))
			{
			if(array_search('4',$array) or array_search('5',$array)) $score=$sum;
			}
		break;

	case 'FHouse':
		if($yahtzee['score'][$index]==(-1))
			{
			if((array_search('3',$array) and array_search('2',$array)) or (array_search('5',$array))) $score='25';
			}
		break;

	case 'SmStraight':
		if($yahtzee['score'][$index]==(-1))
			{
			if(!is_yahtzee($yahtzee))
				{
				$score=30;
				if(count($sorted)<4)$score=0;
				else
					{
					if(($sorted[0]!=($sorted[3]-3)) and ($sorted[1]!=($sorted[4]-3))) $score=0;
					}
				}
			else
				{
				if($yahtzee['score'][$yahtzee['dice'][0]['value']]!=(-1)) $score=30;
				else $score=0; 
				}
			}
		break;

	case 'LgStraight':
		if($yahtzee['score'][$index]==(-1))
			{
			if(!is_yahtzee($yahtzee))
				{
				$score=40;
				if(count($sorted)!=5)$score=0;
				else
					{
					if($sorted[0]!=($sorted[4]-4)) $score=0;
					}
				}
			else
				{
				if($yahtzee['score'][$yahtzee['dice'][0]['value']]!=(-1)) $score=40;
				else $score=0;
				}
			}
		break;

	case 'Chance':
		if($yahtzee['score'][$index]==(-1)) $score=$sum;
		break;

	case 'Yahtzee':
		if($yahtzee['score'][$index]==(-1))
			{
			if(array_search('5',$array)) $score='50';
			}
		break;
	}

# Return score
return($score);
}

function last_score($yahtzee)
{
$index='';
$array_index=array('1','2','3','4','5','6','3Kind','4Kind','FHouse','Chance','SmStraight','LgStraight','Yahtzee');
foreach($array_index as $key=>$value)
	{
	if($yahtzee['score'][$array_index[$key]]==(-1))
		{
		$index=$array_index[$key];
		break;
		}
	}
	
# Return index
return($index);
}

#############################################################################
# Body
#############################################################################
# Retrieve parameters
$user=$_GET['user'];
$action=$_GET['action'];
$value=$_GET['value'];
$page=$_GET['page'];
$score=$_GET['score'];
if($page=='') $page=1;

# Trace
Aastra_trace_call('Yahtzee','user='.$user.', action='.$action.', value='.$value);

# Test User Agent
Aastra_test_phone_version('2.2.0.',0);
Aastra_test_phone_model(array('Aastra55i','Aastra57i','Aastra57iCT','Aastra8000i'),True,0);
Aastra_test_php_function('imagecreate','PHP-GD extension not installed.');

# Get Language
$language=Aastra_get_language();

# Retrieve phone information
$header=Aastra_decode_HTTP_header();
if($user=='') $user=$header['mac'];

# Keep return URI
$XML_SERVER.='?user='.$user;

# Number of softkeys
$nb_softkeys=Aastra_number_softkeys_supported($header);

# Get user context
$yahtzee=get_user_context($user);

# Init XML object
switch($action)
	{
	case 'enter':
		$object = new AastraIPPhoneTextMenu();
		break;
	case 'hi':
		$object = new AastraIPPhoneFormattedTextScreen();
		break;
	case 'status':
		$object = new AastraIPPhoneImageScreen();
		$GDImage = new AastraIPPhoneGDImage();
		break;
	default:
		$object = new AastraIPPhoneImageMenu();
		$GDImage = new AastraIPPhoneGDImage();
		break;
	}
$object->setDestroyOnExit();

# Process action
switch($action)
	{
	# Roll the dices
	case 'roll':
		# Score to be entered
		$yahtzee['set']=False;

		# Roll the dices
		for($i=0;$i<5;$i++)
			{
			if(!$yahtzee['dice'][$i]['hold']) $yahtzee['dice'][$i]['value']=rand(1,6);
			}

		# One extra roll
		$yahtzee['roll']++;

		# End of the game
		if(($yahtzee['round']==13) and ($yahtzee['roll']==3))
			{
			$index=last_score($yahtzee);
			$score=compute_score($yahtzee,$index);
			$object2=new AastraIPPhoneExecute();
			$object2->addEntry($XML_SERVER.'&action=set2&value='.$index.'&score='.$score);
			$object2->output();
			exit;
			}
		break;

	# Hold/Release
	case 'hold':
		if($yahtzee['dice'][$value]['hold']) $yahtzee['dice'][$value]['hold']=False;
		else $yahtzee['dice'][$value]['hold']=True;
		break;

	# Set the score
	case 'set':
	case 'set2':
		# Check if bonus100 is achieved by a yahtzee
		if($yahtzee['score']['Yahtzee']==50)
			{
			# Yahtzee
			if(is_yahtzee($yahtzee))
				{
				if($yahtzee['score']['Bonus100']==(-1)) $yahtzee['score']['Bonus100']=100;
				else $yahtzee['score']['Bonus100']+=100;
				$yahtzee['score']['Total']+=100;
				}
			}

		# Add selected score
		$yahtzee['score'][$value]=$score;
		$yahtzee['score']['Total']+=$score;

		# Next roll/round
		$yahtzee['set']=True;
		if($yahtzee['round']!=13) 
			{
			$yahtzee['round']++;
			$yahtzee['roll']=0;
			}
		else $yahtzee['roll']=3;

		# Check if bonus35 is achieved
		if($yahtzee['score']['Bonus35']==(-1))
			{
			# Subtotal
			$sub=0;
			for($i=1;$i<=6;$i++)
				{
				if($yahtzee['score'][$i]!=(-1)) $sub+=$yahtzee['score'][$i];
				}
			if($sub>=63)
				{
				$yahtzee['score']['Bonus35']=35;
				$yahtzee['score']['Total']+=35;
				}
			}

		# Update Hi-Score
		if(last_score($yahtzee)=='')
			{
			if($yahtzee['score']['Total']>$yahtzee['hiscore']) $yahtzee['hiscore']=$yahtzee['score']['Total'];
			$yahtzee['average']=intval(($yahtzee['average']*$yahtzee['games']+$yahtzee['score']['Total'])/($yahtzee['games']+1));
			$yahtzee['games']++;
			}

		# Reset dices
		for ($i=0;$i<5;$i++)	
			{
			if($action=='set') $yahtzee['dice'][$i]['value']=(-1);
			$yahtzee['dice'][$i]['hold']=false;
			}
		break;

	# Input Score
	case 'enter':
		# Array with all indexes
		$array_index=array('1','2','3','4','5','6','3Kind','4Kind','FHouse','SmStraight','LgStraight','Chance','Yahtzee');
		$array_label=array('Ones','Twos','Threes','Fours','Fives','Sixes','Three of a Kind','Four of a Kind','Full House','Small Straight','Large Straight','Chance','Yahtzee');

		# Selection
		$object->setStyle('radio');
		$title=$yahtzee['dice'][0]['value'];
		for($i=1;$i<5;$i++) $title.='-'.$yahtzee['dice'][$i]['value'];
		$object->setTitle($title);
		$index=0;
		foreach($array_index as $key=>$value)
			{
			if($yahtzee['score'][$array_index[$key]]==(-1))
				{
				$score=compute_score($yahtzee,$array_index[$key]);
				$object->addEntry($array_label[$key].' ('.$score.')',$XML_SERVER.'&action=set&value='.$array_index[$key].'&score='.$score);
				if($value=='Yahtzee') $index=($key+1);
				}
			}

		# If first Yahtzee set the default index
		if((is_yahtzee($yahtzee)) and ($index!=0)) $object->setDefaultIndex($index);
		break;

	# New game
	case 'new':
		# Reset everything
		init_context($yahtzee);
		break;

	# Hi-Score
	case 'hi':
		# Display high scores
		$object->addLine('Hall of Fame',NULL,'center');
		if($header['model']!='Aastra55i') $object->addLine('');
		$object->addLine('Hi-Score: '.$yahtzee['hiscore']);
		$object->addLine('Nb of games: '.$yahtzee['games']);
		$object->addLine('Average score: '.$yahtzee['average']);
		break;

	# Reset Hi-Score
	case 'reset':
		# Back to 0
		$yahtzee['hiscore']=0;
		$yahtzee['games']=0;
		$yahtzee['average']=0;
		break;
	}

# Save current data
Aastra_save_user_context($user,'yahtzee',$yahtzee);

# Complete with softkeys
switch($action)
	{
	case 'enter':
		if($nb_softkeys==6)
			{
			$object->addSoftkey('1','Select','SoftKey:Select');
			$object->addSoftkey('5','Cancel',$XML_SERVER);
			$object->addSoftkey('6','Exit','SoftKey:Exit');
			}
		else
			{
			$object->addSoftkey('9','Cancel',$XML_SERVER);
			$object->addSoftkey('10','Exit','SoftKey:Exit');
			}
		break;
	case 'hi':
		if($nb_softkeys==6)
			{
			$object->addSoftkey('1','Reset',$XML_SERVER.'&action=reset');
			$object->addSoftkey('5','Back',$XML_SERVER);
			$object->addSoftkey('6','Exit','SoftKey:Exit');
			}
		else
			{
			$object->addSoftkey('1','Reset',$XML_SERVER.'&action=reset');
			$object->addSoftkey('9','Back',$XML_SERVER);
			$object->addSoftkey('10','Exit','SoftKey:Exit');
			}
		break;
	case 'status':
		draw_display($GDImage,$yahtzee,$action);
		$object->setGDImage($GDImage);
		if($nb_softkeys==6)
			{
			$object->addSoftkey('5','Back',$XML_SERVER);
			$object->addSoftkey('6','Exit','SoftKey:Exit');
			}
		else
			{
			$object->addSoftkey('9','Back',$XML_SERVER);
			$object->addSoftkey('10','Exit','SoftKey:Exit');
			}
		break;
	default:
		draw_display($GDImage,$yahtzee,$action);
		$object->setGDImage($GDImage);
		switch($yahtzee['roll'])
			{
			case '0':
				$object->addURI('#',$XML_SERVER.'&action=roll');
				$object->addSoftkey('1','Roll',$XML_SERVER.'&action=roll');
				break;
	
			case '1':
			case '2':
				for($i=1;$i<6;$i++) $object->addURI($i,$XML_SERVER.'&action=hold&value='.($i-1));
				if(last_score($yahtzee)!='') 
					{
					$object->addSoftkey('1','Roll',$XML_SERVER.'&action=roll');
					$object->addURI('#',$XML_SERVER.'&action=roll');
					}
				else $object->setBeep();
				if(!$yahtzee['set']) $object->addSoftkey('2','Enter',$XML_SERVER.'&action=enter');
				break;

			default:
				for($i=1;$i<6;$i++) $object->addURI($i,$XML_SERVER.'&action=hold&value='.($i-1));
				if(!$yahtzee['set']) $object->addSoftkey('1','Enter',$XML_SERVER.'&action=enter');
				else 
					{
					if(last_score($yahtzee)!='') 
						{
						$object->addSoftkey('1','Roll',$XML_SERVER.'&action=roll');
						$object->addURI('#',$XML_SERVER.'&action=roll');
						}
					else $object->setBeep();
					}
				break;
			}
		if(is_yahtzee($yahtzee)) $object->setBeep();
		$object->addURI('0',$XML_SERVER.'&action=new');
		if(last_score($yahtzee)!='') $object->addSoftkey('3','Status',$XML_SERVER.'&action=status');
		$object->addSoftkey('4','New Game',$XML_SERVER.'&action=new');
		$object->addSoftkey('5','Hi-Score',$XML_SERVER.'&action=hi');
		if($nb_softkeys==6) $object->addSoftkey('6','Exit','SoftKey:Exit');
		else $object->addSoftkey('10','Exit','SoftKey:Exit');
		break;
	}

# Display object
$object->setTimeout(120);
$object->output();
exit;
?> 
