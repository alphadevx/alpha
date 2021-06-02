<?php

namespace Alpha\Model\Type;

use Alpha\Util\Helper\Validator;
use Alpha\Exception\IllegalArguementException;

/**
 * The Integer complex data type.
 *
 * @since 1.0
 *
 * @author John Collins <dev@alphaframework.org>
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2021, John Collins (founder of Alpha Framework).
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
class Integer extends Type implements TypeInterface
{
    /**
     * The value of the Integer.
     *
     * @var int
     *
     * @since 1.0
     */
    private $value;

    /**
     * The validation rule (reg-ex) applied to Integer values.
     *
     * @var string
     *
     * @since 1.0
     */
    private $validationRule;

    /**
     * The error message for the Integer type when validation fails.
     *
     * @var string
     *
     * @since 1.0
     */
    protected $helper = 'Not a valid integer value!';

    /**
     * The size of the value for the Integer.
     *
     * @var int
     *
     * @since 1.0
     */
    private $size = 11;

    /**
     * The absolute maximum size of the value for the this Integer.
     *
     * @var int
     *
     * @since 1.0
     */
    public const MAX_SIZE = 11;

    /**
     * Constructor.
     *
     * @param int $val
     *
     * @since 1.0
     *
     * @throws \Alpha\Exception\IllegalArguementException
     */
    public function __construct(int $val = 0)
    {
        $this->validationRule = Validator::REQUIRED_INTEGER;

        if (!Validator::isInteger($val)) {
            throw new IllegalArguementException($this->helper);
        }

        if (mb_strlen($val) <= $this->size) {
            $this->value = $val;
        } else {
            throw new IllegalArguementException($this->helper);
        }
    }

    /**
     * Setter for the Integer value.
     *
     * @param mixed $val
     *
     * @since 1.0
     *
     * @throws \Alpha\Exception\IllegalArguementException
     */
    public function setValue(mixed $val): void
    {
        if (!Validator::isInteger($val)) {
            throw new IllegalArguementException($this->helper);
        }

        if (mb_strlen($val) <= $this->size) {
            $this->value = $val;
        } else {
            throw new IllegalArguementException($this->helper);
        }
    }

    /**
     * Getter for the Integer value.
     *
     * @since 1.0
     */
    public function getValue(): int
    {
        return intval($this->value);
    }

    /**
     * Get the validation rule.
     *
     * @since 1.0
     */
    public function getRule(): string
    {
        return $this->validationRule;
    }

    /**
     * Used to set the allowable size of the Integer in the database field.
     *
     * @param int $size
     *
     * @since 1.0
     *
     * @throws \Alpha\Exception\IllegalArguementException
     */
    public function setSize(int $size): void
    {
        if ($size <= self::MAX_SIZE) {
            $this->size = $size;
            $this->helper = 'Not a valid integer value!  A maximum of '.$this->size.' characters is allowed';
        } else {
            throw new IllegalArguementException('Error: the value '.$size.' provided by set_size is greater than the MAX_SIZE '.self::MAX_SIZE.' of this data type.');
        }
    }

    /**
     * Get the allowable size of the Integer in the database field.
     *
     * @since 1.0
     */
    public function getSize(): int
    {
        return $this->size;
    }

    /**
     * Returns the integer value provided but padded with zeros to MAX_SIZE.
     *
     * @param int $val
     *
     * @since 1.0
     */
    public static function zeroPad(int $val): string
    {
        return str_pad($val, self::MAX_SIZE, '0', STR_PAD_LEFT);
    }
}
