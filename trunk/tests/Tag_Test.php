<?php

/**
 *
 * Test case for the tag_object class
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
class Tag_Test extends PHPUnit_Framework_TestCase {
	/**
	 * An article_object for testing
	 * 
	 * @var article_object
	 * @since 1.0
	 */
	private $article;
	
	/**
     * Called before the test functions will be executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here
     * 
     * @since 1.0
     */
    protected function setUp() {
    	$this->article = $this->createArticleObject('unitTestArticle');
    }
    
    /** 
     * Called after the test functions are executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here
     * 
     * @since 1.0
     */    
    protected function tearDown() {
    	$this->article->delete();
        unset($this->article);
    }
    
    /**
     * Creates an article object for testing
     * 
     * @return article_object
     * @since 1.0
     */
    private function createArticleObject($name) {
    	$article = new article_object();
        $article->set('title', $name);
        $article->set('description', 'A test article called unitTestArticle with some stop words and the unitTestArticle title twice');
        $article->set('author', 'blah');
        $article->set('content', 'blah');        
        
        return $article;
    }
    
    /**
     * Testing the tag_object::tokenize method returns a tag called "unittestarticle"
     * 
     * @since 1.0
     */
    public function testTokenizeForExpectedTag() {
    	$tags = tag_object::tokenize($this->article->get('description'), 'article_object', $this->article->getOID());
    	
    	$found = false;
    	foreach($tags as $tag) {
    		if($tag->get('content') == 'unittestarticle') {
    			$found = true;
    			break;
    		}
    	}
    	$this->assertTrue($found, 'Testing the tag_object::tokenize method returns a tag called "unittestarticle"');
    }
    
    /**
     * Testing the tag_object::tokenize method does not return a tag called "a"
     * 
     * @since 1.0
     */
    public function testTokenizeForUnexpectedTag() {
    	$tags = tag_object::tokenize($this->article->get('description'), 'article_object', $this->article->getOID());
    	
    	$found = false;
    	foreach($tags as $tag) {
    		if($tag->get('content') == 'a') {
    			$found = true;
    			break;
    		}
    	}
    	$this->assertFalse($found, 'Testing the tag_object::tokenize method does not return a tag called "a"');
    }

    /**
     * Test to ensure that the duplicated value "unittestarticle" is only converted to a tag_object once by tag_object::tokenize
     * 
     * @since 1.0
     */
    public function testTokenizeNoDuplicates() {
    	$tags = tag_object::tokenize($this->article->get('description'), 'article_object', $this->article->getOID());
    	
    	$count = 0;
    	foreach($tags as $tag) {
    		if($tag->get('content') == 'unittestarticle') {
    			$count++;
    		}
    	}
    	
    	$this->assertEquals(1, $count, 'Test to ensure that the duplicated value "unittestarticle" is only converted to a tag_object once by tag_object::tokenize');
    }
    
    /**
     * Testing that when an article_object is created that tags are autogenerated based on the description
     * 
     * @since 1.0
     */
    public function testSaveArticleGeneratesDescriptionTags() {
    	$this->article->save();    	
    	$tags = $this->article->getPropObject('tags')->getRelatedObjects();
    	
    	$found = false;
    	foreach($tags as $tag) {
    		if($tag->get('content') == 'unittestarticle') {
    			$found = true;
    			break;
    		}
    	}
    	$this->assertTrue($found, 'Testing the tag_object::tokenize method returns a tag called "unittestarticle"');
    }
    
    /**
     * Testing the loadTags() method for accessing the tags on a given object type directly
     * 
     * @since 1.0
     */
    public function testLoadTags() {
    	$this->article->save();    	
    	$tagsA = $this->article->getPropObject('tags')->getRelatedObjects();
    	
    	$tag = new tag_object();
    	$tagsB = $tag->loadTags('article_object', $this->article->getOID());
    	
    	$this->assertEquals(count($tagsA), count($tagsB), 'testing the loadTags() method for accessing the tags on a given object type directly');
    }
}

?>