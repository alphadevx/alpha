<?php

namespace Alpha\Test\View\Widget;

use Alpha\View\Widget\DateBox;
use Alpha\Model\Type\Date;
use Alpha\Exception\IllegalArguementException;
use PHPUnit\Framework\TestCase;

/**
 * Test case for the DateBox widget.
 *
 * @since 2.0
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
class DateBoxTest extends TestCase
{
    /**
     * Testing for an expected exception when a bad object provided.
     *
     * @since 2.0
     */
    public function testConstructorBadSource()
    {
        try {
            $dateBox = new DateBox(new \stdClass());
            $this->fail('Testing for an expected exception when a bad object provided');
        } catch (IllegalArguementException $e) {
            $this->assertEquals('DateBox widget can only accept a Date or Timestamp object!', $e->getMessage(), 'Testing for an expected exception when a bad object provided');
        }
    }

    /**
     * Testing the render() method.
     *
     * @since 2.0
     */
    public function testRender()
    {
        $dateBox = new DateBox(new Date(), 'Test label', 'testName');
        $html = $dateBox->render();
        $this->assertTrue(strpos($html, 'testName') !== false, 'Testing the render() method');
    }
}
