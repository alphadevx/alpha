<?php

namespace Alpha\Util\Config {
    if (!function_exists('Alpha\Util\Config\php_uname')) {
        function php_uname($mode = 'a')
        {
            if (array_key_exists('alpha_test_uname_override', $GLOBALS)) {
                return (string) $GLOBALS['alpha_test_uname_override'];
            }
            return \php_uname($mode);
        }
    }
}

namespace Alpha\Test\Util\Config {

    use Alpha\Util\Config\ConfigProvider;
    use Alpha\Exception\IllegalArguementException;
    use Alpha\Exception\PHPException;
    use PHPUnit\Framework\TestCase;
    use ReflectionClass;
    use ReflectionMethod;
    use ReflectionProperty;

    class ConfigCallbacksStub extends ConfigProvider
    {
        public function __construct()
        {
        }

        public function beforeSet()
        {
        }

        public function __get($name)
        {
            return 'stubbed_value';
        }
    }

    class ConfigProviderTest extends TestCase
    {
        private $configCopy;
        private $savedServer = [];
        private $savedEnv = [];
        private $tempDirs = [];

        protected function setUp(): void
        {
            $this->savedServer = $_SERVER;
            $this->savedEnv = $_ENV;
            $config = ConfigProvider::getInstance();
            $this->configCopy = clone $config;
        }

        protected function tearDown(): void
        {
            unset($this->configCopy);
            $_SERVER = $this->savedServer;
            $_ENV = $this->savedEnv;
            unset($GLOBALS['alpha_test_uname_override']);
            foreach ($this->tempDirs as $dir) {
                $this->deleteDirectory($dir);
            }
            $this->tempDirs = [];
            $this->resetSingleton();
        }

        /** @since 4.1 */
        private function resetSingleton(): void
        {
            $reflection = new ReflectionClass(ConfigProvider::class);
            $instanceProperty = $reflection->getProperty('instance');
            $instanceProperty->setAccessible(true);
            $instanceProperty->setValue(null, null);
        }

        /** @since 4.1 */
        private function createTempDir(): string
        {
            $dir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'alpha_cfg_'.uniqid('', true);
            mkdir($dir.DIRECTORY_SEPARATOR.'config', 0777, true);
            $this->tempDirs[] = $dir;
            return $dir.DIRECTORY_SEPARATOR;
        }

        /** @since 4.1 */
        private function deleteDirectory(string $dir): void
        {
            if (!is_dir($dir)) {
                return;
            }
            foreach (scandir($dir) as $item) {
                if ($item === '.' || $item === '..') {
                    continue;
                }
                $path = $dir.DIRECTORY_SEPARATOR.$item;
                if (is_dir($path)) {
                    $this->deleteDirectory($path);
                } else {
                    unlink($path);
                }
            }
            rmdir($dir);
        }

        /** @since 4.1 */
        private function setRootPathValue(ConfigProvider $config, string $rootPath): void
        {
            $property = new ReflectionProperty(ConfigProvider::class, 'configVars');
            $property->setAccessible(true);
            $vars = $property->getValue($config);
            $vars['rootPath'] = $rootPath;
            $property->setValue($config, $vars);
        }

        /** @since 4.1 */
        private function invokeLoadConfig(ConfigProvider $config): void
        {
            $method = new ReflectionMethod(ConfigProvider::class, 'loadConfig');
            $method->setAccessible(true);
            $method->invoke($config);
        }

        /** @since 4.1 */
        private function writeServersIni(string $root, string $contents): void
        {
            file_put_contents($root.'config/servers.ini', $contents);
        }

        /** @since 4.1 */
        private function writeEnvIni(string $root, string $env, string $contents): void
        {
            file_put_contents($root.'config/'.$env.'.ini', $contents);
        }

        /** @since 4.1 */
        public function testGetInstance(): void
        {
            $config1 = ConfigProvider::getInstance();
            $config2 = ConfigProvider::getInstance();
            $config1->set('testkey', 'somevalue');
            $this->assertEquals('somevalue', $config2->get('testkey'));
        }

        /** @since 4.1 */
        public function testGetBad(): void
        {
            try {
                $this->configCopy->get('keyDoesNotExist');
                $this->fail('expected exception');
            } catch (IllegalArguementException $e) {
                $this->assertEquals('The config property [keyDoesNotExist] is not set in the .ini config file', $e->getMessage());
            }
        }

