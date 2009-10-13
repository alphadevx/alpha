<?php

require_once 'AlphaException.inc';

/**
 * The exception class for 404 resource not found controller errors
 * 
 * @package alpha::exceptions
 * @author John Collins <john@design-ireland.net>
 * @copyright 2009 John Collins
 * @version $Id$
 * 
 */
class ResourceNotFoundException extends AlphaException {
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