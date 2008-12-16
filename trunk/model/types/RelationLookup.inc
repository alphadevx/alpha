<?php

require_once $config->get('sysRoot').'alpha/model/types/AlphaTypeInterface.inc';

/**
 * The RelationLookup complex data type.  Used to store object2object lookup tables for 
 * MANY-TO-MANY relationships between business objects
 * 
 * @package alpha::model::types
 * @author John Collins <john@design-ireland.net>
 * @copyright 2008 John Collins
 * @version $Id$
 * 
 */
class RelationLookup extends DAO implements AlphaTypeInterface {
	/**
	 * The OID of the left busines object in the relation
	 * 
	 * @var Integer
	 */
	protected $leftID;
	
	/**
	 * The OID of the right busines object in the relation
	 * 
	 * @var Integer
	 */
	protected $rightID;
	
	/**
	 * The name of the left business object class in the relation
	 * 
	 * @var String
	 */
	private $leftClassName;
	
	/**
	 * The name of the right business object class in the relation
	 * 
	 * @var String
	 */
	private $rightClassName;
	
	/**
	 * The name of the database table for the lookup (will need to be set at runtime)
	 * 
	 * @var string
	 */
	static protected $TABLE_NAME;
	
	/**
	 * an array of data display labels for the class properties
	 * 
	 * @var array
	 */
	protected $dataLabels = array(
		"OID"=>"RelationLookup ID#",
		"leftID"=>"Left BO ID#",
		"rightID"=>"Right BO ID#"
	);
	
	/**
	 * The message to display to the user when validation fails
	 *
	 * @var string
	 */
	protected $helper = 'Error: not a valid RelationLookup value!';
	
	/**
	 * The constructor
	 */
	public function __construct($leftClassName, $rightClassName) {
		// ensure to call the parent constructor
		parent::__construct();
		
		$this->leftClassName = $leftClassName;
		$this->rightClassName = $rightClassName;
		
		$this->setTableName();
		
		$this->leftID = new Integer();
		$this->rightID = new Integer();
		
		$this->markTransient('leftClassName');
		$this->markTransient('rightClassName');
		$this->markTransient('helper');
		
		// make sure the lookup table exists
		if(!$this->checkTableExists()) {
			$this->makeTable();
		}
	}
	
	private function setTableName() {
		$left = substr($this->leftClassName, 0, -7);
		$right = substr($this->rightClassName, 0, -7);
		
		self::$TABLE_NAME = $left.'2'.$right;
	}
	
	/**
	 * Custom getter for the TABLE_NAME, which can't be static in this class due to
	 * the lookup tablenames being different each time.
	 * 
	 * @return string
	 */
	public function getTableName() {
		if(!empty(self::$TABLE_NAME)) {			
    		return self::$TABLE_NAME;        
    	}else{
    		throw new AlphaException('Error: no TABLE_NAME set for the class '.get_class($this));
    		return '';
    	}
	}
	
	/**
	 * Getter for the validation helper string
	 * 
	 * @return string
	 */	
	public function getHelper() {
		return $this->helper;
	}
	
	/**
	 * Set the validation helper text
	 *
	 * @param string $helper
	 */
	public function setHelper($helper) {
		$this->helper = $helper;
	}
	
	/**
	 * Returns an array of the OIDs of the related objects
	 * 
	 * @return array
	 */
	public function getValue() {
		return array($this->leftID->getValue(), $this->rightID->getValue());
	}
	
	/**
	 * Used to set the OIDs of the related objects.  Pass a two-item array of OIDs, the first 
	 * one being the left object OID, the second being the right.
	 * 
	 * @param array $OIDs
	 */
	public function setValue($OIDs) {
		try{
			$this->leftID->setValue($OIDs[0]);
			$this->rightID->setValue($OIDs[1]);
		}catch(Exception $e) {
			throw new IllegalArguementException('Array value passed to setValue is not valid ['.print_r($OIDs, true).'], array should contain two OIDs');
		}
	}
	
	/**
	 * Used to convert the object to a printable string
	 *
	 * @return string
	 */
	public function __toString() {		
		return strval($this->getTableName());
	}
}