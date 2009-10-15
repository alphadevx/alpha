<?php

require_once 'AlphaException.inc';

/**
 * The exception class for 403 resource not allowed controller errors
 * 
 * @package alpha::exceptions
 * @author John Collins <john@design-ireland.net>
 * @copyright 2009 John Collins
 * @version $Id$
 * 
 */
class ResourceNotAllowedException extends AlphaException {
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