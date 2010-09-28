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
	 * The OID of the left business object in the relation
	 * 
	 * @var Integer
	 */
	protected $leftID;
	
	/**
	 * The OID of the right business object in the relation
	 * 
	 * @var Integer
	 */
	protected $rightID;
	
	/**
	 * The name of the left business object class in the relation
	 * 
	 * @var string
	 */
	private $leftClassName;
	
	/**
	 * The name of the right business object class in the relation
	 * 
	 * @var string
	 */
	private $rightClassName;
	
	/**
	 * Trace logger
	 * 
	 * @var Logger
	 */
	private static $logger = null;
	
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
		if(self::$logger == null)
			self::$logger = new Logger('RelationLookup');
		self::$logger->debug('>>__construct(leftClassName=['.$leftClassName.'], rightClassName=['. $rightClassName.'])');
		
		// ensure to call the parent constructor
		parent::__construct();
		
		if(empty($leftClassName) || empty($rightClassName)) {
			throw new IllegalArguementException('Cannot create RelationLookup object without providing the left and right class names!');
			return;
		}
		
		$this->leftClassName = $leftClassName;
		$this->rightClassName = $rightClassName;
		
		$this->leftID = new Integer();
		$this->rightID = new Integer();
		
		$this->markTransient('leftClassName');
		$this->markTransient('rightClassName');
		$this->markTransient('helper');
		$this->markTransient('TABLE_NAME');
		
		// add a unique composite key to these fields
		$this->markUnique('leftID','rightID');
		
		// make sure the lookup table exists
		if(!$this->checkTableExists()) {			
			// first make sure that the two BO tables exist before relating them with a lookup table
			if(DAO::checkBOTableExists($leftClassName) && DAO::checkBOTableExists($rightClassName)) {
				$this->makeTable();
			}else{
				throw new AlphaException('Error trying to create a lookup table ['.$this->getTableName().'], as tables for BOs ['.$leftClassName.'] or ['.$rightClassName.'] don\'t exist!');
				return;
			}
		}
		
		self::$logger->debug('<<__construct');
	}
	
	/**
	 * Get the leftClassName value
	 *
	 * @return string
	 */
	public function getLeftClassName() {
		return $this->leftClassName;
	}
	
	/**
	 * Get the rightClassName value
	 *
	 * @return string
	 */
	public function getRightClassName() {
		return $this->rightClassName;
	}
	
	/**
	 * Custom getter for the TABLE_NAME, which can't be static in this class due to
	 * the lookup tablenames being different each time.
	 * 
	 * @return string
	 */
	public function getTableName() {
		if(isset($this->leftClassName) && isset($this->rightClassName)) {
			$left = substr($this->leftClassName, 0, -7);
			$right = substr($this->rightClassName, 0, -7);
			self::$logger->debug('Setting table name to ['.$left.'2'.$right.']');			
    		return $left.'2'.$right;
    	}else{
    		throw new AlphaException('Error: no table name set for the class ['.get_class($this).'], left or right class name(s) missing');
    		return '';
    	}
	}
	
	/**
	 * Loads all of the objects of this class by the specified attribute into an array which is returned. 
	 * This custom version provides the left/right class names to the business object constructor, required 
	 * for RelationLookup objects.
	 * 
	 * @param string $atribute The attribute to load the object by
	 * @param string $value The value of the attribute to load the object by
	 * @param integer $start The start of the SQL LIMIT clause
	 * @param integer $limit The amount (limit) of objects to load
	 * @param string $orderBy the name of the field to sort the BOs by
	 * @param string $order the order to sort the BOs by, default is ASC
	 * @return array an array containing objects of this type of business object.
	 */
	public function loadAllByAttribute($attribute, $value, $start=0, $limit=0, $orderBy="OID", $order="ASC") {
		self::$logger->debug('>>loadAllByAttribute(attribute=['.$attribute.'], value=['.$value.'], start=['.$start.'], limit=['.$limit.'], orderBy=['.$orderBy.'], order=['.$order.']');
		
		if(method_exists($this, 'before_loadAllByAttribute_callback'))
			$this->before_loadAllByAttribute_callback();
		
		global $config;
		
		if ($limit == 0)
			$limit = $config->get('sysListPageAmount');
		
		if(!$this->isTableOverloaded())
			$sqlQuery = "SELECT OID FROM ".$this->getTableName()." WHERE $attribute = '$value' ORDER BY ".$orderBy." ".$order." LIMIT ".$start.", ".$limit.";";
		else
			$sqlQuery = "SELECT OID FROM ".$this->getTableName()." WHERE $attribute = '$value' AND classname='".get_class($this)."' ORDER BY ".$orderBy." ".$order." LIMIT ".$start.", ".$limit.";";
			
		$this->lastQuery = $sqlQuery;

		$result = mysql_query($sqlQuery);
		
		if(mysql_error() != '') {
			throw new BONotFoundException('Failed to load object OIDs, MySql error is ['.mysql_error().'], query ['.$this->lastQuery.']');
			self::$logger->debug('<<loadAllByAttribute []');
			return array();
		}
		
		// now build an array of objects to be returned
		$objects = array();
		$count = 0;
		$BO_Class = get_class($this);
		
		while($row = mysql_fetch_assoc($result)) {
			$obj = new $BO_Class($this->leftClassName, $this->rightClassName);
			$obj->load($row["OID"]);
			$objects[$count] = $obj;
			$count++;
		}
		
		if(method_exists($this, 'after_loadAllByAttribute_callback'))
			$this->after_loadAllByAttribute_callback();
		
		self::$logger->debug('<<loadAllByAttribute ['.count($objects).']');
		return $objects;	
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