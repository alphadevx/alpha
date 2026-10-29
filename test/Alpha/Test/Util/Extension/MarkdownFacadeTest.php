<?php

namespace Alpha\Test\Util\Extension;

use Alpha\Model\ActiveRecord;
use Alpha\Model\Article;
use Alpha\Util\Cache\CacheProviderFile;
use Alpha\Util\Config\ConfigProvider;
use Alpha\Util\Extension\MarkdownFacade;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

/**
 * A record stub exposing the optional getAttachmentSecureURL() method that the
 * Markdown facade looks for with method_exists().
 *
 * @since 4.1
 */
class MarkdownFacadeTestAttachmentRecord extends ActiveRecord
{
    public function getAttachmentSecureURL($fileName): string
    {
        return 'secure://' . ltrim($fileName, '/');
    }
}

/**
 * Test cases for the MarkdownFacade class.
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
class MarkdownFacadeTest extends TestCase
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

        $config->set('app.url', 'http://example.test');
        $config->set('app.title', 'MarkdownFacadeTestApp');
        $config->set('cms.images.widget', false);
        $config->set('cms.images.widget.secure', false);
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
     * Builds a plain record mock returning the supplied content.
     *
     * @since 4.1
     */
    private function makeRecord(string $content, string $friendlyName = 'Rec', int $id = 1, int $version = 1): ActiveRecord
    {
        $record = $this->createMock(ActiveRecord::class);
        $record->method('getFriendlyClassName')->willReturn($friendlyName);
        // getID() is final on ActiveRecord, so set the underlying ID property instead
        $idProperty = new ReflectionProperty(ActiveRecord::class, 'ID');
        $idProperty->setAccessible(true);
        $idProperty->setValue($record, $id);
        $record->method('getVersion')->willReturn($version);
        $record->method('get')->willReturnCallback(function ($key, $raw = false) use ($content) {
            return $key === 'content' ? $content : '';
        });

        return $record;
    }

    /**
     * Builds a record mock that also declares getAttachmentSecureURL().
     *
     * @since 4.1
     */
    private function makeAttachmentRecord(string $content): ActiveRecord
    {
        $record = $this->createMock(MarkdownFacadeTestAttachmentRecord::class);
        $record->method('getFriendlyClassName')->willReturn('AttachRec');
        $idProperty = new ReflectionProperty(ActiveRecord::class, 'ID');
        $idProperty->setAccessible(true);
        $idProperty->setValue($record, 42);
        $record->method('getVersion')->willReturn(1);
        $record->method('getAttachmentSecureURL')->willReturnCallback(function ($fileName) {
            return 'secure://' . ltrim($fileName, '/');
        });
        $record->method('get')->willReturnCallback(function ($key, $raw = false) use ($content) {
            return $key === 'content' ? $content : '';
        });

        return $record;
    }

    /**
     * @since 4.1
     */
    public function testNonCachedContentReplacesSysURL(): void
    {
        $record = $this->makeRecord('Hello $sysURL');
        $facade = new MarkdownFacade($record, false);

        $this->assertStringContainsString('Hello http://example.test', $facade->getContent());
    }

    /**
     * @since 4.1
     */
    public function testCacheHitReturnsCachedValue(): void
    {
        $key = 'html_RecHit_00000000011_1';
        (new CacheProviderFile())->set($key, 'cached content');

        $record = $this->makeRecord('ignored', 'RecHit', 11);
        $facade = new MarkdownFacade($record, true);

        $this->assertSame('cached content', $facade->getContent());
    }

    /**
     * @since 4.1
     */
    public function testCacheMissPopulatesCache(): void
    {
        $name = 'RecMiss' . mt_rand();
        $key = 'html_' . $name . '_00000000012_1';
        $cache = new CacheProviderFile();

        $record = $this->makeRecord('fresh content', $name, 12);
        $facade = new MarkdownFacade($record, true);

        $this->assertStringContainsString('fresh content', $facade->getContent());
        $this->assertTrue($cache->check($key), 'Testing that the rendered content was stored in the cache');
    }

    /**
     * @since 4.1
     */
    public function testEmptyContentTriggersReload(): void
    {
        $record = $this->makeRecord('', 'RecReload' . mt_rand(), 13);
        $record->expects($this->once())->method('reload');

        $facade = new MarkdownFacade($record, true);

        $this->assertIsString($facade->getContent());
    }

    /**
     * @since 4.1
     */
    public function testArticleCacheKeyUsesTitleAndFileDate(): void
    {
        $key = 'html_Article_Article Title_2021_01_01_10_00_00';
        (new CacheProviderFile())->set($key, 'article cached');

        $article = $this->createMock(Article::class);
        $article->method('getFriendlyClassName')->willReturn('Article');
        $article->method('isLoadedFromFile')->willReturn(true);
        $article->method('getContentFileDate')->willReturn('2021-01-01 10:00:00');
        $article->method('get')->willReturnCallback(function ($key, $raw = false) {
            return $key === 'title' ? 'Article Title' : '';
        });

        $facade = new MarkdownFacade($article, true);

        $this->assertSame('article cached', $facade->getContent());
    }

    /**
     * @since 4.1
     */
    public function testAttachmentHrefReplacement(): void
    {
        $record = $this->makeAttachmentRecord('See <a href="$attachURL/file.pdf">link</a>');
        $facade = new MarkdownFacade($record, false);

        $this->assertStringContainsString('href="secure://file.pdf" rel="nofollow"', $facade->getContent());
    }

    /**
     * @since 4.1
     */
    public function testImageAttachmentSecureURLNoWidget(): void
    {
        $record = $this->makeAttachmentRecord('<img src="$attachURL/pic.png">');
        $facade = new MarkdownFacade($record, false);

        $this->assertStringContainsString('<img src="secure://pic.png">', $facade->getContent());
    }

    /**
     * @since 4.1
     */
    public function testMarkdownMethodReplacesSysURL(): void
    {
        $record = $this->makeRecord('ignored');
        $facade = new MarkdownFacade($record, false);

        $this->assertStringContainsString('http://example.test/page', $facade->markdown('$sysURL/page'));
    }
}
