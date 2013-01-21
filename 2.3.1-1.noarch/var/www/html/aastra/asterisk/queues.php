<?php 
#############################################################################
# Aastra ACD Queue Information
#
# Copyright (C) 2008 Ethan Schroeder
# ethan.schroeder@schmoozecom.com
#
# Copyright 2005-2010 Aastra Telecom Ltd
#
# Display information on available queues on the system, including statistics.
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version
# of the License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# Supported Aastra Phones
#       55i, 57i, 57iCT, 9480i, 9480iCT, 480i, 480iCT, 6739i
#
# Usage
# 	queues.php?user=$$SIPUSERNAME$$
#
#############################################################################

#############################################################################
# PHP customization for includes and warnings
#############################################################################
$os = strtolower(PHP_OS);
if(strpos($os, "win") === false) ini_set('include_path',ini_get('include_path').':include:../include');
else ini_set('include_path',ini_get('include_path').';include;../include');
error_reporting(E_ERROR | E_PARSE);

#############################################################################
# Includes
#############################################################################
require_once('AastraAsterisk.php');

#############################################################################
# Private functions
#############################################################################

function get_agent_status($queue,$agent)  
{
global $queue_members;

# Connect to AGI
$asm=new AGI_AsteriskManager();
$asm->connect();

# Add event handlers to retrieve the answer
$asm->add_event_handler('queuestatuscomplete','Aastra_asm_event_queues_Asterisk');
$asm->add_event_handler('queuemember','Aastra_asm_event_agents_Asterisk');

# Retrieve info
while(!$queue_members)  
	{
      	$asm->QueueStatus();
      	$count++;
      	if($count==10) break;
    	}

# Get info for the given queue
$status['Logged']=False;
$status['Paused']=False;
foreach($queue_members as $agent_a)  
	{
	if(($agent_a['Queue']==$queue && $agent_a['Location']=='Local/'.$agent.'@from-queue/n') or ($agent_a['Queue']==$queue && $agent_a['Location']=='Local/'.$agent.'@from-internal/n'))
		{
		$status['Logged']=True;
       	if($agent_a['Paused']=='1') $status['Paused']=True;
		$status['Type']=$agent_a['Membership'];
		$status['CallsTaken']=$agent_a['CallsTaken'];
		$status['LastCall']=$agent_a['LastCall'];
		break;
		} 

	# Get Penalty
	$penalty=$asm->database_get('QPENALTY',$queue.'/agents/'.$agent);
	if($penalty=='') $penalty='0';
	$status['Penalty']=$penalty;
	}

# Disconnect properly
$asm->disconnect();

# Return Status
return($status);
}

function filter_queues($queues,$user)
{
# Retrieve real user
$user=Aastra_get_userdevice_Asterisk($user);

# Read config file
$array_config=Aastra_readINIfile(AASTRA_CONFIG_DIRECTORY.'queues.conf','#','=');
foreach($queues as $key=>$value)
	{
	$allowed=False;
	if(array_key_exists($value['Queue'],$array_config)) 
		{
		if($array_config[$value['Queue']]['allowed']=='ALL') $allowed=True;
		else 
			{
			$temp=explode(',',$array_config[$value['Queue']]['allowed']);
			if(in_array($user,$temp)) $allowed=True;
			}
		}
	else
		{
		if(array_key_exists('ALL',$array_config)) 
			{
			if($array_config['ALL']['allowed']=='ALL') $allowed=True;
			else 
				{
				$temp=explode(',',$array_config['ALL']['allowed']);
				if(in_array($user,$temp)) $allowed=True;
				}
			}
		}
	if(!$allowed) unset($queues[$key]);
	}

# Return updated queues
return($queues);
}

#############################################################################
# Body
#############################################################################
$action=Aastra_getvar_safe('action','show_queues');
$user=Aastra_getvar_safe('user');
$q_desc=Aastra_getvar_safe('q_desc');
$queue=Aastra_getvar_safe('queue');
$member=Aastra_getvar_safe('member');
$page=Aastra_getvar_safe('page','1');

# Trace
Aastra_trace_call('queues_asterisk','action='.$action.', user='.$user.', queue='.$queue.', member='.$member);

# Test User Agent
Aastra_test_phone_versions(array('1'=>'','2'=>'1.4.2','3'=>'','4'=>'2.5.3.','5'=>'3.0.1.'),'0');

# Get Language
$language=Aastra_get_language();

