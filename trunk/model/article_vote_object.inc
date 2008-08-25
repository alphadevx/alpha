<?php

// $Id$

require_once $config->get('sysRoot').'alpha/model/mysqlDAO.inc';
require_once $config->get('sysRoot').'alpha/util/handle_error.inc';

/**
 *
 * an article vote class for user ratings
 * 
 * @package Alpha CMS
 * @author John Collins <john@design-ireland.net>
 * @copyright 2006 John Collins
 * 
 * 
 */ 
class article_vote_object extends mysqlDAO
{
	/**
	 * the OID of the parent article
	 * @var Integer
	 */
	var $article_oid;
	
	/**
	 * the OID of the person casting the vote
	 * @var Integer
	 */
	var $person_oid;
	
	/**
	 * the actual vote score (default 1-10)
	 * @var Integer
	 */	
	var $score;
	
	/**
	 * an array of data display labels for the class properties
	 * @var array
	 */
	var $dataLabels = array(
		"OID"=>"Article Vote ID#",
		"article_oid"=>"Article ID#",
		"person_oid"=>"Person ID#",
		"score"=>"Article Score"
	);
	
	/**
	 * the name of the database table for the class
	 * @var string
	 */
	const TABLE_NAME = 'article_vote';
	
	/**
	 * constructor for the class
	 */
	function article_vote_object() {
		// ensure to call the parent constructor
		parent::__construct();
		$this->article_oid = new Integer();
		$this->person_oid = new Integer();
		$this->score = new Integer();
	}	
}

?>