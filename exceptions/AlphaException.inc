<?php

// load config
if(!isset($config))
	require_once '../utils/configLoader.inc';
$config =&configLoader::getInstance();

/**
 * The parent exception class for Alpha
 * 
 * @package Alpha Core Exceptions
 * @author John Collins <john@design-ireland.net>
 * @copyright 2008 John Collins
 * @version $Id$
 * 
 */
class AlphaException extends Exception {	
	/**
	 * The constructor
	 *
	 * @param string $message	 
	 */
	public function __construct($message) {
		parent::__construct($message);
	}
	
	/**
	 * Set the message for the exception
	 *
	 * @param string $message
	 */
	public function setMessage($message) {
		$this->message = $message;
	}
	
	/**
	 * Set the filename for the exception
	 *
	 * @param string $file
	 */
	public function setFile($file) {
		$this->file = $file;
	}
}

?>