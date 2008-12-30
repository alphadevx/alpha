<?php

require_once 'AlphaException.inc';

/**
 * The exception class for security errors
 * 
 * @package alpha::exceptions
 * @author John Collins <john@design-ireland.net>
 * @copyright 2009 John Collins
 * @version $Id$
 * 
 */
class SecurityException extends AlphaException {
	/**
	 * The constructor
	 *
	 * @param string $message	 
	 */
	public function __construct($message) {		
		parent::__construct($message);
	}	
}

?>