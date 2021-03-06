<?php

namespace Alpha\Controller;

/**
 * The interface for all page controllers.
 *
 * @since 1.0
 *
 * @author John Collins <dev@alphaframework.org>
 *
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
interface ControllerInterface
{
    /**
     * Handles HEAD HTTP requests.
     *
     * @param \Alpha\Util\Http\Request $request
     *
     * @since 1.0
     */
    public function doHEAD(\Alpha\Util\Http\Request $request): \Alpha\Util\Http\Response;

    /**
     * Handles GET HTTP requests.
     *
     * @param \Alpha\Util\Http\Request $request
     *
     * @since 1.0
     */
    public function doGET(\Alpha\Util\Http\Request $request): \Alpha\Util\Http\Response;

    /**
     * Handles POST HTTP requests.
     *
     * @param \Alpha\Util\Http\Request $request
     *
     * @since 1.0
     */
    public function doPOST(\Alpha\Util\Http\Request $request): \Alpha\Util\Http\Response;

    /**
     * Handles PUT HTTP requests.
     *
     * @param \Alpha\Util\Http\Request $request
     *
     * @since 1.0
     */
    public function doPUT(\Alpha\Util\Http\Request $request): \Alpha\Util\Http\Response;

    /**
     * Handles PATCH HTTP requests.
     *
     * @param \Alpha\Util\Http\Request $request
     *
     * @since 1.0
     */
    public function doPATCH(\Alpha\Util\Http\Request $request): \Alpha\Util\Http\Response;

    /**
     * Handles DELETE HTTP requests.
     *
     * @param \Alpha\Util\Http\Request $request
     *
     * @since 1.0
     */
    public function doDELETE(\Alpha\Util\Http\Request $request): \Alpha\Util\Http\Response;

    /**
     * Handles OPTIONS HTTP requests.
     *
     * @param \Alpha\Util\Http\Request $request
     *
     * @since 1.0
     */
    public function doOPTIONS(\Alpha\Util\Http\Request $request): \Alpha\Util\Http\Response;

    /**
     * Handles TRACE HTTP requests.
     *
     * @param \Alpha\Util\Http\Request $request
     *
     * @since 2.0.2
     */
    public function doTRACE(\Alpha\Util\Http\Request $request): \Alpha\Util\Http\Response;
}