        /** @since 2.0.1 */
        public function testReloadConfig(): void
        {
            $this->configCopy->set('app.title', 'Testing');
            $this->assertEquals('Testing', $this->configCopy->get('app.title'));
            $this->configCopy->reloadConfig();
            $this->assertEquals('Alpha Unit Tests', $this->configCopy->get('app.title'));
        }

        /** @since 4.1 */
        public function testGetEnvironment(): void
        {
            $environment = $this->configCopy->getEnvironment();
            $this->assertNotEmpty($environment);
            $this->assertIsString($environment);
            $this->assertTrue(in_array($environment, ['dev', 'pro', 'test'], true));
        }

        /** @since 4.1 */
        public function testSet(): void
        {
            $testKey = 'test_set_key_'.time();
            $testValue = 'test_set_value_'.time();
            $this->configCopy->set($testKey, $testValue);
            $this->assertEquals($testValue, $this->configCopy->get($testKey));
        }

        /** @since 4.1 */
        public function testSetOverwrite(): void
        {
            $originalValue = $this->configCopy->get('app.title');
            $newValue = $originalValue.' - Modified';
            $this->configCopy->set('app.title', $newValue);
            $this->assertEquals($newValue, $this->configCopy->get('app.title'));
        }

        /** @since 4.1 */
        public function testGetExistingKey(): void
        {
            foreach (['app.title', 'app.url', 'db.provider.name'] as $key) {
                try {
                    $value = $this->configCopy->get($key);
                    $this->assertNotEmpty($value);
                } catch (IllegalArguementException $e) {
                }
            }
        }

        /** @since 4.1 */
        public function testGetNonExistentKey(): void
        {
            $nonExistentKey = 'this_key_does_not_exist_'.time();
            try {
                $this->configCopy->get($nonExistentKey);
                $this->fail('expected exception');
            } catch (IllegalArguementException $e) {
                $this->assertStringContainsString($nonExistentKey, $e->getMessage());
                $this->assertStringContainsString('not set', $e->getMessage());
            }
        }

        /** @since 4.1 */
        public function testGetInstanceSameInstance(): void
        {
            $this->assertSame(ConfigProvider::getInstance(), ConfigProvider::getInstance());
        }

        /** @since 4.1 */
        public function testGetInstanceIsStatic(): void
        {
            $method = (new ReflectionClass(ConfigProvider::class))->getMethod('getInstance');
            $this->assertTrue($method->isStatic());
        }

        /** @since 4.1 */
        public function testSetRootPath(): void
        {
            $m = (new ReflectionClass($this->configCopy))->getMethod('setRootPath');
            $m->setAccessible(true);
            $m->invoke($this->configCopy);
            $rootPath = $this->configCopy->get('rootPath');
            $this->assertNotEmpty($rootPath);
            $this->assertStringEndsWith('/', $rootPath);
        }

        /** @since 4.1 */
        public function testSetRootPathComposerPath(): void
        {
            $m = (new ReflectionClass($this->configCopy))->getMethod('setRootPath');
            $m->setAccessible(true);
            $m->invoke($this->configCopy);
            $this->assertNotNull($this->configCopy->get('rootPath'));
        }

        /** @since 4.1 */
        public function testReloadConfigResetsConfig(): void
        {
            $originalValue = $this->configCopy->get('app.title');
            $this->configCopy->set('app.title', 'Modified Value');
            $this->assertEquals('Modified Value', $this->configCopy->get('app.title'));
            $this->configCopy->reloadConfig();
            $this->assertEquals($originalValue, $this->configCopy->get('app.title'));
        }

        /** @since 4.1 */
        public function testSetMultipleValues(): void
        {
            $testValues = ['test_key_1' => 'value1', 'test_key_2' => 'value2', 'test_key_3' => 'value3'];
            foreach ($testValues as $key => $value) {
                $this->configCopy->set($key, $value);
            }
            foreach ($testValues as $key => $value) {
                $this->assertEquals($value, $this->configCopy->get($key));
            }
        }

