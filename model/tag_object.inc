<?php

require_once $config->get('sysRoot').'alpha/model/DAO.inc';

/**
 *
 * The tag class used in tag clouds and search
 * 
 * @package alpha::model
 * @author John Collins <john@design-ireland.net>
 * @copyright 2009 John Collins
 * @version $Id$ 
 * 
 */ 
class tag_object extends DAO {
	/**
	 * The name of the class of the object which is tagged
	 *
	 * @var String
	 */
	protected $taggedClass;
	
	/**
	 * The OID of the object which is tagged
	 *
	 * @var Integer
	 */
	protected $taggedOID;
	
	/**
	 * The content of the tag
	 *
	 * @var String
	 */
	protected $content;
	
	/**
	 * An array of data display labels for the class properties
	 * 
	 * @var array
	 */
	protected $dataLabels = array(
		"OID"=>"Tag ID#",		
		"taggedClass"=>"Class Name",
		"taggedOID"=>"Tagged Object ID#",
		"content"=>"Tag"
	);
	
	/**
	 * The name of the database table for the class
	 * 
	 * @var string
	 */
	const TABLE_NAME = 'tag';
	
	/**
	 * Constructor
	 */
	public function __construct() {
		// ensure to call the parent constructor
		parent::__construct();
		$this->taggedClass = new String();
		$this->taggedOID = new Integer();
		$this->content = new String();
	}
	
	/**
	 * Use this callback to create the tclass_toid__tcontent unique table index
	 */
	protected function after_checkIndexes_callback() {
		$indexNames = $this->getIndexes();
		$indexExists = false;
		
		foreach ($indexNames as $index) {					
			if ('tclass_toid_tcontent_unq_idx' == $index) {
		    	$indexExists = true;
			}
		}
		
		if(!$indexExists) {
			$sqlQuery = 'CREATE UNIQUE INDEX tclass_toid_tcontent_unq_idx ON '.$this->getTableName().' (taggedClass,taggedOID,content);';
				
			$this->lastQuery = $sqlQuery;
		
			$result = mysql_query($sqlQuery);
		
			if (mysql_error() != '') {				
				throw new AlphaException('Failed to create the index [tclass_toid_tcontent_unq_idx] on ['.$this->getTableName().'], error is ['.mysql_error().']');
			}
		}
	}

	/**
	 * Splits the passed content by spaces, filters (removes) stop words from stopwords.ini, 
	 * and returns an array of tag_object instances.
	 * 
	 * @param $content
	 * @param $taggedClass
	 * @param $taggedOID
	 * @return array
	 */
	public static function tokenize($content, $taggedClass, $taggedOID) {
		global $config;
		
		// apply stop words
		$lowerWords = explode(' ', $content);
		array_walk($lowerWords, 'tag_object::lowercaseArrayElement');		
		$stopwords = file($config->get('sysRoot').'config/stopwords.ini', FILE_IGNORE_NEW_LINES);
		array_walk($stopwords, 'tag_object::lowercaseArrayElement');
		$filtered = array_diff($lowerWords, $stopwords);		
		
		$tagObjects = array();
		$tagContents = array();
		foreach($filtered as $tagContent) {
			// just making sure that we haven't added this one in already
			if(!in_array($tagContent, $tagContents)) {
				$tag = new tag_object();
				$tag->set('content', trim(strtolower($tagContent)));
				$tag->set('taggedClass', $taggedClass);
				$tag->set('taggedOID', $taggedOID);
				
				array_push($tagObjects, $tag);
				array_push($tagContents, $tagContent);
			}
		}
		
		return $tagObjects;
	}
	
	/**
	 * Applies trim() and strtolower to the array element passed by reference
	 * 
	 * @param $element
	 * @param $key (not required)
	 */
	private static function lowercaseArrayElement(&$element, $key) {
		$element = trim(strtolower($element));
	}
}

?>