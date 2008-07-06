<?php

require_once 'AlphaException.inc';

/**
 * The exception class for BO (Business Object) failed delete errors
 * 
 * @package Alpha Core Exceptions
 * @author John Collins <john@design-ireland.net>
 * @copyright 2008 John Collins
 * @version $Id$
 * 
 */
class FailedDeleteException extends AlphaException {
	/**
	 * The constructor
	 *
	 * @param string $message	 
	 */
	public function __construct($message) {
		$this->errorLevel = 'sysErrorValidation';
		parent::__construct($message);
	}
}

?>