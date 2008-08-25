<?php

// $Id$

require_once $config->get('sysRoot').'alpha/model/mysqlDAO.inc';
require_once $config->get('sysRoot').'alpha/util/handle_error.inc';

/**
 *
 * an article comment class for user comments
 * 
 * @package Alpha CMS
 * @author John Collins <john@design-ireland.net>
 * @copyright 2006 John Collins
 * 
 * 
 */ 
class article_comment_object extends mysqlDAO
{
	/**
	 * the OID of the parent article
	 * @var Integer
	 */
	var $article_oid;	
	
	/**
	 * the content of the comment posted by the user
	 * @var Text
	 */	
	var $content;
	
	/**
	 * an array of data display labels for the class properties
	 * @var array
	 */
	var $dataLabels = array(
		"OID"=>"Article Comment ID#",
		"article_oid"=>"Article ID#",		
		"content"=>"Your Comment"
	);
	
	/**
	 * the name of the database table for the class
	 * @var string
	 */
	const TABLE_NAME = 'article_comment';
	
	/**
	 * constructor for the class
	 */
	function article_comment_object() {
		// ensure to call the parent constructor
		parent::__construct();
		$this->article_oid = new Integer();		
		$this->content = new Text();
	}	
}

?>