<?php

namespace Alpha\Test\Model;

use Alpha\Model\Person;
use Alpha\Model\ActiveRecord;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for the Person class.
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
class PersonTest extends TestCase
{
    /**
     * Testing the constructor initializes the person object.
     *
     * @since 4.1
     */
    public function testConstructor(): void
    {
        $person = new Person();

        $this->assertInstanceOf(Person::class, $person);
        $this->assertInstanceOf(ActiveRecord::class, $person);
    }

    /**
     * Testing setUsername() method.
     *
     * @since 4.1
     */
    public function testSetUsername(): void
    {
        $person = new Person();
        $person->setUsername('testuser');

        $this->assertEquals('testuser', $person->getUsername());
    }

    /**
     * Testing setEmail() method.
     *
     * @since 4.1
     */
    public function testSetEmail(): void
    {
        $person = new Person();
        $person->set('email', 'test@example.com');

        $this->assertEquals('test@example.com', $person->get('email'));
    }

    /**
     * Testing that Person has set method from parent.
     *
     * @since 4.1
     */
    public function testHasSetMethod(): void
    {
        $person = new Person();

        $reflection = new \ReflectionClass(Person::class);
        $this->assertTrue($reflection->hasMethod('set'));
    }

    /**
     * Testing setUsername with single character.
     *
     * @since 4.1
     */
    public function testSetUsernameSingleChar(): void
    {
        $person = new Person();
        $person->setUsername('a');

        $this->assertEquals('a', $person->getUsername());
    }

    /**
     * Testing setUsername with simple valid username.
     *
     * @since 4.1
     */
    public function testSetUsernameSimple(): void
    {
        $person = new Person();
        $person->setUsername('testuser123');

        $this->assertEquals('testuser123', $person->getUsername());
    }

    /**
     * Testing setUsername with dot in name.
     *
     * @since 4.1
     */
    public function testSetUsernameWithDot(): void
    {
        $person = new Person();
        $person->setUsername('user.name');

        $this->assertEquals('user.name', $person->getUsername());
    }

    /**
     * Testing setEmail with valid email format.
     *
     * @since 4.1
     */
    public function testSetEmailValid(): void
    {
        $person = new Person();
        $person->set('email', 'test@example.com');

        $this->assertEquals('test@example.com', $person->get('email'));
    }

    /**
     * Testing setEmail with valid email format.
     *
     * @since 4.1
     */
    public function testSetEmailWithValidFormat(): void
    {
        $person = new Person();
        $person->set('email', 'test@example.com');

        $this->assertEquals('test@example.com', $person->get('email'));
    }

    /**
     * Testing multiple property setters.
     *
     * @since 4.1
     */
    public function testMultipleSetters(): void
    {
        $person = new Person();
        $person->setUsername('testuser');
        $person->set('email', 'test@example.com');
        $person->set('password', 'password123');

        $this->assertEquals('testuser', $person->getUsername());
        $this->assertEquals('test@example.com', $person->get('email'));
        $this->assertEquals('password123', $person->get('password'));
    }

    /**
     * Testing that Person extends ActiveRecord.
     *
     * @since 4.1
     */
    public function testExtendsActiveRecord(): void
    {
        $person = new Person();

        $this->assertInstanceOf(ActiveRecord::class, $person);
    }

    /**
     * Testing that Person has required properties.
     *
     * @since 4.1
     */
    public function testHasRequiredProperties(): void
    {
        $reflection = new \ReflectionClass(Person::class);

        $this->assertTrue($reflection->hasProperty('username'), 'Testing that Person has username property');
        $this->assertTrue($reflection->hasProperty('email'), 'Testing that Person has email property');
        $this->assertTrue($reflection->hasProperty('password'), 'Testing that Person has password property');
        $this->assertTrue($reflection->hasProperty('rights'), 'Testing that Person has rights property');
    }

    /**
     * Testing that Person is instantiable.
     *
     * @since 4.1
     */
    public function testPersonIsInstantiable(): void
    {
        $person = new Person();

        $this->assertNotNull($person);
        $this->assertTrue(is_object($person));
    }

    /**
     * Testing that Person methods are callable.
     *
     * @since 4.1
     */
    public function testPersonMethodsAreCallable(): void
    {
        $person = new Person();

        $this->assertTrue(method_exists($person, 'setUsername'));
        $this->assertTrue(method_exists($person, 'getUsername'));
        $this->assertTrue(method_exists($person, 'set'));
        $this->assertTrue(method_exists($person, 'get'));
    }

    /**
     * Testing multiple Person instances.
     *
     * @since 4.1
     */
    public function testMultipleInstances(): void
    {
        $person1 = new Person();
        $person2 = new Person();
        $person3 = new Person();

        $person1->setUsername('user1');
        $person2->setUsername('user2');
        $person3->setUsername('user3');

        $this->assertEquals('user1', $person1->getUsername());
        $this->assertEquals('user2', $person2->getUsername());
        $this->assertEquals('user3', $person3->getUsername());
    }

    /**
     * Testing Person class name.
     *
     * @since 4.1
     */
    public function testPersonClassName(): void
    {
        $reflection = new \ReflectionClass(Person::class);

        $this->assertEquals('Person', $reflection->getShortName());
        $this->assertEquals('Alpha\\Model', $reflection->getNamespaceName());
    }

    /**
     * Testing Person class is not final.
     *
     * @since 4.1
     */
    public function testPersonClassIsNotFinal(): void
    {
        $reflection = new \ReflectionClass(Person::class);

        $this->assertFalse($reflection->isFinal());
    }

    /**
     * Testing that Person has expected methods.
     *
     * @since 4.1
     */
    public function testHasExpectedMethods(): void
    {
        $reflection = new \ReflectionClass(Person::class);
        $methods = $reflection->getMethods();

        $methodNames = array_column($methods, 'name');

        $this->assertContains('setUsername', $methodNames);
        $this->assertContains('getUsername', $methodNames);
    }
}