# Global compatibility
$nb_softkeys=Aastra_number_softkeys_supported();
$is_color_ftextscreen=Aastra_is_formattedtextscreen_color_supported();

# Check user mode
if($user=='')
	{
	# Display error
	require_once('AastraIPPhoneTextScreen.class.php');
	$object=new AastraIPPhoneTextScreen();
	$object->setDestroyOnExit();
	$object->setTitle(Aastra_get_label('Configuration Error',$language));
	$object->setText(Aastra_get_label('Missing parameter in the command line, please contact your administrator.',$language));
	if($nb_softkeys==6) $object->addSoftkey('6',Aastra_get_label('Exit',$language),'SoftKey:Exit');
	else $object->addSoftkey('10',Aastra_get_label('Exit',$language),'SoftKey:Exit');
	$object->output();
	exit;
	}
else 
	{
	# Keep callback
	$XML_SERVER.='?user='.$user;
	}

# Local variables
$refresh=1;
$header=Aastra_decode_HTTP_header();

# Process action
switch($action)
	{
	# LOGOUT queue
	case 'logout_queue':
		# Retrieve current members
		$members=Aastra_get_queue_members_Asterisk($queue);

		# Do we have any
		foreach($members as $value)
			{
			if($value['type']=='dynamic') Aastra_queue_remove_Asterisk($value['agent'],$queue);
			}

		# Next action
		require_once('AastraIPPhoneExecute.class.php');
		$object=new AastraIPPhoneExecute();
		$object->addEntry($XML_SERVER.'&action=show_queue_members&queue='.$queue.'&q_desc='.$q_desc);
		break;

	# Logout agent
	case 'logout_agent':
		# Remove agent
		Aastra_queue_remove_Asterisk($member,$queue);

		# Next action
		require_once('AastraIPPhoneExecute.class.php');
		$object=new AastraIPPhoneExecute();
		$object->addEntry($XML_SERVER.'&action=show_queue_members&queue='.$queue.'&q_desc='.$q_desc.'&member='.$member);
		break;

	# Show Queues
	case 'show_queues_page':
		$queue='';
	case 'refresh':
		$refresh=0;
	case 'show_queues':
		# Authenticate user
		Aastra_check_signature_Asterisk($user);

		# Retrieve the list of queues
    		$queues=Aastra_get_queues_Asterisk();
		$initial_count=count($queues);

		# Filter queues
		$queues=filter_queues($queues,$user);

		# Display list of queues or error
		if(sizeof($queues))  
			{
			# Sort by name
			$queues=Aastra_array_multi_sort($queues,'Description');

			# Retrieve the size of the display
		    	$chars_supported=Aastra_size_display_line();
			if(Aastra_is_style_textmenu_supported()) $chars_supported--;
			else $chars_supported-=4;

			# Retrieve last page
			$MaxLines=AASTRA_MAXLINES;
			$last=intval(count($queues)/$MaxLines);
			if((count($queues)-$last*$MaxLines) != 0) $last++;

			# Find current queue
			$default='';
			if($queue!='')
				{
				$index=1;
				$found=False;
				foreach($queues as $value)
					{
					if($value['Queue']==$queue) 
						{
						$found=True;
						break;
						}
					$index++;
					}
				if($found)
					{
					$default=$index%$MaxLines;
					$page=(($index-$default)/$MaxLines)+1;
					}
				}

			# Display list of queues
			require_once('AastraIPPhoneTextMenu.class.php');
	      		$object=new AastraIPPhoneTextMenu();
      			$object->setDestroyOnExit();
      			if(Aastra_is_textmenu_wrapitem_supported()) $object->setWrapList();
      			if($last==1) $object->setTitle(Aastra_get_label('ACD Queues',$language));
			else $object->setTitle(sprintf(Aastra_get_label('ACD Queues (%d/%d)',$language),$page,$last));
	      		if(Aastra_is_style_textmenu_supported()) $object->setStyle('none');
			$index=1;
			foreach($queues as $value)
				{
				if(($index>=(($page-1)*$MaxLines+1)) and ($index<=$page*$MaxLines))
					{
					$description=$value['Description'];
					$number='('.$value['Queue'].')';
					if($nb_softkeys!=10) $description=substr($description,0,$chars_supported-strlen($number)-1);
		       		$queue_display=str_pad($description.$number,$chars_supported,'-',STR_PAD_BOTH);
					if(Aastra_is_textmenu_wrapitem_supported()) $queue_display.=' '.sprintf(Aastra_get_label('InQ:%s;H:%s;A:%s',$language),$value['Calls'],$value['Holdtime'],$value['Abandoned']);
					$object->addEntry($queue_display,$XML_SERVER.'&action=show_queue_members&queue='.$value['Queue'].'&q_desc='.$value['Description'],'&queue='.$value['Queue'].'&q_desc='.$value['Description']);
					}
				$index++;
				}

			# Default position
			if($default!='') $object->setDefaultIndex($default);

			# Softkeys
			if($nb_softkeys==6)
				{
        			$object->addSoftkey('1',Aastra_get_label('Agents',$language),'SoftKey:Select');
        			if($page!=1) $object->addSoftkey('2',Aastra_get_label('Previous',$language),$XML_SERVER.'&action=show_queues_page&page='.($page-1));
        			$object->addSoftkey('3',Aastra_get_label('Callers',$language),$XML_SERVER.'&action=show_queue_entries');
        			if($page!=$last) $object->addSoftkey('5',Aastra_get_label('Next',$language),$XML_SERVER.'&action=show_queues_page&page='.($page+1));
      	 			$object->addSoftkey('6',Aastra_get_label('Exit',$language),'SoftKey:Exit');
				}
			else 
				{
        			$object->addSoftkey('1',Aastra_get_label('Agents',$language),$XML_SERVER.'&action=show_queue_members');
        			$object->addSoftkey('2',Aastra_get_label('Callers',$language),$XML_SERVER.'&action=show_queue_entries');
        			if($page!=1) $object->addSoftkey('3',Aastra_get_label('Previous Page',$language),$XML_SERVER.'&action=show_queues_page&page='.($page-1));
        			if($page!=$last) $object->addSoftkey('8',Aastra_get_label('Next Page',$language),$XML_SERVER.'&action=show_queues_page&page='.($page+1));
				$object->addSoftkey('10',Aastra_get_label('Exit',$language),'SoftKey:Exit');
				}

			# Test the refresh
			if(Aastra_is_Refresh_supported()) 
				{
				$object->setRefresh('10',$XML_SERVER.'&action=refresh&page='.$page);
				$new=$object->generate();
				}
			else 
				{
				$refresh=1;
				if($nb_softkeys==6) $object->addSoftkey(3,Aastra_get_label('Refresh',$language),$XML_SERVER.'&page='.$page);
				else $object->addSoftkey(6,Aastra_get_label('Refresh',$language),$XML_SERVER.'&page='.$page);
				}

			# Test refresh
			if($refresh==0)
				{
				$old=@file_get_contents(AASTRA_PATH_CACHE.$header['mac'].'.queues');
				if($new!=$old) $refresh=1;
				}

			# Save last screen
			$stream=@fopen(AASTRA_PATH_CACHE.$header['mac'].'.queues','w');
			@fwrite($stream,$new);
			@fclose($stream);
    			}
	    	else  
			{
			# No ACD queue configured
			require_once('AastraIPPhoneTextScreen.class.php');
      			$object = new AastraIPPhoneTextScreen();
      			$object->setDestroyOnExit();
	      		$object->setTitle(Aastra_get_label('No ACD Queue',$language));
			
			# Error message
			if($initial_count==0) $object->setText(Aastra_get_label('There are no ACD queue configured on the system. Please contact your administrator.',$language));
			else $object->setText(Aastra_get_label('You are not allowed to access any ACD queue configured on the system. Please contact your administrator.',$language));


			# Softkeys
			if($nb_softkeys==6) $object->addSoftKey('6',Aastra_get_label('Exit',$language),'SoftKey:Exit');
			else $object->addSoftKey('10',Aastra_get_label('Exit',$language),'SoftKey:Exit');
    			}
  		break;

	# Show queue members
	case 'show_queue_members':
		# Erase refresh
		@unlink(AASTRA_PATH_CACHE.$header['mac'].'.queues');

		# Retrieve current members
		$members=Aastra_get_queue_members_Asterisk($queue);

		# Do we have any
		if(sizeof($members))
			{
			# Display the list
			require_once('AastraIPPhoneTextMenu.class.php');
			$object = new AastraIPPhoneTextMenu();
      			$object->setDestroyOnExit();
      			$object->setTitle(sprintf('%s (%s)',$q_desc,$queue));
	      		if(Aastra_is_style_textmenu_supported()) $object->setStyle('none');
			$index=1;
			$logout=False;
			foreach($members as $value)
				{
				if($index<=AASTRA_MAXLINES)
					{
					if($value['type']=='dynamic') $logout=True;
					if(Aastra_is_icons_supported())
						{
						if($value['paused']) 
							{
							if($value['type']=='dynamic') $icon='2';
							else $icon='4';
							}
						else
							{
							if($value['type']=='dynamic') $icon='1';
							else $icon='3';
							}
						$object->addEntry(sprintf('%s (%s)',Aastra_get_callerid_Asterisk($value['agent']),$value['agent']),$XML_SERVER.'&action=agent_detail&queue='.$queue.'&q_desc='.$q_desc.'&member='.$value['agent'],'',$icon);
						}
					else
						{
						$display='X';
						if($value['paused']) $display='/';
						$object->addEntry(sprintf('%s %s (%s)',$display,Aastra_get_callerid_Asterisk($value['agent']),$value['agent']),$XML_SERVER.'&action=agent_detail&queue='.$queue.'&q_desc='.$q_desc.'&member='.$value['agent'],'',$icon);
						}
					if($value['agent']==$member) $object->setDefaultIndex($index);
					}
				$index++;
				}

			# Softkeys
			if($nb_softkeys==6)
				{
        			$object->addSoftkey('1',Aastra_get_label('Select',$language),'SoftKey:Select');
        			if($logout) $object->addSoftkey('3',Aastra_get_label('LOGOUT',$language),$XML_SERVER.'&action=logout_queue&queue='.$queue.'&q_desc='.$q_desc);
				$object->addSoftKey('4',Aastra_get_label('Refresh',$language),$XML_SERVER.'&action=show_queue_members&queue='.$queue.'&q_desc='.$q_desc);
				$object->addSoftKey('5',Aastra_get_label('Back',$language),$XML_SERVER.'&action=show_queues&queue='.$queue);
      				$object->addSoftkey('6',Aastra_get_label('Exit',$language),'SoftKey:Exit');
				}
			else
				{
        			if($logout) $object->addSoftkey('1',Aastra_get_label('LOGOUT',$language),$XML_SERVER.'&action=logout_queue&queue='.$queue.'&q_desc='.$q_desc);
				$object->addSoftKey('6',Aastra_get_label('Refresh',$language),$XML_SERVER.'&action=show_queue_members&queue='.$queue.'&q_desc='.$q_desc);
				$object->addSoftKey('9',Aastra_get_label('Back',$language),$XML_SERVER.'&action=show_queues&queue='.$queue);
      				$object->addSoftkey('10',Aastra_get_label('Exit',$language),'SoftKey:Exit');
				$object->setCancelAction($XML_SERVER.'&action=show_queues&queue='.$queue);
				}

			# Icons
			if(Aastra_is_icons_supported())
				{
				if(Aastra_phone_type()!=5)
					{
					$object->addIcon('1','0000FEC6AA92AAC6FE000000'); //Logged on Dynamic
					$object->addIcon('2','0000FE868A92A2C2FE000000'); //Paused Dynamic
					$object->addIcon('3','0000FEFEFEFEFEFEFE000000'); //Logged on Static
					$object->addIcon('4','0000FE868E9EBEFEFE000000'); //Paused Static
					}
				else
					{
					$object->addIcon(1,'Icon:CheckBoxCheck');
					$object->addIcon(2,'Icon:CircleYellow');
					$object->addIcon(3,'Icon:CircleGreen');
					$object->addIcon(4,'Icon:CircleBlue');
					}
				}
      			}
		else
			{
			# Display error message
			require_once('AastraIPPhoneTextScreen.class.php');
      			$object = new AastraIPPhoneTextScreen();
      			$object->setDestroyOnExit();
	      		$object->setTitle(sprintf('%s (%s)',$q_desc,$queue));
	      		$object->setText(Aastra_get_label('There are no connected agent for this queue.',$language));

			# Softkeys
			if($nb_softkeys==6)
				{
				$object->addSoftKey('4',Aastra_get_label('Refresh',$language),$XML_SERVER.'&action=show_queue_members&queue='.$queue.'&q_desc='.$q_desc);
				$object->addSoftKey('5',Aastra_get_label('Back',$language),$XML_SERVER.'&action=show_queues&queue='.$queue);
				$object->addSoftKey('6',Aastra_get_label('Exit',$language),'SoftKey:Exit');
				}
			else
				{
				$object->addSoftKey('6',Aastra_get_label('Refresh',$language),$XML_SERVER.'&action=show_queue_members&queue='.$queue.'&q_desc='.$q_desc);
				$object->addSoftKey('9',Aastra_get_label('Back',$language),$XML_SERVER.'&action=show_queues&queue='.$queue);
				$object->addSoftKey('10',Aastra_get_label('Exit',$language),'SoftKey:Exit');
				$object->setCancelAction($XML_SERVER.'&action=show_queues&queue='.$queue);
				}
			}
		break;

	# Show queue callers
	case 'show_queue_entries':
		# Erase refresh
		@unlink(AASTRA_PATH_CACHE.$header['mac'].'.queues');

		# Retrieve current members
		$entries=Aastra_get_queue_entries_Asterisk($queue);

		# Sort by position
		$entries=Aastra_array_multi_sort($entries,'Position');

		# Do we have any
		if(sizeof($entries))
			{
			# Display the list
			require_once('AastraIPPhoneTextMenu.class.php');
			$object = new AastraIPPhoneTextMenu();
      			$object->setDestroyOnExit();
      			$object->setTitle(sprintf('%s (%s)',$q_desc,$queue));
	      		if(Aastra_is_style_textmenu_supported()) $object->setStyle('none');
			$index=1;
			foreach($entries as $value)
				{
				if($index<=AASTRA_MAXLINES) $object->addEntry(sprintf('%d. %s (%s)',$value['Position'],$value['CallerIDName'],$value['CallerIDNum']),$XML_SERVER.'&action=redirect&queue='.$queue.'&q_desc='.$q_desc.'&member='.$value['Channel'],'&member='.$value['Channel']);
				$index++;
				}

			# Softkeys
			if($nb_softkeys==6)
				{
        			$object->addSoftkey('1',Aastra_get_label('Select',$language),'SoftKey:Select');
				$object->addSoftKey('4',Aastra_get_label('Refresh',$language),$XML_SERVER.'&action=show_queue_entries&queue='.$queue.'&q_desc='.$q_desc);
				$object->addSoftKey('5',Aastra_get_label('Back',$language),$XML_SERVER.'&action=show_queues&queue='.$queue);
      				$object->addSoftkey('6',Aastra_get_label('Exit',$language),'SoftKey:Exit');
				}
			else
				{
				$object->addSoftKey('1',Aastra_get_label('Pickup',$language),$XML_SERVER.'&action=redirect&queue='.$queue.'&q_desc='.$q_desc);
				$object->addSoftKey('6',Aastra_get_label('Refresh',$language),$XML_SERVER.'&action=show_queue_entries&queue='.$queue.'&q_desc='.$q_desc);
				$object->addSoftKey('9',Aastra_get_label('Back',$language),$XML_SERVER.'&action=show_queues&queue='.$queue);
      				$object->addSoftkey('10',Aastra_get_label('Exit',$language),'SoftKey:Exit');
				$object->setCancelAction($XML_SERVER.'&action=show_queues&queue='.$queue);
				}
      			}
		else
			{
			# Display error message
			require_once('AastraIPPhoneTextScreen.class.php');
      			$object = new AastraIPPhoneTextScreen();
      			$object->setDestroyOnExit();
	      		$object->setTitle(sprintf('%s (%s)',$q_desc,$queue));
	      		$object->setText(Aastra_get_label('There are no caller waiting in this queue.',$language));

			# Softkeys
			if($nb_softkeys==6)
				{
				$object->addSoftKey('4',Aastra_get_label('Refresh',$language),$XML_SERVER.'&action=show_queue_entries&queue='.$queue.'&q_desc='.$q_desc);
				$object->addSoftKey('5',Aastra_get_label('Back',$language),$XML_SERVER.'&action=show_queues&queue='.$queue);
				$object->addSoftKey('6',Aastra_get_label('Exit',$language),'SoftKey:Exit');
				}
			else
				{
				$object->addSoftKey('6',Aastra_get_label('Refresh',$language),$XML_SERVER.'&action=show_queue_entries&queue='.$queue.'&q_desc='.$q_desc);
				$object->addSoftKey('9',Aastra_get_label('Back',$language),$XML_SERVER.'&action=show_queues&queue='.$queue);
				$object->addSoftKey('10',Aastra_get_label('Exit',$language),'SoftKey:Exit');
				$object->setCancelAction($XML_SERVER.'&action=show_queues&queue='.$queue);
				}
			}
		break;

	# Show agent detail
	case 'agent_detail':
		# Collect data
		$detail=get_agent_status($queue,$member);

		# Depending on the phone
		if(Aastra_is_formattedtextscreen_supported())
			{
			# Display Agent status
			require_once('AastraIPPhoneFormattedTextScreen.class.php');
			$object = new AastraIPPhoneFormattedTextScreen();
			$object->setDestroyOnExit();
			if($is_color_ftextscreen) 
				{
				if ($detail['Paused'])$color='yellow';
				else $color='green';
				}
			if($is_color_ftextscreen) $object->addLine(sprintf('%s (%s)',Aastra_get_callerid_Asterisk($member),$queue),'double','center',$color);
	      		else $object->addLine(sprintf('%s (%s)',Aastra_get_callerid_Asterisk($member),$queue),NULL,'center');
			if($is_color_ftextscreen) $object->setScrollStart();
			else $object->setScrollStart(Aastra_size_formattedtextscreen()-1);
			if ($detail['Paused'])$text=Aastra_get_label('Status: Paused',$language);
			else $text=Aastra_get_label('Status: Logged on',$language);
			if($is_color_ftextscreen) $object->addLine($text,NULL,NULL,$color);
			else $object->addLine($text);
			$array_label=array(	'static'=>Aastra_get_label('Static',$language),
						'dynamic'=>Aastra_get_label('Dynamic',$language)
						);
			$text=sprintf(Aastra_get_label('Type: %s',$language),$array_label[$detail['Type']]);
			$object->addLine($text);
			$object->addLine(sprintf('%s: %d',Aastra_get_label('Penalty',$language),$detail['Penalty']));
			$text=sprintf(Aastra_get_label('Call(s) Taken: %s',$language),$detail['CallsTaken']);
			$object->addLine($text);
			if($detail['LastCall']!='0') 
				{
				$array_day=array(	'0'=>Aastra_get_label('Sun',$language),
							'1'=>Aastra_get_label('Mon',$language),
							'2'=>Aastra_get_label('Tue',$language),
							'3'=>Aastra_get_label('Wed',$language),
							'4'=>Aastra_get_label('Thu',$language),
							'5'=>Aastra_get_label('Fri',$language),
							'6'=>Aastra_get_label('Sat',$language)
						   );
				$day=$array_day[strftime('%w',$detail['LastCall'])];
				if($AA_FORMAT_DT=='US')
					{
					$format_date='%m/%d/%y';
					$format_time='%I:%M %p';
					}
				else
					{
					$format_date='%d/%m/%y';
					$format_time='%H:%M';
					}
				$text=sprintf(Aastra_get_label('Last Call: %s %s',$language),$day,strftime($format_date,$detail['LastCall']));
				$object->addLine($text);
				$text=sprintf(Aastra_get_label('At %s',$language),strftime($format_time,$detail['LastCall']));
				$object->addLine($text);
				}
			else 
				{
				if($is_color_ftextscreen) $object->addLine(Aastra_get_label('No Last Call',$language),NULL,NULL,'red');
				else $object->addLine(Aastra_get_label('No Last Call',$language));
				}
			$object->setScrollEnd();
			}
		else
			{
			# Display Agent status
			require_once('AastraIPPhoneTextScreen.class.php');
			$object = new AastraIPPhoneTextScreen();
			$object->setDestroyOnExit();
	      		$object->setTitle(sprintf('%s (%s)',Aastra_get_callerid_Asterisk($member),$queue));
			if ($detail['Paused'])$text=Aastra_get_label('Status: Paused.',$language);
			else $text=Aastra_get_label('Status: Logged on.',$language);
			$array_label=array(	'static'=>Aastra_get_label('Static',$language),
						'dynamic'=>Aastra_get_label('Dynamic',$language)
						);
			$text.=' '.sprintf(Aastra_get_label('Type: %s.',$language),$array_label[$detail['Type']]);
			$text.=' '.sprintf('%s: %d',Aastra_get_label('Penalty',$language),$detail['Penalty']);
			$text.=' '.sprintf(Aastra_get_label('Call(s) Taken: %s.',$language),$detail['CallsTaken']);
			if($detail['LastCall']!='0') 
				{
				$array_day=array(	'0'=>Aastra_get_label('Sun',$language),
							'1'=>Aastra_get_label('Mon',$language),
							'2'=>Aastra_get_label('Tue',$language),
							'3'=>Aastra_get_label('Wed',$language),
							'4'=>Aastra_get_label('Thu',$language),
							'5'=>Aastra_get_label('Fri',$language),
							'6'=>Aastra_get_label('Sat',$language)
						   );
				$day=$array_day[strftime('%w',$detail['LastCall'])];
				if($AA_FORMAT_DT=='US')
					{
					$format_date='%m/%d/%y';
					$format_time='%I:%M %p';
					}
				else
					{
					$format_date='%d/%m/%y';
					$format_time='%H:%M';
					}
				$format=sprintf(Aastra_get_label('%s at %s',$language),$format_date,$format_time);
				$text.=' '.sprintf(Aastra_get_label('Last Call: %s %s.',$language),$day,strftime($format,$detail['LastCall']));
				}
			else $text.=' '.Aastra_get_label('No Last Call.',$language);
	     		$object->setText($text);
			}

		# Softkeys
		if($nb_softkeys==6)
			{
      			if($detail['Type']=='dynamic') $object->addSoftkey('3',Aastra_get_label('Logout',$language),$XML_SERVER.'&action=logout_agent&queue='.$queue.'&q_desc='.$q_desc.'&member='.$member);
			$object->addSoftKey('4',Aastra_get_label('Refresh',$language),$XML_SERVER.'&action=agent_detail&queue='.$queue.'&q_desc='.$q_desc.'&member='.$member);
			$object->addSoftKey('5',Aastra_get_label('Back',$language),$XML_SERVER.'&action=show_queue_members&queue='.$queue.'&q_desc='.$q_desc.'&member='.$member);
			$object->addSoftKey('6',Aastra_get_label('Exit',$language),'SoftKey:Exit');
			}
		else
			{
      			if($detail['Type']=='dynamic') $object->addSoftkey('1',Aastra_get_label('Logout',$language),$XML_SERVER.'&action=logout_agent&queue='.$queue.'&q_desc='.$q_desc.'&member='.$member);
			$object->addSoftKey('6',Aastra_get_label('Refresh',$language),$XML_SERVER.'&action=agent_detail&queue='.$queue.'&q_desc='.$q_desc.'&member='.$member);
			$object->addSoftKey('9',Aastra_get_label('Back',$language),$XML_SERVER.'&action=show_queue_members&queue='.$queue.'&q_desc='.$q_desc.'&member='.$member);
			$object->addSoftKey('10',Aastra_get_label('Exit',$language),'SoftKey:Exit');
			$object->setCancelAction($XML_SERVER.'&action=show_queue_members&queue='.$queue.'&q_desc='.$q_desc.'&member='.$member);
			}
		break;

	# Redirect
	case 'redirect':
		# Redirect the call
		if(Aastra_redirect_Asterisk($member,Aastra_get_userdevice_Asterisk($user),'1','default'))
			{
			# Remove display
			require_once('AastraIPPhoneExecute.class.php');
			$object=new AastraIPPhoneExecute();
			$object->setTriggerDestroyOnExit('');
			$object->addEntry('');
			}
		else
			{
			# Error message
			require_once('AastraIPPhoneTextScreen.class.php');
      			$object = new AastraIPPhoneTextScreen();
      			$object->setDestroyOnExit();
	      		$object->setTitle(Aastra_get_label('Pickup failed',$language));
	      		$object->setText(Aastra_get_label('Failed to intercept the caller in the queue.',$language));

			# Softkeys
			if($nb_softkeys==6) $object->addSoftKey('6',Aastra_get_label('Close',$language),$XML_SERVER.'&action=show_queues&queue='.$queue);
			else 
				{
				$object->addSoftKey('10',Aastra_get_label('Close',$language),$XML_SERVER.'&action=show_queues&queue='.$queue);
				$object->setCancelAction($XML_SERVER.'&action=show_queues&queue='.$queue);
				}
			}
		break;
	}

# Display XML object
if($refresh==1) $object->output();
else
	{
	# Do nothing
	require_once('AastraIPPhoneExecute.class.php');
	$object2=new AastraIPPhoneExecute();
	$object2->addEntry('');
	$object2->output();
	}
exit;
?>
