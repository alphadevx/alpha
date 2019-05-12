<?php

namespace Alpha\Test\View\Widget;

use Alpha\View\Widget\Image;
use Alpha\Util\Config\ConfigProvider;
use Alpha\Exception\IllegalArguementException;
use PHPUnit\Framework\TestCase;

/**
 * Test case for the Image generation widget.
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
class ImageTest extends TestCase
{
    /**
     * An Image for testing.
     *
     * @var \Alpha\View\Widget\Image
     *
     * @since 1.0
     */
    private $img;

    /**
     * Set up tests.
     *
     * @since 1.0
     */
    protected function setUp(): void
    {
        $config = ConfigProvider::getInstance();

        $this->img = new Image($config->get('app.root').'public/images/icons/accept.png', 16, 16, 'png');
    }

    /**
     * Tear down tests.
     *
     * @since 1.0
     */
    protected function tearDown(): void
    {
        unset($this->img);
    }

    /**
     * Testing for an expected exception when a bad source file path is provided.
     *
     * @since 1.0
     */
    public function testConstructorBadSource()
    {
        try {
            $this->img = new Image('/does/not/exist.png', 16, 16, 'png');
            $this->fail('testing for an expected exception when a bad source file path is provided');
        } catch (IllegalArguementException $e) {
            $this->assertEquals('The source file for the Image widget [/does/not/exist.png] cannot be found!', $e->getMessage(), 'testing for an expected exception when a bad source file path is provided');
        }
    }

    /**
     * Testing for an expected exception when a bad source type is provided.
     *
     * @since 1.0
     */
    public function testConstructorBadSourceType()
    {
        $config = ConfigProvider::getInstance();

        try {
            $this->img = new Image($config->get('app.root').'public/images/icons/accept.png', 16, 16, 'tif');
            $this->fail('testing for an expected exception when a bad source type is provided');
        } catch (IllegalArguementException $e) {
            $this->assertEquals('Not a valid enum option!', $e->getMessage(), 'testing for an expected exception when a bad source type is provided');
        }
    }

    /**
     * Testing for an expected exception when a quality value is provided.
     *
     * @since 1.0
     */
    public function testConstructorQuality()
    {
        $config = ConfigProvider::getInstance();

        try {
            $this->img = new Image($config->get('app.root').'public/images/icons/accept.png', 16, 16, 'png', 2.5);
            $this->fail('testing for an expected exception when a quality value is provided');
        } catch (IllegalArguementException $e) {
            $this->assertEquals('The quality setting of [2.5] is outside of the allowable range of 0.0 to 1.0', $e->getMessage(), 'testing for an expected exception when a quality value is provided');
        }
    }

    /**
     * Testing that the constructor will call setFilename internally to get up a filename  to store the generated image automatically.
     *
     * @since 1.0
     */
    public function testConstructorSetFilename()
    {
        $config = ConfigProvider::getInstance();

        $this->assertEquals($config->get('app.file.store.dir').'cache/images/accept_16x16.png', $this->img->getFilename(), 'testing that the constructor will call setFilename internally to get up a filename  to store the generated image automatically');
    }

    /**
     * Testing the convertImageURLToPath method.
     *
     * @since 1.0
     */
    public function testConvertImageURLToPath()
    {
        $config = ConfigProvider::getInstance();

        $this->assertEquals('images/testimage.png', Image::convertImageURLToPath($config->get('app.url').'/images/testimage.png'), 'testing the convertImageURLToPath method');
    }
}
