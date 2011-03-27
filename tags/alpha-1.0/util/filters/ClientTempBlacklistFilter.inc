<?php

require_once $config->get('sysRoot').'alpha/util/filters/AlphaFilterInterface.inc';
require_once $config->get('sysRoot').'alpha/model/BadRequestObject.inc';
require_once $config->get('sysRoot').'alpha/exceptions/BONotFoundException.inc';
require_once $config->get('sysRoot').'alpha/exceptions/ResourceNotAllowedException.inc';

/**
 * Class for filtering requests from temporariy blacklisted HTTP clients
 * 
 * @package alpha::util::filters
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
class ClientTempBlacklistFilter implements AlphaFilterInterface {
	/**
	 * Trace logger
	 * 
	 * @var Logger
	 * @since 1.0
	 */
	private static $logger = null;
	
	/**
	 * Constructor
	 * 
	 * @since 1.0
	 */
	public function __construct() {
		self::$logger = new Logger('ClientTempBlacklistFilter');		
	}
	
	/**
	 * (non-PHPdoc)
	 * @see alpha/util/filters/AlphaFilterInterface#process()
	 * 
	 * @throws ResourceNotAllowedException
	 */
	public function process() {
		global $config;
		
		$client = $_SERVER['HTTP_USER_AGENT'];
		$IP = $_SERVER['REMOTE_ADDR'];
		
		if(!empty($client) && !empty($IP)) {
			$request = new BadRequestObject();
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