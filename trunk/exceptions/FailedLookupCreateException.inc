<?php

require_once 'AlphaException.inc';

/**
 * The exception class for a failed attempt to create a database lookup table for MANY-TO-MANY relationships
 * 
 * @package Alpha Core Exceptions
 * @author John Collins <john@design-ireland.net>
 * @copyright 2008 John Collins
 * @version $Id$
 * 
 */
class FailedLookupCreateException extends AlphaException {
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