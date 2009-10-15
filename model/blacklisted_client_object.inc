<?php

require_once $config->get('sysRoot').'alpha/model/DAO.inc';
require_once $config->get('sysRoot').'alpha/util/handle_error.inc';

/**
 *
 * A HTTP client that is blacklisted from accessing this application
 * 
 * @package alpha::model
 * @author John Collins <john@design-ireland.net>
 * @copyright 2009 John Collins
 * @version $Id$
 * 
 */ 
class blacklisted_client_object extends DAO {
	/**
	 * The (unique) client string this is blocked
	 * 
	 * @var String
	 */
	protected $client;
	
	/**
	 * An array of data display labels for the class properties
	 * 
	 * @var array
	 */
	protected $dataLabels = array(
		'OID'=>"Blacklisted Client ID#",		
		'client'=>'Client string'
	);
	
	/**
	 * the name of the database table for the class
	 * @var string
	 */
	const TABLE_NAME = 'blacklisted_client';
	
	/**
	 * constructor for the class
	 */
	public function __construct() {
		// ensure to call the parent constructor
		parent::__construct();
		
		$this->client = new String();
		$this->markUnique('client');
	}
}

?>