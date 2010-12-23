<?php

require_once $config->get('sysRoot').'alpha/model/AlphaDAO.inc';
require_once $config->get('sysRoot').'alpha/util/helpers/Validator.inc';

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
class tag_object extends AlphaDAO {
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
	 * Trace logger
	 * 
	 * @var Logger
	 */
	private static $logger = null;
	
	/**
	 * The constructor
	 */
	public function __construct() {
		if(self::$logger == null)
			self::$logger = new Logger('tag_object');
			
		// ensure to call the parent constructor
		parent::__construct();
		$this->taggedClass = new String();
		$this->taggedOID = new Integer();
		$this->content = new String();
	}
	
	/**
	 * Returns an array of tag_objects matching the class and OID provided
	 * 
	 * @param $taggedClass
	 * @param $taggedOID
	 * @return array
	 */
	public function loadTags($taggedClass, $taggedOID) {
		global $config;		
		
		$sqlQuery = "SELECT OID FROM ".$this->getTableName()." WHERE taggedOID='$taggedOID' AND taggedClass='$taggedClass' ORDER BY OID";
		
		$this->lastQuery = $sqlQuery;

		$result = mysql_query($sqlQuery);
		
		if(mysql_error() != '') {
			throw new BONotFoundException('Failed to load object OIDs, MySql error is ['.mysql_error().'], query ['.$this->lastQuery.']');
			return array();
		}
		
		// now build an array of objects to be returned
		$objects = array();
		$count = 0;
		$BO_Class = get_class($this);
		
		while($row = mysql_fetch_assoc($result)) {
			$obj = new $BO_Class();
			$obj->load($row['OID']);
			$objects[$count] = $obj;
			$count++;
		}
		
		return $objects;
	}
	
	/**
	 * Returns a hash array of the most popular tags based on their occurence in the database, 
	 * ordered by alphabet and restricted to the a count matching the $limit supplied.  The 
	 * returned has array uses the tag content as a key and the database value as a value.
	 *  
	 * @param $limit
	 * @return array
	 */
	public static function getPopularTagsArray($limit) {
		global $config;		
		
		$sqlQuery = "SELECT content, count(*) as count FROM `tag` GROUP BY content ORDER BY count DESC LIMIT $limit";
		
		$result = mysql_query($sqlQuery);
		
		if(mysql_error() != '') {
			throw new BONotFoundException('Failed to query the tags table, MySql error is ['.mysql_error().'], query ['.$this->lastQuery.']');
			return array();
		}
		
		// now build an array of tags to be returned
		$popTags = array();		
		
		while($row = mysql_fetch_assoc($result)) {			
			$popTags[$row['content']] = $row['count'];			
		}
		
		// sort the array by content key before returning
		ksort($popTags);		
		return $popTags;
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
	 * @param $taggedClass Optionally provide a BO class name
	 * @param $taggedOID Optionally provide a BO instance OID
	 * @param $applyStopwords Defaults true, set to false if you want to ignore the stopwords.
	 * @return array
	 */
	public static function tokenize($content, $taggedClass='', $taggedOID='', $applyStopwords=true) {
		if(self::$logger == null)
			self::$logger = new Logger('tag_object');
			
		global $config;
		
		// apply stop words
		$lowerWords = preg_split("/[\s,.:]+/", $content);
		array_walk($lowerWords, 'tag_object::lowercaseArrayElement');
		if($applyStopwords) {		
			$stopwords = file($config->get('sysRoot').'config/stopwords.ini', FILE_IGNORE_NEW_LINES);
			array_walk($stopwords, 'tag_object::lowercaseArrayElement');
			$filtered = array_diff($lowerWords, $stopwords);
		}else{
			$filtered = $lowerWords;
		}
		
		$tagObjects = array();
		$tagContents = array();
		foreach($filtered as $tagContent) {
			// we only want to create word tags
			if(Validator::isAlpha($tagContent)) {
				// just making sure that we haven't added this one in already
				if(!in_array($tagContent, $tagContents) && !empty($tagContent)) {
					$tag = new tag_object();
					$tag->set('content', trim(strtolower($tagContent)));
					if(!empty($taggedClass))
						$tag->set('taggedClass', $taggedClass);
					if(!empty($taggedOID))
						$tag->set('taggedOID', $taggedOID);
					
					array_push($tagObjects, $tag);
					array_push($tagContents, $tagContent);
				}
			}
		}
		
		self::$logger->debug('Tags generated: ['.print_r($tagContents, true).']');
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
	
	/**
	 * Cleans tag content by remocing white spaces and converting to lowercase.
	 * 
	 * @param $content
	 * @return string
	 */
	public static function cleanTagContent($content) {
		return trim(strtolower(str_replace(' ', '', $content)));
	}
}

?>