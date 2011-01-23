<?php

// load config
if(!isset($config)) {
	require_once 'AlphaConfig.inc';
	$config = AlphaConfig::getInstance();
}

require_once $config->get('sysRoot').'alpha/util/CatchError.inc';

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
		
		self::$logger = new Logger('AlphaCronManager');			
		self::$logger->setLogFile($config->get('sysRoot').'logs/tasks.log');
		
		self::$logger->debug('>>__construct()');
		
		self::$logger->info('New AlphaCronManager invoked');

		$taskList = self::getTaskClassNames();
		
		self::$logger->info('Found ['.count($taskList).'] tasks in the directory ['.$config->get('sysRoot').'tasks]');
		
		foreach($taskList as $taskClass) {
			self::$logger->info('Loading task ['.$taskClass.']');
			self::loadClassDef($taskClass);
			$task = new $taskClass;
			
			$startTime = microtime(true);			
			$maxAllowedTime = $startTime+$task->getMaxRunTime();
			
			self::$logger->info('Start time is ['.$startTime.'], maximum task run time is ['.$task->getMaxRunTime().']');
			
			// only continue to execute for the task max time
			set_time_limit($task->getMaxRunTime());
			$task->doTask();
			
			self::$logger->info('Done in ['.round(microtime(true)-$startTime, 5).'] seconds');
		}
		
		self::$logger->info('Finished processing all cron tasks');
		
		self::$logger->debug('<<__construct');
	}
	
	/**
	 * Loops over the /tasks directory and builds an array of all of the task
	 * class names in the system.
	 *
	 * @return array
	 */
	public static function getTaskClassNames() {
		global $config;
		
		if(self::$logger == null) {
			self::$logger = new Logger('AlphaCronManager');
			self::$logger->setLogFile($config->get('sysRoot').'logs/tasks.log');
		}
		self::$logger->debug('>>getTaskClassNames()');
		
		global $config;
		
		$classNameArray = array();		
		
		$handle = opendir($config->get('sysRoot').'tasks');
   		
        // loop over the business object directory
	    while (false !== ($file = readdir($handle))) {
	    	if (preg_match("/_task.inc/", $file)) {
	    		$classname = substr($file, 0, -4);
	    		
	    		array_push($classNameArray, $classname);
	    	}
	    }

	    self::$logger->debug('<<getTaskClassNames ['.print_r($classNameArray, true).']');
	    return $classNameArray;
	}
	
	/**
	 * Loads the definition for the task classname provided
	 * 
	 * @param string $classname 
	 */
	public static function loadClassDef($classname) {
		global $config;
		
		if(self::$logger == null) {
			self::$logger = new Logger('AlphaCronManager');
			self::$logger->setLogFile($config->get('sysRoot').'logs/tasks.log');
		}
		self::$logger->debug('>>loadClassDef(classname=['.$classname.'])');
		
		if(file_exists($config->get('sysRoot').'tasks/'.$classname.'.inc'))
			require_once $config->get('sysRoot').'tasks/'.$classname.'.inc';
		else
			throw new IllegalArguementException('The class ['.$classname.'] is not defined anywhere!');
		
		self::$logger->debug('<<loadClassDef');
	}
}

// invoke a cron manager object
$processor = new AlphaCronManager();

?>