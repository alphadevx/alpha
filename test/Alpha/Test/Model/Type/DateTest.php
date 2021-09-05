<?php

namespace Alpha\Test\Model\Type;

use Alpha\Model\Type\Date;
use Alpha\Exception\IllegalArguementException;
use Alpha\Util\Config\ConfigProvider;
use PHPUnit\Framework\TestCase;

/**
 * Test case for the Date data type.
 *
 * @since 1.0
 *
 * @author John Collins <dev@alphaframework.org>
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2021, John Collins (founder of Alpha Framework).
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
class DateTest extends TestCase
{
    /**
     * An Date for testing.
     *
     * @var \Alpha\Model\Type\Date
     *
     * @since 1.0
     */
    private $date1;

    /**
     * Called before the test functions will be executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here.
     *
     * @since 1.0
     */
    protected function setUp(): void
    {
        $config = ConfigProvider::getInstance();

        // override setting to ensure dates default to now
        $config->set('app.default.datetime', 'now');

        $this->date1 = new Date();
    }

    /**
     * Called after the test functions are executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here.
     *
     * @since 1.0
     */
    protected function tearDown(): void
    {
        unset($this->date1);
    }

    /**
     * Testing the constructor has set the Date to today by default.
     *
     * @since 1.0
     */
    public function testDefaultDateValue()
    {
        $this->assertEquals(date('Y-m-d'), $this->date1->getValue(), 'testing the constructor has set the Date to today by default');
    }

    /**
     * Testing the setValue method.
     *
     * @since 1.0
     */
    public function testSetValuePass()
    {
        $this->date1->setDateValue(2000, 1, 1);

        $this->assertEquals('2000-01-01', $this->date1->getValue(), 'testing the setValue method');
    }

    /**
     * Testing the setValue method with a bad date value (out of range).
     *
     * @since 1.0
     */
    public function testSetValueInvalidValue()
    {
        try {
            $this->date1->setDateValue(2000, 13, 1);
            $this->fail('testing the setDateValue method with a bad date value (out of range)');
        } catch (IllegalArguementException $e) {
            $this->assertEquals('The day value 2000-13-1 provided is invalid!', $e->getMessage(), 'testing the setDateValue method with a bad date value (out of range)');
        }

        try {
            $date = new Date('2000-13-01');
            $this->fail('testing the constructor method with a bad date value (out of range)');
        } catch (IllegalArguementException $e) {
            $this->assertEquals('The date value 2000-13-01 provided is invalid!', $e->getMessage(), 'testing the constructor method with a bad date value (out of range)');
        }

        try {
            $this->date1->setValue('2000-13-01');
            $this->fail('testing the setValue method with a bad date value (out of range)');
        } catch (IllegalArguementException $e) {
            $this->assertEquals('The date value 2000-13-01 provided is invalid!', $e->getMessage(), 'testing the setValue method with a bad date value (out of range)');
        }
    }

    /**
     * Testing the populateFromString method.
     *
     * @since 1.0
     */
    public function testPopulateFromString()
    {
        $this->date1->populateFromString('2007-08-13');

        $this->assertEquals('2007-08-13', $this->date1->getValue(), 'testing the populateFromString method');

        try {
            $this->date1->populateFromString('2007-08-40');
            $this->fail('testing the populateFromString method with a bad date value');
        } catch (IllegalArguementException $e) {
            $this->assertEquals('The date value 2007-08-40 provided is invalid!', $e->getMessage(), 'testing the populateFromString method with a bad date value');
        }

        try {
            $this->date1->populateFromString('2007-08-aa');
            $this->fail('testing the populateFromString method with a bad date value');
        } catch (IllegalArguementException $e) {
            $this->assertEquals('The date value 2007-08-00 provided is invalid!', $e->getMessage(), 'testing the populateFromString method with a bad date value');
        }

        try {
            $this->date1->populateFromString('bad');
            $this->fail('testing the populateFromString method with a bad date value');
        } catch (IllegalArguementException $e) {
            $this->assertEquals('Invalid Date value [bad] provided!', $e->getMessage(), 'testing the populateFromString method with a bad date value');
        }
    }

    /**
     * Testing that the validation will cause an invalid date to fail on the constructor.
     *
     * @since 1.0
     */
    public function testValidationOnConstructor()
    {
        try {
            $date = new Date('blah');
            $this->fail('testing that the validation will cause an invalid date to fail on the constructor');
        } catch (IllegalArguementException $e) {
            $this->assertTrue(true, 'testing that the validation will cause an invalid date to fail on the constructor');
        }
    }

    /**
     * Testing the getEuroValue method for converting to European date format.
     *
     * @since 1.0
     */
    public function testGetEuroValue()
    {
        $this->assertEquals(date('d/m/y'), $this->date1->getEuroValue(), 'testing the getEuroValue method for converting to European date format');
    }

    /**
     * Testing the getWeekday() method when the default constructor is used.
     *
     * @since 1.0
     */
    public function testGetWeekday()
    {
        $this->assertEquals(date('l'), $this->date1->getWeekday(), 'testing the getWeekday() method when the default constructor is used');
    }

    /**
     * Testing the getUnixValue() method.
     *
     * @since 1.2.1
     */
    public function testGetUnixValue()
    {
        $date = new Date('2012-12-10');

        $this->assertEquals('1355097600', $date->getUnixValue(), 'testing the getUnixValue() method');
    }

    /**
     * Testing the getUSValue method for converting to European date format.
     *
     * @since 1.2.1
     */
    public function testGetUSValue()
    {
        $this->assertEquals(date('m/d/y'), $this->date1->getUSValue(), 'testing the getUSValue method for converting to US date format');
    }

    /**
     * Testing the increment method
     *
     * @since 3.1
     */
    public function testIncrement()
    {
        $this->date1 = new Date('2020-01-01');
        $this->date1->increment('7 day');
        $this->assertEquals('2020-01-08', $this->date1->getValue(), 'testing the increment method');
        $this->date1->increment('1 month');
        $this->assertEquals('2020-02-08', $this->date1->getValue(), 'testing the increment method');
        $this->date1->increment('1 year');
        $this->assertEquals('2021-02-08', $this->date1->getValue(), 'testing the increment method');
    }

    /**
     * Testing the getStartAndEndDate method
     *
     * @since 3.1
     */
    public function testGetStartAndEndDate()
    {
        $this->assertEquals('2019-12-30', Date::getStartAndEndDate(1, 2020)['start'], 'testing the getStartAndEndDate method');
        $this->assertEquals('2020-01-05', Date::getStartAndEndDate(1, 2020)['end'], 'testing the getStartAndEndDate method');
        $this->assertEquals('2019-12-23', Date::getStartAndEndDate(52, 2019)['start'], 'testing the getStartAndEndDate method');
        $this->assertEquals('2019-12-29', Date::getStartAndEndDate(52, 2019)['end'], 'testing the getStartAndEndDate method');
        $this->assertEquals('2020-02-03', Date::getStartAndEndDate(6, 2020)['start'], 'testing the getStartAndEndDate method');
        $this->assertEquals('2020-02-09', Date::getStartAndEndDate(6, 2020)['end'], 'testing the getStartAndEndDate method');
    }
}
