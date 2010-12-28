<?php

require_once $config->get('sysRoot').'alpha/util/filters/AlphaFilterInterface.inc';
require_once $config->get('sysRoot').'alpha/model/blacklisted_client_object.inc';
require_once $config->get('sysRoot').'alpha/exceptions/BONotFoundException.inc';
require_once $config->get('sysRoot').'alpha/exceptions/ResourceNotAllowedException.inc';
require_once $config->get('sysRoot').'alpha/util/db_connect.inc';

/**
 * Class for filtering requests from blacklisted HTTP clients
 * 
 * @package alpha::util::filters
 * @author John Collins <john@design-ireland.net>
 * @copyright 2009 John Collins
 * @version $Id$
 *
 */
class ClientBlacklistFilter implements AlphaFilterInterface {
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
		self::$logger = new Logger('ClientBlacklistFilter');		
	}
	
	/**
	 * (non-PHPdoc)
	 * @see alpha/util/filters/AlphaFilterInterface#process()
	 */
	public function process() {
		$client = $_SERVER['HTTP_USER_AGENT'];
		
		if(!empty($client)) {
			$badClient = new blacklisted_client_object();
			try {
				$badClient->loadByAttribute('client', $client);
			}catch (BONotFoundException $bonf) {
				// client is not on the list!
				return;
			}
			// if we got this far then the client is bad
			self::$logger->warn('The client ['.$client.'] was blocked from accessing the resource ['.$_SERVER['REQUEST_URI'].']');
			throw new ResourceNotAllowedException('Not allowed!');
		}
	}
}

?>