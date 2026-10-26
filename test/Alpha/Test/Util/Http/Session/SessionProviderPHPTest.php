<?php

namespace Alpha\Test\Util\Http\Session;

use Alpha\Util\Http\Session\SessionProviderPHP;
use Alpha\Util\Http\Session\SessionProviderInterface;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Test cases for the SessionProviderPHP class.
 *
 * @since 4.1
 *
 * @author John Collins <dev@alphaframework.org>
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2024, John Collins (founder of Alpha Framework).
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
class SessionProviderPHPTest extends TestCase
{
    /**
     * Called after the test functions are executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here.
     *
     * @since 4.1
     */
    protected function tearDown(): void
    {
        // Destroy any active session to ensure clean test isolation
        if (session_id() !== '') {
            session_destroy();
        }
        $_SESSION = [];
    }

    /**
     * Testing that the constructor initializes the session correctly.
     *
     * @since 4.1
     */
    public function testConstructor(): void
    {
        // Start a fresh session
        if (session_id() !== '') {
            session_destroy();
        }

        $provider = new SessionProviderPHP();

        $this->assertInstanceOf(SessionProviderPHP::class, $provider);
        $this->assertInstanceOf(SessionProviderInterface::class, $provider);
    }

    /**
     * Testing that init() starts a new session when none exists.
     *
     * @since 4.1
     */
    public function testInitStartsSession(): void
    {
        if (session_id() !== '') {
            session_destroy();
        }

        $provider = new SessionProviderPHP();
        $sessionId = $provider->getID();

        $this->assertNotEmpty($sessionId, 'Testing that init() starts a new session');
        $this->assertIsString($sessionId, 'Testing that session ID is a string');
        $this->assertEquals($sessionId, session_id(), 'Testing that session ID matches PHP session ID');
    }

    /**
     * Testing that init() resumes an existing session.
     *
     * @since 4.1
     */
    public function testInitResumesSession(): void
    {
        if (session_id() !== '') {
            session_destroy();
        }

        // Start a session
        session_start();
        $originalSessionId = session_id();

        $provider = new SessionProviderPHP();
        $newSessionId = $provider->getID();

        $this->assertEquals($originalSessionId, $newSessionId, 'Testing that init() resumes an existing session');
    }

    /**
     * Testing that set() stores a value in the session.
     *
     * @since 4.1
     */
    public function testSet(): void
    {
        if (session_id() !== '') {
            session_destroy();
        }

        $provider = new SessionProviderPHP();

        $provider->set('test_key', 'test_value');

        $this->assertEquals('test_value', $provider->get('test_key'), 'Testing that set() stores a value in the session');
    }

    /**
     * Testing that set() overwrites an existing value.
     *
     * @since 4.1
     */
    public function testSetOverwrite(): void
    {
        if (session_id() !== '') {
            session_destroy();
        }

        $provider = new SessionProviderPHP();

        $provider->set('test_key', 'original_value');
        $provider->set('test_key', 'new_value');

        $this->assertEquals('new_value', $provider->get('test_key'), 'Testing that set() overwrites an existing value');
    }

    /**
     * Testing that set() stores different types of values.
     *
     * @since 4.1
     */
    public function testSetDifferentTypes(): void
    {
        if (session_id() !== '') {
            session_destroy();
        }

        $provider = new SessionProviderPHP();

        // Test string
        $provider->set('string_key', 'string_value');
        $this->assertEquals('string_value', $provider->get('string_key'));

        // Test integer
        $provider->set('int_key', 123);
        $this->assertEquals(123, $provider->get('int_key'));

        // Test float
        $provider->set('float_key', 3.14);
        $this->assertEquals(3.14, $provider->get('float_key'), 0.001);

        // Test boolean
        $provider->set('bool_key', true);
        $this->assertTrue($provider->get('bool_key'));

        // Test array
        $testArray = ['a' => 1, 'b' => 2];
        $provider->set('array_key', $testArray);
        $this->assertEquals($testArray, $provider->get('array_key'));

        // Test null
        $provider->set('null_key', null);
        $this->assertNull($provider->get('null_key'));
    }

    /**
     * Testing that get() returns false when key does not exist.
     *
     * @since 4.1
     */
    public function testGetNonExistentKey(): void
    {
        if (session_id() !== '') {
            session_destroy();
        }

        $provider = new SessionProviderPHP();

        $this->assertFalse($provider->get('non_existent_key'), 'Testing that get() returns false when key does not exist');
    }

