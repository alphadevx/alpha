<?php

namespace Alpha\Test\View\Widget;

use Alpha\View\Widget\Button;
use Alpha\Exception\IllegalArguementException;

/**
 * Test case for the Button widget.
 *
 * @since 3.0
 *
 * @author John Collins <dev@alphaframework.org>
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
class ButtonTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Testing the render method for expected results
     *
     * @since 3.0
     */
    public function testRender()
    {
        $button = new Button('submit', 'Save', 'buttonID', '', '', 'Tooltip test helloworld', 'right');
        $this->assertContains('helloworld', $button->render(), 'Checking for tooltip text');
        $this->assertContains('Save', $button->render(), 'Checking for botton text');
        $this->assertContains('buttonID', $button->render(), 'Checking for ID text');
        $this->assertContains('right', $button->render(), 'Checking for tooltip position text');

        $button = new Button('submit', 'Save', 'buttonID', '/path/to/image.png', '', 'Tooltip test helloworld', 'right');
        $this->assertContains('helloworld', $button->render(), 'Checking for tooltip text');
        $this->assertContains('Save', $button->render(), 'Checking for botton text');
        $this->assertContains('buttonID', $button->render(), 'Checking for ID text');
        $this->assertContains('right', $button->render(), 'Checking for tooltip position text');
        $this->assertContains('<img', $button->render(), 'Checking that an image tag is rendered');
        $this->assertContains('image.png', $button->render(), 'Checking for image URL text');

        $button = new Button('submit', 'Save', 'buttonID', '', '/path/to/glyph.png', 'Tooltip test helloworld', 'right');
        $this->assertContains('helloworld', $button->render(), 'Checking for tooltip text');
        $this->assertContains('Save', $button->render(), 'Checking for botton text');
        $this->assertContains('buttonID', $button->render(), 'Checking for ID text');
        $this->assertContains('right', $button->render(), 'Checking for tooltip position text');
        $this->assertContains('<span', $button->render(), 'Checking that a span tag is rendered');
        $this->assertContains('glyph.png', $button->render(), 'Checking for glyph image URL text');

        $button = new Button('submit', 'Save', 'buttonID');
        $this->assertContains('1234', $button->render('1234'), 'Checking that the desired button width is rendered');
    }
}
