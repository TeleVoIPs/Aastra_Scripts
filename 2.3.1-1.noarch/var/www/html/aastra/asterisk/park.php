<?php
#############################################################################
# Asterisk Parked Calls
#
# Aastra SIP Phones 1.4.2 or better
#
# Copyright 2007-2010 Aastra Telecom Ltd
#
# script.php?user=USER&linestate=$$LINESTATE$$&autopick=1
#   USER is the user extension
#   [optional] linestate will indicate current phone state,
#   if not connected then park the call
#   [optional] autopick=1 will automatically pickup the parked call if there is only one.
#
# Supported Aastra Phones
#     All phones
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
require_once('AastraAsterisk.php');

#############################################################################
# Main code
#############################################################################
# Retrieve parameters
$user=Aastra_getvar_safe('user');
$action=Aastra_getvar_safe('action');
$value=Aastra_getvar_safe('value');
$linestate=Aastra_getvar_safe('linestate');
$autopick=Aastra_getvar_safe('autopick');







# Initial action
if($action=='')
{
    if($linestate=='CONNECTED') $action='park';
	else $action='list';
}

# Trace
Aastra_trace_call('park_asterisk','user='.$user.', linestate='.$linestate);

# Test User Agent
Aastra_test_phone_versions(array('1'=>'1.4.2.','2'=>'1.4.2.','3'=>'2.5.3.','4'=>'2.5.3.','5'=>'3.0.1.'),'0');

# Retrieve phone information
$header=Aastra_decode_HTTP_header();

# Retrieve phone model
$model=$header['model'];

# Get phone language
$language=Aastra_get_language();

# Global compatibility
$nb_softkeys=Aastra_number_softkeys_supported();

# Keep URL
$XML_SERVER.='?user='.$user;

