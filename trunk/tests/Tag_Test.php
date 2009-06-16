<?php

/**
 *
 * Test case for the tag_object class
 * 
 * @package alpha::tests
 * @author John Collins <john@design-ireland.net>
 * @copyright 2009 John Collins
 * @version $Id$ 
 * 
 */
class Tag_Test extends PHPUnit_Framework_TestCase {
	/**
	 * An article_object for testing
	 * 
	 * @var article_object
	 */
	private $article;
	
	/**
     * called before the test functions will be executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here
     */
    protected function setUp() {
    	$this->article = $this->createArticleObject('unitTestArticle');
        // just making sure no previous test article is in the DB
        $this->article->deleteAllByAttribute('title', 'unitTestArticle');        
    }
    
    /** 
     * called after the test functions are executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here
     */    
    protected function tearDown() {
    	if(!$this->article->isTransient())
    		$this->article->delete(); 
        unset($this->article);
    }
    
    /**
     * creates an article object for testing
     * 
     * @return article_object
     */
    private function createArticleObject($name) {
    	$article = new article_object();
        $article->set('title', $name);        
        $article->set('description', 'A test article called unitTestArticle with some stop words and the unitTestArticle title twice');        
        
        return $article;
    }
    
    /**
     * Testing the tag_object::tokenize method returns a tag called "unittestarticle"
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
}

?>