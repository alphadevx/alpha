<?php

namespace Alpha\Test\Util\Feed;

use Alpha\Model\Article;
use Alpha\Util\Feed\RSS;
use XMLReader;
use SimpleXMLElement;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for the RSS class (RSS 1.0).
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
class RSSTest extends TestCase
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
     * Test creating a basic RSS feed with default parameters.
     *
     * @since 4.1
     */
    public function testConstructorWithDefaults(): void
    {
        $feed = new RSS('Alpha\Model\Article', 'Test Feed Title', 'http://www.alphaframework.org/', 'Test Feed Description');

        $this->assertInstanceOf(RSS::class, $feed);
        $this->assertStringContainsString('http://purl.org/rss/1.0/', $feed->render());
    }

    /**
     * Test creating an RSS feed with all parameters.
     *
     * @since 4.1
     */
    public function testConstructorWithAllParameters(): void
    {
        $feed = new RSS(
            'Alpha\Model\Article',
            'Test Feed Title',
            'http://www.alphaframework.org/',
            'Test Feed Description',
            '2021-12-25 10:00:00',
            123,
            20
        );

        $this->assertInstanceOf(RSS::class, $feed);
        $this->assertStringContainsString('http://purl.org/rss/1.0/', $feed->render());
    }

    /**
     * Test that RSS feed has correct namespace.
     *
     * @since 4.1
     */
    public function testNamespace(): void
    {
        $feed = new RSS('Alpha\Model\Article', 'Test Feed Title', 'http://www.alphaframework.org/', 'Test Feed Description');

        $reflection = new \ReflectionClass($feed);
        $property = $reflection->getProperty('nameSpace');
        $property->setAccessible(true);

        $this->assertEquals('http://purl.org/rss/1.0/', $property->getValue($feed));
    }

    /**
     * Test that RSS feed has correct RDF namespace.
     *
     * @since 4.1
     */
    public function testRDFNamespace(): void
    {
        $feed = new RSS('Alpha\Model\Article', 'Test Feed Title', 'http://www.alphaframework.org/', 'Test Feed Description');

        $reflection = new \ReflectionClass($feed);
        $property = $reflection->getProperty('rdfns');
        $property->setAccessible(true);

        $this->assertEquals('http://www.w3.org/1999/02/22-rdf-syntax-ns#', $property->getValue($feed));
    }

    /**
     * Test that RSS feed uses correct root tag.
     *
     * @since 4.1
     */
    public function testRootTag(): void
    {
        $feed = new RSS('Alpha\Model\Article', 'Test Feed Title', 'http://www.alphaframework.org/', 'Test Feed Description');

        $reflection = new \ReflectionClass($feed);
        $property = $reflection->getProperty('rootTag');
        $property->setAccessible(true);

        $this->assertEquals('<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns="http://purl.org/rss/1.0/" />', $property->getValue($feed));
    }

    /**
     * Test that RSS feed has channel element (inherits from Feed).
     *
     * @since 4.1
     */
    public function testHasChannelIsTrue(): void
    {
        $feed = new RSS('Alpha\Model\Article', 'Test Feed Title', 'http://www.alphaframework.org/', 'Test Feed Description');

        $reflection = new \ReflectionClass($feed);
        $property = $reflection->getProperty('hasChannel');
        $property->setAccessible(true);

        $this->assertTrue($property->getValue($feed));
    }

    /**
     * Test adding a record to an RSS feed and parsing the XML.
     *
     * @since 4.1
     */
    public function testAddRecordToRSSAndParse(): void
    {
        $feed = new RSS('Alpha\Model\Article', 'Test Feed Title', 'http://www.alphaframework.org/', 'Test Feed Description');
        $feed->setFieldMappings('title', 'URL', 'description', 'created_ts', 'ID');
        $feed->addRecord($this->record);
        $xml = $feed->render();

        $reader = new XMLReader();
        $validXML = $reader->XML($xml);

        $this->assertTrue($validXML, 'Confirming that the generated XML can be parsed correctly');
    }

    /**
     * Test RSS feed uses RDF namespace in root element.
     *
     * @since 4.1
     */
    public function testRSSRootUsesRDFNamespace(): void
    {
        $feed = new RSS('Alpha\Model\Article', 'Test Feed Title', 'http://www.alphaframework.org/', 'Test Feed Description');
        $xml = $feed->render();

        $this->assertStringContainsString('xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"', $xml);
        $this->assertStringContainsString('xmlns="http://purl.org/rss/1.0/"', $xml);
    }

    /**
     * Test RSS feed items have rdf:about attribute.
     *
     * @since 4.1
     */
    public function testRSSItemHasRDFAboutAttribute(): void
    {
        $feed = new RSS('Alpha\Model\Article', 'Test Feed Title', 'http://www.alphaframework.org/', 'Test Feed Description');
        $feed->setFieldMappings('title', 'URL', 'description', 'created_ts', 'ID');
        $feed->addRecord($this->record);
        $xml = $feed->render();

        $this->assertStringContainsString('rdf:about="http://www.alphaframework.org/"', $xml);
    }

    /**
     * Test RSS feed items use Seq container for items list.
     *
     * @since 4.1
     */
    public function testRSSItemsUseSeqContainer(): void
    {
        $feed = new RSS('Alpha\Model\Article', 'Test Feed Title', 'http://www.alphaframework.org/', 'Test Feed Description');
        $feed->setFieldMappings('title', 'URL', 'description', 'created_ts', 'ID');
        $feed->addRecord($this->record);
        $xml = $feed->render();

        // RSS 1.0 uses Seq element to list items
        $this->assertStringContainsString('<rdf:Seq', $xml);
    }

    /**
     * Test RSS feed items are added to Seq container.
     *
     * @since 4.1
     */
    public function testRSSItemsAddedToSeq(): void
    {
        $feed = new RSS('Alpha\Model\Article', 'Test Feed Title', 'http://www.alphaframework.org/', 'Test Feed Description');
        $feed->setFieldMappings('title', 'URL', 'description', 'created_ts', 'ID');
        $feed->addRecord($this->record);
        $xml = $feed->render();

        // Check for Seq containing li elements
        $this->assertStringContainsString('<li', $xml);
    }

    /**
     * Test RSS feed items have resource attribute.
     *
     * @since 4.1
     */
    public function testRSSItemLiHasResourceAttribute(): void
    {
        $feed = new RSS('Alpha\Model\Article', 'Test Feed Title', 'http://www.alphaframework.org/', 'Test Feed Description');
        $feed->setFieldMappings('title', 'URL', 'description', 'created_ts', 'ID');
        $feed->addRecord($this->record);
        $xml = $feed->render();

        // RSS 1.0 uses rdf:li with resource attribute (empty if URL not set on record)
        $this->assertStringContainsString('rdf:li resource="', $xml);
    }

    /**
     * Test rendering RSS feed with multiple items.
     *
     * @since 4.1
     */
    public function testRenderMultipleItems(): void
    {
        $feed = new RSS('Alpha\Model\Article', 'Test Feed Title', 'http://www.alphaframework.org/', 'Test Feed Description');
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

        $this->assertStringContainsString('<li', $xml);
        $this->assertStringContainsString('Test Article Title', $xml);
        $this->assertStringContainsString('Second Article Title', $xml);
    }

    /**
     * Test RSS feed XML output is valid and well-formed.
     *
     * @since 4.1
     */
    public function testValidXMLStructure(): void
    {
        $feed = new RSS('Alpha\Model\Article', 'Test Feed Title', 'http://www.alphaframework.org/', 'Test Feed Description');
        $feed->setFieldMappings('title', 'URL', 'description', 'created_ts', 'ID');
        $feed->addRecord($this->record);
        $xml = $feed->render();

        $reader = new XMLReader();
        $validXML = $reader->XML($xml);

        $this->assertTrue($validXML, 'Confirming that the generated XML is well-formed');
    }

    /**
     * Test RSS feed channel element structure with required elements.
     *
     * @since 4.1
     */
    public function testRSSChannelStructure(): void
    {
        $feed = new RSS('Alpha\Model\Article', 'Test Feed Title', 'http://www.alphaframework.org/', 'Test Feed Description');
        $feed->setFieldMappings('title', 'URL', 'description', 'created_ts', 'ID');
        $feed->addRecord($this->record);
        $xml = $feed->render();

        $simpleXML = new SimpleXMLElement($xml);

        // Check for required channel elements
        $this->assertNotNull($simpleXML->channel);
        $this->assertNotNull($simpleXML->channel->title);
        $this->assertNotNull($simpleXML->channel->link);
        $this->assertNotNull($simpleXML->channel->description);
    }

    /**
     * Test that feed renders properly when rssDoc is initialized.
     *
     * @since 4.1
     */
    public function testRenderFeed(): void
    {
        $feed = new RSS('Alpha\Model\Article', 'Test Feed Title', 'http://www.alphaframework.org/', 'Test Feed Description');
        $xml = $feed->render();

        $this->assertStringContainsString('<?xml', $xml);
        $this->assertStringContainsString('<rdf:RDF', $xml);
        $this->assertStringContainsString('</rdf:RDF>', $xml);
    }

    /**
     * Test RSS feed tagMap inheritance from parent class.
     *
     * @since 4.1
     */
    public function testTagMapInheritance(): void
    {
        $feed = new RSS('Alpha\Model\Article', 'Test Feed Title', 'http://www.alphaframework.org/', 'Test Feed Description');

        $reflection = new \ReflectionClass($feed);
        $property = $reflection->getProperty('tagMap');
        $property->setAccessible(true);

        $tagMap = $property->getValue($feed);
        $this->assertEquals('item', $tagMap['item']);
        $this->assertEquals('description', $tagMap['feeddesc']);
        $this->assertEquals('description', $tagMap['itemdesc']);
    }

    /**
     * Test that items are properly linked in the Seq container.
     *
     * @since 4.1
     */
    public function testItemsProperlyLinkedInSeq(): void
    {
        $feed = new RSS('Alpha\Model\Article', 'Test Feed Title', 'http://www.alphaframework.org/', 'Test Feed Description');
        $feed->setFieldMappings('title', 'URL', 'description', 'created_ts', 'ID');
        $feed->addRecord($this->record);
        $xml = $feed->render();

        $simpleXML = new SimpleXMLElement($xml);
        $simpleXML->registerXPathNamespace('rdf', 'http://www.w3.org/1999/02/22-rdf-syntax-ns#');
        $simpleXML->registerXPathNamespace('rss', 'http://purl.org/rss/1.0/');

        // Check for Seq element
        $seqs = $simpleXML->xpath('//rdf:Seq');
        $this->assertEquals(1, count($seqs), 'Should have exactly one Seq element');

        // Check for li elements within Seq
        $lis = $simpleXML->xpath('//rdf:Seq/rdf:li');
        $this->assertGreaterThan(0, count($lis), 'Should have at least one li element');
    }

    /**
     * Test that adding multiple items properly updates the Seq container.
     *
     * @since 4.1
     */
    public function testMultipleItemsUpdateSeq(): void
    {
        $feed = new RSS('Alpha\Model\Article', 'Test Feed Title', 'http://www.alphaframework.org/', 'Test Feed Description');
        $feed->setFieldMappings('title', 'URL', 'description', 'created_ts', 'ID');

        $feed->addRecord($this->record);

        $record2 = new Article();
        $record2->set('title', 'Second Article Title');
        $record2->set('description', 'Second Article Description');
        $record2->set('created_ts', '2011-01-02 00:00:00');
        $feed->addRecord($record2);

        $xml = $feed->render();
        $simpleXML = new SimpleXMLElement($xml);

        // Count li elements
        $lis = $simpleXML->xpath('//rdf:Seq/rdf:li');
        $this->assertCount(2, $lis, 'Should have 2 li elements for 2 records');
    }
}
