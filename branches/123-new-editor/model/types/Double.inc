<?php

/**
 * The Double complex data type
 *
 * @package alpha::model::types
 * @since 1.0
 * @author John Collins <dev@alphaframework.org>
 * @version $Id$
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2013, John Collins (founder of Alpha Framework).
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
class Double extends AlphaType implements AlphaTypeInterface {
	/**
	 * The value of the Double
	 *
	 * @var double
	 * @since 1.0
	 */
 	private $value;

	/**
	 * The validation rule (reg-ex) applied to Double values
	 *
	 * @var string
	 * @since 1.0
	 */
 	private $validationRule;

	/**
	 * The error message for the Double type when validation fails
	 *
	 * @var string
	 * @since 1.0
	 */
	protected $helper = 'Not a valid double value!';

	/**
	 * The size of the value for the Double
	 *
	 * @var integer
	 * @since 1.0
	 */
	private $size = 13;

	/**
	 * The absolute maximum size of the value for the this double
	 *
	 * @var integer
	 * @since 1.0
	 */
	const MAX_SIZE = 13;

	/**
	 * Constructor
	 *
	 * @param double $val
	 * @since 1.0
	 * @throws IllegalArguementException
	 */
	public function __construct($val=0.0) {
		$this->validationRule = AlphaValidator::REQUIRED_DOUBLE;

		if(!AlphaValidator::isDouble($val))
			throw new IllegalArguementException($this->helper);

		if (mb_strlen($val) <= $this->size) {
			$this->value = $val;
		}else{
			throw new IllegalArguementException($this->helper);
		}
	}

	/**
	 * Setter for the Double value
	 *
	 * @param double $val
	 * @since 1.0
	 * @throws IllegalArguementException
	 */
	public function setValue($val) {
		if(!AlphaValidator::isDouble($val))
			throw new IllegalArguementException($this->helper);

		if (mb_strlen($val) <= $this->size) {
			$this->value = $val;
		}else{
			throw new IllegalArguementException($this->helper);
		}
	}

	/**
	 * Getter for the Double value
	 *
	 * @return double
	 * @since 1.0
	 */
	public function getValue() {
		return $this->value;
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
	 * Used to set the allowable size of the Double in the database field
	 *
	 * @param integer $size
	 * @since 1.0
	 * @throws IllegalArguementException
	 */
	public function setSize($size) {
		if ($size <= self::MAX_SIZE) {
			$this->size = $size;
		}else{
			throw new IllegalArguementException('The value '.$size.' provided by setSize is greater than the MAX_SIZE '.self::MAX_SIZE.' of this data type.');
		}
	}

	/**
	 * Get the allowable size of the Double in the database field
	 *
	 * @param boolean $databaseDimension
	 * @return mixed
	 * @since 1.0
	 */
	public function getSize($databaseDimension=false){
		if($databaseDimension)
			return $this->size.',2';
		else
			return $this->size;
	}

	/**
	 * Used to convert the object to a printable string
	 *
	 * @return string
	 * @since 1.0
	 */
	public function __toString() {
		return strval(sprintf("%01.2f", $this->value));
	}
}

?>