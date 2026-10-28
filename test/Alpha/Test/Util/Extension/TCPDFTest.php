<?php

namespace Alpha\Test\Util\Extension;

use Alpha\Util\Extension\TCPDF;
use Alpha\Util\Logging\Logger;
use Alpha\Util\Security\SecurityUtils;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

/**
 * Test cases for the TCPDF class.
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
class TCPDFTest extends TestCase
{
    /**
     * A temporary PNG file used as a valid image source.
     *
     * @var string
     *
     * @since 4.1
     */
    private $pngPath;

    /**
     * A logger mock injected into the class under test.
     *
     * @var \PHPUnit\Framework\MockObject\MockObject
     *
     * @since 4.1
     */
    private $logger;

    /**
     * @since 4.1
     */
    protected function setUp(): void
    {
        parent::setUp();

        // write a valid 1x1 transparent PNG to a temp file for TCPDF to embed
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==', true);
        $this->pngPath = sys_get_temp_dir() . '/alpha_tcpdf_test_' . getmypid() . '.png';
        file_put_contents($this->pngPath, $png);

        $this->logger = $this->createMock(Logger::class);
        $loggerProperty = new ReflectionProperty(TCPDF::class, 'logger');
        $loggerProperty->setAccessible(true);
        $loggerProperty->setValue(null, $this->logger);
    }

    /**
     * @since 4.1
     */
    protected function tearDown(): void
    {
        if (file_exists($this->pngPath)) {
            unlink($this->pngPath);
        }

        $loggerProperty = new ReflectionProperty(TCPDF::class, 'logger');
        $loggerProperty->setAccessible(true);
        $loggerProperty->setValue(null, null);

        parent::tearDown();
    }

    /**
     * Test that the class extends the vendor TCPDF library.
     *
     * @since 4.1
     */
    public function testConstructor(): void
    {
        $pdf = new TCPDF();
        $this->assertInstanceOf(\TCPDF::class, $pdf);
    }

    /**
     * Test inserting an image with a plain filesystem path.
     *
     * @since 4.1
     */
    public function testImageWithPlainPath(): void
    {
        $this->logger->expects($this->atLeastOnce())->method('debug');

        $pdf = new TCPDF();
        $pdf->AddPage();

        $result = $pdf->Image($this->pngPath, 10, 10, 20, 20);

        $this->assertNotFalse($result);
    }

    /**
     * Test inserting an image with an encrypted /tk/ token path, which should
     * decode the source file from the token before embedding it.
     *
     * @since 4.1
     */
    public function testImageWithEncryptedTokenPath(): void
    {
        $this->logger->expects($this->atLeastOnce())->method('debug');

        $pdf = new TCPDF();
        $pdf->AddPage();

        $encrypted = SecurityUtils::encrypt('source=' . $this->pngPath);
        $tk = strtr(base64_encode($encrypted), '+/', '-_');

        $result = $pdf->Image('encrypted.png/tk/' . $tk, 10, 10, 20, 20);

        $this->assertNotFalse($result);
    }
}
