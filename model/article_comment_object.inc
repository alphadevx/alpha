<?php

require_once $config->get('sysRoot').'alpha/model/AlphaDAO.inc';
require_once $config->get('sysRoot').'alpha/util/handle_error.inc';

/**
 *
 * An article comment class for user comments
 * 
 * @package alpha::model
 * @author John Collins <john@design-ireland.net>
 * @copyright 2009 John Collins
 * @version $Id$
 * 
 */ 
class article_comment_object extends AlphaDAO {
	/**
	 * The article this comment belongs to
	 * 
	 * @var Relation
	 */
	protected $article_oid;
	
	/**
	 * The content of the comment posted by the user
	 * 
	 * @var Text
	 */	
	protected $content;
	
	/**
	 * An array of data display labels for the class properties
	 * 
	 * @var array
	 */
	protected $dataLabels = array(
		"OID"=>"Article Comment ID#",
		"article_oid"=>"Article",		
		"content"=>"Comment"
	);
	
	/**
	 * the name of the database table for the class
	 * @var string
	 */
	const TABLE_NAME = 'article_comment';
	
	/**
	 * constructor for the class
	 */
	public function __construct() {
		// ensure to call the parent constructor
		parent::__construct();
		
		$this->article_oid = new Relation();
		$this->article_oid->setRelatedClass('article_object');
		$this->article_oid->setRelatedClassField('OID');
		$this->article_oid->setRelatedClassDisplayField('description');
		$this->article_oid->setRelationType('MANY-TO-ONE');
				
		$this->content = new Text();
		$this->content->setAllowHTML(false);
	}	
}

?>