<?php

require_once $config->get('sysRoot').'alpha/model/types/AlphaTypeInterface.inc';

/**
 * A customer sequence datatype, which is stored as a string and is made up of a string prefix
 * and an integer sequence, which is stored in a database.
 * 
 * @package alpha::model::types
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
class Sequence extends AlphaDAO implements AlphaTypeInterface{	
	/**
	 * The string prefix (must be capital alphabet characters only)
	 * 
	 * @var String
	 * @since 1.0
	 */
	protected $prefix;
	
	/**
	 * The integer sequence number incremented for each Sequence value with this prefix
	 *
	 * @var Integer
	 * @since 1.0
	 */
	protected $sequence;
	
	/**
	 * The name of the database table for the class
	 * 
	 * @var string
	 * @since 1.0
	 */
	const TABLE_NAME = 'Sequence';
	
	/**
	 * An array of data display labels for the class properties
	 * 
	 * @var array
	 * @since 1.0
	 */
	protected $dataLabels = array("OID"=>"Sequence ID#","prefix"=>"Sequence prefix","sequence"=>"Sequence number");
	
	/**
	 * The message to display to the user when validation fails
	 *
	 * @var string
	 * @since 1.0
	 */
	protected $helper = 'Not a valid sequence value!';
	
	/**
	 * The size of the value for the this Sequence
	 * 
	 * @var integer
	 * @since 1.0
	 */
	protected $size = 255;
	
	/**
	 * The validation rule for the Sequence type
	 * 
	 * @var string
	 * @since 1.0
	 */
	protected $validationRule;
	
	/**
	 * The absolute maximum size of the value for the this Sequence
	 * 
	 * @var integer
	 * @since 1.0
	 */
	const MAX_SIZE = 255;
	
	/**
	 * The constructor
	 * 
	 * @since 1.0
	 */
	public function __construct() {
		// ensure to call the parent constructor
		parent::__construct();
		
		$this->validationRule = AlphaValidator::ALLOW_ALL;
		
		$this->sequence = new Integer();
		
		$this->prefix = new String();
		$this->prefix->setRule(AlphaValidator::REQUIRED_ALPHA_UPPER);
		$this->prefix->setHelper('Sequence prefix must be uppercase string!');
		$this->markUnique('prefix');
		
		$this->markTransient('helper');
		$this->markTransient('validationRule');
		$this->markTransient('size');
	}
	
	/**
 	 * Get the validation rule
 	 *
 	 * @return string
 	 * @since 1.0
 	 */
 	public function getRule() {
		return $this->validationRule;
	}
	
	/**
	 * Sets the sequence number to be the maximum value matching the prefix in the database
	 * plus one.  Note that calling this method increments the maximum value in the database.
	 * 
	 * @since 1.0
	 */
	public function setSequenceToNext() {
		
		try {
			$this->loadByAttribute('prefix', $this->prefix->getValue());
		}catch (BONotFoundException $e) {
			$this->set('sequence', 0);
		}
		
		$this->set('sequence', $this->get('sequence')+1);
		$this->save();
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
	 * Used to get the Sequence value as a string
	 * 
	 * @return string
	 * @since 1.0
	 */
	public function getValue() {
		if($this->prefix->getValue() != '' && $this->sequence->getValue() != 0)		
			return $this->prefix->getValue().'-'.$this->sequence->getValue();
		else
			return '';
	}
	
	/**
	 * Accepts a string to set the Sequence prefix/sequence values to, in the 
	 * format PREFIX-00000000000
	 * 
	 * @param string $val
	 * @since 1.0
	 * @throws IllegalArguementException
	 */
	public function setValue($val) {
		if (strlen($val) <= $this->size) {
			if(!empty($val)) {
				if(!AlphaValidator::isSequence($val))
					throw new IllegalArguementException($this->helper);
					
				$parts = explode('-', $val);				
				$this->prefix->setValue($parts[0]);
				$this->sequence->setValue($parts[1]);
			}
		}else{
			throw new IllegalArguementException($this->helper);
		}
	}
	
	/**
	 * Get the allowable size of the Sequence in the database field
	 *	
	 * @return integer
	 * @since 1.0
	 */
	public function getSize(){
		return $this->size;
	}
	
	/**
	 * Used to convert the object to a printable string
	 *
	 * @return string
	 * @since 1.0
	 */
	public function __toString() {		
		return $this->prefix->getValue().'-'.$this->sequence->getValue();
	}
}

?>