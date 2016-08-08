<?php

namespace Alpha\Util\Security;

use Alpha\Util\Config\ConfigProvider;

/**
 * A utility class for carrying out various security tasks.
 *
 * @since 1.2.2
 *
 * @author John Collins <dev@alphaframework.org>
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2016, John Collins (founder of Alpha Framework).
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
class SecurityUtils
{
    /**
     * Encrypt provided data using AES 256 algorithm and the security.encryption.key.
     *
     * @param string $data
     *
     * @return string
     *
     * @since 1.2.2
     */
    public static function encrypt($data)
    {
        $config = ConfigProvider::getInstance();

        $ivsize = openssl_cipher_iv_length('aes-256-ecb');
        $iv = openssl_random_pseudo_bytes($ivsize);

        $encryptedData = openssl_encrypt(
            $data,
            'aes-256-ecb',
            $config->get('security.encryption.key'),
            OPENSSL_RAW_DATA,
            $iv
        );

        return $iv . $encryptedData;
    }

    /**
     * Decrypt provided data using AES 256 algorithm and the security.encryption.key.
     *
     * @param string $data
     *
     * @return string
     *
     * @since 1.2.2
     */
    public static function decrypt($data)
    {
        $config = ConfigProvider::getInstance();

        $ivsize = openssl_cipher_iv_length('aes-256-ecb');
        $iv = mb_substr($data, 0, $ivsize, '8bit');
        $ciphertext = mb_substr($data, $ivsize, null, '8bit');

        $decryptedData = openssl_decrypt(
            $ciphertext,
            'aes-256-ecb',
            $config->get('security.encryption.key'),
            OPENSSL_RAW_DATA,
            $iv
        );

        return $decryptedData;
    }

    /**
     * Checks to see if the admin password provided matches the default admin password in the config file.
     *
     * @param string $password The encrypted admin password stored in the database.
     *
     * @return boolean
     *
     * @since 2.0.2
     */
    public static function checkAdminPasswordIsDefault($password)
    {
        $config = ConfigProvider::getInstance();

        return password_verify($config->get('app.install.password'), $password);
    }
}
