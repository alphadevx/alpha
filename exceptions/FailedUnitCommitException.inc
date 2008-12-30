<?php

require_once 'AlphaException.inc';

/**
 * The exception class for Controller unit of work failed to save exceptions
 * 
 * @package alpha::exceptions
 * @author John Collins <john@design-ireland.net>
 * @copyright 2008 John Collins
 * @version $Id$
 * 
 */
class FailedUnitCommitException extends AlphaException {
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