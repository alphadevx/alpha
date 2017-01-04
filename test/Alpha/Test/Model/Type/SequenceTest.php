<?php

namespace Alpha\Test\Model\Type;

use Alpha\Test\Model\ModelTestCase;
use Alpha\Model\Type\Sequence;
use Alpha\Exception\IllegalArguementException;

/**
 * Test cases for the Sequence data type.
 *
 * @since 1.0
 *
 * @author John Collins <dev@alphaframework.org>
 *
 * @version $Id: SequenceTest.php 1843 2014-11-13 22:41:33Z alphadevx $
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
class SequenceTest extends ModelTestCase
{
    /**
     * a Sequence for testing.
     *
     * @var Alpha\Model\Type\Sequence
     *
     * @since 1.0
     */
    private $sequence;

    /**
     * Called before the test functions will be executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here.
     *
     * @since 1.0
     */
    protected function setUp()
    {
        parent::setUp();
        $this->sequence = new Sequence();
        $this->sequence->rebuildTable();
        $this->sequence->set('prefix', 'TEST');
        $this->sequence->set('sequence', 1);
        $this->sequence->save();
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
        parent::tearDown();
        $this->sequence->dropTable();
        unset($this->sequence);
    }

    /**
     * Testing to ensure that a bad parameter will cause an IllegalArguementException.
     *
     * @since 1.0
     */
    public function testSetValueBad()
    {
        try {
            $this->sequence->setValue('invalid');
            $this->fail('Testing to ensure that a bad parameter will cause an IllegalArguementException');
        } catch (IllegalArguementException $e) {
            $this->assertEquals($this->sequence->getHelper(), $e->getMessage(), 'Testing to ensure that a bad parameter will cause an IllegalArguementException');
        }
    }

    /**
     * Testing to ensure that a good parameter will not cause an IllegalArguementException.
     *
     * @since 1.0
     */
    public function testSetValueGood()
    {
        try {
            $this->sequence->setValue('VALID-1');
            $this->assertEquals('VALID', $this->sequence->get('prefix'), 'Testing to ensure that a good parameter will not cause an IllegalArguementException');
            $this->assertEquals(1, $this->sequence->get('sequence'), 'Testing to ensure that a good parameter will not cause an IllegalArguementException');
        } catch (IllegalArguementException $e) {
            $this->fail('Testing to ensure that a good parameter will not cause an IllegalArguementException');
        }
    }

    /**
     * Testing that sequence prefixes are uppercase.
     *
     * @since 1.0
     */
    public function testPrefixValidation()
    {
        try {
            $this->sequence->set('prefix', 'bad');
        } catch (IllegalArguementException $e) {
            $this->assertEquals($this->sequence->getPropObject('prefix')->getHelper(), $e->getMessage(), 'Testing that sequence prefixes are uppercase');
        }
    }

    /**
     * Testing the setSequenceToNext methid increments the sequence number.
     *
     * @since 1.0
     */
    public function testSetSequenceToNext()
    {
        $this->sequence->setSequenceToNext();

        $this->assertEquals('TEST-2', $this->sequence->getValue(), 'Testing the setSequenceToNext methid increments the sequence number');
    }

    /**
     * Testing the toString method.
     *
     * @since 1.0
     */
    public function testToString()
    {
        $this->assertEquals('TEST-1', $this->sequence->__toString(), 'Testing the toString method');
    }
}
