<?php

namespace Alpha\Test\Util\Logging;

use Alpha\Util\Logging\LogProviderFile;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for the LogProviderFile class.
 *
 * @since 2.0.4
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
class LogProviderFileTest extends TestCase
{
    private $logPath = '/tmp/alphatestlog.log';

    protected function setUp()
    {
        if (file_exists($this->logPath)) {
            unlink($this->logPath);
        }

        $backName = str_replace('.log', '-backup-'.date('y-m-d').'.log.zip', $this->logPath);

        if (file_exists($backName)) {
            unlink($backName);
        }
    }

    /**
     * Testing the writeLine() method.
     *
     * @since 2.0.4
     */
    public function testWriteLine()
    {
        $this->assertFalse(file_exists($this->logPath));

        $logFile = new LogProviderFile();
        $logFile->setPath($this->logPath);
        $logFile->writeLine(array('test entry'));

        $this->assertTrue(file_exists($this->logPath));
    }

    /**
     * Testing the backupFile() method.
     *
     * @since 2.0.4
     */
    public function testBackupFile()
    {
        $this->assertFalse(file_exists($this->logPath));

        $logFile = new LogProviderFile();
        $logFile->setPath($this->logPath);
        $logFile->setMaxSize(1);

        for ($i = 0; $i < 15000; $i++) {
            $logFile->writeLine(array('test entry test entry test entry test entry test entry test entry test entry'));
        }

        $this->assertTrue(file_exists($this->logPath));

        $backName = str_replace('.log', '-backup-'.date('y-m-d').'.log.zip', $this->logPath);

        $this->assertTrue(file_exists($backName));
    }
}
