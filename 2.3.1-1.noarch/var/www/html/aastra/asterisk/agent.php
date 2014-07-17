<?php 
#############################################################################
# Aastra ACD agent management
#
# Copyright (C) 2008 Ethan Schroeder
# ethan.schroeder@schmoozecom.com
# Copyright (C) 2009 Aastra Telecom
#
# Display available queues on the system, including statistics.  Allows users
# to choose a queue, then login or logoff the queue
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
#      All phones
#
# Key configuration:
# 	agent.php?agent=XXXX
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
require_once('AastraAsterisk.php');

#############################################################################
# Private functions
#############################################################################
function get_queues($agent='')
{
global $queue_details;
$queue_details='';

# Connect to AGI
$asm=new AGI_AsteriskManager();
$asm->connect();

# Add event handler to retrieve results of the query
$asm->add_event_handler('queueparams','asm_event_queues');
$asm->add_event_handler('queuestatuscomplete','asm_event_queues');

# Get all the queues
while(!$queue_details) $asm->QueueStatus();

# Open freePBX database
$db=Aastra_connect_freePBX_db_Asterisk();

# Process the list of queues
$index=0;
for($i=0;$i<sizeof($queue_details);$i++)  
	{
	# Valid queue
	if(isset($queue_details[$i]['Queue']))
		{
	      	if(($queue_details[$i]['Queue']!='') && ($queue_details[$i]['Queue']!='default'))
			{
			# Collect information
			$queues[$index]['Queue']=$queue_details[$i]['Queue'];
			$queues[$index]['Calls']=$queue_details[$i]['Calls'];
			$queues[$index]['Holdtime']=$queue_details[$i]['Holdtime'];
			$queues[$index]['Completed']=$queue_details[$i]['Completed'];
			$queues[$index]['Abandoned']=$queue_details[$i]['Abandoned'];

			# Get description and password
		       $sql='SELECT descr,password FROM queues_config WHERE extension='.$queue_details[$i]['Queue'];
      			$result=$db->query($sql);
	      		list($queues[$index]['Description'],$queues[$index]['Password'])= $result->fetchRow(DB_FETCHMODE_ARRAY);

			# Next valid queue
			$index++;
			}
      		}
	}

# Dynamic member restricted?
foreach($queues as $index=>$value)
	{
	$queues[$index]['Restricted']=False;
	$queues[$index]['Allowed']=True;
	$dynmemberonly=$asm->database_get('QPENALTY',$queues[$index]['Queue'].'/dynmemberonly');
	if($dynmemberonly=='yes') 
		{
		$queues[$index]['Restricted']=True;
		if($agent!='')
			{
			if($asm->database_get('QPENALTY',$queues[$index]['Queue'].'/agents/'.$agent)=='') $queues[$index]['Allowed']=False;
			}
		}
	}

# Disconnect properly
$asm->disconnect();

# Sort by name
$queues=Aastra_array_multi_sort($queues,'Description');

# Return
return($queues);
}

