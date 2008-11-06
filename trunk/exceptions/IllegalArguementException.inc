<?php

require_once 'AlphaException.inc';

/**
 * The exception class for an illegal arguement (parameter) being passed to a method
 * 
 * @package Alpha Core Exceptions
 * @author John Collins <john@design-ireland.net>
 * @copyright 2008 John Collins
 * @version $Id$
 * 
 */
class IllegalArguementException extends AlphaException {
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