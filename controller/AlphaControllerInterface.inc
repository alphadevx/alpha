<?php

/**
 * The interface for all page controllers
 * 
 * @package alpha::controller
 * @author John Collins <john@design-ireland.net>
 * @copyright 2008 John Collins
 * @version $Id: AlphaTypeInterface.inc 604 2008-08-25 21:57:40Z john $
 * 
 */
interface AlphaControllerInterface {
	public function doGET($params);
	public function doPOST($params);
}

?>