<?php

// load config
require_once 'configLoader.inc';
$config =&configLoader::getInstance();

require_once $config->get('sysRoot').'alpha/util/catch_error.inc';

/**
 *
 * The main class responsible for running custom cron tasks found under the [webapp]/tasks
 * directory.  This class should be executed from Linux cron via the CLI.
 * 
 * @package alpha::util
 * @author John Collins <john@design-ireland.net>
 * @copyright 2009 John Collins
 * @version $Id$
 * 
 */
class AlphaCronManager {
	/**
	 * Trace logger
	 * 
	 * @var Logger
	 */
	private static $logger = null;
	
	public function __construct() {
		global $config;
		
		if(self::$logger == null) {
			self::$logger = new Logger('AlphaCronManager');
			self::$logger->setLogFile($config->get('sysRoot').'logs/tasks.log');
		}
		
		self::$logger->debug('>>__construct()');
		
		self::$logger->info('New AlphaCronManager invoked');
		
		self::$logger->debug('<<__construct');
	}
}

// invoke a cron manager object
$processor = new AlphaCronManager();

?>