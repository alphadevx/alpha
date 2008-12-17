<?php

require_once $config->get('sysRoot').'alpha/model/DAO.inc';
require_once $config->get('sysRoot').'alpha/model/person_object.inc';

/**
 *
 * The main person/user class for the site
 * 
 * @package alpha::model
 * @author John Collins <john@design-ireland.net>
 * @copyright 2008 John Collins
 * @version $Id$ 
 * 
 */ 
class rights_object extends DAO {	
	/**
	 * The name of the rights
	 * 
	 * @var String
	 */
	protected $name;
	
	/**
	 * A Relation containing all of the person_objects that have these rights
	 *
	 * @var Relation
	 */
	protected $members;
		
	/**
	 * An array of data display labels for the class properties
	 * 
	 * @var array
	 */
	protected $dataLabels = array(
		"OID"=>"Rights Group ID#",		
		"name"=>"Rights Group Name",
		"members"=>"Rights Group Members"
	);
	
	/**
	 * The name of the database table for the class
	 * 
	 * @var string
	 */
	const TABLE_NAME = 'rights';
	
	/**
	 * Constructor
	 */
	public function __construct() {
		// ensure to call the parent constructor
		parent::__construct();
		$this->name = new String();
		
		
		// add unique key to name field
		$this->markUnique('name');
		
		$this->members = new Relation();
		$this->markTransient('members');
	}
	
	/**
	 * Set up the transient attributes for the rights group after it has loaded
	 */
	protected function after_load_callback() {
		global $config;
		
		// set up MANY-TO-MANY relation person2rights
		$this->members->setRelatedClass('person_object', 'left');
		$this->members->setRelatedClassDisplayField('email', 'left');
		$this->members->setRelatedClass('rights_object', 'right');
		$this->members->setRelatedClassDisplayField('name', 'right');
		$this->members->setRelationType('MANY-TO-MANY');
		$this->members->setValue($this->getID());
	}
}

?>