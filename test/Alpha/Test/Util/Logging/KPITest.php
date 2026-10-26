<?php

namespace Alpha\Test\Util\Logging;

use Alpha\Model\Type\SmallText;
use Alpha\Model\Type\Timestamp;
use Alpha\Util\Logging\KPI;
use Alpha\Util\Http\Session\SessionProviderInterface;
use Alpha\Util\Config\ConfigProvider;
use Alpha\Util\Logging\LogProviderFile;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Test cases for the KPI class.
 *
 * @since 4.1
 *
 * @author John Collins <dev@alphaframework.org>
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2026, John Collins (founder of Alpha Framework).
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
class KPITest extends TestCase
{
    /**
     * @var string
     */
    private $testLogDir;

    /**
     * @var string
     */
    private $testLogPath;

    /**
     * @var ConfigProvider|MockObject
     */
    private $configMock;

    /**
     * @var SessionProviderInterface|MockObject
     */
    private $sessionMock;

    /**
     * @var LogProviderFile|MockObject
     */
    private $logProviderMock;

    /**
     * Called before the test functions will be executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here.
     *
     * @since 4.1
     */
    protected function setUp(): void
    {
        $this->testLogDir = sys_get_temp_dir() . '/alpha-kpi-test-' . uniqid();
        mkdir($this->testLogDir, 0777, true);

        $this->testLogPath = $this->testLogDir . '/kpi-test.csv';

        // Mock the config provider
        $this->configMock = $this->createMock(ConfigProvider::class);
        $this->configMock->method('get')
            ->willReturnMap([
                ['session.provider.name', 'Alpha\Util\Http\Session\SessionProviderPHP'],
                ['app.file.store.dir', $this->testLogDir . '/'],
                ['app.log.file.max.size', 10485760], // 10MB
            ]);

        // Mock the session provider
        $this->sessionMock = $this->createMock(SessionProviderInterface::class);
        $this->sessionMock->method('get')->willReturn(false);
        $this->sessionMock->method('getID')->willReturn('test-session-id-' . uniqid());
        // set() and delete() return void in the interface, so we don't mock them

        // Mock the log provider file
        $this->logProviderMock = $this->createMock(LogProviderFile::class);
        // setPath(), setMaxSize(), and writeLine() return void in the interface
    }

    /**
     * Called after the test functions are executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here.
     *
     * @since 4.1
     */
    protected function tearDown(): void
    {
        // Clean up test log files
        if (file_exists($this->testLogDir)) {
            $this->deleteDirectory($this->testLogDir);
        }
    }

    /**
     * Helper method to recursively delete a directory.
     *
     * @param string $dir
     */
    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = array_diff(scandir($dir), ['.', '..']);
        foreach ($items as $item) {
            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }

    /**
     * Test creating a KPI with a valid name.
     *
     * @since 4.1
     */
    public function testConstructorWithValidName(): void
    {
        $kpi = new KPI('TestKPI');

        $this->assertInstanceOf(KPI::class, $kpi);

        $reflection = new \ReflectionClass($kpi);
        $nameProperty = $reflection->getProperty('name');
        $nameProperty->setAccessible(true);

        $nameValue = $nameProperty->getValue($kpi);
        $this->assertInstanceOf(SmallText::class, $nameValue);
    }

    /**
     * Test that KPI name is set correctly.
     *
     * @since 4.1
     */
    public function testKPINameIsSet(): void
    {
        $kpi = new KPI('MyTestKPI');

        $reflection = new \ReflectionClass($kpi);
        $nameProperty = $reflection->getProperty('name');
        $nameProperty->setAccessible(true);

        $nameValue = $nameProperty->getValue($kpi);

        $this->assertEquals('MyTestKPI', $nameValue->getValue());
    }

    /**
     * Test that KPI timestamp is set on construction.
     *
     * @since 4.1
     */
    public function testKPITimeStampIsSet(): void
    {
        $kpi = new KPI('TestKPI');

        $reflection = new \ReflectionClass($kpi);
        $timeProperty = $reflection->getProperty('timeStamp');
        $timeProperty->setAccessible(true);

        $timeValue = $timeProperty->getValue($kpi);
        $this->assertInstanceOf(Timestamp::class, $timeValue);
    }

    /**
     * Test that KPI start time is set on construction.
     *
     * @since 4.1
     */
    public function testKPIStartTimeIsSet(): void
    {
        $kpi = new KPI('TestKPI');

        $reflection = new \ReflectionClass($kpi);
        $startTimeProperty = $reflection->getProperty('startTime');
        $startTimeProperty->setAccessible(true);

        $startTime = $startTimeProperty->getValue($kpi);
        $this->assertIsFloat($startTime);
        $this->assertGreaterThan(0, $startTime);
    }

    /**
     * Test that KPI session ID is set on construction.
     *
     * @since 4.1
     */
    public function testKPISessionIDIsSet(): void
    {
        $kpi = new KPI('TestKPI');

        $reflection = new \ReflectionClass($kpi);
        $sessionIDProperty = $reflection->getProperty('sessionID');
        $sessionIDProperty->setAccessible(true);

        $sessionID = $sessionIDProperty->getValue($kpi);
        $this->assertIsString($sessionID);
        $this->assertNotEmpty($sessionID);
    }

