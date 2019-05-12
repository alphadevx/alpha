<?php

namespace Alpha\Test\Model\Type;

use Alpha\Model\Type\Text;
use Alpha\Util\Helper\Validator;
use Alpha\Exception\IllegalArguementException;
use PHPUnit\Framework\TestCase;

/**
 * Test case for the Text data type.
 *
 * @since 1.0
 *
 * @author John Collins <dev@alphaframework.org>
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
class TextTest extends TestCase
{
    /**
     * A Text for testing.
     *
     * @var Text
     *
     * @since 1.0
     */
    private $txt;

    /**
     * Called before the test functions will be executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here.
     *
     * @since 1.0
     */
    protected function setUp(): void
    {
        $this->txt = new Text();
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
        unset($this->txt);
    }

    /**
     * Testing the text constructor for acceptance of correct data.
     *
     * @since 1.0
     */
    public function testConstructorPass()
    {
        $this->txt = new Text('A Text Value!');

        $this->assertEquals('A Text Value!', $this->txt->getValue(), 'testing the Text constructor for pass');
    }

    /**
     * Testing the text setValue method with bad data when the default validation rule is overridden.
     *
     * @since 1.0
     */
    public function testSetValueFail()
    {
        $this->txt->setRule(Validator::REQUIRED_TEXT);

        try {
            $this->txt->setValue('');
            $this->fail('testing the text setValue method with bad data when the default validation rule is overridden');
        } catch (IllegalArguementException $e) {
            $this->assertTrue(true, 'testing the text setValue method with bad data when the default validation rule is overridden');
        }
    }

    /**
     * Testing the text setValue method with good data when the default validation rule is overridden.
     *
     * @since 1.0
     */
    public function testSetValuePass()
    {
        $this->txt->setRule(Validator::REQUIRED_TEXT);

        try {
            $this->txt->setValue('Some text');

            $this->assertEquals('Some text', $this->txt->getValue(), 'testing the text setValue method with good data when the default validation rule is overridden');
        } catch (IllegalArguementException $e) {
            $this->fail('testing the text setValue method with good data when the default validation rule is overridden');
        }
    }

    /**
     * Testing the setSize method to see if validation fails.
     *
     * @since 1.0
     */
    public function testSetSizeInvalid()
    {
        $this->txt = new Text();
        $this->txt->setSize(4);

        try {
            $this->txt->setValue('Too many characters!');
            $this->fail('testing the setSize method to see if validation fails');
        } catch (IllegalArguementException $e) {
            $this->assertEquals('Not a valid text value!', $e->getMessage(), 'testing the setSize method to see if validation fails');
        }
    }

    /**
     * Testing the __toString method.
     *
     * @since 1.0
     */
    public function testToString()
    {
        $this->txt = new Text('__toString result');

        $this->assertEquals('The value of __toString result', 'The value of '.$this->txt, 'testing the __toString method');
    }
}
