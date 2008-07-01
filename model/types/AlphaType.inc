<?php

require_once $config->get('sysRoot').'alpha/exceptions/AlphaFrameworkException.inc';

/**
 * The base (abstract) complex data type
 * 
 * @package Alpha Core Datatype
 * @author John Collins <john@design-ireland.net>
 * @copyright 2008 John Collins
 * @version $Id$
 * 
 */
abstract class AlphaType {	
	/**
	 * Used to get the validation helper message string
	 *
	 * @return string
	 */
	public function getHelper() {
		return $this->helper;
	}
	
	/**
	 * Set the validation helper text
	 *
	 * @param string $helper
	 */
	public function setHelper($helper) {
		$this->helper = $helper;
	}
	
	/**
	 * Used to convert the object to a printable string
	 *
	 * @return string
	 */
	public function __toString() {		
		return strval($this->getValue());
	}
}

?>