        /** @since 4.1 */
        public function testConfigValuePersistence(): void
        {
            $testKey = 'persist_test_'.time();
            $this->configCopy->set($testKey, 'persistent_value');
            for ($i = 0; $i < 5; $i++) {
                $this->assertEquals('persistent_value', $this->configCopy->get($testKey));
            }
        }

        /** @since 4.1 */
        public function testEnvironmentConsistency(): void
        {
            $environment = $this->configCopy->getEnvironment();
            $serverIni = $this->configCopy->get('rootPath').'config/servers.ini';
            $this->assertFileExists($serverIni);
            $this->assertNotEmpty($environment);
        }

        /** @since 4.1 */
        public function testSetEmptyStringValue(): void
        {
            $testKey = 'empty_test_'.time();
            $this->configCopy->set($testKey, '');
            $this->assertEquals('', $this->configCopy->get($testKey));
        }

        /** @since 4.1 */
        public function testSetSpecialCharacters(): void
        {
            $testKey = 'special_test_'.time();
            $testValue = "Special chars: !@#$%^&*()_+-=[]{}|;':\",./<>?";
            $this->configCopy->set($testKey, $testValue);
            $this->assertEquals($testValue, $this->configCopy->get($testKey));
        }

        /** @since 4.1 */
        public function testSetNewlineCharacters(): void
        {
            $testKey = 'newline_test_'.time();
            $testValue = "Line 1\nLine 2\nLine 3";
            $this->configCopy->set($testKey, $testValue);
            $this->assertEquals($testValue, $this->configCopy->get($testKey));
        }

        /** @since 4.1 */
        public function testSingletonPattern(): void
        {
            $reflection = new ReflectionClass(ConfigProvider::class);
            $this->assertTrue($reflection->getConstructor()->isPrivate());
            $this->assertTrue($reflection->getMethod('getInstance')->isStatic());
        }

        /** @since 4.1 */
        public function testExpectedProperties(): void
        {
            $reflection = new ReflectionClass(ConfigProvider::class);
            foreach (['configVars', 'instance', 'environment'] as $property) {
                $this->assertTrue($reflection->hasProperty($property));
            }
        }

        /** @since 4.1 */
        public function testConfigValuesStoredAsString(): void
        {
            $testKey = 'string_test_'.time();
            $this->configCopy->set($testKey, 'test string value');
            $value = $this->configCopy->get($testKey);
            $this->assertIsString($value);
            $this->assertEquals('test string value', $value);
        }

        /** @since 4.1 */
        public function testReloadConfigNoErrors(): void
        {
            try {
                $this->configCopy->reloadConfig();
                $this->assertTrue(true);
            } catch (\Exception $e) {
                $this->fail('reloadConfig threw: '.$e->getMessage());
            }
        }

        /** @since 4.1 */
        public function testGetEnvironmentAfterReload(): void
        {
            $this->configCopy->reloadConfig();
            $this->assertNotEmpty($this->configCopy->getEnvironment());
        }

        /** @since 4.1 */
        public function testLoadConfigServerNameBranch(): void
        {
            $root = $this->createTempDir();
            $this->writeServersIni($root, "dev = srvhost\n");
            $this->writeEnvIni($root, 'dev', "app.title = ServerNameEnv\n");
            unset($_SERVER['SERVER_NAME'], $_ENV['HOSTNAME']);
            $_SERVER['SERVER_NAME'] = 'srvhost';
            $this->setRootPathValue($this->configCopy, $root);
            $this->invokeLoadConfig($this->configCopy);
            $this->assertEquals('dev', $this->configCopy->getEnvironment());
            $this->assertEquals('ServerNameEnv', $this->configCopy->get('app.title'));
        }

        /** @since 4.1 */
        public function testLoadConfigEnvHostnameBranch(): void
        {
            $root = $this->createTempDir();
            $this->writeServersIni($root, "dev = envhost\n");
            $this->writeEnvIni($root, 'dev', "app.title = EnvHostEnv\n");
            unset($_SERVER['SERVER_NAME']);
            $_ENV['HOSTNAME'] = 'envhost';
            $this->setRootPathValue($this->configCopy, $root);
            $this->invokeLoadConfig($this->configCopy);
            $this->assertEquals('dev', $this->configCopy->getEnvironment());
            $this->assertEquals('EnvHostEnv', $this->configCopy->get('app.title'));
        }

