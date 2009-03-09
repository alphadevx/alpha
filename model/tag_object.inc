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
}

?>