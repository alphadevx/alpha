<?php

namespace Alpha\Model\Type;

use Alpha\Util\Helper\Validator;
use Alpha\Exception\IllegalArguementException;

/**
 * The LargeText complex data type.
 *
 * @since 3.1
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
class LargeText extends Text implements TypeInterface
{
    /**
     * The value of the LargeText object.
     *
     * @var string
     *
     * @since 3.1
     */
    private $value;

    /**
     * The validation rule for the LargeText type.
     *
     * @var string
     *
     * @since 3.1
     */
    private $validationRule;

    /**
     * Used to determine if the LargeText object can support HTML content or not.  Defaults to true, if set to false
     * then HTML content should be filtered.
     *
     * @var bool
     *
     * @since 3.1
     */
    private $allowHTML = true;

    /**
     * The error message for the string type when validation fails.
     *
     * @var string
     *
     * @since 3.1
     */
    protected $helper = 'Not a valid LargeText value!';

    /**
     * The size of the value for the this LargeText.
     *
     * @var int
     *
     * @since 3.1
     */
    protected $size = 16777215;

    /**
     * The absolute maximum size of the value for the this LargeText.
     *
     * @var int
     *
     * @since 1.0
     */
    public const MAX_SIZE = 16777215;
}
