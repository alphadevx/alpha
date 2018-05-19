<?php

namespace Alpha\Model\Type;

use Alpha\Util\Helper\Validator;
use Alpha\Exception\IllegalArguementException;

/**
 * The Boolean complex data type.
 *
 * @since 1.0
 *
 * @author John Collins <dev@alphaframework.org>
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2018, John Collins (founder of Alpha Framework).
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
 */
class Boolean extends Type implements TypeInterface
{
    /**
     * The value of the Boolean.
     *
     * @var bool
     *
     * @since 1.0
     */
    private $booleanValue;

    /**
     * The binary (1/0) value of the Boolean.  This is the value stored in the database.
     *
     * @var int
     *
     * @since 1.0
     */
    private $value;

    /**
     * The error message returned for invalid values.
     *
     * @var string
     *
     * @since 1.0
     */
    protected $helper = 'Not a valid Boolean value!';

    /**
     * Constructor.
     *
     * @param bool $val
     *
     * @since 1.0
     *
     * @throws \Alpha\Exception\IllegalArguementException
     */
    public function __construct($val = true)
    {
        if (!Validator::isBoolean($val)) {
            throw new IllegalArguementException($this->helper);
        }

        if (Validator::isBooleanTrue($val)) {
            $this->value = 1;
            $this->booleanValue = true;
        } else {
            $this->value = 0;
            $this->booleanValue = false;
        }
    }

    /**
     * Used to set the Boolean value.
     *
     * @param mixed $val Will accept a boolean true/false or integer 1/0.
     *
     * @since 1.0
     *
     * @throws \Alpha\Exception\IllegalArguementException
     */
    public function setValue($val)
    {
        if (!Validator::isBoolean($val)) {
            throw new IllegalArguementException($this->helper);
        }

        if (Validator::isBooleanTrue($val)) {
            $this->value = 1;
            $this->booleanValue = true;
        } else {
            $this->value = 0;
            $this->booleanValue = false;
        }
    }

    /**
     * Used to get the binary (1/0) value of the Boolean.  This is the value stored in the database.
     *
     * @return int
     *
     * @since 1.0
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Used to get the boolean value of the Boolean.
     *
     * @return bool
     *
     * @since 1.0
     */
    public function getBooleanValue()
    {
        return $this->booleanValue;
    }

    /**
     * Used to convert the object to a printable string.
     *
     * @return string
     *
     * @since 1.0
     */
    public function __toString()
    {
        return $this->value ? 'true' : 'false';
    }
}
