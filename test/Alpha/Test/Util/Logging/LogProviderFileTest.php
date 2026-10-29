<?php

namespace Alpha\Test\Util\Logging;

use Alpha\Util\Logging\LogProviderFile;
use Alpha\Util\Config\ConfigProvider;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for the LogProviderFile class.
 *
 * @since 2.0.4
 *
 * @author John Collins <dev@alphaframework.org>
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2019, John Collins (founder of Alpha Framework).
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

    protected function setUp(): void
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
     * Testing the writeLine() method.
     */
    public function testWriteLineErrorHandle()
    {
        $config = ConfigProvider::getInstance();
        $config->set('app.file.store.dir', '/tmp/not/');

        $logFile = new LogProviderFile();
        $logFile->setPath('/tmp/not/logs/there.log');
        $logFile->writeLine(array('test entry'));

        $this->assertTrue(file_exists('/tmp/not/logs/there.log'));

        $config->set('app.file.store.dir', '/tmp/');
    }

    /**
     * Testing the backupFile() method.
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

    /**
     * Testing that the writeLine method recovers from a missing log directory
     * by falling back to the directory configured in app.file.store.dir. A
     * unique directory is used to ensure the failure path is always exercised.
     *
     * @since 4.1
     */
    public function testWriteLineRecoversFromMissingDirectory(): void
    {
        $dir = sys_get_temp_dir() . '/alpha-logrc-' . uniqid();

        $config = ConfigProvider::getInstance();
        $savedDir = $config->get('app.file.store.dir');

        $config->set('app.file.store.dir', $dir . '/');

        $logFile = new LogProviderFile();
        $logFile->setPath($dir . '/logs/recovered.log');

        try {
            $logFile->writeLine(array('recovered entry'));

            $this->assertTrue(file_exists($dir . '/logs/recovered.log'), 'Testing that the log line was written after the directory fallback');
        } finally {
            $config->set('app.file.store.dir', $savedDir);

            @unlink($dir . '/logs/recovered.log');
            @rmdir($dir . '/logs');
            @rmdir($dir);
        }
    }

    /**
     * Testing that the renderLog method renders SQL level entries and unknown
     * log levels appropriately.
     *
     * @since 4.1
     */
    public function testRenderLogSqlAndUnknownLevels(): void
    {
        $logFile = new LogProviderFile();
        $logFile->setPath($this->logPath);

        $logFile->writeLine(array('2026-01-01 00:00:00', 'SQL', 'a sql entry'));
        $logFile->writeLine(array('2026-01-01 00:00:01', 'BOGUS', 'a weird entry'));

        $html = $logFile->renderLog(array(0 => 'Timestamp', 1 => 'Level', 2 => 'Message'));

        $this->assertTrue(str_contains($html, 'class="sql"'), 'Testing that SQL level entries are rendered with the sql style');
        $this->assertTrue(str_contains($html, '<td>BOGUS</td>'), 'Testing that unknown log levels are rendered in a plain cell');
    }
}