    /**
     * Testing that get() returns the correct value for an existing key.
     *
     * @since 4.1
     */
    public function testGetExistingKey(): void
    {
        if (session_id() !== '') {
            session_destroy();
        }

        $provider = new SessionProviderPHP();

        $provider->set('test_key', 'test_value');
        $value = $provider->get('test_key');

        $this->assertEquals('test_value', $value, 'Testing that get() returns the correct value for an existing key');
    }

    /**
     * Testing that delete() removes a key from the session.
     *
     * @since 4.1
     */
    public function testDelete(): void
    {
        if (session_id() !== '') {
            session_destroy();
        }

        $provider = new SessionProviderPHP();

        $provider->set('test_key', 'test_value');
        $this->assertEquals('test_value', $provider->get('test_key'), 'Testing that key exists before delete');

        $provider->delete('test_key');
        $this->assertFalse($provider->get('test_key'), 'Testing that delete() removes a key from the session');
    }

    /**
     * Testing that delete() does nothing when key does not exist.
     *
     * @since 4.1
     */
    public function testDeleteNonExistentKey(): void
    {
        if (session_id() !== '') {
            session_destroy();
        }

        $provider = new SessionProviderPHP();

        // Delete a non-existent key should not throw an error
        $provider->delete('non_existent_key');

        $this->assertTrue(true, 'Testing that delete() does nothing when key does not exist');
    }

    /**
     * Testing that destroy() clears all session data.
     *
     * @since 4.1
     */
    public function testDestroy(): void
    {
        if (session_id() !== '') {
            session_destroy();
        }

        $provider = new SessionProviderPHP();

        $provider->set('key1', 'value1');
        $provider->set('key2', 'value2');
        $provider->set('key3', 'value3');

        $this->assertEquals('value1', $provider->get('key1'));
        $this->assertEquals('value2', $provider->get('key2'));
        $this->assertEquals('value3', $provider->get('key3'));

        $provider->destroy();

        $this->assertFalse($provider->get('key1'), 'Testing that destroy() clears session data');
        $this->assertFalse($provider->get('key2'), 'Testing that destroy() clears session data');
        $this->assertFalse($provider->get('key3'), 'Testing that destroy() clears session data');
    }

    /**
     * Testing that destroy() clears the PHP session array.
     *
     * @since 4.1
     */
    public function testDestroyClearsSessionArray(): void
    {
        if (session_id() !== '') {
            session_destroy();
        }

        $provider = new SessionProviderPHP();

        $provider->set('test_key', 'test_value');

        // Verify data exists in PHP session array
        $this->assertArrayHasKey('test_key', $_SESSION);

        $provider->destroy();

        // Verify data is cleared from PHP session array
        $this->assertArrayNotHasKey('test_key', $_SESSION, 'Testing that destroy() clears the PHP session array');
    }

    /**
     * Testing that getID() returns a valid session ID.
     *
     * @since 4.1
     */
    public function testGetID(): void
    {
        if (session_id() !== '') {
            session_destroy();
        }

        $provider = new SessionProviderPHP();
        $sessionId = $provider->getID();

        $this->assertNotEmpty($sessionId, 'Testing that getID() returns a non-empty session ID');
        $this->assertIsString($sessionId, 'Testing that getID() returns a string');
        $this->assertEquals($sessionId, session_id(), 'Testing that getID() returns the PHP session ID');
    }

    /**
     * Testing that getID() returns consistent session IDs within the same request.
     *
     * @since 4.1
     */
    public function testGetIDConsistent(): void
    {
        if (session_id() !== '') {
            session_destroy();
        }

        $provider = new SessionProviderPHP();

        $sessionId1 = $provider->getID();
        $sessionId2 = $provider->getID();
        $sessionId3 = $provider->getID();

        $this->assertEquals($sessionId1, $sessionId2, 'Testing that getID() returns consistent session IDs');
        $this->assertEquals($sessionId2, $sessionId3, 'Testing that getID() returns consistent session IDs');
    }

    /**
     * Testing that multiple values can be stored and retrieved.
     *
     * @since 4.1
     */
    public function testMultipleValues(): void
    {
        if (session_id() !== '') {
            session_destroy();
        }

        $provider = new SessionProviderPHP();

        $provider->set('key1', 'value1');
        $provider->set('key2', 'value2');
        $provider->set('key3', 'value3');

        $this->assertEquals('value1', $provider->get('key1'));
        $this->assertEquals('value2', $provider->get('key2'));
        $this->assertEquals('value3', $provider->get('key3'));
    }

