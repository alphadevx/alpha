<?php

require_once $config->get('sysRoot').'alpha/model/DAO.inc';

/**
 *
 * The main person/user class for the site
 * 
 * @package alpha::model
 * @author John Collins <john@design-ireland.net>
 * @copyright 2008 John Collins
 * @version $Id: person_object.inc 701 2008-12-15 19:53:22Z johnc $ 
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
	 * An array of data display labels for the class properties
	 * 
	 * @var array
	 */
	protected $dataLabels = array(
		"OID"=>"Rights Group ID#",		
		"name"=>"Rights Group Name"
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
	}
}

?>