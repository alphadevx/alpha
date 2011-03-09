<?php

// load config
if(!isset($config)) {
	require_once 'AlphaConfig.inc';
	$config = AlphaConfig::getInstance();
}

require_once $config->get('sysRoot').'alpha/util/AlphaErrorHandlers.inc';

/**
 *
 * The main class responsible for running custom cron tasks found under the [webapp]/tasks
 * directory.  This class should be executed from Linux cron via the CLI.
 * 
 * @package alpha::util
 * @since 1.0
 * @author John Collins <john@design-ireland.net>
 * @version $Id$
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2010, John Collins (founder of Alpha Framework).  
 * All rights reserved.
 * 
 * <pre>
 * Redistribution and use in source and binary forms, with or 
 * without modification, are permitted provided that the 
 * following conditions are met:
 * 
 * * Redistributions of source code must retain the above 
 *   copyright notice, this list of conditions and the 
 *   following disclaimer.
 * * Redistributions in binary form must reproduce the above 
 *   copyright notice, this list of conditions and the 
 *   following disclaimer in the documentation and/or other 
 *   materials provided with the distribution.
 * * Neither the name of the Alpha Framework nor the names 
 *   of its contributors may be used to endorse or promote 
 *   products derived from this software without specific 
 *   prior written permission.
 *   
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND 
 * CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, 
 * INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF 
 * MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE 
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR 
 * CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, 
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT 
 * NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; 
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) 
 * HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN 
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE 
 * OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS 
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 * </pre>
 *  
 */
class AlphaCronManager {
	/**
	 * Trace logger
	 * 
	 * @var Logger
	 * @since 1.0
	 */
	private static $logger = null;
	
	/**
	 * Constructor
	 * 
	 * @since 1.0
	 */
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
	 * @since 1.0
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

	    self::$logger->debug('<<getTaskClassNames ['.var_export($classNameArray, true).']');
	    return $classNameArray;
	}
	
	/**
	 * Loads the definition for the task classname provided
	 * 
	 * @param string $classname
	 * @since 1.0 
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