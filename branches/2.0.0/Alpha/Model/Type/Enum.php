<?php

namespace Alpha\Model\Type;

use Alpha\Util\Helper\Validator;
use Alpha\Exception\IllegalArguementException;

/**
 * The Enum complex data type
 *
 * @since 1.0
 * @author John Collins <dev@alphaframework.org>
 * @version $Id$
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
class Enum extends Type implements TypeInterface
{
	/**
	 * An array of valid enum options
	 *
	 * @var array
	 * @since 1.0
	 */
	private $options;

	/**
	 * The currently selected enum option
	 *
	 * @var string
	 * @since 1.0
	 */
	private $value = '';

	/**
	 * The message to display to the user when validation fails
	 *
	 * @var string
	 * @since 1.0
	 */
	protected $helper = 'Not a valid enum option!';

	/**
	 * Constructor that sets up the enum options
	 *
	 * @param array $opts
	 * @since 1.0
	 * @throws Alpha\Exception\IllegalArguementException
	 */
	public function __construct($opts=array(''))
	{
		if(is_array($opts))
			$this->options = $opts;
		else
			throw new IllegalArguementException('Not a valid enum option array!');
	}

	/**
	 * Setter for the enum options
	 *
	 * @param array $opts
	 * @since 1.0
	 * @throws Alpha\Exception\IllegalArguementException
	 */
	public function setOptions($opts)
	{
		if(is_array($opts))
			$this->options = $opts;
		else
			throw new IllegalArguementException('Not a valid enum option array!');
	}

	/**
	 * Get the array of enum options
	 *
	 * @param boolean $alphaSort Set to true if you want the Enum options in alphabetical order (default false)
	 * @return array
	 * @since 1.0
	 */
	public function getOptions($alphaSort = false)
	{
		if($alphaSort)
			sort($this->options, SORT_STRING);
		return $this->options;
	}

	/**
	 * Used to get the current enum item
	 *
	 * @return string
	 * @since 1.0
	 */
	public function getValue()
	{
		return $this->value;
	}

	/**
	 * Used to select the current enum item
	 *
	 * @param string $item The item to set as selected in the Enum
	 * @since 1.0
	 * @throws Alpha\Exception\IllegalArguementException
	 */
	public function setValue($item)
	{
		if (in_array($item, $this->options)) {
			$this->value = $item;
		}else{
			throw new IllegalArguementException($this->getHelper());
		}
	}
}

?>