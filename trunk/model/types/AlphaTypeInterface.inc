<?php

require_once $config->get('sysRoot').'alpha/exceptions/AlphaFrameworkException.inc';

/**
 * The interface for all complex data types
 * 
 * @package Alpha Core Datatype
 * @author John Collins <john@design-ireland.net>
 * @copyright 2008 John Collins
 * @version $Id$
 * 
 */
interface AlphaTypeInterface {
	public function setValue($val);
	public function getValue();
	public function setHelper($helper);
	public function getHelper();
	public function __toString();
}

?>