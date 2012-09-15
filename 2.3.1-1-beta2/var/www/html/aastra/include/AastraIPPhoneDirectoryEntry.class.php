<?php
########################################################################################################
# Aastra XML API Classes - AastraIPPhoneDirectoryEntry
# Copyright Aastra Telecom 2007-2010
#
# Internal class for AastraIPPhoneDirectory object.
########################################################################################################

class AastraIPPhoneDirectoryEntry extends AastraIPPhone {
	var $_name;
	var $_telephone;

	function AastraIPPhoneDirectoryEntry($name, $telephone)
	{
		$this->_name=$name;
		$this->_telephone=$telephone;
	}

	function getName()
	{
		return($this->_name);
	}

	function render()
	{
		$name = $this->escape($this->_name);
		$telephone = $this->escape($this->_telephone);
		return("<MenuItem>\n<Prompt>{$name}</Prompt>\n<URI>{$telephone}</URI>\n</MenuItem>\n");
	}
}

?>
