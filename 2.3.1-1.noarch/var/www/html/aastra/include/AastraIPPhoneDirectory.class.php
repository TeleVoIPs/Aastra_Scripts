<?php
########################################################################################################
# Aastra XML API Classes - AastraIPPhoneDirectory
# Copyright Aastra Telecom 2005-2010
#
# AastraIPPhoneDirectory object.
#
# Public methods
#
# Inherited from AastraIPPhone
#     setTitle(Title) to setup the title of an object (optional)
#          @title		string
#     setTitleWrap() to set the title to be wrapped on 2 lines (optional)
#     setCancelAction(uri) to set the cancel parameter with the URI to be called on Cancel (optional)
#          @uri		string
#     setDestroyOnExit() to set DestroyonExit parameter to 'yes', 'no' by default (optional)
#     setBeep() to enable a notification beep with the object (optional)
#     setLockIn() to set the Lock-in tag to 'yes' (optional)
#     setTimeout(timeout) to define a specific timeout for the XML object (optional)
#          @timeout		integer (seconds)
#     addSoftkey(index,label,uri,icon_index) to add custom soktkeys to the object (optional)
#          @index		integer, softkey number
#          @label		string
#          @uri		string
#          @icon_index	integer, icon number
#     setRefresh(timeout,URL) to add Refresh parameters to the object (optional)
#          @timeout		integer (seconds)
#          @URL		string
#     setEncodingUTF8() to change encoding from default ISO-8859-1 to UTF-8 (optional)
#     addIcon(index,icon) to add custom icons to the object (optional)
#          @index		integer, icon index
#          @icon		string, icon name or definition
#     generate() to return the generated XML for the object
#     output(flush) to display the object
#          @flush		boolean optional, output buffer to be flushed out or not.
# 
# Specific to the object
#     setNext(next) to set URI of the next page (optional)
#          @next		string
#     setPrevious(previous) to set URI of the previous page (optional)
#          @previous		string
#     addEntry(name,phone) to add an element in the list to be displayed, at least one is needed.
#          @name		string
#          @number		string, number for dialing
#     natsortbyname() to order the list
#
# Example
#     require_once('AastraIPPhoneDirectory.class.php');
#     $directory = new AastraIPPhoneDirectory();
#     $directory->setTitle('Title');
#     $directory->setNext('http://myserver.com/script.php?page=2');
#     $directory->setPrevious('http://myserver.com/script.php?page=0');
#     $directory->setDestroyOnExit();
#     $directory->addEntry('John Doe', '200');
#     $directory->addEntry('Jane Doe', '201');
#     $directory->natsortByName();
#     $directory->addSoftkey('1', 'Label', 'http://myserver.com/script.php?action=1');
#     $directory->addSoftkey('6', 'Exit', 'SoftKey:Exit');
#     $directory->output();
#
########################################################################################################

require_once('AastraIPPhone.class.php');
require_once('AastraIPPhoneDirectoryEntry.class.php');

class AastraIPPhoneDirectory extends AastraIPPhone {
	var $_next="";
	var $_previous="";
	var $_maxitems="30";

	function setNext($next)
	{
		$this->_next = $next;
	}

	function setPrevious($previous)
	{
		$this->_previous = $previous;
	}

	function addEntry($name, $telephone)
	{
		$this->_entries[] = new AastraIPPhoneDirectoryEntry($name, $telephone);
	}

	function natsortByName()
	{
		$tmpary = array();
		foreach ($this->_entries as $id => $entry) {
			$tmpary[$id] = $entry->getName();
		}
		natsort($tmpary);
		foreach ($tmpary as $id => $name) {
			$newele[] = $this->_entries[$id];
		}
		$this->_entries = $newele;
	}

	function render()
	{
		# Begining of root tag
		$out = "<AastraIPPhoneDirectory";

		# Previous
		if($this->_previous!="")
			{
			$previous = $this->escape($this->_previous);
			$out .= " previous=\"$previous\"";
			}

		# Next
		if($this->_next!="")
			{ 
			$next = $this->escape($this->_next);
			$out .= " next=\"$next\"";
			}

		# DestroyOnExit
		if($this->_destroyOnExit == 'yes') $out .= " destroyOnExit=\"yes\"";

		# CancelAction
		if($this->_cancelAction != "")
			{ 
			$cancelAction = $this->escape($this->_cancelAction);
			$out .= " cancelAction=\"{$cancelAction}\"";
			}

		# Beep
		if($this->_beep=='yes') $out .= " Beep=\"yes\"";

		# Lockin
		if($this->_lockin=='yes') $out .= " LockIn=\"yes\"";

		# Timeout
		if($this->_timeout!=0) $out .= " Timeout=\"{$this->_timeout}\"";

		# End of root tag
		$out .= ">\n";

		# Title
		if ($this->_title!='')
			{
			$title = $this->escape($this->_title);
		 	$out .= "<Title";
		 	if ($this->_title_wrap=='yes') $out .= " wrap=\"yes\"";
			$out .= ">".$title."</Title>\n";
			}

		# Items
		$index=0;
		if (isset($this->_entries) && is_array($this->_entries)) 
			{
			foreach ($this->_entries as $entry) 
				{
				if($index<$this->_maxitems) $out .= $entry->render();
				$index++;
				}
			}

		# Softkeys
		if (isset($this->_softkeys) && is_array($this->_softkeys)) 
			{
		  	foreach ($this->_softkeys as $softkey) $out .= $softkey->render();
			}

		# Icons
		if (isset($this->_icons) && is_array($this->_icons)) 
			{
  			$IconList=False;
  			foreach ($this->_icons as $icon) 
  				{
	  			if(!$IconList) 
  					{
	  				$out .= "<IconList>\n";
	  				$IconList=True;
	  				}
	  			$out .= $icon->render();
  				}
  			if($IconList) $out .= "</IconList>\n";
			}

		# End tag
		$out .= "</AastraIPPhoneDirectory>\n";

		# Return XML object
		return $out;
	}
}
?>
