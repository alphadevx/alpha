<?php

namespace Alpha\Test\Util\Security;

use Alpha\Util\Security\SecurityUtils;
use Alpha\Util\Config\ConfigProvider;

/**
 * Test cases for the SecurityUtils class.
 *
 * @since 2.0.2
 *
 * @author John Collins <dev@alphaframework.org>
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2017, John Collins (founder of Alpha Framework).
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
class SecurityUtilsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Testing the checkAdminPasswordIsDefault() method.
     *
     * @since 2.0.2
     */
    public function testCheckAdminPasswordIsDefault()
    {
        $config = ConfigProvider::getInstance();
        $config->set('app.install.password', 'test');

        $this->assertTrue(SecurityUtils::checkAdminPasswordIsDefault(password_hash('test', PASSWORD_DEFAULT, ['cost' => 12])), 'Testing when the default password is compared');
        $this->assertFalse(SecurityUtils::checkAdminPasswordIsDefault(password_hash('different', PASSWORD_DEFAULT, ['cost' => 12])), 'Testing when a non-default password is compared');
    }

    /**
     * Testing encrypt/decrypt methods.
     *
     * @since 2.0.2
     */
    public function testEncryptDecrypt()
    {
        $plain = "test string";
        $encrypted = SecurityUtils::encrypt($plain);

        $this->assertEquals($plain, SecurityUtils::decrypt($encrypted), "Testing encrypt/decrypt methods");
    }
}