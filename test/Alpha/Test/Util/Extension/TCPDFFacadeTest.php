<?php

namespace Alpha\Test\Util\Extension;

use Alpha\Model\ActiveRecord;
use Alpha\Util\Cache\CacheProviderFile;
use Alpha\Util\Config\ConfigProvider;
use Alpha\Util\Extension\TCPDFFacade;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;

/**
 * A record stub for the TCPDF facade tests that exposes the optional
 * getAttachmentSecureURL() method the facade looks for with method_exists().
 *
 * @since 4.1
 */
class TCPDFFacadeTestArticle extends ActiveRecord
{
    public array $data = array();

    public function get(string $prop, bool $noChildMethods = false): mixed
    {
        return $this->data[$prop] ?? '';
    }

    public function getVersion(): int
    {
        return 1;
    }

    public function getAttachmentSecureURL(string $fileName): string
    {
        return 'secure/' . $fileName;
    }
}

/**
 * A record stub without the optional getAttachmentSecureURL() method.
 *
 * @since 4.1
 */
class TCPDFFacadeTestPlainArticle extends ActiveRecord
{
    public array $data = array();

    public function get(string $prop, bool $noChildMethods = false): mixed
    {
        return $this->data[$prop] ?? '';
    }

    public function getVersion(): int
    {
        return 1;
    }
}

/**
 * Test cases for the TCPDFFacade class.
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
class TCPDFFacadeTest extends TestCase
{
    /**
     * Config values snapshotted in setUp and restored in tearDown so the
     * global config singleton is not polluted for later test classes.
     *
     * @var array
     *
     * @since 4.1
     */
    private $originalConfig = array();

    /**
     * @since 4.1
     */
    protected function setUp(): void
    {
        parent::setUp();

        $config = ConfigProvider::getInstance();
        foreach (array('app.url', 'app.title', 'cms.images.widget', 'cms.images.widget.secure') as $key) {
            $this->originalConfig[$key] = $config->get($key);
        }

        $config->set('app.url', 'http://localhost');
        $config->set('app.title', 'TCPDFFacadeTestApp');
        $config->set('cms.images.widget', false);
        $config->set('cms.images.widget.secure', false);

        // purge any cached PDF/HTML entries from previous runs so the
        // constructor never short circuits on stale cache files
        $cacheDir = $config->get('app.file.store.dir') . '/cache/files/';
        foreach (glob($cacheDir . 'TCPDFFacadeTestApp-*') as $file) {
            @unlink($file);
        }
    }

    /**
     * @since 4.1
     */
    protected function tearDown(): void
    {
        $config = ConfigProvider::getInstance();
        foreach ($this->originalConfig as $key => $value) {
            $config->set($key, $value);
        }

        parent::tearDown();
    }

    /**
     * Builds a record stub without running the ActiveRecord constructor.
     *
     * @since 4.1
     */
    private function makeArticle(string $class, string $content): ActiveRecord
    {
        $article = (new ReflectionClass($class))->newInstanceWithoutConstructor();
        $article->data = array(
            'content' => $content,
            'author' => 'John',
            'title' => 'My Title',
            'description' => 'My Desc',
            'URL' => 'http://localhost/article',
        );

        return $article;
    }

    /**
     * Reads a private property (used to discover the facade cache keys).
     *
     * @since 4.1
     */
    private function getPrivateProperty(object $object, string $name)
    {
        $property = new ReflectionProperty(get_class($object), $name);
        $property->setAccessible(true);

        return $property->getValue($object);
    }

    /**
     * @since 4.1
     */
    public function testSysURLAndSecureLinkReplacement(): void
    {
        $article = $this->makeArticle(TCPDFFacadeTestArticle::class, '<p>hello $sysURL</p><a href="$attachURL/doc.pdf">doc</a>');
        $facade = new TCPDFFacade($article);

        $out = $facade->getContent();
        $this->assertStringContainsString('http://localhost', $out);
        $this->assertStringContainsString('href=secure/doc.pdf', $out);
    }

    /**
     * @since 4.1
     */
    public function testPlainArticleLeavesAttachURLsIntact(): void
    {
        $article = $this->makeArticle(TCPDFFacadeTestPlainArticle::class, '<p>hi</p><a href="$attachURL/doc.pdf">doc</a><img src="$attachURL/pic.png" alt="p">');
        $facade = new TCPDFFacade($article);

        $out = $facade->getContent();
        $this->assertStringContainsString('$attachURL/doc.pdf', $out);
        $this->assertStringContainsString('$attachURL/pic.png', $out);
    }

    /**
     * @since 4.1
     */
    public function testHTMLCacheHitReturnsCachedContent(): void
    {
        $article = $this->makeArticle(TCPDFFacadeTestArticle::class, '<p>real content</p>');
        $facade = new TCPDFFacade($article);

        $htmlKey = $this->getPrivateProperty($facade, 'HTMLCacheKey');
        (new CacheProviderFile())->set($htmlKey, 'cached html');

        $secondArticle = $this->makeArticle(TCPDFFacadeTestArticle::class, 'ignored content');
        $secondFacade = new TCPDFFacade($secondArticle);

        $this->assertSame('cached html', $secondFacade->getContent());
    }

    /**
     * @since 4.1
     */
    public function testGetPDFDataReadsFromCache(): void
    {
        $article = $this->makeArticle(TCPDFFacadeTestArticle::class, '<p>some content</p>');
        $facade = new TCPDFFacade($article);

        $pdfKey = $this->getPrivateProperty($facade, 'PDFCacheKey');
        (new CacheProviderFile())->set($pdfKey, 'PDFBYTES');

        $this->assertSame('PDFBYTES', $facade->getPDFData());
    }

    /**
     * @since 4.1
     */
    public function testPDFCacheHitSkipsGeneration(): void
    {
        $article = $this->makeArticle(TCPDFFacadeTestArticle::class, '<p>anything</p>');
        $firstFacade = new TCPDFFacade($article);
        $pdfKey = $this->getPrivateProperty($firstFacade, 'PDFCacheKey');
        (new CacheProviderFile())->set($pdfKey, 'CACHED-PDF');

        $article2 = $this->makeArticle(TCPDFFacadeTestArticle::class, 'anything');
        $facade = new TCPDFFacade($article2);

        $this->assertInstanceOf(TCPDFFacade::class, $facade);
    }
}