function get_agent_status($queue,$agent)  
{
global $queue_members;
$queue_members='';

# Transcode for device and user mode
$agent=Aastra_get_userdevice_Asterisk($agent);

# Connect to AGI
$asm=new AGI_AsteriskManager();
$asm->connect();

# Add event handlers to retrieve the answer
$asm->add_event_handler('queuestatuscomplete','asm_event_queues');
$asm->add_event_handler('queuemember','asm_event_agents');

# Retrieve info
$count=0;
while(!$queue_members)  
	{
      	$asm->QueueStatus();
      	$count++;
      	if($count==10) break;
    	}

# Process info
if($queue!='')
	{
	# Get info for the given queue
	$status['Logged']=False;
	$status['Paused']=False;
	foreach($queue_members as $agent_a)  
		{
		if(($agent_a['Queue']==$queue && $agent_a['Location']=='Local/'.$agent.'@from-queue/n') or ($agent_a['Queue']==$queue && $agent_a['Location']=='Local/'.$agent.'@from-internal/n'))

			{
			$status['Logged']=True;
	       	if($agent_a['Paused']=='1') $status['Paused']=True;
			$status['CallsTaken']=$agent_a['CallsTaken'];
			$status['LastCall']=$agent_a['LastCall'];
			break;
      			} 
    		}
	
	# Get agent type
	$db=Aastra_connect_freePBX_db_Asterisk();
 	$sql="SELECT id FROM queues_details WHERE data LIKE 'Local/".$agent."@%' AND id=".$queue;
	$result=$db->query($sql);
	if(sizeof($result->fetchRow(DB_FETCHMODE_ARRAY))>0) $status['Type']='static';
	else $status['Type']='dynamic';

	# Get Penalty
	$penalty=$asm->database_get('QPENALTY',$queue.'/agents/'.$agent);
	if($penalty=='') $penalty='0';
	$status['Penalty']=$penalty;
	}
else
	{
	# Get info for all the queues for the agent
	foreach($queue_members as $agent_a)  
		{
		# Dynamic agents are using from-queue context, static agents are using from-internal
		if(($agent_a['Location']=='Local/'.$agent.'@from-queue/n') or ($agent_a['Location']=='Local/'.$agent.'@from-internal/n'))
			{
			$status[$agent_a['Queue']]['Status']=1;
	       	if($agent_a['Paused']=='1') $status[$agent_a['Queue']]['Status']=2;
			$status[$agent_a['Queue']]['Membership']=$agent_a['Membership'];
      			} 
    		}
	}

# Disconnect properly
$asm->disconnect();

# Return Status
return($status);
}

function is_agent_logged($agent)
{
global $queue_members;
$queue_members='';

# Transcode for device and user mode
$agent=Aastra_get_userdevice_Asterisk($agent);

# Not logged by default
$return=False;

# Connect to AGI
$asm=new AGI_AsteriskManager();
$asm->connect();

# Add event handlers to retrieve the answer
$asm->add_event_handler('queuestatuscomplete','asm_event_queues');
$asm->add_event_handler('queuemember','asm_event_agents');

# Retrieve info
while(!$queue_members)  
	{
      	$asm->QueueStatus();
      	$count++;
      	if($count==10) break;
    	}

# Get info for all the queues
foreach($queue_members as $agent_a)  
	{
	if(($agent_a['Location']=='Local/'.$agent.'@from-queue/n') or (($agent_a['Location']=='Local/'.$agent.'@from-internal/n')))  
		{
		$return=True;
		break;
		} 
	}

# Disconnect properly
$asm->disconnect();

# Return Status
return($return);
}

function asm_event_queues($e, $parameters, $server, $port)  
{
global $queue_details;

if(sizeof($parameters))  
	{
      	if($parameters['Event']='QueueParams') $queue_details[]=$parameters;
    	}
}

function asm_event_agents($e, $parameters, $server, $port)  
{
global $queue_members;

if(sizeof($parameters))  
	{
      	if($parameters['Event']='QueueMember') $queue_members[]=$parameters;
    	}
}

#############################################################################
# Body
#############################################################################
$action=Aastra_getvar_safe('action','show_queues');
$agent=Aastra_getvar_safe('agent');
$password=Aastra_getvar_safe('password');
$q_pass=Aastra_getvar_safe('q_pass');
$q_desc=Aastra_getvar_safe('q_desc');
$queue=Aastra_getvar_safe('queue');
$page=Aastra_getvar_safe('page','1');
$menu_set=Aastra_getvar_safe('menu_set','1');

# Trace
Aastra_trace_call('agent_asterisk','agent='.$agent.', action='.$action.', queue='.$queue.', page='.$page);

# Test User Agent
Aastra_test_phone_versions(array('1'=>'1.4.2.','2'=>'1.4.2.','3'=>'2.5.3.','4'=>'2.5.3.','5'=>'3.0.1.'),'0');

# Get Language
$language=Aastra_get_language();

# Keep callback
$XML_SERVER.='?agent='.$agent;

# Local variables
$refresh=1;
$header=Aastra_decode_HTTP_header();

# Compatibility
$is_icons=Aastra_is_icons_supported();
$nb_softkeys=Aastra_number_softkeys_supported();
if($nb_softkeys) $MaxLines=AASTRA_MAXLINES;
else $MaxLines=AASTRA_MAXLINES-2;

