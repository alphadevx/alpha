<?php

namespace Alpha\Model\Type;

use Alpha\Util\Helper\Validator;
use Alpha\Exception\IllegalArguementException;

/**
 * The Text complex data type.
 *
 * @since 1.0
 *
 * @author John Collins <dev@alphaframework.org>
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2022, John Collins (founder of Alpha Framework).
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
class Text extends Type implements TypeInterface
{
    /**
     * The value of the Text object.
     *
     * @var string
     *
     * @since 1.0
     */
    private $value;

    /**
     * The validation rule for the Text type.
     *
     * @var string
     *
     * @since 1.0
     */
    private $validationRule;

    /**
     * Used to determine if the Text object can support HTML content or not.  Defaults to true, if set to false
     * then HTML content should be filtered.
     *
     * @var bool
     *
     * @since 1.0
     */
    private $allowHTML = true;

    /**
     * The error message for the string type when validation fails.
     *
     * @var string
     *
     * @since 1.0
     */
    protected $helper = 'Not a valid Text value!';

    /**
     * The size of the value for the this Text.
     *
     * @var int
     *
     * @since 1.0
     */
    protected $size = 65535;

    /**
     * The absolute maximum size of the value for the this Text.
     *
     * @var int
     *
     * @since 1.0
     */
    public const MAX_SIZE = 65535;

    /**
     * Constructor.
     *
     * @param string $val
     *
     * @since 1.0
     *
     * @throws \Alpha\Exception\IllegalArguementException
     */
    public function __construct(string $val = '')
    {
        $this->validationRule = Validator::ALLOW_ALL;

        if (mb_strlen($val) <= $this->size) {
            if (preg_match($this->validationRule, $val)) {
                $this->value = $val;
            } else {
                throw new IllegalArguementException($this->helper);
            }
        } else {
            throw new IllegalArguementException($this->helper);
        }
    }

    /**
     * Setter for the value.
     *
     * @param mixed $val
     *
     * @since 1.0
     *
     * @throws \Alpha\Exception\IllegalArguementException
     */
    public function setValue(mixed $val): void
    {
        if ($val == null) {
            $val = '';
        }

        if (mb_strlen($val) <= $this->size) {
            if (preg_match($this->validationRule, $val)) {
                $this->value = $val;
            } else {
                throw new IllegalArguementException($this->helper);
            }
        } else {
            throw new IllegalArguementException($this->helper);
        }
    }

    /**
     * Getter for the value.
     *
     * @since 1.0
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Setter to override the default validation rule.
     *
     * @param string $rule
     *
     * @since 1.0
     */
    public function setRule(string $rule): void
    {
        $this->validationRule = $rule;
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
     * Used to set the allowable size of the Text in the database field.
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
        } else {
            throw new IllegalArguementException('The value '.$size.' provided by setSize is greater than the MAX_SIZE '.self::MAX_SIZE.' of this data type.');
        }
    }

    /**
     * Get the allowable size of the Double in the database field.
     *
     * @since 1.0
     */
    public function getSize(): int
    {
        return $this->size;
    }

    /**
     * Set the $allowHTML value.
     *
     * @param bool $allowHTML
     *
     * @since 1.0
     */
    public function setAllowHTML(bool $allowHTML): void
    {
        $this->allowHTML = $allowHTML;
    }

    /**
     * Get the $allowHTML value.
     *
     * @since 1.0
     */
    public function getAllowHTML(): bool
    {
        return $this->allowHTML;
    }
}
