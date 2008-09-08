<?php

require_once 'AlphaException.inc';

/**
 * The exception class for errors sending email
 * 
 * @package Alpha Core Exceptions
 * @author John Collins <john@design-ireland.net>
 * @copyright 2008 John Collins
 * @version $Id$
 * 
 */
class MailNotSentException extends AlphaException {
	/**
	 * The constructor
	 *
	 * @param string $message	 
	 */
	public function __construct($message) {
		$this->errorLevel = 'sysErrorPhp';
		parent::__construct($message);
	}
}

?>