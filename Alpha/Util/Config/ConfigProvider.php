<?php

namespace Alpha\Util\Config;

use Alpha\Exception\IllegalArguementException;

/**
 * A singleton config class.
 *
 * @since 1.0
 *
 * @author John Collins <dev@alphaframework.org>
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2015, John Collins (founder of Alpha Framework).
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
class ConfigProvider
{
    /**
     * Array to store the config variables.
     *
     * @var array
     *
     * @since 1.0
     */
    private $configVars = array();

    /**
     * The config object singleton.
     *
     * @var Alpha\Util\Config\ConfigProvider
     *
     * @since 1.0
     */
    private static $instance;

    /**
     * The config environment (dev, pro, test).
     *
     * @var string
     *
     * @since 2.0
     */
    private $environment;

    /**
     * Private constructor means the class cannot be instantiated from elsewhere.
     *
     * @since 1.0
     */
    private function __construct()
    {
    }

    /**
     * Get the config object instance.
     *
     * @return Alpha\Util\Config\ConfigProvider
     *
     * @since 1.0
     */
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
            self::$instance->setRootPath();

            // check to see if a child class with callbacks has been implemented
            if (file_exists(self::$instance->get('rootPath').'config/ConfigCallbacks.inc')) {
                require_once self::$instance->get('rootPath').'config/ConfigCallbacks.inc';

                self::$instance = new ConfigCallbacks();
                self::$instance->setRootPath();
            }

            // populate the config from the ini file
            self::$instance->loadConfig();
        }

        return self::$instance;
    }

    /**
     * Get config value.
     *
     * @param $key string
     *
     * @return string
     *
     * @throws Alpha\Exception\IllegalArguementException
     *
     * @since 1.0
     */
    public function get($key)
    {
        if (array_key_exists($key, $this->configVars)) {
            return $this->configVars[$key];
        } else {
            throw new IllegalArguementException('The config property ['.$key.'] is not set in the .ini config file');
        }
    }

    /**
     * Set config value.
     *
     * @param $key string
     * @param $val string
     *
     * @since 1.0
     */
    public function set($key, $val)
    {
        /*
         * If you need to alter a config option after it has been set in the .ini
         * files, you can override this class and implement this callback method
         */
        if (method_exists($this, 'before_set_callback')) {
            $val = $this->before_set_callback($key, $val, $this->configVars);
        }

        $this->configVars[$key] = $val;
    }

    /**
     * Sets the root directory of the application.
     *
     * @since 1.0
     */
    private function setRootPath()
    {
        if (strpos(__DIR__, 'vendor/alphadevx/alpha/Alpha/Util/Config') !== false) {
            $this->set('rootPath', str_replace('vendor/alphadevx/alpha/Alpha/Util/Config', '', __DIR__));
        } else {
            $this->set('rootPath', str_replace('Alpha/Util/Config', '', __DIR__));
        }
    }

    /**
     * Loads the config from the relevent .ini file, dependant upon the current
     * environment (hostname).  Note that this method will die() on failure!
     *
     * @since 1.0
     */
    private function loadConfig()
    {
        $rootPath = $this->get('rootPath');

        // first we need to see if we are in dev, pro or test environment
        if (isset($_SERVER['SERVER_NAME'])) {
            $server = $_SERVER['SERVER_NAME'];
        } elseif (isset($_ENV['HOSTNAME'])) {
            // we may be running in CLI mode
            $server = $_ENV['HOSTNAME'];
        } elseif (php_uname('n') != '') {
            // CLI on Linux or Windows should have this
            $server = php_uname('n');
        } else {
            die('Unable to determine the server name');
        }

        // Load the servers to see which environment the current server is set as
        $serverIni = $rootPath.'config/servers.ini';

        if (file_exists($serverIni)) {
            $envs = parse_ini_file($serverIni);
            $environment = '';

            foreach ($envs as $env => $serversList) {
                $servers = explode(',', $serversList);

                if (in_array($server, $servers)) {
                    $environment = $env;
                }
            }

            if ($environment == '') {
                die('No environment configured for the server '.$server);
            }
        } else {
            die('Failed to load the config file ['.$serverIni.']');
        }

        $this->environment = $environment;

        if (mb_substr($environment, -3) == 'CLI') { // CLI mode
            $envIni = $rootPath.'config/'.mb_substr($environment, 0, 3).'.ini';
        } else {
            $envIni = $rootPath.'config/'.$environment.'.ini';
        }

        if (!file_exists($envIni)) {
            die('Failed to load the config file ['.$envIni.']');
        }

        $configArray = parse_ini_file($envIni);

        foreach (array_keys($configArray) as $key) {
            $this->set($key, $configArray[$key]);
        }
    }

    /**
     * Get the configuration environment for this application.
     *
     * @return string
     *
     * @since 2.0
     */
    public function getEnvironment()
    {
        return $this->environment;
    }
}
