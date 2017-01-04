<?php

namespace Alpha\Task;

use Alpha\Util\Logging\Logger;
use Alpha\Util\Config\ConfigProvider;

/**
 * The main class responsible for running custom cron tasks found under the [webapp]/Task
 * directory.  This class should be executed from Linux cron via the CLI.
 *
 * @since 1.0
 *
 * @author John Collins <dev@alphaframework.org>
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2017, John Collins (founder of Alpha Framework).
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
 */
class CronManager
{
    /**
     * Trace logger.
     *
     * @var Alpha\Util\Logging\Logger
     *
     * @since 1.0
     */
    private static $logger = null;

    /**
     * Constructor.
     *
     * @since 1.0
     */
    public function __construct()
    {
        $config = ConfigProvider::getInstance();

        self::$logger = new Logger('CronManager');
        self::$logger->setLogProviderFile($config->get('app.file.store.dir').'logs/tasks.log');

        self::$logger->debug('>>__construct()');

        self::$logger->info('New CronManager invoked');

        $taskList = self::getTaskClassNames();

        self::$logger->info('Found ['.count($taskList).'] tasks in the directory ['.$config->get('app.root').'tasks]');

        foreach ($taskList as $taskClass) {
            $taskClass = 'Alpha\Task\\'.$taskClass;
            self::$logger->info('Loading task ['.$taskClass.']');
            $task = new $taskClass();

            $startTime = microtime(true);
            $maxAllowedTime = $startTime + $task->getMaxRunTime();

            self::$logger->info('Start time is ['.$startTime.'], maximum task run time is ['.$task->getMaxRunTime().']');

            // only continue to execute for the task max time
            set_time_limit($task->getMaxRunTime());
            $task->doTask();

            self::$logger->info('Done in ['.round(microtime(true) - $startTime, 5).'] seconds');
        }

        self::$logger->info('Finished processing all cron tasks');

        self::$logger->debug('<<__construct');
    }

    /**
     * Loops over the /tasks directory and builds an array of all of the task
     * class names in the system.
     *
     * @return array
     *
     * @since 1.0
     */
    public static function getTaskClassNames()
    {
        $config = ConfigProvider::getInstance();

        if (self::$logger == null) {
            self::$logger = new Logger('CronManager');
            self::$logger->setLogFile($config->get('app.file.store.dir').'logs/tasks.log');
        }
        self::$logger->debug('>>getTaskClassNames()');

        $classNameArray = array();

        if (file_exists($config->get('app.root').'Task')) {
            $handle = opendir($config->get('app.root').'Task');

            // loop over the custom task directory
            while (false !== ($file = readdir($handle))) {
                if (preg_match('/Task.php/', $file)) {
                    $classname = mb_substr($file, 0, -4);

                    array_push($classNameArray, $classname);
                }
            }
        }

        if (file_exists($config->get('app.root').'Alpha/Task')) {
            $handle = opendir($config->get('app.root').'Alpha/Task');

            // loop over the custom task directory
            while (false !== ($file = readdir($handle))) {
                if (preg_match('/Task.php/', $file)) {
                    $classname = mb_substr($file, 0, -4);

                    array_push($classNameArray, $classname);
                }
            }
        }

        self::$logger->debug('<<getTaskClassNames ['.var_export($classNameArray, true).']');

        return $classNameArray;
    }
}

// invoke a cron manager object
$processor = new CronManager();