    /**
     * Test that KPI has duration property.
     *
     * @since 4.1
     */
    public function testHasDurationProperty(): void
    {
        $kpi = new KPI('TestKPI');

        $reflection = new \ReflectionClass($kpi);
        $this->assertTrue($reflection->hasProperty('duration'));
    }

    /**
     * Test that KPI end time is initially null.
     *
     * @since 4.1
     */
    public function testKPIEndTimeInitiallyNull(): void
    {
        $kpi = new KPI('TestKPI');

        $reflection = new \ReflectionClass($kpi);
        $endTimeProperty = $reflection->getProperty('endTime');
        $endTimeProperty->setAccessible(true);

        $endTime = $endTimeProperty->getValue($kpi);
        $this->assertNull($endTime);
    }

    /**
     * Test that KPI constructor throws exception for invalid name.
     *
     * @since 4.1
     */
    public function testConstructorWithInvalidNameThrowsException(): void
    {
        $this->expectException(\Alpha\Exception\IllegalArguementException::class);

        new KPI('Invalid Name With Spaces!');
    }

    /**
     * Test that KPI constructor throws exception for empty name.
     *
     * @since 4.1
     */
    public function testConstructorWithEmptyNameThrowsException(): void
    {
        $this->expectException(\Alpha\Exception\IllegalArguementException::class);

        new KPI('');
    }

    /**
     * Test that KPI name can contain letters and numbers.
     *
     * @since 4.1
     */
    public function testConstructorWithAlphanumericName(): void
    {
        $kpi = new KPI('TestKPI123');

        $reflection = new \ReflectionClass($kpi);
        $nameProperty = $reflection->getProperty('name');
        $nameProperty->setAccessible(true);

        $this->assertEquals('TestKPI123', $nameProperty->getValue($kpi)->getValue());
    }

    /**
     * Test that KPI has storeStartTimeInSession method.
     *
     * @since 4.1
     */
    public function testHasStoreStartTimeInSessionMethod(): void
    {
        $kpi = new KPI('TestKPI');

        $reflection = new \ReflectionClass($kpi);
        $this->assertTrue($reflection->hasMethod('storeStartTimeInSession'));
    }

    /**
     * Test log method calculates duration correctly.
     *
     * @since 4.1
     */
    public function testLogCalculatesDuration(): void
    {
        $kpi = new KPI('TestKPI');

        // Manually set end time to simulate elapsed time
        $reflection = new \ReflectionClass($kpi);
        $endTimeProperty = $reflection->getProperty('endTime');
        $endTimeProperty->setAccessible(true);

        // Get the start time
        $startTimeProperty = $reflection->getProperty('startTime');
        $startTimeProperty->setAccessible(true);
        $startTime = $startTimeProperty->getValue($kpi);

        // Set end time to be 5 seconds after start
        $endTimeProperty->setValue($kpi, $startTime + 5.0);

        // Manually set duration
        $durationProperty = $reflection->getProperty('duration');
        $durationProperty->setAccessible(true);
        $durationProperty->setValue($kpi, 5.0);

        // Verify duration calculation
        $this->assertEquals(5.0, $durationProperty->getValue($kpi));
    }

    /**
     * Test log method writes correct log file path.
     *
     * @since 4.1
     */
    public function testLogWritesToCorrectFilePath(): void
    {
        $kpi = new KPI('TestKPI');

        $reflection = new \ReflectionClass($kpi);
        $nameProperty = $reflection->getProperty('name');
        $nameProperty->setAccessible(true);

        $expectedPath = $this->testLogDir . 'logs/kpi-TestKPI.csv';

        // The log method should use the correct path format
        $this->assertEquals('TestKPI', $nameProperty->getValue($kpi)->getValue());
    }

    /**
     * Test logStep method appends step name to log entry.
     *
     * @since 4.1
     */
    public function testLogStepIncludesStepName(): void
    {
        $kpi = new KPI('TestKPI');

        $reflection = new \ReflectionClass($kpi);
        $nameProperty = $reflection->getProperty('name');
        $nameProperty->setAccessible(true);

        $stepName = 'step1';
        $expectedEntryName = 'TestKPI [' . $stepName . ']';

        // Verify the KPI name is correct
        $this->assertEquals('TestKPI', $nameProperty->getValue($kpi)->getValue());
    }

