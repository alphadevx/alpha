<?php

require_once $config->get('sysRoot').'alpha/model/DAO.inc';
require_once $config->get('sysRoot').'alpha/util/handle_error.inc';

/**
 *
 * An article vote class for user ratings
 * 
 * @package alpha::model
 * @author John Collins <john@design-ireland.net>
 * @copyright 2009 John Collins
 * $Id$
 * 
 */ 
class article_vote_object extends DAO {
	/**
	 * The article this comment belongs to
	 * 
	 * @var Relation
	 */
	protected $article_oid;
	
	/**
	 * The person who cast the vote
	 * 
	 * @var Relation
	 */
	protected $person_oid;
	
	/**
	 * The actual vote score (default 1-10)
	 * 
	 * @var Integer
	 */	
	protected $score;
	
	/**
	 * An array of data display labels for the class properties
	 * 
	 * @var array
	 */
	protected $dataLabels = array(
		"OID"=>"Article Vote ID#",
		"article_oid"=>"Article",
		"person_oid"=>"Voter",
		"score"=>"Article Score"
	);
	
	/**
	 * the name of the database table for the class
	 * 
	 * @var string
	 */
	const TABLE_NAME = 'article_vote';
	
	/**
	 * Constructor for the class
	 */
	public function __construct() {
		// ensure to call the parent constructor
		parent::__construct();
		
		$this->article_oid = new Relation();
		$this->article_oid->setRelatedClass('article_object');
		$this->article_oid->setRelatedClassField('OID');
		$this->article_oid->setRelatedClassDisplayField('description');
		$this->article_oid->setRelationType('MANY-TO-ONE');
		
		$this->person_oid = new Relation();
		$this->person_oid->setRelatedClass('person_object');
		$this->person_oid->setRelatedClassField('OID');
		$this->person_oid->setRelatedClassDisplayField('email');
		$this->person_oid->setRelationType('MANY-TO-ONE');
		
		$this->score = new Integer();
	}	
}

?>