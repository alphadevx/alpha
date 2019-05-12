<?php

namespace Alpha\Test\Model\Type;

use Alpha\Model\Type\Timestamp;
use Alpha\Exception\IllegalArguementException;
use Alpha\Util\Config\ConfigProvider;
use PHPUnit\Framework\TestCase;

/**
 * Test case for the Timestamp data type.
 *
 * @since 1.0
 *
 * @author John Collins <dev@alphaframework.org>
 *
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2019, John Collins (founder of Alpha Framework).
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
     * @since 1.0
     */
    private $timestamp1;

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

        $config->set('app.default.datetime', 'now');
        $this->timestamp1 = new Timestamp();
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
        unset($this->timestamp1);
    }

    /**
     * Testing the constructor has set the Timestamp to today by default.
     *
     * @since 1.0
     */
    public function testDefaultTimestampValue()
    {
        $this->assertEquals(date('Y-m-d H:i:s'), $this->timestamp1->getValue(), 'testing the constructor has set the Timestamp to now by default');
    }

    /**
     * Testing the setValue method.
     *
     * @since 1.0
     */
    public function testSetValuePass()
    {
        $this->timestamp1->setTimestampValue(2000, 1, 1, 23, 33, 5);

        $this->assertEquals('2000-01-01 23:33:05', $this->timestamp1->getValue(), 'testing the setValue method');
    }

    /**
     * Testing the setValue method with a bad month.
     *
     * @since 1.0
     */
    public function testSetValueInvalidMonth()
    {
        try {
            $this->timestamp1->setTimestampValue(2000, 'blah', 1, 0, 0, 0);
            $this->fail('testing the setValue method with a bad month');
        } catch (IllegalArguementException $e) {
            $this->assertEquals('The month value blah provided is invalid!', $e->getMessage(), 'testing the setValue method with a bad month');
        }
    }

    /**
     * Testing the setValue method with a bad timestamp value (out of range).
     *
     * @since 1.0
     */
    public function testSetValueInvalidValue()
    {
        try {
            $this->timestamp1->setTimestampValue(2000, 13, 1, 0, 0, 0);
            $this->fail('testing the setValue method with a bad timestamp value (out of range)');
        } catch (IllegalArguementException $e) {
            $this->assertEquals('The day value 2000-13-1 provided is invalid!', $e->getMessage(), 'testing the setValue method with a bad timestamp value (out of range)');
        }
    }

    /**
     * Testing the populate_from_string method.
     *
     * @since 1.0
     */
    public function testPopulateFromString()
    {
        $this->timestamp1->populateFromString('2007-08-13 23:44:07');

        $this->assertEquals('2007-08-13 23:44:07', $this->timestamp1->getValue(), 'testing the populateFromString method');
    }

    /**
     * Testing that the validation will cause an invalid timestamp to fail on the constructor.
     *
     * @since 1.0
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
     * @since 1.0
     */
    public function testGetEuroValue()
    {
        $this->assertEquals(date('d/m/y'), $this->timestamp1->getEuroValue(), 'testing the get_euro_value method for converting to European timestamp format');
    }

    /**
     * Testing the getWeekday() method when the default constructor is used.
     *
     * @since 1.0
     */
    public function testGetWeekday()
    {
        $this->assertEquals(date('l'), $this->timestamp1->getWeekday(), 'testing the getWeekday() method when the default constructor is used');
    }

    /**
     * Testing the getUnixValue() method.
     *
     * @since 1.2.1
     */
    public function testGetUnixValue()
    {
        $timestamp = new Timestamp('2012-12-18 11:30:00');

        $this->assertEquals(1355830200, $timestamp->getUnixValue(), 'testing the getUnixValue() method');
    }

    /**
     * Testing the getTimeAway() method.
     *
     * @since 2.0
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
}
