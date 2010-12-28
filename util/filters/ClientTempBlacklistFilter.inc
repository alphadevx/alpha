<?php

require_once $config->get('sysRoot').'alpha/util/filters/AlphaFilterInterface.inc';
require_once $config->get('sysRoot').'alpha/model/badrequest_object.inc';
require_once $config->get('sysRoot').'alpha/exceptions/BONotFoundException.inc';
require_once $config->get('sysRoot').'alpha/exceptions/ResourceNotAllowedException.inc';
require_once $config->get('sysRoot').'alpha/util/db_connect.inc';

/**
 * Class for filtering requests from temporariy blacklisted HTTP clients
 * 
 * @package alpha::util::filters
 * @author John Collins <john@design-ireland.net>
 * @copyright 2010 John Collins
 * @version $Id$
 *
 */
class ClientTempBlacklistFilter implements AlphaFilterInterface {
	/**
	 * Trace logger
	 * 
	 * @var Logger
	 */
	private static $logger = null;
	
	/**
	 * Constructor
	 */
	public function __construct() {
		self::$logger = new Logger('ClientTempBlacklistFilter');		
	}
	
	/**
	 * (non-PHPdoc)
	 * @see alpha/util/filters/AlphaFilterInterface#process()
	 */
	public function process() {
		global $config;
		
		$client = $_SERVER['HTTP_USER_AGENT'];
		$IP = $_SERVER['REMOTE_ADDR'];
		
		if(!empty($client) && !empty($IP)) {
			$request = new badrequest_object();
			$request->set('client', $client);
			$request->set('IP', $IP);
			$badRequestCount = $request->getBadRequestCount();
			
			if($badRequestCount >= $config->get('sysTempBlacklistFilerLimit')) {
				// if we got this far then the client is bad
				self::$logger->warn('The client ['.$client.'] was blocked from accessing the resource ['.$_SERVER['REQUEST_URI'].'] on a temporary basis');
				throw new ResourceNotAllowedException('Not allowed!');
			}
		}
	}
}

?>