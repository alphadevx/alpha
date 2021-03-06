<?php

namespace Alpha\Test\View;

use Alpha\View\PersonView;
use Alpha\View\View;
use Alpha\Model\Person;
use Alpha\Util\Config\ConfigProvider;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for the PersonView class.
 *
 * @since 3.0
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
class PersonViewTest extends TestCase
{
    /**
     * Testing the displayLoginForm() method.
     *
     * @since 3.0
     */
    public function testDisplayLoginForm()
    {
        $person = new Person();
        $view = View::getInstance($person);

        $this->assertTrue(strpos($view->displayLoginForm(), 'Login') !== false, 'Testing the displayLoginForm() method');
    }

    /**
     * Testing the displayResetForm() method.
     *
     * @since 3.0
     */
    public function testDisplayResetForm()
    {
        $person = new Person();
        $view = View::getInstance($person);

        $this->assertTrue(strpos($view->displayResetForm(), 'reset') !== false, 'Testing the displayResetForm() method');
    }

    /**
     * Testing the displayRegisterForm() method.
     *
     * @since 3.0
     */
    public function testDisplayRegisterForm()
    {
        $person = new Person();
        $view = View::getInstance($person);

        $this->assertTrue(strpos($view->displayRegisterForm(), 'Register') !== false, 'Testing the displayRegisterForm() method');
    }
}
