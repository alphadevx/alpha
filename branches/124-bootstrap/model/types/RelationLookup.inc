<?php

/**
 * The RelationLookup complex data type.  Used to store object2object lookup tables for
 * MANY-TO-MANY relationships between business objects
 *
 * @package alpha::model::types
 * @since 1.0
 * @author John Collins <dev@alphaframework.org>
 * @version $Id$
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2012, John Collins (founder of Alpha Framework).
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
class RelationLookup extends AlphaDAO implements AlphaTypeInterface {
	/**
	 * The OID of the left business object in the relation
	 *
	 * @var Integer
	 * @since 1.0
	 */
	protected $leftID;

	/**
	 * The OID of the right business object in the relation
	 *
	 * @var Integer
	 * @since 1.0
	 */
	protected $rightID;

	/**
	 * The name of the left business object class in the relation
	 *
	 * @var string
	 * @since 1.0
	 */
	private $leftClassName;

	/**
	 * The name of the right business object class in the relation
	 *
	 * @var string
	 * @since 1.0
	 */
	private $rightClassName;

	/**
	 * Trace logger
	 *
	 * @var Logger
	 * @since 1.0
	 */
	private static $logger = null;

	/**
	 * an array of data display labels for the class properties
	 *
	 * @var array
	 * @since 1.0
	 */
	protected $dataLabels = array("OID"=>"RelationLookup ID#","leftID"=>"Left BO ID#","rightID"=>"Right BO ID#");

	/**
	 * The message to display to the user when validation fails
	 *
	 * @var string
	 * @since 1.0
	 */
	protected $helper = 'Not a valid RelationLookup value!';

	/**
	 * The constructor
	 *
	 * @throws FailedLookupCreateException
	 * @throws IllegalArguementException
	 * @since 1.0
	 */
	public function __construct($leftClassName, $rightClassName) {
		self::$logger = new Logger('RelationLookup');
		self::$logger->debug('>>__construct(leftClassName=['.$leftClassName.'], rightClassName=['. $rightClassName.'])');

		// ensure to call the parent constructor
		parent::__construct();

		if(empty($leftClassName) || empty($rightClassName)) {
			throw new IllegalArguementException('Cannot create RelationLookup object without providing the left and right class names!');
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
		if(!$this->checkTableExists() && AlphaDAO::isInstalled()) {
			// first make sure that the two BO tables exist before relating them with a lookup table
			if(AlphaDAO::checkBOTableExists($leftClassName) && AlphaDAO::checkBOTableExists($rightClassName)) {
				$this->makeTable();
			}else{
				throw new FailedLookupCreateException('Error trying to create a lookup table ['.$this->getTableName().'], as tables for BOs ['.$leftClassName.'] or ['.$rightClassName.'] don\'t exist!');
			}
		}

		self::$logger->debug('<<__construct');
	}

	/**
	 * Get the leftClassName value
	 *
	 * @return string
	 * @since 1.0
	 */
	public function getLeftClassName() {
		return $this->leftClassName;
	}

	/**
	 * Get the rightClassName value
	 *
	 * @return string
	 * @since 1.0
	 */
	public function getRightClassName() {
		return $this->rightClassName;
	}

	/**
	 * Custom getter for the TABLE_NAME, which can't be static in this class due to
	 * the lookup tablenames being different each time.
	 *
	 * @return string
	 * @since 1.0
	 * @throws AlphaException
	 */
	public function getTableName() {
		if(isset($this->leftClassName) && isset($this->rightClassName)) {
			$left = mb_substr($this->leftClassName, 0, mb_strpos($this->leftClassName, 'Object'));
			$right = mb_substr($this->rightClassName, 0, mb_strpos($this->rightClassName, 'Object'));
			self::$logger->debug('Setting table name to ['.$left.'2'.$right.']');
    		return $left.'2'.$right;
    	}else{
    		throw new AlphaException('No table name set for the class ['.get_class($this).'], left or right class name(s) missing');
    	}
	}

	/**
	 * This custom version provides the left/right class names to the business object constructor, required
	 * for RelationLookup objects.
	 *
	 * (non-PHPdoc)
	 * @see alpha/model/AlphaDAO::loadAllByAttribute()
	 */
	public function loadAllByAttribute($attribute, $value, $start=0, $limit=0, $orderBy="OID", $order="ASC", $ignoreClassType=false, $constructorArgs=array()) {
		if(!isset(self::$logger))
			self::$logger = new Logger('RelationLookup');

		self::$logger->debug('>>loadAllByAttribute(attribute=['.$attribute.'], value=['.$value.'], start=['.$start.'], limit=['.$limit.'], orderBy=['.$orderBy.'], order=['.$order.'], ignoreClassType=['.$ignoreClassType.'], constructorArgs=['.print_r($constructorArgs, true).']');

		if(method_exists($this, 'before_loadAllByAttribute_callback'))
			$this->before_loadAllByAttribute_callback();

		global $config;

		$provider = AlphaDAOProviderFactory::getInstance($config->get('db.provider.name'), $this);
		$objects = $provider->loadAllByAttribute($attribute, $value, $start, $limit, $orderBy, $order, $ignoreClassType, array($this->leftClassName, $this->rightClassName));

		if(method_exists($this, 'after_loadAllByAttribute_callback'))
			$this->after_loadAllByAttribute_callback();

		self::$logger->debug('<<loadAllByAttribute ['.count($objects).']');
		return $objects;
	}

	/**
	 * Getter for the validation helper string
	 *
	 * @return string
	 * @since 1.0
	 */
	public function getHelper() {
		return $this->helper;
	}

	/**
	 * Set the validation helper text
	 *
	 * @param string $helper
	 * @since 1.0
	 */
	public function setHelper($helper) {
		$this->helper = $helper;
	}

	/**
	 * Returns an array of the OIDs of the related objects
	 *
	 * @return array
	 * @since 1.0
	 */
	public function getValue() {
		return array($this->leftID->getValue(), $this->rightID->getValue());
	}

	/**
	 * Used to set the OIDs of the related objects.  Pass a two-item array of OIDs, the first
	 * one being the left object OID, the second being the right.
	 *
	 * @param array $OIDs
	 * @since 1.0
	 * @throws IllegalArguementException
	 */
	public function setValue($OIDs) {
		try{
			$this->leftID->setValue($OIDs[0]);
			$this->rightID->setValue($OIDs[1]);
		}catch(Exception $e) {
			throw new IllegalArguementException('Array value passed to setValue is not valid ['.var_export($OIDs, true).'], array should contain two OIDs');
		}
	}

	/**
	 * Used to convert the object to a printable string
	 *
	 * @return string
	 * @since 1.0
	 */
	public function __toString() {
		return strval($this->getTableName());
	}
}