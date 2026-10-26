<?php

namespace Alpha\Test\Util\Feed;

use Alpha\Model\Article;
use Alpha\Util\Feed\Atom;
use XMLReader;
use SimpleXMLElement;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for the Atom class.
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
class AtomTest extends TestCase
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
     * Test creating a basic Atom feed with default parameters.
     *
     * @since 4.1
     */
    public function testConstructorWithDefaults(): void
    {
        $feed = new Atom('Alpha\Model\Article', 'Test Feed Title', 'http://www.alphaframework.org/', 'Test Feed Description');

        $this->assertInstanceOf(Atom::class, $feed);
        $this->assertStringContainsString('http://www.w3.org/2005/Atom', $feed->render());
    }

    /**
     * Test creating an Atom feed with custom pubDate and id parameters.
     *
     * @since 4.1
     */
    public function testConstructorWithCustomParameters(): void
    {
        $feed = new Atom(
            'Alpha\Model\Article',
            'Test Feed Title',
            'http://www.alphaframework.org/',
            'Test Feed Description',
            '2021-12-25 10:00:00',
            123
        );

        $this->assertInstanceOf(Atom::class, $feed);
        $this->assertStringContainsString('http://www.w3.org/2005/Atom', $feed->render());
    }

    /**
     * Test that Atom feed has correct namespace.
     *
     * @since 4.1
     */
    public function testNamespace(): void
    {
        $feed = new Atom('Alpha\Model\Article', 'Test Feed Title', 'http://www.alphaframework.org/', 'Test Feed Description');

        $reflection = new \ReflectionClass($feed);
        $property = $reflection->getProperty('nameSpace');
        $property->setAccessible(true);

        $this->assertEquals('http://www.w3.org/2005/Atom', $property->getValue($feed));
    }

    /**
     * Test that Atom feed uses correct root tag.
     *
     * @since 4.1
     */
    public function testRootTag(): void
    {
        $feed = new Atom('Alpha\Model\Article', 'Test Feed Title', 'http://www.alphaframework.org/', 'Test Feed Description');

        $reflection = new \ReflectionClass($feed);
        $property = $reflection->getProperty('rootTag');
        $property->setAccessible(true);

        $this->assertEquals('<feed xmlns="http://www.w3.org/2005/Atom" />', $property->getValue($feed));
    }

    /**
     * Test that Atom feed has no channel element.
     *
     * @since 4.1
     */
    public function testHasChannelIsFalse(): void
    {
        $feed = new Atom('Alpha\Model\Article', 'Test Feed Title', 'http://www.alphaframework.org/', 'Test Feed Description');

        $reflection = new \ReflectionClass($feed);
        $property = $reflection->getProperty('hasChannel');
        $property->setAccessible(true);

        $this->assertFalse($property->getValue($feed));
    }

    /**
     * Test adding an author to an Atom feed.
     *
     * @since 4.1
     */
    public function testAddAuthor(): void
    {
        $feed = new Atom('Alpha\Model\Article', 'Test Feed Title', 'http://www.alphaframework.org/', 'Test Feed Description');
        $feed->addAuthor('John Doe');

        $xml = $feed->render();
        $this->assertStringContainsString('<author>', $xml);
        $this->assertStringContainsString('<name>John Doe</name>', $xml);
    }

    /**
     * Test adding multiple authors to an Atom feed.
     *
     * @since 4.1
     */
    public function testAddMultipleAuthors(): void
    {
        $feed = new Atom('Alpha\Model\Article', 'Test Feed Title', 'http://www.alphaframework.org/', 'Test Feed Description');
        $feed->addAuthor('John Doe');
        $feed->addAuthor('Jane Smith');

        $xml = $feed->render();
        $this->assertStringContainsString('<author>', $xml);
        $this->assertStringContainsString('<name>John Doe</name>', $xml);
        $this->assertStringContainsString('<name>Jane Smith</name>', $xml);
    }

    /**
     * Test adding a record to an Atom feed and parsing the XML.
     *
     * @since 4.1
     */
    public function testAddRecordToAtomAndParse(): void
    {
        $feed = new Atom('Alpha\Model\Article', 'Test Feed Title', 'http://www.alphaframework.org/', 'Test Feed Description');
        $feed->setFieldMappings('title', 'URL', 'description', 'created_ts', 'ID');
        $feed->addRecord($this->record);
        $xml = $feed->render();

        $reader = new XMLReader();
        $validXML = $reader->XML($xml);

        $this->assertTrue($validXML, 'Confirming that the generated XML can be parsed correctly');

        $simpleXML = new SimpleXMLElement($xml);
        $simpleXML->registerXPathNamespace('atom', 'http://www.w3.org/2005/Atom');

        $feeds = $simpleXML->xpath('//atom:feed');
        $this->assertEquals('Test Feed Title', (string) $feeds[0]->title, 'Testing that the feed title is present');
        $this->assertEquals('http://www.alphaframework.org/', (string) $feeds[0]->link->attributes()->href, 'Testing that the feed URL is present');
    }

    /**
     * Test that Atom feed items use 'entry' tag instead of 'item'.
     *
     * @since 4.1
     */
    public function testAtomItemsUseEntryTag(): void
    {
        $feed = new Atom('Alpha\Model\Article', 'Test Feed Title', 'http://www.alphaframework.org/', 'Test Feed Description');
        $feed->setFieldMappings('title', 'URL', 'description', 'created_ts', 'ID');
        $feed->addRecord($this->record);
        $xml = $feed->render();

        $this->assertStringContainsString('<entry', $xml);
        $this->assertStringNotContainsString('<item>', $xml);
    }

    /**
     * Test Atom feed uses 'subtitle' for feed description instead of 'description'.
     *
     * @since 4.1
     */
    public function testAtomFeedDescriptionUsesSubtitle(): void
    {
        $feed = new Atom('Alpha\Model\Article', 'Test Feed Title', 'http://www.alphaframework.org/', 'Test Feed Description');
        $feed->setFieldMappings('title', 'URL', 'description', 'created_ts', 'ID');
        $feed->addRecord($this->record);
        $xml = $feed->render();

        $simpleXML = new SimpleXMLElement($xml);
        $simpleXML->registerXPathNamespace('atom', 'http://www.w3.org/2005/Atom');

        $feeds = $simpleXML->xpath('//atom:feed');
        $this->assertEquals('Test Feed Description', (string) $feeds[0]->subtitle, 'Testing that the feed description is present as subtitle');
    }

    /**
     * Test Atom feed items use 'summary' instead of 'description'.
     *
     * @since 4.1
     */
    public function testAtomItemDescriptionUsesSummary(): void
    {
        $feed = new Atom('Alpha\Model\Article', 'Test Feed Title', 'http://www.alphaframework.org/', 'Test Feed Description');
        $feed->setFieldMappings('title', 'URL', 'description', 'created_ts', 'ID');
        $feed->addRecord($this->record);
        $xml = $feed->render();

        $simpleXML = new SimpleXMLElement($xml);
        $simpleXML->registerXPathNamespace('atom', 'http://www.w3.org/2005/Atom');

        $items = $simpleXML->xpath('//atom:entry');
        $this->assertEquals('Test Article Description', (string) $items[0]->summary, 'Testing that the feed item description is present as summary');
    }

    /**
     * Test rendering Atom feed with multiple items.
     *
     * @since 4.1
     */
    public function testRenderMultipleItems(): void
    {
        $feed = new Atom('Alpha\Model\Article', 'Test Feed Title', 'http://www.alphaframework.org/', 'Test Feed Description');
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

        $this->assertStringContainsString('<entry', $xml);
        $this->assertStringContainsString('Test Article Title', $xml);
        $this->assertStringContainsString('Second Article Title', $xml);

        // Count entry elements
        $simpleXML = new SimpleXMLElement($xml);
        $simpleXML->registerXPathNamespace('atom', 'http://www.w3.org/2005/Atom');
        $items = $simpleXML->xpath('//atom:entry');
        $this->assertCount(2, $items, 'Should have 2 entries');
    }

    /**
     * Test Atom feed XML output is valid and well-formed.
     *
     * @since 4.1
     */
    public function testValidXMLStructure(): void
    {
        $feed = new Atom('Alpha\Model\Article', 'Test Feed Title', 'http://www.alphaframework.org/', 'Test Feed Description');
        $feed->setFieldMappings('title', 'URL', 'description', 'created_ts', 'ID');
        $feed->addRecord($this->record);
        $feed->addAuthor('John Doe');
        $xml = $feed->render();

        $reader = new XMLReader();
        $validXML = $reader->XML($xml);

        $this->assertTrue($validXML, 'Confirming that the generated XML is well-formed');

        // Verify required Atom elements are present
        $simpleXML = new SimpleXMLElement($xml);
        $simpleXML->registerXPathNamespace('atom', 'http://www.w3.org/2005/Atom');

        $feeds = $simpleXML->xpath('//atom:feed');
        $this->assertEquals(1, count($feeds), 'Should have exactly one feed element');
    }

    /**
     * Test Atom feed default pubDate format when not provided.
     *
     * @since 4.1
     */
    public function testDefaultPubDateFormat(): void
    {
        $feed = new Atom('Alpha\Model\Article', 'Test Feed Title', 'http://www.alphaframework.org/', 'Test Feed Description');
        $feed->setFieldMappings('title', 'URL', 'description', 'created_ts', 'ID');
        $feed->addRecord($this->record);
        $xml = $feed->render();

        $simpleXML = new SimpleXMLElement($xml);
        $simpleXML->registerXPathNamespace('atom', 'http://www.w3.org/2005/Atom');

        $items = $simpleXML->xpath('//atom:entry');
        $this->assertStringContainsString('T', (string) $items[0]->updated, 'PubDate should be in ISO 8601 format');
    }

    /**
     * Test that feed renders empty string when rssDoc is not initialized.
     *
     * @since 4.1
     */
    public function testRenderEmptyFeed(): void
    {
        $feed = new Atom('Alpha\Model\Article', 'Test Feed Title', 'http://www.alphaframework.org/', 'Test Feed Description');
        $xml = $feed->render();

        $this->assertStringContainsString('<?xml', $xml);
        $this->assertStringContainsString('<feed', $xml);
        $this->assertStringContainsString('</feed>', $xml);
    }
}