# Process action
switch($action)
	{
	# Menu for non-softkey phones
	case 'menu':
		# TextMenu for the actions
		require_once('AastraIPPhoneTextMenu.class.php');
      		$object=new AastraIPPhoneTextMenu();
		$object->setDestroyOnExit();
		$object->setTitle(sprintf('%s (%s)',$q_desc,$queue));
		if(Aastra_is_style_textmenu_supported()) $object->setStyle('none');

		# Get agent status for this queue
		$status=get_agent_status($queue,$agent);

		# Agent logged?
		if($status['Logged'])  
			{
			# Dynamic agent?
			if($status['Type']=='dynamic') $object->addEntry(Aastra_get_label('Logoff',$language),$XML_SERVER.'&action=log&queue='.$queue.'&q_pass='.$q_pass.'&q_desc='.$q_desc);

			# Paused?
			if($status['Paused']) $object->addEntry(Aastra_get_label('Unpause',$language),$XML_SERVER.'&action=pause&queue='.$queue.'&q_pass='.$q_pass.'&q_desc='.$q_desc);
			else $object->addEntry(Aastra_get_label('Pause',$language),$XML_SERVER.'&action=pause&queue='.$queue.'&q_pass='.$q_pass.'&q_desc='.$q_desc);
			}
		else
			{
			# Dynamic agent?
			if($status['Type']=='dynamic') $object->addEntry(Aastra_get_label('Logon',$language),$XML_SERVER.'&action=log&queue='.$queue.'&q_pass='.$q_pass.'&q_desc='.$q_desc);
			}

		# Cancel action
		$object->setCancelAction($XML_SERVER.'&action=show_queues&queue='.$queue);
		break;

	# Login/Logout
	case 'log':
		# No change by default
		$update=False;

		# Get agent status for this queue
		$status=get_agent_status($queue,$agent);

		# Agent logged?
		if($status['Logged'])  
			{
			# Dynamic agent?
			if($status['Type']=='dynamic')
				{
				# Remove agent from selected queue
				Aastra_queue_remove_Asterisk($agent,$queue);

				# Update needed
				$update=True;
				}
			}
		else
			{
			# Queue has a password
		    	if($q_pass)  
				{
				# User input
				require_once('AastraIPPhoneInputScreen.class.php');
	           		$object=new AastraIPPhoneInputScreen();
     		 		$object->setDestroyOnExit();
      				$object->setTitle(sprintf(Aastra_get_label('Logon to %s (%s)',$language),$q_desc,$queue));
      				$object->setPrompt(Aastra_get_label('Enter Password',$language));
	      			$object->setParameter('password');
		     		$object->setType('number');
     				$object->setURL($XML_SERVER.'&action=authenticate&queue='.$queue.'&q_pass='.$q_pass.'&q_desc='.$q_desc);
     				$object->setPassword();

				# Softkeys
				if($nb_softkeys)
					{
					if($nb_softkeys==6)
						{
						$object->addSoftkey('1',Aastra_get_label('Backspace',$language),'SoftKey:BackSpace');
						$object->addSoftkey('4',Aastra_get_label('Cancel',$language),$XML_SERVER.'&action=show_queues&queue='.$queue);
						$object->addSoftkey('5',Aastra_get_label('Submit',$language),'SoftKey:Submit');
						$object->addSoftkey('6',Aastra_get_label('Exit',$language),'SoftKey:Exit');
						}
					else
						{
						$object->addSoftkey('9',Aastra_get_label('Cancel',$language),$XML_SERVER.'&action=show_queues&queue='.$queue);
						$object->addSoftkey('10',Aastra_get_label('Exit',$language),'SoftKey:Exit');
						}
					}
				$object->setCancelAction($XML_SERVER.'&action=show_queues&queue='.$queue);
    				}
	    		else  
				{
				# Add agent to selected queue
				Aastra_queue_add_Asterisk($agent,$queue,$status['Penalty']);
				$update=True;
				}
			}

		# Update needed
		if(!$object)
			{
			# Prepare display callback
			require_once('AastraIPPhoneExecute.class.php');
	      		$object=new AastraIPPhoneExecute();


			# Update needed
			if($update)
				{
				# Update via the "check" mechanism
				$object->addEntry($XML_SERVER.'&action=check');

				# Next display
      				if($update) $object->addEntry($XML_SERVER.'&action=show_queues&queue='.$queue);
				}
			else
				{
				# Do Nothing
				$object->addEntry('');
				}
    			}
		break;

	# Logoff ALL
	case 'logoff_all':
		# Retrieve the list of queues
		$queues=get_queues();
		$agents=get_agent_status('',$agent);

		# Remove from each queue if logged in as dynamic
		foreach($queues as $value)
			{
			if(($agents[$value['Queue']]['Status']>0) and ($agents[$value['Queue']]['Membership']!='static')) Aastra_queue_remove_Asterisk($agent,$value['Queue']);
			}

		# Next Actions
		require_once('AastraIPPhoneExecute.class.php');
		$object=new AastraIPPhoneExecute();

		# Update via the "check" mechanism
		$object->addEntry($XML_SERVER.'&action=check');

		# Callback for the display
      		$object->addEntry($XML_SERVER.'&action=show_queues&queue='.$queue);
		break;

	# Pause/Unpause ALL
	case 'pause_all':
	case 'unpause_all':
		# Pause or unpause
		if($action=='pause_all') Aastra_queue_pause_Asterisk($agent,'','true');
		else Aastra_queue_pause_Asterisk($agent,'','false');

		# Callback for the display
		require_once('AastraIPPhoneExecute.class.php');
		$object=new AastraIPPhoneExecute();
      		$object->addEntry($XML_SERVER.'&action=show_queues&queue='.$queue);
		break;

	# Authenticate
	case 'authenticate':
		# Password OK
    		if($password==$q_pass)  
			{
			# Get agent status for this queue
			$status=get_agent_status($queue,$agent);

			# Add agent to the queue
			Aastra_queue_add_Asterisk($agent,$queue,$status['Penalty']);

			# Next actions
			require_once('AastraIPPhoneExecute.class.php');
      			$object = new AastraIPPhoneExecute();

			# Update via the "check" mechanism
			$object->addEntry($XML_SERVER.'&action=check');

			# Send a SIP Notification if mode is device and user
			if($AA_FREEPBX_MODE=='2') Aastra_propagate_changes_Asterisk($agent,Aastra_get_userdevice_Asterisk($agent),array('agent'));
	
			# Callback for the display
      			$object->addEntry($XML_SERVER.'&action=show_queues&queue'.$queue);
			}
	    	else  	
			{
			# Input password again
			require_once('AastraIPPhoneExecute.class.php');
      			$object=new AastraIPPhoneExecute();
	      		$object->addEntry($XML_SERVER.'&action=log&queue='.$queue.'&q_pass='.$q_pass.'&q_desc='.$q_desc);
    			}
		break;

	# Show Queues
	case 'show_queues_page':
		$queue='';
	case 'refresh':
		$refresh=0;
	case 'show_queues':
		# Retrieve the list of queues
    		$queues=get_queues($agent);
		$agents=get_agent_status('',$agent);

		# Display list of queues or error
		if(sizeof($queues))  
			{
			# Check if at least one queue allowed
			foreach($queues as $key=>$value)
				{
				if(!$value['Allowed']) unset($queues[$key]);
				}

			# At least one queue allowed for the user
			if(sizeof($queues))
				{
				# Retrieve the size of the display
			    	$chars_supported=Aastra_size_display_line();
				if(Aastra_is_style_textmenu_supported()) $chars_supported--;
				else $chars_supported-=4;
				if($is_icons) $chars_supported-=2;
				else $chars_supported-=1;

				# Retrieve last page
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
						if(!$nb_softkeys and ($page!=1)) $default++;
						}
					}

				# TextMenu for the list
				require_once('AastraIPPhoneTextMenu.class.php');
	      			$object=new AastraIPPhoneTextMenu();
	      			$object->setDestroyOnExit();
      				if(Aastra_is_textmenu_wrapitem_supported()) $object->setWrapList();
      				if($last==1) $object->setTitle(Aastra_get_label('ACD Queues',$language));
				else $object->setTitle(sprintf(Aastra_get_label('ACD Queues (%d/%d)',$language),$page,$last));
	      			if(Aastra_is_style_textmenu_supported()) $object->setStyle('none');

				# Previous page for non softkey phones
				if(!$nb_softkeys and ($page!=1)) $object->addEntry(Aastra_get_label('Previous Page',$language),$XML_SERVER.'&action=show_queues_page&page='.($page-1));
				
				# Display list of queues
				$index=1;
				$logoff_all=False;
				$pause=False;
				$pause_all=False;
				$unpause_all=False;
				foreach($queues as $value)
					{
					# Compute extra keys
					if(!$logoff_all and ($agents[$value['Queue']]['Status']>0) and ($agents[$value['Queue']]['Membership']!='static')) $logoff_all=True;
					if(!$pause and ($agents[$value['Queue']]['Status']>0)) $pause=True;
					$status=get_agent_status($value['Queue'],$agent);
					if($status['Logged'])  
						{
						# New pause state?
						if(!$pause_all and (!$status['Paused'])) $pause_all=True;
						if(!$unpause_all and ($status['Paused'])) $unpause_all=True;
						}

					# Display if right page
					if(($index>=(($page-1)*$MaxLines+1)) and ($index<=$page*$MaxLines))
						{
						# Prepare display
						$description=$value['Description'];
						$number='('.$value['Queue'].')';
						if($nb_softkeys!=10) $description=substr($description,0,$chars_supported-strlen($number)-1);
		       			$queue_display=str_pad($description.$number,$chars_supported,'-',STR_PAD_BOTH);
      						if(Aastra_is_textmenu_wrapitem_supported()) $queue_display.=chr(160).' '.sprintf(Aastra_get_label('InQ:%s;H:%s;A:%s',$language),$value['Calls'],$value['Holdtime'],$value['Abandoned']);
						if($nb_softkeys) $select='log';
						else $select='menu';

						# Icons supported
						if($is_icons)
							{
							# Display with icons
							if($agents[$value['Queue']]['Membership']!='static')
								{
								$icon='1';
								if($agents[$value['Queue']]['Status']=='1') $icon='2';
								if($agents[$value['Queue']]['Status']=='2') $icon='3';
								}
							else
								{
								$icon='4';
								if($agents[$value['Queue']]['Status']=='2') $icon='5';
								}
							$object->addEntry($queue_display,$XML_SERVER.'&action='.$select.'&queue='.$value['Queue'].'&q_desc='.$value['Description'].'&q_pass='.$value['Password'],'&queue='.$value['Queue'].'&q_desc='.$value['Description'].'&q_pass='.$value['Password'],$icon);
							}
						else
							{
							# Display without icons
							if($agents[$value['Queue']]['Status']=='') $queue_display='-'.$queue_display;
							if($agents[$value['Queue']]['Status']=='1') $queue_display='X'.$queue_display;
							if($agents[$value['Queue']]['Status']=='2') $queue_display='/'.$queue_display;
							$object->addEntry($queue_display,$XML_SERVER.'&action='.$select.'&queue='.$value['Queue'].'&q_desc='.$value['Description'].'&q_pass='.$value['Password'],'&queue='.$value['Queue'].'&q_desc='.$value['Description'].'&q_pass='.$value['Password']);
							}
						}
					$index++;
					}

				# Default position
				if($default!='') $object->setDefaultIndex($default);

				# Previous page for non softkey phones
				if(!$nb_softkeys and ($page!=$last)) $object->addEntry(Aastra_get_label('Next Page',$language),$XML_SERVER.'&action=show_queues_page&page='.($page+1));

				# Softkeys
	      			if($nb_softkeys)  
					{
					if($nb_softkeys==6)
						{
						if($last==1)
							{
			        			$object->addSoftkey('1',Aastra_get_label('Logon/off',$language),'SoftKey:Select');
							if($pause) $object->addSoftkey('2',Aastra_get_label('(Un)Pause',$language),$XML_SERVER.'&action=pause');
							if($logoff_all) $object->addSoftkey('3',Aastra_get_label('LOGOFF',$language),$XML_SERVER.'&action=logoff_all');
							if($pause_all) $object->addSoftkey('4',Aastra_get_label('PAUSE',$language),$XML_SERVER.'&action=pause_all');
							if($unpause_all) $object->addSoftkey('5',Aastra_get_label('UNPAUSE',$language),$XML_SERVER.'&action=unpause_all');
	        					$object->addSoftkey('6',Aastra_get_label('Exit',$language),'SoftKey:Exit');
							}
						else
							{
							if($menu_set==1)
								{
				        			$object->addSoftkey('1',Aastra_get_label('Logon/off',$language),'SoftKey:Select');
								if($page!=1) $object->addSoftkey('2',Aastra_get_label('Previous',$language),$XML_SERVER.'&action=show_queues_page&page='.($page-1));
								if($pause) $object->addSoftkey('3',Aastra_get_label('(Un)Pause',$language),$XML_SERVER.'&action=pause');
								if($logoff_all) $object->addSoftkey('4',Aastra_get_label('LOGOFF',$language),$XML_SERVER.'&action=logoff_all');
								if($last!=$page) $object->addSoftkey('5',Aastra_get_label('Next',$language),$XML_SERVER.'&action=show_queues_page&page='.($page+1));
	        						$object->addSoftkey('6',Aastra_get_label('More',$language),$XML_SERVER.'&action=show_queues&queue='.$queue.'&menu_set=2');
								}
							else
								{
								if($pause_all) $object->addSoftkey('1',Aastra_get_label('PAUSE',$language),$XML_SERVER.'&action=pause_all');
								if($page!=1) $object->addSoftkey('2',Aastra_get_label('Previous',$language),$XML_SERVER.'&action=show_queues_page&page='.($page-1));
								if($unpause_all) $object->addSoftkey('3',Aastra_get_label('UNPAUSE',$language),$XML_SERVER.'&action=unpause_all');
								$object->addSoftkey('4',Aastra_get_label('Exit',$language),'SoftKey:Exit');
								if($last!=$page) $object->addSoftkey('5',Aastra_get_label('Next',$language),$XML_SERVER.'&action=show_queues_page&page='.($page+1));
	        						$object->addSoftkey('6',Aastra_get_label('More',$language),$XML_SERVER.'&action=show_queues&queue='.$queue.'&menu_set=1');
								}
							}
						}
					else
						{
						$object->addSoftkey('1',Aastra_get_label('Logon/Logoff',$language),$XML_SERVER.'&action=log');
						if($pause) $object->addSoftkey('2',Aastra_get_label('(Un)Pause',$language),$XML_SERVER.'&action=pause');
						if($page!=1) $object->addSoftkey('3',Aastra_get_label('Previous Page',$language),$XML_SERVER.'&action=show_queues_page&page='.($page-1));
						if($pause_all) $object->addSoftkey('6',Aastra_get_label('PAUSE',$language),$XML_SERVER.'&action=pause_all');
						if($unpause_all) $object->addSoftkey('7',Aastra_get_label('UNPAUSE',$language),$XML_SERVER.'&action=unpause_all');
						if($last!=$page) $object->addSoftkey('8',Aastra_get_label('Next Page',$language),$XML_SERVER.'&action=show_queues_page&page='.($page+1));
						if($logoff_all) $object->addSoftkey('9',Aastra_get_label('LOGOFF',$language),$XML_SERVER.'&action=logoff_all');
        					$object->addSoftkey('10',Aastra_get_label('Exit',$language),'SoftKey:Exit');
						}
      					}

				# Icons
				if($is_icons)
					{
					if(Aastra_phone_type()!=5)
						{
						$object->addIcon('1','0000FE8282828282FE000000'); //Logged off Dynamic
						$object->addIcon('2','0000FEC6AA92AAC6FE000000'); //Logged on Dynamic
						$object->addIcon('3','0000FE868A92A2C2FE000000'); //Paused Dynamic
						$object->addIcon('4','0000FEFEFEFEFEFEFE000000'); //Logged on Static
						$object->addIcon('5','0000FE868E9EBEFEFE000000'); //Paused Static
						}
					else
						{
						$object->addIcon(1,'Icon:CircleRed');
						$object->addIcon(2,'Icon:CheckBoxCheck');
						$object->addIcon(3,'Icon:CircleYellow');
						$object->addIcon(4,'Icon:CircleGreen');
						$object->addIcon(5,'Icon:CircleBlue');
						}
					}

				# Test the refresh
				if(Aastra_is_Refresh_supported()) 
					{
					$object->setRefresh('10',$XML_SERVER.'&action=refresh&page='.$page.'&menu_set='.$menu_set);
					$new=$object->generate();
					}
				else 
					{
					$refresh=1;
					if(($nb_softkeys) and (Aastra_is_textmenu_wrapitem_supported())) 
						{
						if($nb_softkeys==10) $object->addSoftkey(9,Aastra_get_label('Refresh',$language),$XML_SERVER.'&page='.$page);
						}
					}	

				# Test refresh
				if($refresh==0)
					{
					$old=@file_get_contents(AASTRA_PATH_CACHE.$header['mac'].'.agent');
					if($new!=$old) $refresh=1;
					}

				# Save last screen
				$stream=@fopen(AASTRA_PATH_CACHE.$header['mac'].'.agent','w');
				@fwrite($stream,$new);
				@fclose($stream);
    				}
			else
				{
				# The agent is not allowed to any queue
				require_once('AastraIPPhoneTextScreen.class.php');
      				$object=new AastraIPPhoneTextScreen();
      				$object->setDestroyOnExit();
		      		$object->setTitle(Aastra_get_label('No ACD Queue',$language));
		      		$object->setText(Aastra_get_label('You are not allowed to connect to any ACD queue on this platform. Please contact your administrator.',$language));

				# Softkeys
      				if($nb_softkeys==6) $object->addSoftKey('6',Aastra_get_label('Exit',$language),'SoftKey:Exit');
				else $object->addSoftKey('10',Aastra_get_label('Exit',$language),'SoftKey:Exit');
				}
			}	
	    	else  
			{
			# The agent is not a member of any queues or no queues
			require_once('AastraIPPhoneTextScreen.class.php');
      			$object=new AastraIPPhoneTextScreen();
      			$object->setDestroyOnExit();
	      		$object->setTitle(Aastra_get_label('No ACD Queue',$language));
	      		$object->setText(Aastra_get_label('There are no ACD queue configured on your system. Please contact your administrator.',$language));

			# Softkeys
      			if($nb_softkeys==6) $object->addSoftKey('6',Aastra_get_label('Exit',$language),'SoftKey:Exit');
			else $object->addSoftKey('10',Aastra_get_label('Exit',$language),'SoftKey:Exit');
    			}
  		break;
	
	# Pause or unpause
	case 'pause':
		# Get agent status for this queue
		$status=get_agent_status($queue,$agent);

		# Agent logged?
		if($status['Logged'])  
			{
			# New pause state?
			if($status['Paused']) Aastra_queue_pause_Asterisk($agent,$queue,'false');
			else Aastra_queue_pause_Asterisk($agent,$queue,'true');

			# Prepare display callback
			require_once('AastraIPPhoneExecute.class.php');
	      		$object=new AastraIPPhoneExecute();
      			$object->addEntry($XML_SERVER.'&action=show_queues&queue='.$queue);
			}
		else
			{
			# Do Nothing
			require_once('AastraIPPhoneExecute.class.php');
	      		$object=new AastraIPPhoneExecute();
      			$object->addEntry('');
    			}
		break;

	# Initial or recurrent check
	case 'check':
	case 'register':
		# Update needed
		$update=1;

		# Get global status
		if(is_agent_logged($agent)) $status=1;
		else $status=0;

		# Get last agent status
		$data=Aastra_get_user_context($agent,'agent');
		$last=$data['last'];
		$key=$data['key'];

		# Save status if changed
		if($status!=$last) 
			{
			$data['last']=$status;
			Aastra_save_user_context($agent,'agent',$data);
			}

		# Update needed?
		if(($action=='check') and ($status==$last)) $update=0;
		if(($action=='register') and ($status==0)) $update=0;

		# Prepare display update
		require_once('AastraIPPhoneExecute.class.php');
		$object=new AastraIPPhoneExecute();

		# Update LED if necessary
		if(($key!='') and Aastra_is_ledcontrol_supported() and ($update==1)) 
			{
			if($status==1) $object->addEntry('Led: '.$key.'=on');
			else $object->addEntry('Led: '.$key.'=off');
			}
		else
			{
			# Do nothing
			$object->addEntry('');
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
