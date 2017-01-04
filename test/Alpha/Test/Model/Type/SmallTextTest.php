<?php

namespace Alpha\Test\Model\Type;

use Alpha\Model\Type\SmallText;
use Alpha\Util\Helper\Validator;
use Alpha\Exception\IllegalArguementException;

/**
 * Test case for the SmallText data type.
 *
 * @since 1.0
 *
 * @author John Collins <dev@alphaframework.org>
 *
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
class SmallTextTest extends \PHPUnit_Framework_TestCase
{
    /**
     * A SmallText for testing.
     *
     * @var string
     *
     * @since 1.0
     */
    private $str1;

    /**
     * A helper string for username reg-ex validation tests.
     *
     * @var string
     *
     * @since 1.0
     */
    private $usernameHelper = 'Please provide a name for display on the website (only letters, numbers, and .-_ characters are allowed!).';

    /**
     * A helper string for email reg-ex validation tests.
     *
     * @var string
     *
     * @since 1.0
     */
    private $emailHelper = 'Please provide a valid e-mail address as your username';

    /**
     * A helper string for URL reg-ex validation tests.
     *
     * @var string
     *
     * @since 1.0
     */
    private $urlHelper = 'URLs must be in the format http://some_domain/ or left blank!';

    /**
     * Called before the test functions will be executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here.
     *
     * @since 1.0
     */
    protected function setUp()
    {
        $this->str1 = new SmallText();
    }

    /**
     * Called after the test functions are executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here.
     *
     * @since 1.0
     */
    protected function tearDown()
    {
        unset($this->str1);
    }

    /**
     * Testing the str constructor for acceptance of correct data.
     *
     * @since 1.0
     */
    public function testConstructorPass()
    {
        $this->str1 = new SmallText('A SmallText Value!');

        $this->assertEquals('A SmallText Value!', $this->str1->getValue(), 'testing the SmallText constructor for pass');
    }

    /**
     * Testing passing an invalid username string.
     *
     * @since 1.0
     */
    public function testSetUsernameValueInvalid()
    {
        try {
            $this->str1->setRule(Validator::REQUIRED_USERNAME);
            $this->str1->setSize(70);
            $this->str1->setHelper($this->usernameHelper);

            $this->str1->setValue('invalid user.');
            $this->fail('testing passing an invalid username string');
        } catch (IllegalArguementException $e) {
            $this->assertEquals($this->usernameHelper, $e->getMessage(), 'testing passing an invalid username string');
        }
    }

    /**
     * Testing passing a valid username string.
     *
     * @since 1.0
     */
    public function testSetUsernameValueValid()
    {
        try {
            $this->str1->setRule(Validator::REQUIRED_USERNAME);
            $this->str1->setSize(70);
            $this->str1->setHelper($this->usernameHelper);

            $this->str1->setValue('user_name.-test123gg');
        } catch (IllegalArguementException $e) {
            $this->fail('testing passing a valid username string: '.$e->getMessage());
        }
    }

    /**
     * Testing passing an invalid email string.
     *
     * @since 1.0
     */
    public function testSetEmailValueInvalid()
    {
        try {
            $this->str1->setRule(Validator::REQUIRED_EMAIL);
            $this->str1->setSize(70);
            $this->str1->setHelper($this->emailHelper);

            $this->str1->setValue('invalid email');
            $this->fail('testing passing an invalid email string');
        } catch (IllegalArguementException $e) {
            $this->assertEquals($this->emailHelper, $e->getMessage(), 'testing passing an invalid email string');
        }
    }

    /**
     * Testing passing a valid email string.
     *
     * @since 1.0
     */
    public function testSetEmailValueValid()
    {
        try {
            $this->str1->setRule(Validator::REQUIRED_EMAIL);
            $this->str1->setSize(70);
            $this->str1->setHelper($this->emailHelper);

            $this->str1->setValue('user@somewhere.com');
            $this->str1->setValue('user@somewhere.ie');
            $this->str1->setValue('user@somewhere.co.uk');
            $this->str1->setValue('user@somewhere.net');
            $this->str1->setValue('user@somewhere.org');
            $this->str1->setValue('some.user@somewhere.com');
            $this->str1->setValue('some.user@somewhere.ie');
            $this->str1->setValue('some.user@somewhere.co.uk');
            $this->str1->setValue('some.user@somewhere.net');
            $this->str1->setValue('some.user@somewhere.org');
        } catch (IllegalArguementException $e) {
            $this->fail('testing passing a valid email string: '.$e->getMessage());
        }
    }

    /**
     * Testing passing an invalid URL string.
     *
     * @since 1.0
     */
    public function testSetURLValueInvalid()
    {
        try {
            $this->str1->setRule(Validator::OPTIONAL_HTTP_URL);
            $this->str1->setHelper($this->urlHelper);

            $this->str1->setValue('invalid url');
            $this->fail('testing passing an invalid URL string');
        } catch (IllegalArguementException $e) {
            $this->assertEquals($this->urlHelper, $e->getMessage(), 'testing passing an invalid URL string');
        }
    }

    /**
     * Testing passing a valid URL string.
     *
     * @since 1.0
     */
    public function testSetURLValueValid()
    {
        try {
            $this->str1->setRule(Validator::OPTIONAL_HTTP_URL);
            $this->str1->setHelper($this->urlHelper);

            $this->str1->setValue('http://www.google.com/');
            $this->str1->setValue('http://slashdot.org/');
            $this->str1->setValue('http://www.yahoo.com/');
            $this->str1->setValue('http://www.design-ireland.net/');
            $this->str1->setValue('http://www.theregister.co.uk/');
            $this->str1->setValue('http://www.bbc.co.uk/');
        } catch (IllegalArguementException $e) {
            $this->fail('testing passing a valid URL string: '.$e->getMessage());
        }
    }

    /**
     * Testing the setSize method to see if validation fails.
     *
     * @since 1.0
     */
    public function testSetSizeInvalid()
    {
        $this->str1 = new SmallText();
        $this->str1->setSize(4);

        try {
            $this->str1->setValue('Too many characters!');
            $this->fail('testing the setSize method to see if validation fails');
        } catch (IllegalArguementException $e) {
            $this->assertEquals('Not a valid smalltext value!', $e->getMessage(), 'testing the setSize method to see if validation fails');
        }
    }

    /**
     * Testing the __toString method.
     *
     * @since 1.0
     */
    public function testToString()
    {
        $this->str1 = new SmallText('__toString result');

        $this->assertEquals('The value of __toString result', 'The value of '.$this->str1, 'testing the __toString method');
    }

    /**
     * Testing to see if the password setter/inspector is working.
     *
     * @since 1.0
     */
    public function testIsPassword()
    {
        $this->str1->isPassword();

        $this->assertTrue($this->str1->checkIsPassword(), 'testing to see if the password setter/inspector is working');
    }

    /**
     * Testing to see that isPassword makes the string required.
     *
     * @since 1.0
     */
    public function testIsPasswordRequired()
    {
        $this->str1->isPassword();

        try {
            $this->str1->setValue('');
            $this->fail('testing to see that isPassword makes the string required');
        } catch (IllegalArguementException $e) {
            $this->assertEquals('Password is required!', $e->getMessage(), 'testing to see that isPassword makes the string required');
        }
    }
}