# Process action
switch($action)
{
    # Park (does not work on 6739i 3.0.1)
	case 'park':
		# Retrieve parking lot
		$parking=Aastra_get_park_config_Asterisk();
		# Parking lot configured
		if($parking['parkext']!='')
		{
            # Decompose the string
			$chars=preg_split('//',$parking['parkext'],-1,PREG_SPLIT_NO_EMPTY);
			# Send key sequence
			require_once('AastraIPPhoneExecute.class.php');
			$object=new AastraIPPhoneExecute();
			$object->addEntry('Key:KeyPadPound');
			$object->addEntry('Key:KeyPadPound');
			foreach($chars as $value)
			{
			    switch($value)
				{
				    case '0':
					case '1':
					case '2':
					case '3':
					case '4':
					case '5':
					case '6':
					case '7':
					case '8':
					case '9':
						$object->addEntry('Key:KeyPad'.$value);
						break;
					case '*':
						$object->addEntry('Key:KeyPadStar');
					case '#':
						$object->addEntry('Key:KeyPadPound');
						break;
				}
			}
			$object->addEntry('Key:KeyPadPound');
		}
		else
		{
		    # No parking configured
			require_once('AastraIPPhoneTextScreen.class.php');
		  	$object=new AastraIPPhoneTextScreen();
			$object->setDestroyOnExit();
			$object->setTitle(Aastra_get_label('Error',$language));
			$object->setText(Aastra_get_label('Parking lot not configured. Please contact your administrator',$language));
			# Softkeys
			if($nb_softkeys)
			{
				if($nb_softkeys==6) $object->addSoftkey(6,Aastra_get_label('Exit',$language),'SoftKey:Exit');
				else $object->addSoftkey(10,Aastra_get_label('Exit',$language),'SoftKey:Exit');
			}
		}
		break;

	# Poor man's dial
	case 'dial':
		# Do nothing
		require_once('AastraIPPhoneExecute.class.php');
		$object=new AastraIPPhoneExecute();
		$object->addEntry('');
		$object->output(True);

		# Dial the number
		Aastra_dial_number_Asterisk($user,$value);
		exit;
		break;

    # Parked calls
	case 'list':
		# Get Parked calls
		$park=Aastra_get_parked_calls_Asterisk();
		$count=count($park);

    	# Update display
		if($count==0)
		{
			# No park calls
			require_once('AastraIPPhoneTextScreen.class.php');
		  	$object=new AastraIPPhoneTextScreen();
			$object->setDestroyOnExit();
			$object->setTitle(Aastra_get_label('Parked Calls',$language));
			$object->setText(Aastra_get_label('No parked calls on the platform.',$language));

     		# Softkeys
			if($nb_softkeys)
			{
                if($model=='Aastra6867i')
                {
                    $object->addSoftkey(1,Aastra_get_label('Refresh',$language),$XML_SERVER.'&action=list');
                    $object->addSoftkey(4,Aastra_get_label('Exit',$language),'SoftKey:Exit');
                }
                elseif($nb_softkeys==6)
				{
					$object->addSoftkey(1,Aastra_get_label('Refresh',$language),$XML_SERVER.'&action=list');
					$object->addSoftkey(4,Aastra_get_label('Exit',$language),'SoftKey:Exit');
				}
				else
				{
					$object->addSoftkey(6,Aastra_get_label('Refresh',$language),$XML_SERVER.'&action=list');
					$object->addSoftkey(10,Aastra_get_label('Exit',$language),'SoftKey:Exit');
				}
			}
		}
		else
		{
			# Only one and autopick
			if(($count==1) and ($autopick=='1'))
			{
				# Dial the orbit
				require_once('AastraIPPhoneExecute.class.php');
				$object = new AastraIPPhoneExecute();
				if(Aastra_is_dialuri_supported()) $object->addEntry('Dial:'.$park[0][0]);
				else $object->addEntry($XML_SERVER.'&action=dial&value='.$park[0][0]);
			}
			else
			{
				# Display the queue
				require_once('AastraIPPhoneTextMenu.class.php');
				$object = new AastraIPPhoneTextMenu();
				$object->setDestroyOnExit();
				if(Aastra_is_style_textmenu_supported()) $object->setStyle('none');
				$object->setTitle(Aastra_get_label('Parked Calls',$language));
				if(Aastra_is_softkeys_supported())
				{
					if(Aastra_is_dialuri_supported())
					{
						for ($index=0;$index<$count;$index++) $object->addEntry(sprintf('%s-%s',$park[$index][0],$park[$index][1]),'Dial:'.$park[$index][0],$park[$index][0],'',$park[$index][0]);
                        if($model!=='Aastra6867i')
                        {
                            $object->addSoftkey(1,Aastra_get_label('Pickup',$language),'SoftKey:Select');
                        }
					}
					else
					{
						if(Aastra_is_dialkey_supported())
						{
							for ($index=0;$index<$count;$index++) $object->addEntry(sprintf('%s-%s',$park[$index][0],$park[$index][1]),$park[$index][0],$park[$index][0]);
                            if($model!=='Aastra6867i')
                            {
                    		    $object->addSoftkey(1,Aastra_get_label('Pickup',$language),'SoftKey:Dial');
                            }
						}

						{
							for ($index=0;$index<$count;$index++) $object->addEntry(sprintf('%s-%s',$park[$index][0],$park[$index][1]),$XML_SERVER.'&action=dial&value='.$park[$index][0],$park[$index][0]);
                            if($model!=='Aastra6867i')
                            {
                                $object->addSoftkey(1,Aastra_get_label('Pickup',$language),'SoftKey:Select');
                            }
						}
					}
					if($nb_softkeys)
					{
                        if($model=='Aastra6867i')
                        {
                     	    $object->addSoftkey(1,Aastra_get_label('Refresh',$language),$XML_SERVER.'&action=list');
						    $object->addSoftkey(2,Aastra_get_label('Exit',$language),'SoftKey:Exit');
                        }
						elseif($nb_softkeys==6)
						{
							$object->addSoftkey(1,Aastra_get_label('Refresh',$language),$XML_SERVER.'&action=list');
							$object->addSoftkey(4,Aastra_get_label('Exit',$language),'SoftKey:Exit');
						}
						else
						{
							$object->addSoftkey(6,Aastra_get_label('Refresh',$language),$XML_SERVER.'&action=list');
							$object->addSoftkey(10,Aastra_get_label('Exit',$language),'SoftKey:Exit');
						}
					}
				}
				else
				{
					if(Aastra_is_dialuri_supported())
					{
						for ($index=0;$index<$count;$index++) $object->addEntry(sprintf('%s-%s',$park[$index][0],$park[$index][1]),'Dial:'.$park[$index][0],$park[$index][0],'',$park[$index][0]);
					}
					else
					{
						for ($index=0;$index<$count;$index++) $object->addEntry(sprintf('%s-%s',$park[$index][0],$park[$index][1]),$XML_SERVER.'&action=dial&value='.$park[$index][0],$park[$index][0]);
					}
				}
			}
		}
		break;
}

# Display object
$object->output();
exit;
?>