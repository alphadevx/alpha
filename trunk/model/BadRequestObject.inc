<?php

require_once $config->get('sysRoot').'alpha/model/AlphaDAO.inc';

/**
 *
 * A HTTP request that resulted in a 404 response.  The class is only used when the
 * sysEnableClientTempBlacklistFiler setting is set to true to enable the filter.
 * 
 * @package alpha::model
 * @since 1.0
 * @author John Collins <john@design-ireland.net>
 * @version $Id$
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2011, John Collins (founder of Alpha Framework).  
 * All rights reserved.
 * 
 * <pre>
 * Redistribution and use in source and binary forms, with or 
 * without modification, are permitted provided that the 
 * following conditions are met:
 * 
 * * Redistributions of source code must retain the above 
 *   copyright notice, this list of conditions and the 
 *   following disclaimer.
 * * Redistributions in binary form must reproduce the above 
 *   copyright notice, this list of conditions and the 
 *   following disclaimer in the documentation and/or other 
 *   materials provided with the distribution.
 * * Neither the name of the Alpha Framework nor the names 
 *   of its contributors may be used to endorse or promote 
 *   products derived from this software without specific 
 *   prior written permission.
 *   
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND 
 * CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, 
 * INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF 
 * MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE 
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR 
 * CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, 
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT 
 * NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; 
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) 
 * HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN 
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE 
 * OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS 
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 * </pre>
 *  
 */
class BadRequestObject extends AlphaDAO {
	/**
	 * The HTTP user-agent client string
	 * 
	 * @var String
	 * @since 1.0
	 */
	protected $client;
	
	/**
	 * The IP of the client
	 * 
	 * @var String
	 * @since 1.0
	 */
	protected $IP;
	
	/**
	 * The non-existant resource that the client requested
	 * 
	 * @var String
	 * @since 1.0
	 */
	protected $requestedResource;
	
	/**
	 * An array of data display labels for the class properties
	 * 
	 * @var array
	 * @since 1.0
	 */
	protected $dataLabels = array('OID'=>'Bad request ID#','client'=>'Client string','IP'=>'IP','requestedResource'=>'Requested resource');
	
	/**
	 * The name of the database table for the class
	 * 
	 * @var string
	 * @since 1.0
	 */
	const TABLE_NAME = 'BadRequest';
	
	/**
	 * Trace logger
	 * 
	 * @var Logger
	 * @since 1.0
	 */
	private static $logger = null;
	
	/**
	 * Constructor for the class
	 * 
	 * @since 1.0
	 */
	public function __construct() {
		self::$logger = new Logger('BadRequestObject');
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
	 * @return integer
	 * @since 1.0
	 * @throws AlphaException
	 */
	public function getBadRequestCount() {
		global $config;
		
		$sqlQuery = "SELECT COUNT(OID) AS request_count FROM ".$this->getTableName()." WHERE IP = '".$this->IP->getValue()."' AND client = '".$this->client->getValue()."' AND created_ts > NOW()-INTERVAL '".$config->get('sysTempBlacklistFilerPeriod')."' MINUTE";
		
		$result = $this->query($sqlQuery);
		
		if(isset($result[0]))
			$row = $result[0];
		else
			throw new AlphaException('No result set returned when querying the bad request table');
		
		if(isset($row['request_count'])) {
			return $row['request_count'];
		}else{
			return 0;
		}
	}
}

?>