<?php
#########################################################################################################
# Aastra XML API Classes - Aastra XML API Classes - AastraIPPhoneSPImage
# Copyright Aastra Telecom 2009
#
# Firmware 2.2.0 or better
#
# AastraIPPhoneSPImage for AastraIPPhoneImageScreen and AastraIPPhoneImageScreen.
#
# Public methods
#
#      setText(text,line,align,offset) 
#	Writes text to the image using the system font
#	text		The text to be displayed, text can include %1%..%9% to include a custom icon as a
#			character.
#	line		Line where the text will be displayed (1 to 5). 
#	align		Text alignment in the line  be 'left', 'right' or 'center'. 'left' is the default 
#			value.
#	offset		Offset in the alignment, not used for 'center'.
#
#	addIcon(index,icon)
#	Adds the definition of a custom icon as an hex string same as the regular phone icons.
#	index		Icon index (1 to 9)
#	icon		Hex string representing the icon
#
#	setBitmap(bitmap,line,column)
#	Writes a custom bitmap on the display
# 	bitmap		Bitmap name 'answered' or 'unanswered'
#	line/column	Line/Column to display the bitmap
#
# Example 
#	require_once('AastraIPPhoneSPImage.class.php');
#	$SPimage->addIcon('1','286CEE6C2800');
#	$SPimage->setBitmap('answered',3,1);
#	$SPimage->setText('Jean Valjean',1,'left',3);
#	$SPimage->setText('9057604454',2,'left',3);
#	$SPimage->setText('Sep 9 10:14am',4,'left',3);
#	$SPimage->setText('Use #1# to browse',5,'center');
########################################################################################################

