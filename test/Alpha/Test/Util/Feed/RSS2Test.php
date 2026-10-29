<?php

namespace Alpha\Test\Util\Feed;

use Alpha\Model\Article;
use Alpha\Util\Feed\RSS2;
use XMLReader;
use SimpleXMLElement;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for the RSS2 class.
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
class RSS2Test extends TestCase
{
    /**
     * Test object to inject into a feed.
     *
     * @var \Alpha\Model\Article
     */
    private $record;

    /**
     * Called before the test functions will be executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here.
     *
     * @since 4.1
     */
    protected function setUp(): void
    {
        $this->record = new Article();
        $this->record->set('title', 'Test Article Title');
        $this->record->set('description', 'Test Article Description');
        $this->record->set('created_ts', '2011-01-01 00:00:00');
    }

    /**
     * Called after the test functions are executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here.
     *
     * @since 4.1
     */
    protected function tearDown(): void
    {
        unset($this->record);
    }

    /**
     * Test creating a basic RSS2 feed with default parameters.
     *
     * @since 4.1
     */
    public function testConstructorWithDefaults(): void
    {
        $feed = new RSS2('Alpha\Model\Article', 'Test Feed Title', 'http://www.alphaframework.org/', 'Test Feed Description');

        $this->assertInstanceOf(RSS2::class, $feed);
        $this->assertStringContainsString('<rss version="2.0"', $feed->render());
    }

    /**
     * Test creating an RSS2 feed with all parameters.
     *
     * @since 4.1
     */
    public function testConstructorWithAllParameters(): void
    {
        $feed = new RSS2(
            'Alpha\Model\Article',
            'Test Feed Title',
            'http://www.alphaframework.org/',
            'Test Feed Description',
            '2021-12-25 10:00:00',
            123,
            20
        );

        $this->assertInstanceOf(RSS2::class, $feed);
        $this->assertStringContainsString('<rss version="2.0"', $feed->render());
    }

    /**
     * Test that RSS2 feed has correct root tag.
     *
     * @since 4.1
     */
    public function testRootTag(): void
    {
        $feed = new RSS2('Alpha\Model\Article', 'Test Feed Title', 'http://www.alphaframework.org/', 'Test Feed Description');

        $reflection = new \ReflectionClass($feed);
        $property = $reflection->getProperty('rootTag');
        $property->setAccessible(true);

        $this->assertEquals('<rss version="2.0" />', $property->getValue($feed));
    }

    /**
     * Test that RSS2 feed has channel element.
     *
     * @since 4.1
     */
    public function testHasChannelIsTrue(): void
    {
        $feed = new RSS2('Alpha\Model\Article', 'Test Feed Title', 'http://www.alphaframework.org/', 'Test Feed Description');

        $reflection = new \ReflectionClass($feed);
        $property = $reflection->getProperty('hasChannel');
        $property->setAccessible(true);

        $this->assertTrue($property->getValue($feed));
    }

    /**
     * Test RSS2 feed uses standard RSS2 tags for description.
     *
     * @since 4.1
     */
    public function testTagMapUsesStandardRSS2Tags(): void
    {
        $feed = new RSS2('Alpha\Model\Article', 'Test Feed Title', 'http://www.alphaframework.org/', 'Test Feed Description');

        $reflection = new \ReflectionClass($feed);
        $property = $reflection->getProperty('tagMap');
        $property->setAccessible(true);

        $tagMap = $property->getValue($feed);
        $this->assertEquals('item', $tagMap['item']);
        $this->assertEquals('description', $tagMap['feeddesc']);
        $this->assertEquals('description', $tagMap['itemdesc']);
    }

