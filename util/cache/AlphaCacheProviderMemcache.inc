<?php

require_once $config->get('sysRoot').'alpha/util/cache/AlphaCacheProviderInterface.inc';

/**
 *
 * An implementation of the AlphaCacheProviderInterface interface that uses Memcache as the
 * target store.
 * 
 * @package alpha::util::cache
 * @since 1.1
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
class AlphaCacheProviderMemcache implements AlphaCacheProviderInterface {
	/**
	 * Trace logger
	 * 
	 * @var Logger
	 * @since 1.1
	 */
	private static $logger = null;
	
	/**
	 * Constructor
	 * 
	 * @since 1.1
	 */
	public function __construct() {
		self::$logger = new Logger('AlphaCacheProviderMemcache');
	}
	
	/**
	 * (non-PHPdoc)
	 * @see alpha/util/cache/AlphaCacheProviderInterface::get()
	 * @since 1.1
	 */
	public function get($key) {
		self::$logger->debug('>>get(key=['.$key.'])');
		
		global $config;

      	try {
	      	$memcache = new Memcache();
	      	$memcache->connect($config->get('sysMemcachedHost'), 11211);
	
	      	$value = $memcache->get($key);
	      	
	      	self::$logger->debug('<<get: ['.print_r($value, true).'])');
	      	return $value;
      	}catch(Exception $e) {
      		self::$logger->error('Error while attempting to load a business object from Memcached instance: ['.$e->getMessage().']');
      		self::$logger->debug('<<get: [false])');
	        return false;
      	}
	}
	
	/**
	 * (non-PHPdoc)
	 * @see alpha/util/cache/AlphaCacheProviderInterface::set()
	 * @since 1.1
	 */
	public function set($key, $value, $expiry=0) {
		global $config;
		
		try {
	      	$memcache = new Memcache();
		    $memcache->connect($config->get('sysMemcachedHost'), 11211);
		
		    if($expiry > 0)
		    	$memcache->set($key, $value, MEMCACHE_COMPRESSED, $expiry);
		    else
		    	$memcache->set($key, $value, MEMCACHE_COMPRESSED);

      	}catch(Exception $e) {
      		self::$logger->error('Error while attempting to store a value to Memcached instance: ['.$e->getMessage().']');
      	}
	}
	
	/**
	 * (non-PHPdoc)
	 * @see alpha/util/cache/AlphaCacheProviderInterface::delete()
	 * @since 1.1
	 */
	public function delete($key) {
		global $config;

		try {
      		$memcache = new Memcache();
      		$memcache->connect($config->get('sysMemcachedHost'), 11211);
      		$memcache->delete($key);
		}catch(Exception $e) {
      		self::$logger->error('Error while attempting to remove a value from Memcached instance: ['.$e->getMessage().']');
      	}
	}
}