<?php
#############################################################################
# Asterisk Outgoing
#
# Aastra SIP Phones 1.4.2 or better
#
# Copyright 2005-2010 Aastra Telecom Ltd
#
# script.php?number=NUMBER&action=ACTION
# NUMBER is the remote phone number ($$REMOTENUMBER$$)
# ACTION can be "check" or "cancel" (optional)
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
require_once('AastraCommon.php');

#############################################################################
# Retrieve parameters
$number=Aastra_getvar_safe('number');
$action=Aastra_getvar_safe('action','check');

# Trace
Aastra_trace_call('outgoing_asterisk','action='.$action.', number='.$number);

# Process action
switch($action)
	{
	# Check number
	case 'check':
		# Not identified yet
		$found=False;

		# Retrieve phone model
		$header=Aastra_decode_HTTP_header();

		# Check for outgoing vmail (Bug 3.2.0)
		if(($number=='vmail') or ($number=='VMAIL') or ($number==''))
			{
			# Retrieve user
			$user=Aastra_getvar_safe('user');

			# Retrieve session
			$array=Aastra_read_session('vmail',$user);
			if(((time()-$array['time'])<5) and ($array['uri_outgoing']!=''))
				{
				# Call initial script
				# Bug for 3.3.x sleep(1);
				require_once('AastraIPPhoneExecute.class.php');
				$object=new AastraIPPhoneExecute();
				$object->addEntry($array['uri_outgoing']);
				$found=True;
				}
			}
		
		# IF lookup enabled and not trying to transfer
		if(!$found and $AA_OUTGOING_STATE)
			{
			# Get Language
			$language=Aastra_get_language();

			# Get global compatibility
			$nb_softkeys=Aastra_number_softkeys_supported();
			$is_keypress=Aastra_is_keypress_supported();

			# Retrieve intercom prefix
			$intercom=Aastra_get_intercom_config_Asterisk();

			# Remove prefix if identified
			if($intercom!='')
				{
				$pos=strpos($number,$intercom);
				if($pos!==False)
					{
					if($pos==0) $number=substr($number,strlen($intercom));
					}
				}

			# Lookup caller ID as an Asterisk extension
			$callerid=Aastra_get_callerid_Asterisk($number);
			if($callerid=='Unknown') $callerid='';

			# Caller ID identified as internal
			if($callerid!='') $callerid_type='internal';
			else
				{
				# External lookup?
				if($AA_OUTGOING_LOOKUP!='')
					{
					# Remove outgoing prefix
					$ARRAY_CONFIG=Aastra_readINIfile(AASTRA_CONFIG_DIRECTORY.'directory.conf','#','=');
					$dialplan=$ARRAY_CONFIG['Dialplan'];
					if($dialplan['outgoing']!='') 
						{
						$pos=strpos($number,$dialplan['outgoing']);
						if($pos!==False)
							{
							if($pos==0) $number=substr($number,strlen($dialplan['outgoing']));
							}
						}
				
					# Perform the lookup			
					$handle=@fopen($AA_OUTGOING_LOOKUP.$number,'r');
					if($handle)
						{	
						while ($line=fgets($handle,1000)) $callerid.=$line;
						fclose($handle);
						if($callerid!='') $callerid_type='external';
						}
					}
				}

			# We identified something
			if($callerid!='')
				{
				# Labels for status
				$status_text=Aastra_status_config_Asterisk();

				# Display formatted screen if supported
				if(Aastra_is_formattedtextscreen_supported())
					{
					# Create formatted screen
					require_once('AastraIPPhoneFormattedTextScreen.class.php');
					$object = new AastraIPPhoneFormattedTextScreen();

					# Called party is internal
					if($callerid_type=='internal')
						{
						# Presence enabled
						if($AA_PRESENCE_STATE)
							{
							# Retrieve presence data
							$away=Aastra_manage_presence_Asterisk($number,'get','status');

							# Display presence data
							if($away['status']!=AA_PRESENCE_AVAILABLE)
								{
								if($away['status']==AA_PRESENCE_DISCONNECTED) $color='red';
								else $color='yellow';
								$line[]=array($callerid.' ('.$number.')','double',$color);
								$line[]=array($status_text[$away['status']]['label'],$color);
								$date_time=Aastra_format_presence_dt_Asterisk($away['return']);
								foreach($date_time as $value) $line[]=array($value);
								}
							else
								{
								$line[]=array($callerid.' ('.$number.')','double','green');
								$line[]=array($status_text[$away['status']]['label'],'green');
								$hint=Aastra_get_user_hint_asterisk($number);
								$array_hint=explode('&',$hint);
								$array_hint=array_flip($array_hint);
								unset($array_hint['Ringing']);
								$array_hint=array_flip($array_hint);
								if((!strstr($array_hint[0],'Idle')) and ($array_hint[0]!=''))$line[]=array(Aastra_get_label('Phone is in use',$language),'yellow');
								}
							}
						else
							{
							# Presence disabled
							$line[]=array($callerid,'double');
							$line[]=array(sprintf(Aastra_get_label('Ext. %s',$language),$number));
							}
						}
					else
						{
						# External call
						$line[]=array($number,'double'.'green');
						$line[]=array($callerid);
						}
	
					# Format the display
					if(Aastra_is_formattedtextscreen_color_supported())
						{
						for($i=0;$i<5;$i++) $object->addLine('');
						foreach($line as $value) $object->addLine($value[0],$value[1],NULL,$value[2]);
						}
					else
						{
						$count=count($line);
						$size=Aastra_size_formattedtextscreen();
						if($size>=$count)
							{
							$extra=($size-$count)/2;
							for($i=0;$i<$extra;$i++) $object->addLine('');
							foreach($line as $value) $object->addLine($value[0]);
							}
						else
							{
							$index=1;
							foreach($line as $value) 
								{
								if($index=='2') $object->setScrollStart($size-1);
								$object->addLine($value[0]);
								$index++;
								}
							$object->setScrollEnd();
							}	
						}
					}
				else
					{
					# Use a TextScreen
					require_once('AastraIPPhoneTextScreen.class.php');
					$object = new AastraIPPhoneTextScreen();

					# Internal call
					if($callerid_type=='internal')
						{
						# Presence enabled
						if($AA_PRESENCE_STATE)
							{
							$away=Aastra_manage_presence_Asterisk($number,'get','status');
							$object->setTitle($callerid);
							$object->setText('Ext.'.$number);
							if($away['status']!=AA_PRESENCE_AVAILABLE)
								{
								$line=Aastra_format_presence_dt_Asterisk($away['return']);
								$dt=$line[0];
								if($line[1]!='') $dt.=' '.$line[1];
								$text=sprintf(Aastra_get_label('Currently %s. %s.',$language),$status_text[$away['status']]['label'],$dt);
								}
							else $text=sprintf(Aastra_get_label('Currently %s.',$language),$status_text[$away['status']]['label']);
							$object->setText($text);
							}	
						else
							{
							$object->setTitle($callerid);
							$object->setText('Ext.'.$number);
							}
						}
					else
						{
						$object->setTitle($number);
						$object->setText($callerid);
						}
					}

				# Softkeys
				if($nb_softkeys)
					{
					if($nb_softkeys==6) 
						{
						$object->addSoftkey('6',Aastra_get_label('More',$language),'SoftKey:Exit');
						if($is_keypress) $object->addSoftkey('1',Aastra_get_label('Cancel',$language),$XML_SERVER.'?action=cancel');
 						}
					else $object->addSoftkey('10',Aastra_get_label('More',$language),'SoftKey:Exit');
					}

				# Common parameters
				$object->setDestroyOnExit();
				$object->setTimeout('4');
				$found=True;
				}
			}

		# No match whatsoever
		if(!$found)
			{
			require_once('AastraIPPhoneExecute.class.php');
			$object = new AastraIPPhoneExecute();
			$object->addEntry('');
			}
		break;

	# Cancel
	case 'cancel':
		# Send 2 goodbyes to hangup the call
		require_once('AastraIPPhoneExecute.class.php');
		$object = new AastraIPPhoneExecute();
		$object->setTriggerDestroyOnExit();
		$object->addEntry('Key:Goodbye');
		break;
	}

# Display output
$object->output();
exit;
?>
