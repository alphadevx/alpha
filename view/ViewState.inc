<?php

require_once $config->get('sysRoot').'alpha/exceptions/IllegalArguementException.inc';

/**
 * 
 * A singleton class that maintains the view state in the session
 * 
 * @package alpha::view
 * @author John Collins <john@design-ireland.net>
 * @copyright 2010 John Collins
 * @version $Id$ 
 *
 */
class ViewState {
	/**
	 * The name of the last selected tab by the user
	 * 
	 * @var string
	 */
	protected $selectedTab;
	
	/**
	 * The view state object singleton
	 *
	 * @var ViewState
	 */
	protected static $instance;

	/**
	 * Private constructor means the class cannot be instantiated from elsewhere
	 */
	private function __construct () {}
	
	/**
	 * Get the ViewState instance.  Loads from $_SESSION if its not already in memory, otherwise
	 * a new instance will be returned with empty properties.
	 * 
	 * @var ViewState
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
	 */
	public function get($key) {
		if(property_exists(get_class($this), $key))
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