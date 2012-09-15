<?php
################################################################################
# Aastra XML API Classes - AastraIPPhoneSoftkeyEntry
# Firmware 2.0 or better
# Copyright Aastra Telecom 2005-2010
#
# Internal class for AastraIPPhone object.
################################################################################

class AastraIPPhoneSoftkeyEntry {
	var $_index;
	var $_label;
	var $_uri;
	var $_icon;

	function AastraIPPhoneSoftkeyEntry($index, $label, $uri, $icon)
	{
		$this->_index = $index;
		$this->_label = $label;
		$this->_uri = $uri;
		$this->_icon = $icon;
	}

	function render()
	{
		$index=$this->_index;
		$xml = "<SoftKey index=\"".$index."\"";
		if($this->_icon!=NULL) $xml.= " icon=\"".$this->_icon."\"";
		$xml .= ">\n";
		$xml .= "<Label>".$this->_label."</Label>\n";
		$xml .= "<URI>".$this->_uri."</URI>\n";
		$xml .= "</SoftKey>\n";
		return($xml);
	}
}
?>
