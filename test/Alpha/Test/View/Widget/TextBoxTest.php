<?php

namespace Alpha\Test\View\Widget;

use Alpha\View\Widget\TextBox;
use Alpha\Model\Type\Text;
use Alpha\Exception\IllegalArguementException;
use PHPUnit\Framework\TestCase;

/**
 * Test case for the TextBox widget.
 *
 * @since 2.0
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
class TextBoxTest extends TestCase
{
    /**
     * Testing the render() method.
     *
     * @since 4.0
     */
    public function testRender()
    {
        $textBox = new TextBox(new Text(), 'Test label', 'testName');
        $html = $textBox->render();
        $this->assertTrue(strpos($html, 'testName') !== false, 'Testing the render() method');
    }

    /**
     * Testing the get/set text object methods
     *
     * @since 4.0
     */
    public function testGetSetTextObject()
    {
        $textBox = new TextBox(new Text(), 'Test label', 'testName');
        $text = new Text('alpha');
        $textBox->setTextObject($text);
        $this->assertEquals('alpha', $textBox->getTextObject()->getValue(), 'Testing the get/set text object methods');
    }
}