########################################################################################################
class AastraIPPhoneSPImage
{
	var $_matrix;
	var $_icon;
	
function setText($text,$line,$align='left',$offset='0')
	{
	# Remove some non supported accented characters
	$text=strtr($text,"ŠšŸ¥µÁÃÅÈËÌÍĞÑÒÓÔÕØÙÚÛÜİãåìğòõøıÿ","SZszYYuAAAEEIIDNOOOOOUUUUYaaiooooyy"); 

	# Check for icons in the text
	$vars=preg_match_all('/\#\d+\#/',$text,$matches);
	if($vars>0)
		{
		for ($i=1;$i<10; $i++) 
			{
	       	$pattern="/\#$i\#/";
	       	$text=preg_replace($pattern,chr($i+20),$text);
			}
		}

	# Modify text for positioning
	switch($align)
		{
		case 'left':
			if($offset!='0') $text=str_pad($text,strlen($text)+$offset,' ',STR_PAD_LEFT);
			break;
		case 'right':
			if($offset!='0') $text=str_pad($text,strlen($text)+$offset,' ',STR_PAD_RIGHT);
			$text=str_pad($text,24,' ',STR_PAD_LEFT);
			break;
		case 'center':
			$text=str_pad($text,24,' ',STR_PAD_BOTH);
			break;
		}

	# Store text
	$len=strlen($text);
	$row=$line-1;
	if($row<5)
		{
		for($i=0;($i<$len) and ($i<24);$i++)  
			{
			$char=substr($text,$i,1);
			if($char!=' ') $this->_matrix[$row][$i]=$char;
			}
		}
	}

function addIcon($index,$icon)
	{
	for($i=0;$i<6;$i++) $array[$i]=substr($icon,$i*2,2);
	$this->_icon[$index]=$array;
	}

function setBitmap($icon,$line,$column)
	{
	switch($icon)
		{
		case 'answered':
		case 'unanswered':
		case 'recording':
		case 'paused':
			for($i=0;$i<2;$i++)
				{
				for($j=0;$j<2;$j++) $this->_matrix[$line-1+$i][$column-1+$j]=$icon.$i.$j;
				}
			break;
		case 'read':
		case 'new':
		case 'playing':
			for($i=0;$i<2;$i++)
				{
				for($j=0;$j<3;$j++) $this->_matrix[$line-1+$i][$column-1+$j]=$icon.$i.$j;
				}
			break;
		}
	}

function getSPImage() 
	{
	# Characters
	$letter[' ']=array('00','00','00','00','00','00');
	$letter['!']=array('00','00','f2','00','00','00');
	$letter[chr(34)]=array('00','e0','00','e0','00','00');
	$letter['#']=array('28','fe','28','fe','28','00');
	$letter['$']=array('24','54','d6','54','48','00');
	$letter['%']=array('c4','c8','10','26','46','00');
	$letter['&']=array('6c','92','aa','44','0a','00');
	$letter[chr(39)]=array('00','e0','00','00','00','00');
	$letter['(']=array('38','44','82','00','00','00');
	$letter[')']=array('82','44','38','00','00','00');
	$letter['*']=array('28','10','7c','10','28','00');
	$letter['+']=array('10','10','7c','10','10','00');
	$letter[',']=array('00','0a','0c','00','00','00');
	$letter['-']=array('10','10','10','10','10','00');
	$letter['.']=array('00','06','06','00','00','00');
	$letter['/']=array('04','08','10','20','40','00');
	$letter['0']=array('7c','8a','92','a2','7c','00');
	$letter['1']=array('00','42','fe','02','00','00');
	$letter['2']=array('42','86','8a','92','62','00');
	$letter['3']=array('84','82','a2','d2','8c','00');
	$letter['4']=array('18','28','48','fe','08','00');
	$letter['5']=array('e4','a2','a2','a2','9c','00');
	$letter['6']=array('3c','52','92','92','0c','00');
	$letter['7']=array('80','8e','90','a0','c0','00');
	$letter['8']=array('6c','92','92','92','6c','00');
	$letter['9']=array('60','92','92','94','78','00');
	$letter[':']=array('00','6c','6c','00','00','00');
	$letter[';']=array('00','6a','6c','00','00','00');
	$letter['<']=array('10','28','44','82','00','00');
	$letter['=']=array('28','28','28','28','28','00');
	$letter['>']=array('82','44','28','10','00','00');
	$letter['?']=array('40','80','8a','90','60','00');
	$letter['@']=array('4c','92','9e','82','7c','00');
	$letter['A']=array('7e','88','88','88','7e','00');
	$letter['B']=array('fe','92','92','92','6c','00');
	$letter['C']=array('7c','82','82','82','44','00');
	$letter['D']=array('fe','82','82','44','38','00');
	$letter['E']=array('fe','92','92','92','82','00');
	$letter['F']=array('fe','90','90','90','80','00');
	$letter['G']=array('7c','82','92','92','5e','00');
	$letter['H']=array('fe','10','10','10','fe','00');
	$letter['I']=array('00','82','fe','82','00','00');
	$letter['J']=array('04','02','82','fc','80','00');
	$letter['K']=array('fe','10','28','44','82','00');
	$letter['L']=array('fe','02','02','02','02','00');
	$letter['M']=array('fe','40','30','40','fe','00');
	$letter['N']=array('fe','20','10','08','fe','00');
	$letter['O']=array('7c','82','82','82','7c','00');
	$letter['P']=array('fe','90','90','90','60','00');
	$letter['Q']=array('7c','82','8a','84','7a','00');
	$letter['R']=array('fe','90','98','94','62','00');
	$letter['S']=array('62','92','92','92','8c','00');
	$letter['T']=array('80','80','fe','80','80','00');
	$letter['U']=array('fc','02','02','02','fc','00');
	$letter['V']=array('f8','04','02','04','f8','00');
	$letter['W']=array('fc','02','1c','02','fc','00');
	$letter['X']=array('c6','28','10','28','c6','00');
	$letter['Y']=array('e0','10','0e','10','e0','00');
	$letter['Z']=array('86','8a','92','a2','c2','00');
	$letter['[']=array('fe','82','82','00','00','00');
	$letter["\\"]=array('40','20','10','08','04','00');
	$letter[']']=array('82','82','fe','00','00','00');
	$letter['^']=array('20','40','80','40','20','00');
	$letter['_']=array('01','01','01','01','01','00');
	$letter['`']=array('00','00','80','00','00','00');
	$letter['a']=array('04','2a','2a','2a','1e','00');
	$letter['b']=array('fe','12','22','22','1c','00');
	$letter['c']=array('1c','22','22','22','04','00');
	$letter['d']=array('1c','22','22','12','fe','00');
	$letter['e']=array('1c','2a','2a','2a','18','00');
	$letter['f']=array('10','7e','90','80','40','00');
	$letter['g']=array('30','4a','4a','4a','7c','00');
	$letter['h']=array('fe','10','20','20','1e','00');
	$letter['i']=array('00','22','be','02','00','00');
	$letter['j']=array('04','02','22','bc','00','00');
	$letter['k']=array('fe','08','14','22','00','00');
	$letter['l']=array('00','82','fe','02','00','00');
	$letter['m']=array('3e','20','18','20','1e','00');
	$letter['n']=array('3e','10','20','20','1e','00');
	$letter['o']=array('1c','22','22','22','1c','00');
	$letter['p']=array('3e','28','28','28','10','00');
	$letter['q']=array('10','28','28','18','3e','00');
	$letter['r']=array('3e','10','20','20','10','00');
	$letter['s']=array('12','2a','2a','2a','04','00');
	$letter['t']=array('20','fc','22','02','04','00');
	$letter['u']=array('3c','02','02','04','3e','00');
	$letter['v']=array('38','04','02','04','38','00');
	$letter['w']=array('3c','02','0c','02','3c','00');
	$letter['x']=array('22','14','08','14','22','00');
	$letter['y']=array('30','0a','0a','0a','3c','00');
	$letter['z']=array('22','26','2a','32','22','00');
	$letter['{']=array('10','6c','82','00','00','00');
	$letter['|']=array('fe','00','00','00','00','00');
	$letter['}']=array('82','6c','10','00','00','00');
	$letter['~']=array('08','10','10','08','08','10');

	# Some common accented characters for French Spanish and German
	$letter['À']=array('1e','a4','64','24','1e','00');
	$letter['Â']=array('1e','64','a4','64','1e','00');
       $letter['â']=array('04','6a','aa','6a','1e','00');
       $letter['à']=array('04','aa','6a','2a','1e','00');
	$letter['æ']=array('24','2a','1e','2a','1a','00');
	$letter['Æ']=array('7e','90','fe','92','92','00');
	$letter['Ç']=array('7c','83','83','82','44','00');
       $letter['ç']=array('1c','22','23','23','22','00');
	$letter['É']=array('3e','2a','6a','aa','22','00');
	$letter['Ê']=array('3e','6a','aa','6a','22','00');
       $letter['é']=array('1c','2a','6a','aa','18','00');
       $letter['ê']=array('1c','6a','aa','6a','18','00');
       $letter['è']=array('1c','aa','6a','2a','18','00');
       $letter['ë']=array('1c','aa','2a','aa','18','00');
	$letter['Î']=array('00','62','be','62','00','00');
       $letter['î']=array('00','52','9e','42','00','00');
       $letter['ï']=array('00','a2','3e','82','00','00');
       $letter['ô']=array('0c','52','92','52','0c','00');
	$letter['Œ']=array('7c','82','fe','92','92','00');
	$letter['œ']=array('1c','22','1e','2a','1a','00');
       $letter['û']=array('1c','42','82','44','1e','00');
       $letter['ù']=array('3c','82','42','04','3e','00');
	$letter['ü']=array('3c','82','02','84','3e','00');
	$letter['á']=array('04','2a','6a','aa','1e','00');
	$letter['í']=array('00','12','5e','82','00','00');
	$letter['ó']=array('0c','12','52','92','0C','00');
	$letter['ú']=array('3c','02','42','84','3E','00');
	$letter['¿']=array('0c','12','a2','02','04','00');
	$letter['¡']=array('00','00','be','00','00','00');
	$letter['ñ']=array('5e','88','50','50','8E','00');
	$letter['ß']=array('7e','90','92','92','6c','00');
	$letter['Ü']=array('3c','82','02','82','3c','00');
	$letter['Ö']=array('1c','a2','22','a2','1c','00');
	$letter['ö']=array('0c','92','12','92','0c','00');
	$letter['Ä']=array('1e','a4','24','a4','1e','00');
	$letter['ä']=array('04','aa','2a','aa','1e','00');

	# Custom Icons
	for($i=1;$i<10;$i++)
		{
		if($this->_icon[$i]) $letter[chr(20+$i)]=$this->_icon[$i];
		}

	# Bitmap 'answered'
	$letter['answered00']=array('ff','c3','c3','00','00','03');
	$letter['answered01']=array('03','03','00','00','00','00');
	$letter['answered10']=array('00','00','30','70','f0','f0');
	$letter['answered11']=array('f0','f0','f0','70','30','00');

	# Bitmap 'unanswered'
	$letter['unanswered00']=array('00','00','0e','0e','08','0b');
	$letter['unanswered01']=array('0b','0b','08','0e','0e','00');
	$letter['unanswered10']=array('00','00','30','70','f0','f0');
	$letter['unanswered11']=array('f0','f0','f0','70','30','00');

	# Bitmap 'recording'
	$letter['recording00']=array('00','02','03','00','7f','ea');
	$letter['recording01']=array('d5','ea','7f','00','03','02');
	$letter['recording10']=array('00','00','e0','10','c8','e9');
	$letter['recording11']=array('6f','e9','c8','10','e0','00');

	# Bitmap 'paused'
	$letter['paused00']=array('1f','18','18','03','07','0f');
	$letter['paused01']=array('0c','0f','0c','0f','07','03');
	$letter['paused10']=array('fc','0c','0c','e0','f0','f8');
	$letter['paused11']=array('18','f8','18','f8','f0','e0');

	# Bitmap 'new'
	$letter['new00']=array('00','00','00','03','03','02');
	$letter['new01']=array('02','02','02','02','02','02');
	$letter['new02']=array('02','03','03','00','00','00');
	$letter['new10']=array('00','00','00','fe','06','8a');
	$letter['new11']=array('52','22','12','12','22','52');
	$letter['new12']=array('8a','06','fe','00','00','00');

	# Bitmap 'read'
	$letter['read00']=array('00','00','00','03','05','08');
	$letter['read01']=array('10','20','20','20','20','10');
	$letter['read02']=array('08','05','03','00','00','00');
	$letter['read10']=array('00','00','00','fe','06','8a');
	$letter['read11']=array('52','22','22','22','22','52');
	$letter['read12']=array('8a','06','fe','00','00','00');

	# Bimap 'playing'
	$letter['playing00']=array('00','00','00','ff','c3','c3');
	$letter['playing01']=array('00','28','11','45','38','82');
	$letter['playing02']=array('7c','00','00','00','00','00');
	$letter['playing10']=array('00','00','00','00','00','30');
	$letter['playing11']=array('70','f0','f0','f0','f0','70');
	$letter['playing12']=array('30','00','00','00','00','00');

	# Transform matrix into graphic
	for($i=0;$i<5;$i++) $image[$i]=array_fill(0,144,'00');

	foreach($this->_matrix as $i=>$value1)
		{
		foreach($value1 as $j=>$value2)
			{
			$index=$j*6;
			if($letter[$value2])
				{
				$image[$i][$index]=$letter[$value2][0];
				$image[$i][$index+1]=$letter[$value2][1];
				$image[$i][$index+2]=$letter[$value2][2];
				$image[$i][$index+3]=$letter[$value2][3];
				$image[$i][$index+4]=$letter[$value2][4];
				$image[$i][$index+5]=$letter[$value2][5];
				}
			}
		}

	# Prepare output
	$output='';
	for($i=0;$i<144;$i++)
		{
		for($j=0;$j<5;$j++) $output.=$image[$j][$i];
		}

	# Return image
	return $output;
	}
}
?>