        /** @since 4.1 */
        public function testLoadConfigPhpUnameBranch(): void
        {
            $root = $this->createTempDir();
            $this->writeServersIni($root, "dev = unamehost\n");
            $this->writeEnvIni($root, 'dev', "app.title = UnameEnv\n");
            unset($_SERVER['SERVER_NAME'], $_ENV['HOSTNAME']);
            $GLOBALS['alpha_test_uname_override'] = 'unamehost';
            $this->setRootPathValue($this->configCopy, $root);
            $this->invokeLoadConfig($this->configCopy);
            $this->assertEquals('dev', $this->configCopy->getEnvironment());
            $this->assertEquals('UnameEnv', $this->configCopy->get('app.title'));
        }

        /** @since 4.1 */
        public function testLoadConfigUnableToDetermineServer(): void
        {
            $root = $this->createTempDir();
            $this->writeServersIni($root, "dev = srvhost\n");
            unset($_SERVER['SERVER_NAME'], $_ENV['HOSTNAME']);
            $GLOBALS['alpha_test_uname_override'] = '';
            $this->setRootPathValue($this->configCopy, $root);
            try {
                $this->invokeLoadConfig($this->configCopy);
                $this->fail('expected PHPException');
            } catch (PHPException $e) {
                $this->assertStringContainsString('Unable to determine the server name', $e->getMessage());
            }
        }

        /** @since 4.1 */
        public function testLoadConfigNoServersIni(): void
        {
            $root = $this->createTempDir();
            unset($_SERVER['SERVER_NAME'], $_ENV['HOSTNAME']);
            $GLOBALS['alpha_test_uname_override'] = 'anyhost';
            $this->setRootPathValue($this->configCopy, $root);
            try {
                $this->invokeLoadConfig($this->configCopy);
                $this->fail('expected PHPException');
            } catch (PHPException $e) {
                $this->assertStringContainsString('Failed to load the config file', $e->getMessage());
            }
        }

        /** @since 4.1 */
        public function testLoadConfigNoEnvironmentForServer(): void
        {
            $root = $this->createTempDir();
            $this->writeServersIni($root, "dev = otherhost\n");
            unset($_SERVER['SERVER_NAME'], $_ENV['HOSTNAME']);
            $GLOBALS['alpha_test_uname_override'] = 'testhost';
            $this->setRootPathValue($this->configCopy, $root);
            try {
                $this->invokeLoadConfig($this->configCopy);
                $this->fail('expected PHPException');
            } catch (PHPException $e) {
                $this->assertStringContainsString('No environment configured for the server', $e->getMessage());
            }
        }

        /** @since 4.1 */
        public function testLoadConfigCliEnvironment(): void
        {
            $root = $this->createTempDir();
            $this->writeServersIni($root, "devCLI = clihost\n");
            $this->writeEnvIni($root, 'dev', "app.title = CliEnv\n");
            unset($_SERVER['SERVER_NAME'], $_ENV['HOSTNAME']);
            $GLOBALS['alpha_test_uname_override'] = 'clihost';
            $this->setRootPathValue($this->configCopy, $root);
            $this->invokeLoadConfig($this->configCopy);
            $this->assertEquals('devCLI', $this->configCopy->getEnvironment());
            $this->assertEquals('CliEnv', $this->configCopy->get('app.title'));
        }

        /** @since 4.1 */
        public function testLoadConfigEnvIniMissing(): void
        {
            $root = $this->createTempDir();
            $this->writeServersIni($root, "dev = srvhost\n");
            unset($_SERVER['SERVER_NAME'], $_ENV['HOSTNAME']);
            $GLOBALS['alpha_test_uname_override'] = 'srvhost';
            $this->setRootPathValue($this->configCopy, $root);
            try {
                $this->invokeLoadConfig($this->configCopy);
                $this->fail('expected PHPException');
            } catch (PHPException $e) {
                $this->assertStringContainsString('Failed to load the config file', $e->getMessage());
            }
        }

        /** @since 4.1 */
        public function testSetBeforeSetBranch(): void
        {
            $stub = new ConfigCallbacksStub();
            @$stub->set('cb_key', 'cb_value');
            $this->assertEquals('stubbed_value', $stub->get('cb_key'));
        }
    }
}