    /**
     * Testing that session ID is generated correctly.
     *
     * @since 4.1
     */
    public function testSessionIDGenerated(): void
    {
        if (session_id() !== '') {
            session_destroy();
        }

        $provider = new SessionProviderPHP();
        $sessionId = $provider->getID();

        $this->assertNotEmpty($sessionId, 'Testing that session ID is generated');
        $this->assertGreaterThan(0, strlen($sessionId), 'Testing that session ID has a valid length');
    }

    /**
     * Testing that session data can be updated.
     *
     * @since 4.1
     */
    public function testUpdateValue(): void
    {
        if (session_id() !== '') {
            session_destroy();
        }

        $provider = new SessionProviderPHP();

        $provider->set('update_key', 'initial_value');
        $this->assertEquals('initial_value', $provider->get('update_key'));

        $provider->set('update_key', 'updated_value');
        $this->assertEquals('updated_value', $provider->get('update_key'));

        $provider->set('update_key', 're-updated_value');
        $this->assertEquals('re-updated_value', $provider->get('update_key'));
    }

    /**
     * Testing that delete() only removes the specified key.
     *
     * @since 4.1
     */
    public function testDeleteOnlyRemovesSpecifiedKey(): void
    {
        if (session_id() !== '') {
            session_destroy();
        }

        $provider = new SessionProviderPHP();

        $provider->set('key1', 'value1');
        $provider->set('key2', 'value2');
        $provider->set('key3', 'value3');

        $provider->delete('key2');

        $this->assertFalse($provider->get('key2'), 'Testing that key2 is deleted');
        $this->assertEquals('value1', $provider->get('key1'), 'Testing that key1 is not affected by delete');
        $this->assertEquals('value3', $provider->get('key3'), 'Testing that key3 is not affected by delete');
    }

    /**
     * Testing that SessionProviderPHP implements SessionProviderInterface.
     *
     * @since 4.1
     */
    public function testImplementsInterface(): void
    {
        $provider = new SessionProviderPHP();

        $this->assertInstanceOf(SessionProviderInterface::class, $provider);

        // Verify all interface methods exist
        $this->assertTrue(method_exists($provider, 'init'), 'Testing that init() method exists');
        $this->assertTrue(method_exists($provider, 'destroy'), 'Testing that destroy() method exists');
        $this->assertTrue(method_exists($provider, 'get'), 'Testing that get() method exists');
        $this->assertTrue(method_exists($provider, 'set'), 'Testing that set() method exists');
        $this->assertTrue(method_exists($provider, 'delete'), 'Testing that delete() method exists');
        $this->assertTrue(method_exists($provider, 'getID'), 'Testing that getID() method exists');
    }

    /**
     * Testing that the class has the expected properties.
     *
     * @since 4.1
     */
    public function testExpectedProperties(): void
    {
        $reflection = new ReflectionClass(SessionProviderPHP::class);

        $this->assertTrue($reflection->hasProperty('logger'), 'Testing that class has logger property');
    }

    /**
     * Testing that get() returns exact values without modification.
     *
     * @since 4.1
     */
    public function testGetExactValues(): void
    {
        if (session_id() !== '') {
            session_destroy();
        }

        $provider = new SessionProviderPHP();

        // Test with special characters
        $specialValue = "Special: !@#$%^&*()_+-=[]{}|;':\",./<>?\n\r\t";
        $provider->set('special_key', $specialValue);
        $this->assertEquals($specialValue, $provider->get('special_key'));

        // Test with unicode
        $unicodeValue = "Unicode: 你好世界 🌍";
        $provider->set('unicode_key', $unicodeValue);
        $this->assertEquals($unicodeValue, $provider->get('unicode_key'));

        // Test with long string
        $longValue = str_repeat('a', 10000);
        $provider->set('long_key', $longValue);
        $this->assertEquals($longValue, $provider->get('long_key'));
    }

    /**
     * Testing that set() with empty string value works correctly.
     *
     * @since 4.1
     */
    public function testSetEmptyString(): void
    {
        if (session_id() !== '') {
            session_destroy();
        }

        $provider = new SessionProviderPHP();

        $provider->set('empty_key', '');
        $this->assertEquals('', $provider->get('empty_key'));
    }

    /**
     * Testing that session data survives within the same request.
     *
     * @since 4.1
     */
    public function testDataSurvivesInSameRequest(): void
    {
        if (session_id() !== '') {
            session_destroy();
        }

        $provider = new SessionProviderPHP();

        $provider->set('survive_key', 'survive_value');

        // Multiple get operations should return the same value
        for ($i = 0; $i < 5; $i++) {
            $this->assertEquals('survive_value', $provider->get('survive_key'));
        }
    }
}