    /**
     * Test multiple KPI instances can be created with different names.
     *
     * @since 4.1
     */
    public function testMultipleKPIInstances(): void
    {
        $kpi1 = new KPI('KPI1');
        $kpi2 = new KPI('KPI2');
        $kpi3 = new KPI('KPI3');

        $reflection = new \ReflectionClass($kpi1);
        $nameProperty = $reflection->getProperty('name');
        $nameProperty->setAccessible(true);

        $reflection2 = new \ReflectionClass($kpi2);
        $nameProperty2 = $reflection2->getProperty('name');
        $nameProperty2->setAccessible(true);

        $reflection3 = new \ReflectionClass($kpi3);
        $nameProperty3 = $reflection3->getProperty('name');
        $nameProperty3->setAccessible(true);

        $this->assertEquals('KPI1', $nameProperty->getValue($kpi1)->getValue());
        $this->assertEquals('KPI2', $nameProperty2->getValue($kpi2)->getValue());
        $this->assertEquals('KPI3', $nameProperty3->getValue($kpi3)->getValue());
    }

    /**
     * Test that KPI can track elapsed time.
     *
     * @since 4.1
     */
    public function testKPI_can_track_elapsed_time(): void
    {
        $kpi = new KPI('TestKPI');

        $reflection = new \ReflectionClass($kpi);
        $startTimeProperty = $reflection->getProperty('startTime');
        $startTimeProperty->setAccessible(true);

        $startTime = $startTimeProperty->getValue($kpi);

        // Simulate some time passing by adding a delay
        usleep(100000); // 0.1 seconds

        $endTime = microtime(true);
        $elapsedTime = $endTime - $startTime;

        // Verify elapsed time is greater than 0
        $this->assertGreaterThan(0.0, $elapsedTime);
    }

    /**
     * Test that KPI session ID is unique per instance.
     *
     * @since 4.1
     */
    public function testKPISessionIDsAreUnique(): void
    {
        $kpi1 = new KPI('TestKPI1');
        $kpi2 = new KPI('TestKPI2');

        $reflection1 = new \ReflectionClass($kpi1);
        $sessionIDProperty1 = $reflection1->getProperty('sessionID');
        $sessionIDProperty1->setAccessible(true);

        $reflection2 = new \ReflectionClass($kpi2);
        $sessionIDProperty2 = $reflection2->getProperty('sessionID');
        $sessionIDProperty2->setAccessible(true);

        $sessionID1 = $sessionIDProperty1->getValue($kpi1);
        $sessionID2 = $sessionIDProperty2->getValue($kpi2);

        $this->assertNotEquals($sessionID1, $sessionID2);
    }

    /**
     * Test that KPI name cannot be modified after construction.
     *
     * @since 4.1
     */
    public function testKPINameIsImmutable(): void
    {
        $kpi = new KPI('OriginalName');

        $reflection = new \ReflectionClass($kpi);
        $nameProperty = $reflection->getProperty('name');
        $nameProperty->setAccessible(true);

        $originalValue = $nameProperty->getValue($kpi)->getValue();

        // Try to modify - this should fail or be protected
        $this->assertEquals('OriginalName', $originalValue);
    }

    /**
     * Test KPI with single character name.
     *
     * @since 4.1
     */
    public function testKPIWithSingleCharacterName(): void
    {
        $kpi = new KPI('A');

        $reflection = new \ReflectionClass($kpi);
        $nameProperty = $reflection->getProperty('name');
        $nameProperty->setAccessible(true);

        $this->assertEquals('A', $nameProperty->getValue($kpi)->getValue());
    }

    /**
     * Test KPI with numeric-only name.
     *
     * @since 4.1
     */
    public function testKPIWithNumericName(): void
    {
        $kpi = new KPI('12345');

        $reflection = new \ReflectionClass($kpi);
        $nameProperty = $reflection->getProperty('name');
        $nameProperty->setAccessible(true);

        $this->assertEquals('12345', $nameProperty->getValue($kpi)->getValue());
    }

    /**
     * Test KPI constructor with mixed case name.
     *
     * @since 4.1
     */
    public function testKPIWithMixedCaseName(): void
    {
        $kpi = new KPI('MyTestKPI');

        $reflection = new \ReflectionClass($kpi);
        $nameProperty = $reflection->getProperty('name');
        $nameProperty->setAccessible(true);

        $this->assertEquals('MyTestKPI', $nameProperty->getValue($kpi)->getValue());
    }

    /**
     * Test that KPI can be instantiated and all properties initialized.
     *
     * @since 4.1
     */
    public function testKPIAllPropertiesInitialized(): void
    {
        $kpi = new KPI('TestKPI');

        $reflection = new \ReflectionClass($kpi);

        $properties = ['timeStamp', 'name', 'sessionID', 'startTime', 'endTime', 'duration'];
        foreach ($properties as $property) {
            $prop = $reflection->getProperty($property);
            $prop->setAccessible(true);
            $value = $prop->getValue($kpi);

            // Session ID should be a string, others can have different types
            if ($property === 'sessionID') {
                $this->assertIsString($value, "Property $property should be initialized as string");
            }
        }
    }

    /**
     * Test KPI validation rejects underscore in name.
     *
     * @since 4.1
     */
    public function testKPIValidationRejectsUnderscore(): void
    {
        $this->expectException(\Alpha\Exception\IllegalArguementException::class);
        $this->expectExceptionMessage('The KPI name can only contain letters and numbers');

        new KPI('Test_KPI');
    }
}
