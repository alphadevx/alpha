<?php

namespace Alpha\Model\Type;

use Alpha\Util\Helper\Validator;
use Alpha\Exception\IllegalArguementException;

/**
 * The String complex data type
 *
 * @since 1.0
 * @author John Collins <dev@alphaframework.org>
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2015, John Collins (founder of Alpha Framework).
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
class String extends Type implements TypeInterface
{
	/**
	 * The value of the string
	 *
	 * @var string
	 * @since 1.0
	 */
	private $value;

	/**
	 * The validation rule for the string type
	 *
	 * @var string
	 * @since 1.0
	 */
	private $validationRule;

	/**
	 * The error message for the string type when validation fails
	 *
	 * @var string
	 * @since 1.0
	 */
	protected $helper = 'Not a valid string value!';

	/**
	 * The size of the value for the this String
	 *
	 * @var integer
	 * @since 1.0
	 */
	private $size = 255;

	/**
	 * The absolute maximum size of the value for the this String
	 *
	 * @var integer
	 * @since 1.0
	 */
	const MAX_SIZE = 255;

	/**
	 * Simple boolean to determine if the string is a password or not
	 *
	 * @var boolean
	 * @since 1.0
	 */
	private $password = false;

	/**
	 * Constructor
	 *
	 * @param string $val
	 * @since 1.0
	 * @throws Alpha\Exception\IllegalArguementException
	 */
	public function __construct($val='')
	{

		$this->validationRule = Validator::ALLOW_ALL;

		if (mb_strlen($val) <= $this->size) {
			if (preg_match($this->validationRule, $val)) {
				$this->value = $val;
			}else{
				throw new IllegalArguementException($this->helper);
			}
		}else{
			throw new IllegalArguementException($this->helper);
		}
	}

	/**
	 * Setter for the value
	 *
	 * @param string $val
	 * @since 1.0
	 * @throws Alpha\Exception\IllegalArguementException
	 */
	public function setValue($val)
	{

		if (mb_strlen($val) <= $this->size) {
			if (preg_match($this->validationRule, $val)) {
				$this->value = $val;
			}else{
				throw new IllegalArguementException($this->helper);
			}
		}else{
			throw new IllegalArguementException($this->helper);
		}
	}

	/**
	 * Getter for the value
	 *
	 * @return string
	 * @since 1.0
	 */
	public function getValue()
	{
		return $this->value;
	}

	/**
	 * Setter to override the default validation rule
	 *
	 * @param string $rule
	 * @since 1.0
	 */
	public function setRule($rule)
	{
		$this->validationRule = $rule;
	}

	/**
 	 * Get the validation rule
 	 *
 	 * @return string
 	 * @since 1.0
 	 */
 	public function getRule()
 	{
		return $this->validationRule;
	}

	/**
	 * Used to set the allowable size of the String in the database field
	 *
	 * @param integer $size
	 * @since 1.0
	 * @throws Alpha\Exception\IllegalArguementException
	 */
	public function setSize($size)
	{
		if ($size <= self::MAX_SIZE) {
			$this->size = $size;
		}else{
			throw new IllegalArguementException('Error: the value '.$size.' provided by setSize is greater than the MAX_SIZE '.self::MAX_SIZE.' of this data type.');
		}
	}

	/**
	 * Get the allowable size of the Double in the database field
	 *
	 * @return integer
	 * @since 1.0
	 */
	public function getSize()
	{
		return $this->size;
	}

	/**
	 * Sets up an appropriate validation rule for a required field
	 *
	 * @param bool $req
	 * @since 1.0
	 */
	public function isRequired($req=true)
	{
		if ($req) {
			$this->validationRule = Validator::REQUIRED_STRING;
			$this->helper = 'This string requires a value!';
		}
	}

	/**
	 * Define the string as a password (making it required by validation rule)
	 *
	 * @param boolean $pass
	 * @since 1.0
	 */
	public function isPassword($pass=true)
	{
		$this->password = $pass;

		if($pass) {
			$this->validationRule = '/\w+/';
			$this->helper = 'Password is required!';
		}
	}

	/**
	 * Checks to see if the string is a password or not
	 *
	 * @return boolean
	 * @since 1.0
	 */
	public function checkIsPassword()
	{
		return $this->password;
	}
}

?>