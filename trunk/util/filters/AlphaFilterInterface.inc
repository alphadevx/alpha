<?php

/**
 * Interface for HTTP filters applied to front controller requests
 * 
 * @package alpha::util::filters
 * @author John Collins <john@design-ireland.net>
 * @copyright 2009 John Collins
 * @version $Id$
 *
 */
interface AlphaFilterInterface {
	/**
	 * The method for doing the work of the filter
	 */
	public function process();
}

?>