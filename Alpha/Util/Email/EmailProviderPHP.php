<?php

namespace Alpha\Util\Email;

use Alpha\Exception\MailNotSentException;
use Alpha\Exception\PHPException;
use Alpha\Util\Logging\Logger;
use Alpha\Util\Config\ConfigProvider;

/**
 * Sends an email using the mb_send_mail() function from PHP.
 *
 * @since 2.0
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
class EmailProviderPHP implements EmailProviderInterface
{
    /**
     * Trace logger.
     *
     * @var \Alpha\Util\Logging\Logger
     *
     * @since 2.0
     */
    private static $logger = null;

    /**
     * Constructor.
     *
     * @since 2.0
     */
    public function __construct()
    {
        self::$logger = new Logger('EmailProviderPHP');
    }

    /**
     * {@inheritdoc}
     */
    public function send($to, $from, $subject, $body, $isHTML = false): void
    {
        self::$logger->debug('>>send(to=['.$to.'], from=['.$from.'], subject=['.$subject.'], body=['.$body.'], isHTML=['.$isHTML.'])');

        $config = ConfigProvider::getInstance();

        $headers = 'MIME-Version: 1.0'."\n";
        if ($isHTML) {
            $headers .= 'Content-type: text/html; charset=iso-8859-1'."\n";
        }
        $headers .= 'From: '.$from."\n";

        if ($config->getEnvironment() != 'dev' && $config->getEnvironment() != 'test') {
            try {
                mb_send_mail($to, $subject, $body, $headers);
            } catch (PHPException $e) {
                throw new MailNotSentException('Error sending a mail to ['.$to.']');
            }
        } else {
            self::$logger->info("Sending email:\n".$headers."\n".$body);
        }

        self::$logger->debug('<<send');
    }
}
