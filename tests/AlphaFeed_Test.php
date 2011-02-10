<?php

require_once $config->get('sysRoot').'alpha/util/feeds/RSS.inc';
require_once $config->get('sysRoot').'alpha/util/feeds/RSS2.inc';
require_once $config->get('sysRoot').'alpha/util/feeds/Atom.inc';
require_once $config->get('sysRoot').'alpha/model/article_object.inc';

/**
 * Test cases for the AlphaFeed class and its children
 * 
 * @package alpha::tests
 * @since 1.0
 * @author John Collins <john@design-ireland.net>
 * @version $Id$
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2010, John Collins (founder of Alpha Framework).  
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
 *  
 */
class AlphaFeed_Test extends PHPUnit_Framework_TestCase {
	/**
	 * Test object to inject into a feed
	 * 
	 * @var article_object
	 */
	private $BO;
	
	/**
     * Called before the test functions will be executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here
     * 
     * @since 1.0
     */
    protected function setUp() {
    	$this->BO = new article_object();
    	$this->BO->set('title', 'Test Article Title');
    	$this->BO->set('description', 'Test Article Description');
    	$this->BO->set('created_ts', '2011-01-01 00:00:00');
    }
    
    /** 
     * Called after the test functions are executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here
     * 
     * @since 1.0
     */    
    protected function tearDown() {
    	unset($this->BO);
    }
    
    public function testAddItemToRSSandParse() {
    	$feed = new RSS('article_object', 'Test Feed Title', 'http://www.alphaframework.org/', 'Test Feed Description');
		$feed->setFieldMappings('title', 'URL', 'description', 'created_ts', 'OID');
		$feed->addBO($this->BO);
		$xml = $feed->render();
		
		$reader = new XMLReader();
		$validXML = $reader->XML($xml);
		
		$this->assertTrue($validXML, 'Confirming that the generated XML can be parsed correctly');
		
		$simpleXML = new SimpleXMLElement($xml);
		$simpleXML->registerXPathNamespace('rss', 'http://purl.org/rss/1.0/');

		$channels = $simpleXML->xpath('//rss:channel');
		$this->assertEquals('Test Feed Title', (string)$channels[0]->title, 'Testing that the feed title is present');
		$this->assertEquals('http://www.alphaframework.org/', (string)$channels[0]->link, 'Testing that the feed URL is present');
		
		$items = $simpleXML->xpath('//rss:item');
		$this->assertEquals('Test Article Title', (string)$items[0]->title, 'Testing that the feed item title is present');
		$this->assertEquals('Test Article Description', (string)$items[0]->description, 'Testing that the feed item description is present');
		$this->assertEquals('2011-01-01T00:00:00+00:00', (string)$items[0]->updated, 'Testing that the feed item publish time is present');
    }
    
	public function testAddItemToRSS2andParse() {
    	$feed = new RSS2('article_object', 'Test Feed Title', 'http://www.alphaframework.org/', 'Test Feed Description');
		$feed->setFieldMappings('title', 'URL', 'description', 'created_ts', 'OID');
		$feed->addBO($this->BO);
		$xml = $feed->render();
		
		$reader = new XMLReader();
		$validXML = $reader->XML($xml);
		
		$this->assertTrue($validXML, 'Confirming that the generated XML can be parsed correctly');
		
		$simpleXML = new SimpleXMLElement($xml);
		
		$channels = $simpleXML->xpath('channel');
		$this->assertEquals('Test Feed Title', (string)$channels[0]->title, 'Testing that the feed title is present');
		$this->assertEquals('http://www.alphaframework.org/', (string)$channels[0]->link, 'Testing that the feed URL is present');
		
		$items = $simpleXML->xpath('channel/item');
		$this->assertEquals('Test Article Title', (string)$items[0]->title, 'Testing that the feed item title is present');
		$this->assertEquals('Test Article Description', (string)$items[0]->description, 'Testing that the feed item description is present');
		$this->assertEquals('2011-01-01T00:00:00+00:00', (string)$items[0]->updated, 'Testing that the feed item publish time is present');
    }
    
	public function testAddItemToAtomandParse() {
    	$feed = new Atom('article_object', 'Test Feed Title', 'http://www.alphaframework.org/', 'Test Feed Description');
		$feed->setFieldMappings('title', 'URL', 'description', 'created_ts', 'OID');
		$feed->addBO($this->BO);
		$xml = $feed->render();
		
		$reader = new XMLReader();
		$validXML = $reader->XML($xml);
		
		$this->assertTrue($validXML, 'Confirming that the generated XML can be parsed correctly');
		
		$simpleXML = new SimpleXMLElement($xml);
		$simpleXML->registerXPathNamespace('atom', 'http://www.w3.org/2005/Atom');
		
		$feeds = $simpleXML->xpath('//atom:feed');
		$this->assertEquals('Test Feed Title', (string)$feeds[0]->title, 'Testing that the feed title is present');
		$this->assertEquals('http://www.alphaframework.org/', (string)$feeds[0]->link->attributes()->href, 'Testing that the feed URL is present');
		
		$items = $simpleXML->xpath('//atom:entry');
		$this->assertEquals('Test Article Title', (string)$items[0]->title, 'Testing that the feed item title is present');
		$this->assertEquals('Test Article Description', (string)$items[0]->summary, 'Testing that the feed item description is present');
		$this->assertEquals('2011-01-01T00:00:00+00:00', (string)$items[0]->updated, 'Testing that the feed item publish time is present');
    }
}

?>