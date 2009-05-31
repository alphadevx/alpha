<?php

/**
 *
 * A filter class for filtering user input from text fields
 * 
 * @package alpha::util
 * @author John Collins <john@design-ireland.net>
 * @copyright 2009 John Collins
 * @version $Id$
 * 
 */
class InputFilter {
	/**
	 * The encoding method which applies the filters
	 * 
	 * @param string $text
	 * @param boolean $allowHTML
	 * @return string
	 */
	public static function encode($text, $allowHTML=false) {
		if(!get_magic_quotes_gpc()) {
			if($allowHTML)
				return addslashes($text);
			else
				return htmlentities(addslashes($text));
		}else{
			if($allowHTML)
				return $text;
			else
				return htmlentities($text);
		}
	}
	
	/**
	 * The decoding method which reverses the filters
	 * 
	 * @param string $text
	 * @param boolean $allowHTML
	 * @return string
	 */
	public static function decode($text, $allowHTML=false) {
		if(!get_magic_quotes_gpc())
			if($allowHTML)		
				return stripslashes($text);
			else
				return html_entity_decode(stripslashes($text));
		else
			if($allowHTML)
				return $text;
			else
				return html_entity_decode($text);
	}
}

?>