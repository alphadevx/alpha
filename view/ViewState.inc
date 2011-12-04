<?php

require_once $config->get('sysRoot').'alpha/exceptions/IllegalArguementException.inc';

/**
 * 
 * A singleton class that maintains the view state in the session
 * 
 * @package alpha::view
 * @since 1.0
 * @author John Collins <dev@alphaframework.org>
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
class ViewState {
	/**
	 * The name of the last selected tab by the user
	 * 
	 * @var string
	 * @since 1.0
	 */
	protected $selectedTab;
	
	/**
	 * The amount of rows to expand the Markdown edit TextBox by
	 * 
	 * @var string
	 * @since 1.0
	 */
	protected $markdownTextBoxRows;
	
	/**
	 * The view state object singleton
	 *
	 * @var ViewState
	 * @since 1.0
	 */
	protected static $instance;

	/**
	 * Private constructor means the class cannot be instantiated from elsewhere
	 * 
	 * @since 1.0
	 */
	private function __construct () {}
	
	/**
	 * Get the ViewState instance.  Loads from $_SESSION if its not already in memory, otherwise
	 * a new instance will be returned with empty properties.
	 * 
	 * @var ViewState
	 * @since 1.0
	 */
	public static function getInstance() {
		// if we don't already have the object in memory...
		if (!isset(self::$instance)) {
			// load from the session, otherwise return a new object
			if(isset($_SESSION['ViewState'])) {
				return unserialize($_SESSION['ViewState']);
			}else{
				self::$instance = new ViewState();
				return self::$instance;
			}
		}else{
			return self::$instance;
		}
	}
	
	/**
	 * Get the attribute value indicated by the key
	 * 
	 * @param string $key
	 * @throws IllegalArguementException
	 * @return string
	 * @since 1.0
	 */
	public function get($key) {
		$attribute = new ReflectionProperty(get_class($this), $key);
		
		if($attribute != null)
			return $this->$key;
		else
			throw new IllegalArguementException('The property ['.$key.'] does not exist on the ['.get_class($this).'] class');
	}
	
	/**
	 * Sets the attribute value indicated by the key.  The ViewState instance will be serialized and saved back to the $_SESSION.
	 * 
	 * @param string $key
	 * @param string $value
	 * @throws IllegalArguementException
	 * @since 1.0
	 */
	public function set($key, $value) {
		$attribute = new ReflectionProperty(get_class($this), $key);
		
		if($attribute != null) {
			$this->$key = $value;
			$_SESSION[get_class($this)] = serialize($this);
		}else{
			throw new IllegalArguementException('The property ['.$key.'] does not exist on the ['.get_class($this).'] class');
		}
	}
}

?>