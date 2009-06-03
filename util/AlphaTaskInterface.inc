<?php

/**
 * The interface for all cron tasks placed in [webapp]/tasks
 * 
 * @package alpha::util
 * @author John Collins <john@design-ireland.net>
 * @copyright 2009 John Collins
 * @version $Id$
 * 
 */
interface AlphaTaskInterface {
	/**
	 * Runs the functionalcode that makes up the task
	 */
	public function doTask();
	
	/**
	 * Gets the maximum allowed runtime for this cron (in seconds)
	 * 
	 * @return int
	 */
	public function getMaxRunTime();
}

?>