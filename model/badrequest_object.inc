<?php

require_once $config->get('sysRoot').'alpha/model/AlphaDAO.inc';
require_once $config->get('sysRoot').'alpha/util/handle_error.inc';

/**
 *
 * A HTTP request that resulted in a 404 response.  The class is only used when the
 * sysEnableClientTempBlacklistFiler setting is set to true to enable the filter.
 * 
 * @package alpha::model
 * @author John Collins <john@design-ireland.net>
 * @copyright 2010 John Collins
 * @version $Id$
 * 
 */ 
class badrequest_object extends AlphaDAO {
	/**
	 * The HTTP user-agent client string
	 * 
	 * @var String
	 */
	protected $client;
	
	/**
	 * The IP of the client
	 * 
	 * @var String
	 */
	protected $IP;
	
	/**
	 * The non-existant resource that the client requested
	 * 
	 * @var String
	 */
	protected $requestedResource;
	
	/**
	 * An array of data display labels for the class properties
	 * 
	 * @var array
	 */
	protected $dataLabels = array(
		'OID'=>"Bad request ID#",		
		'client'=>'Client string',
		'IP'=>'IP',
		'requestedResource'=>'Requested resource'
	);
	
	/**
	 * the name of the database table for the class
	 * @var string
	 */
	const TABLE_NAME = 'badrequest';
	
	/**
	 * Trace logger
	 * 
	 * @var Logger
	 */
	private static $logger = null;
	
	/**
	 * constructor for the class
	 */
	public function __construct() {
		if(self::$logger == null)
			self::$logger = new Logger('badrequest_object');
		self::$logger->debug('>>__construct()');
		
		// ensure to call the parent constructor
		parent::__construct();
		
		$this->client = new String();
		$this->IP = new String();
		$this->requestedResource = new String();
		
		self::$logger->debug('<<__construct');
	}
	
	/**
	 * Gets the count of bad requests for the client with this IP and client string in the past
	 * configurable period (sysTempBlacklistFilerPeriod)
	 * 
	 * @return int
	 */
	public function getBadRequestCount() {
		global $config;
		
		$sqlQuery = 'SELECT COUNT(OID) AS request_count FROM '.$this->getTableName().' WHERE IP = ? AND client = ? AND created_ts > NOW()-INTERVAL ? MINUTE';
		
		$this->lastQuery = $sqlQuery;
		$stmt = AlphaDAO::getConnection()->stmt_init();

		$row = array();
		
		if($stmt->prepare($sqlQuery)) {
			$stmt->bind_param('ssi', $this->IP->getValue(), $this->client->getValue(), $config->get('sysTempBlacklistFilerPeriod'));
			
			$stmt->execute();
			
			$result = $this->bindResult($stmt);
			if(isset($result[0]))
				$row = $result[0];
				
			$stmt->close();
		}else{
			self::$logger->warn('The following query caused an unexpected result ['.$sqlQuery.']');
			return 0;
		}
		
		if(isset($row['request_count'])) {
			return $row['request_count'];
		}else{
			return 0;
		}
	}
}

?>