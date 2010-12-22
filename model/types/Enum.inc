<?php

require_once $config->get('sysRoot').'alpha/exceptions/AlphaException.inc';
require_once $config->get('sysRoot').'alpha/model/types/AlphaType.inc';
require_once $config->get('sysRoot').'alpha/model/types/AlphaTypeInterface.inc';

/**
 * The Enum complex data type
 *
 * @package Alpha Core Datatype
 * @author John Collins <john@design-ireland.net>
 * @copyright 2008 John Collins
 * @version $Id$
 *
 */
class Enum extends AlphaType implements AlphaTypeInterface {
	/**
	 * An array of valid enum options
	 * @var array
	 */
	private $options;

	/**
	 * The currently selected enum option
	 *
	 * @var string
	 */
	private $value = '';

	/**
	 * The message to display to the user when validation fails
	 *
	 * @var string
	 */
	protected $helper = 'Error: not a valid enum option!';

	/**
	 * Constructor that sets up the enum options
	 *
	 * @param array $opts
	 */
	public function __construct($opts=array('')) {
		if(is_array($opts))
			$this->options = $opts;
		else
			throw new IllegalArguementException('Error: not a valid enum option array!');
	}

	/**
	 * Setter for the enum options
	 *
	 * @param array $opts
	 */
	public function setOptions($opts) {
		if(is_array($opts))
			$this->options = $opts;
		else
			throw new IllegalArguementException('Error: not a valid enum option array!');
	}

	/**
	 * Get the array of enum options
	 *
	 * @param bool $alphaSort set to true if you want the Enum options in alphabetical order (default false)
	 * @return array
	 */
	public function getOptions($alphaSort = false) {
		if($alphaSort)
			sort($this->options, SORT_STRING);
		return $this->options;
	}	

	/**
	 * Used to get the current enum item
	 *
	 * @return string
	 */
	public function getValue() {
		return $this->value;
	}

	/**
	 * Used to select the current enum item
	 *
	 * @param string $item The item to set as selected in the Enum
	 */
	public function setValue($item) {
		if (in_array($item, $this->options)) {
			$this->value = $item;
		}else{
			throw new IllegalArguementException($this->getHelper());
		}
	}
}

?>