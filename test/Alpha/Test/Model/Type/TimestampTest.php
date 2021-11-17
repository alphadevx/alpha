<?php

namespace Alpha\Test\Model\Type;

use Alpha\Model\Type\Timestamp;
use Alpha\Exception\IllegalArguementException;
use Alpha\Util\Config\ConfigProvider;
use PHPUnit\Framework\TestCase;

/**
 * Test case for the Timestamp data type.
 *
 *
 *
 * @author John Collins <dev@alphaframework.org>
 *
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
class TimestampTest extends TestCase
{
    /**
     * An Timestamp for testing.
     *
     * @var Timestamp
     *
     */
    private $timestamp1;

    /**
     * Called before the test functions will be executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here.
     *
     */
    protected function setUp(): void
    {
        $config = ConfigProvider::getInstance();

        $config->set('app.default.datetime', 'now');
        $this->timestamp1 = new Timestamp();
    }

    /**
     * Called after the test functions are executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here.
     *
     */
    protected function tearDown(): void
    {
        unset($this->timestamp1);
    }

    /**
     * Testing the constructor has set the Timestamp to today by default.
     *
     */
    public function testDefaultTimestampValue()
    {
        $this->assertEquals(date('Y-m-d H:i:s'), $this->timestamp1->getValue(), 'testing the constructor has set the Timestamp to now by default');
    }

    /**
     * Testing the setValue method.
     *
     */
    public function testSetValuePass()
    {
        $this->timestamp1->setTimestampValue(2000, 1, 1, 23, 33, 5);

        $this->assertEquals('2000-01-01 23:33:05', $this->timestamp1->getValue(), 'testing the setValue method');
    }

    /**
     * Testing the setValue method with a bad timestamp value (out of range).
     *
     */
    public function testSetValueInvalidValue()
    {
        try {
            $this->timestamp1->setTimestampValue(26, 12, 1, 0, 0, 0);
            $this->fail('testing the setValue method with a bad timestamp value (out of range)');
        } catch (IllegalArguementException $e) {
            $this->assertEquals('The year value 26 provided is invalid!', $e->getMessage(), 'testing the setValue method with a bad timestamp value (out of range)');
        }

        try {
            $this->timestamp1->setTimestampValue(2000, 13, 1, 0, 0, 0);
            $this->fail('testing the setValue method with a bad timestamp value (out of range)');
        } catch (IllegalArguementException $e) {
            $this->assertEquals('The month value 13 provided is invalid!', $e->getMessage(), 'testing the setValue method with a bad timestamp value (out of range)');
        }

        try {
            $this->timestamp1->setTimestampValue(2000, 12, 100, 0, 0, 0);
            $this->fail('testing the setValue method with a bad timestamp value (out of range)');
        } catch (IllegalArguementException $e) {
            $this->assertEquals('The day value 100 provided is invalid!', $e->getMessage(), 'testing the setValue method with a bad timestamp value (out of range)');
        }

        try {
            $this->timestamp1->setTimestampValue(2000, 1, 1, 25, 0, 0);
            $this->fail('testing the setValue method with a bad timestamp value (out of range)');
        } catch (IllegalArguementException $e) {
            $this->assertEquals('The hour value 25 provided is invalid!', $e->getMessage(), 'testing the setValue method with a bad timestamp value (out of range)');
        }

        try {
            $this->timestamp1->setTimestampValue(2000, 7, 1, 23, 99, 0);
            $this->fail('testing the setValue method with a bad timestamp value (out of range)');
        } catch (IllegalArguementException $e) {
            $this->assertEquals('The minute value 99 provided is invalid!', $e->getMessage(), 'testing the setValue method with a bad timestamp value (out of range)');
        }

        try {
            $this->timestamp1->setTimestampValue(2000, 4, 6, 0, 59, 61);
            $this->fail('testing the setValue method with a bad timestamp value (out of range)');
        } catch (IllegalArguementException $e) {
            $this->assertEquals('The second value 61 provided is invalid!', $e->getMessage(), 'testing the setValue method with a bad timestamp value (out of range)');
        }
    }

    /**
     * Testing the populate_from_string method.
     *
     */
    public function testPopulateFromString()
    {
        $this->timestamp1->populateFromString('2007-08-13 23:44:07');

        $this->assertEquals('2007-08-13 23:44:07', $this->timestamp1->getValue(), 'testing the populateFromString method');

        try {
            $this->timestamp1->populateFromString('2007-08-40 23:44:07');
            $this->fail('testing the populateFromString method with a bad date value');
        } catch (IllegalArguementException $e) {
            $this->assertEquals('The day value 40 provided is invalid!', $e->getMessage(), 'testing the populateFromString method with a bad date value');
        }

        try {
            $this->timestamp1->populateFromString('2007-08-aa 23:44:07');
            $this->fail('testing the populateFromString method with a bad date value');
        } catch (IllegalArguementException $e) {
            $this->assertEquals('The day value 0 provided is invalid!', $e->getMessage(), 'testing the populateFromString method with a bad date value');
        }

        try {
            $this->timestamp1->populateFromString('bad');
            $this->fail('testing the populateFromString method with a bad date value');
        } catch (IllegalArguementException $e) {
            $this->assertEquals('Not a valid timestamp value!  A timestamp should be in the format YYYY-MM-DD HH:MM:SS.', $e->getMessage(), 'testing the populateFromString method with a bad date value');
        }

        $this->timestamp1->populateFromString('0000-00-00 00:00:00');

        $this->assertEquals('0000-00-00 00:00:00', $this->timestamp1->getValue(), 'testing the populateFromString method with empty value');

        $this->timestamp1->populateFromString('');

        $this->assertEquals('0000-00-00 00:00:00', $this->timestamp1->getValue(), 'testing the populateFromString method with empty value');
    }

    /**
     * Testing that the validation will cause an invalid timestamp to fail on the constructor.
     *
     */
    public function testValidationOnConstructor()
    {
        try {
            $timestamp = new Timestamp('blah');
            $this->fail('testing that the validation will cause an invalid timestamp to fail on the constructor');
        } catch (IllegalArguementException $e) {
            $this->assertTrue(true, 'testing that the validation will cause an invalid timestamp to fail on the constructor');
        }
    }

    /**
     * Testing the get_euro_value method for converting to European timestamp format.
     *
     */
    public function testGetEuroValue()
    {
        $this->assertEquals(date('d/m/y'), $this->timestamp1->getEuroValue(), 'testing the get_euro_value method for converting to European timestamp format');
    }

    /**
     * Testing the getWeekday() method when the default constructor is used.
     *
     */
    public function testGetWeekday()
    {
        $this->assertEquals(date('l'), $this->timestamp1->getWeekday(), 'testing the getWeekday() method when the default constructor is used');
    }

    /**
     * Testing the getUnixValue() method.
     *
     */
    public function testGetUnixValue()
    {
        $timestamp = new Timestamp('2012-12-18 11:30:00');

        $this->assertEquals(1355830200, $timestamp->getUnixValue(), 'testing the getUnixValue() method');
    }

    /**
     * Testing the getTimeAway() method.
     *
     */
    public function testGetTimeAway()
    {
        $timestamp = new Timestamp();
        $timestamp->setDate($timestamp->getYear() + 1, $timestamp->getMonth(), $timestamp->getDay());

        $this->assertEquals('1 year from now', $timestamp->getTimeAway(), 'testing the getTimeAway() method');

        $timestamp = new Timestamp();
        $timestamp->setDate($timestamp->getYear() - 2, $timestamp->getMonth(), $timestamp->getDay());

        $this->assertEquals('2 years ago', $timestamp->getTimeAway(), 'testing the getTimeAway() method');
    }

    /**
     * Testing the setDate method.
     *
     */
    public function testSetDateFail()
    {
        try {
            $this->timestamp1->setDate(2000, 1, 99);
            $this->fail('testing the setDate method with a bad value');
        } catch (IllegalArguementException $e) {
            $this->assertEquals('The day value 99 provided is invalid!', $e->getMessage(), 'testing the setDate method with a bad value');
        }
    }

    /**
     * Testing the setTime method.
     *
     */
    public function testSetTimePass()
    {
        $this->timestamp1->setTime(23, 33, 5);

        $this->assertEquals('23:33:05', $this->timestamp1->getTime(), 'testing the setTime method');
    }

    /**
     * Testing the setTime method.
     *
     */
    public function testSetTimeFail()
    {
        try {
            $this->timestamp1->setTime(99, 1, 1);
            $this->fail('testing the setTime method with a bad value');
        } catch (IllegalArguementException $e) {
            $this->assertEquals('The hour value 99 provided is invalid!', $e->getMessage(), 'testing the setTime method with a bad value');
        }

        try {
            $this->timestamp1->setTime(1, 99, 1);
            $this->fail('testing the setTime method with a bad value');
        } catch (IllegalArguementException $e) {
            $this->assertEquals('The minute value 99 provided is invalid!', $e->getMessage(), 'testing the setTime method with a bad value');
        }

        try {
            $this->timestamp1->setTime(1, 1, 99);
            $this->fail('testing the setTime method with a bad value');
        } catch (IllegalArguementException $e) {
            $this->assertEquals('The second value 99 provided is invalid!', $e->getMessage(), 'testing the setTime method with a bad value');
        }
    }

    /**
     * Testing the getYear() method when the default constructor is used.
     *
     * @since 4.0
     */
    public function testGetYear()
    {
        $this->assertEquals(date('Y'), $this->timestamp1->getYear(), 'testing the getYear() method when the default constructor is used');
    }

    /**
     * Testing the getMonth() method when the default constructor is used.
     *
     * @since 4.0
     */
    public function testGetMonth()
    {
        $this->assertEquals(date('m'), $this->timestamp1->getMonth(), 'testing the getMonth() method when the default constructor is used');
    }

    /**
     * Testing the getMonthDay() method when the default constructor is used.
     *
     * @since 4.0
     */
    public function testGetDay()
    {
        $this->assertEquals(date('d'), $this->timestamp1->getDay(), 'testing the getDay() method when the default constructor is used');
    }
}
