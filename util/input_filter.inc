<?php

// $Id$

require_once $config->get('sysRoot').'alpha/model/types/Text.inc';

/**
 *
 * A filter class for filtering user input from text fields
 * 
 * @package Alpha Util
 * @author John Collins <john@design-ireland.net>
 * @copyright 2006 John Collins 
 * 
 */
 
class input_filter
{
	/**
	 * the text to be filtered
	 * @var Text
	 */
	var $text;
	
	/**
	 * the constructor
	 * @param Text the text to be filtered
	 */
	function input_filter($text) {
		$this->text = $text;
	}
	
	/**
	 * the encoding method which applies the filters
	 * @return string the text with the filters applied
	 */
	function encode() {		
		return htmlentities($this->text->getValue());
	}
	
	/**
	 * the decoding method which reverses the filters
	 * @return string the text with the filters reversed
	 */
	function decode() {		
		return html_entity_decode($this->text->getValue());
	}
}
?>