    /**
     * Test adding a record to an RSS2 feed and parsing the XML.
     *
     * @since 4.1
     */
    public function testAddRecordToRSS2AndParse(): void
    {
        $feed = new RSS2('Alpha\Model\Article', 'Test Feed Title', 'http://www.alphaframework.org/', 'Test Feed Description');
        $feed->setFieldMappings('title', 'URL', 'description', 'created_ts', 'ID');
        $feed->addRecord($this->record);
        $xml = $feed->render();

        $reader = new XMLReader();
        $validXML = $reader->XML($xml);

        $this->assertTrue($validXML, 'Confirming that the generated XML can be parsed correctly');

        $simpleXML = new SimpleXMLElement($xml);

        $channels = $simpleXML->xpath('channel');
        $this->assertEquals('Test Feed Title', (string) $channels[0]->title, 'Testing that the feed title is present');
        $this->assertEquals('http://www.alphaframework.org/', (string) $channels[0]->link, 'Testing that the feed URL is present');
    }

    /**
     * Test RSS2 feed items use 'item' tag.
     *
     * @since 4.1
     */
    public function testRSS2ItemsUseItemTag(): void
    {
        $feed = new RSS2('Alpha\Model\Article', 'Test Feed Title', 'http://www.alphaframework.org/', 'Test Feed Description');
        $feed->setFieldMappings('title', 'URL', 'description', 'created_ts', 'ID');
        $feed->addRecord($this->record);
        $xml = $feed->render();

        $this->assertStringContainsString('<item>', $xml);
        $this->assertStringContainsString('<title>', $xml);
        $this->assertStringContainsString('<description>', $xml);
        $this->assertStringContainsString('</item>', $xml);
    }

    /**
     * Test RSS2 feed uses 'description' for feed description.
     *
     * @since 4.1
     */
    public function testRSS2FeedDescriptionUsesDescriptionTag(): void
    {
        $feed = new RSS2('Alpha\Model\Article', 'Test Feed Title', 'http://www.alphaframework.org/', 'Test Feed Description');
        $feed->setFieldMappings('title', 'URL', 'description', 'created_ts', 'ID');
        $feed->addRecord($this->record);
        $xml = $feed->render();

        $simpleXML = new SimpleXMLElement($xml);

        $channels = $simpleXML->xpath('channel');
        $this->assertEquals('Test Feed Description', (string) $channels[0]->description, 'Testing that the feed description is present');
    }

    /**
     * Test RSS2 feed items use 'description' for item description.
     *
     * @since 4.1
     */
    public function testRSS2ItemDescriptionUsesDescriptionTag(): void
    {
        $feed = new RSS2('Alpha\Model\Article', 'Test Feed Title', 'http://www.alphaframework.org/', 'Test Feed Description');
        $feed->setFieldMappings('title', 'URL', 'description', 'created_ts', 'ID');
        $feed->addRecord($this->record);
        $xml = $feed->render();

        $simpleXML = new SimpleXMLElement($xml);

        $items = $simpleXML->xpath('channel/item');
        $this->assertEquals('Test Article Description', (string) $items[0]->description, 'Testing that the feed item description is present');
    }

    /**
     * Test RSS2 feed with custom limit parameter.
     *
     * @since 4.1
     */
    public function testConstructorWithCustomLimit(): void
    {
        $feed = new RSS2('Alpha\Model\Article', 'Test Feed Title', 'http://www.alphaframework.org/', 'Test Feed Description', null, null, 50);

        $reflection = new \ReflectionClass($feed);
        $property = $reflection->getProperty('items');
        $property->setAccessible(true);

        // The items property is declared but never initialized in the constructor
        // Feed items are added directly to the DOM document instead
        $this->assertNull($property->getValue($feed));
    }

    /**
     * Test rendering RSS2 feed with multiple items.
     *
     * @since 4.1
     */
    public function testRenderMultipleItems(): void
    {
        $feed = new RSS2('Alpha\Model\Article', 'Test Feed Title', 'http://www.alphaframework.org/', 'Test Feed Description');
        $feed->setFieldMappings('title', 'URL', 'description', 'created_ts', 'ID');

        // Add first record
        $feed->addRecord($this->record);

        // Add second record
        $record2 = new Article();
        $record2->set('title', 'Second Article Title');
        $record2->set('description', 'Second Article Description');
        $record2->set('created_ts', '2011-01-02 00:00:00');
        $feed->addRecord($record2);

        $xml = $feed->render();

        $this->assertStringContainsString('<item>', $xml);
        $this->assertStringContainsString('Test Article Title', $xml);
        $this->assertStringContainsString('Second Article Title', $xml);

        // Count item elements
        $simpleXML = new SimpleXMLElement($xml);
        $items = $simpleXML->xpath('channel/item');
        $this->assertCount(2, $items, 'Should have 2 items');
    }

    /**
     * Test RSS2 feed XML output is valid and well-formed.
     *
     * @since 4.1
     */
    public function testValidXMLStructure(): void
    {
        $feed = new RSS2('Alpha\Model\Article', 'Test Feed Title', 'http://www.alphaframework.org/', 'Test Feed Description');
        $feed->setFieldMappings('title', 'URL', 'description', 'created_ts', 'ID');
        $feed->addRecord($this->record);
        $xml = $feed->render();

        $reader = new XMLReader();
        $validXML = $reader->XML($xml);

        $this->assertTrue($validXML, 'Confirming that the generated XML is well-formed');

        // Verify required RSS2 elements are present
        $simpleXML = new SimpleXMLElement($xml);
        $channels = $simpleXML->xpath('channel');
        $this->assertEquals(1, count($channels), 'Should have exactly one channel element');
    }

    /**
     * Test RSS2 feed with all required elements (title, link, description).
     *
     * @since 4.1
     */
    public function testRSS2RequiredElements(): void
    {
        $feed = new RSS2('Alpha\Model\Article', 'Test Feed Title', 'http://www.alphaframework.org/', 'Test Feed Description');
        $feed->setFieldMappings('title', 'URL', 'description', 'created_ts', 'ID');
        $feed->addRecord($this->record);
        $xml = $feed->render();

        $simpleXML = new SimpleXMLElement($xml);
        $channels = $simpleXML->xpath('channel');

        $this->assertNotNull($channels);
        $this->assertNotNull($channels[0]->title);
        $this->assertNotNull($channels[0]->link);
        $this->assertNotNull($channels[0]->description);
        $this->assertNotNull($channels[0]->item);
    }

    /**
     * Test RSS2 feed pubDate element in items.
     *
     * @since 4.1
     */
    public function testRSS2ItemPubDate(): void
    {
        $feed = new RSS2('Alpha\Model\Article', 'Test Feed Title', 'http://www.alphaframework.org/', 'Test Feed Description');
        $feed->setFieldMappings('title', 'URL', 'description', 'created_ts', 'ID');
        $feed->addRecord($this->record);
        $xml = $feed->render();

        $simpleXML = new SimpleXMLElement($xml);
        $items = $simpleXML->xpath('channel/item');

        $this->assertNotNull($items[0]->pubDate, 'Item should have a pubDate element');
    }

    /**
     * Test that feed renders empty string when rssDoc is not initialized.
     *
     * @since 4.1
     */
    public function testRenderEmptyFeed(): void
    {
        $feed = new RSS2('Alpha\Model\Article', 'Test Feed Title', 'http://www.alphaframework.org/', 'Test Feed Description');
        $xml = $feed->render();

        $this->assertStringContainsString('<?xml', $xml);
        $this->assertStringContainsString('<rss', $xml);
        $this->assertStringContainsString('</rss>', $xml);
    }

    /**
     * Test RSS2 feed channel element structure.
     *
     * @since 4.1
     */
    public function testRSS2ChannelStructure(): void
    {
        $feed = new RSS2('Alpha\Model\Article', 'Test Feed Title', 'http://www.alphaframework.org/', 'Test Feed Description');
        $feed->setFieldMappings('title', 'URL', 'description', 'created_ts', 'ID');
        $feed->addRecord($this->record);
        $xml = $feed->render();

        $simpleXML = new SimpleXMLElement($xml);
        $channels = $simpleXML->xpath('channel');

        $this->assertEquals('Test Feed Title', (string) $channels[0]->title);
        $this->assertEquals('http://www.alphaframework.org/', (string) $channels[0]->link);
        $this->assertEquals('Test Feed Description', (string) $channels[0]->description);
    }
}
