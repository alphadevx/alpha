<?php

namespace Alpha\Util\Backup;

use Alpha\Util\Config\ConfigProvider;

/**
 * A utility class for carrying out various backup tasks
 *
 * @since 1.1
 * @author John Collins <dev@alphaframework.org>
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2015, John Collins (founder of Alpha Framework).
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
 *
 */
class BackupUtils
{
    /**
     * Backs up the attachments and logs directories to the destination backup directory
     *
     * @param string $backupDir
     * @since 1.1
     */
    public static function backUpAttachmentsAndLogs($backupDir)
    {
        $config = ConfigProvider::getInstance();

        FileUtils::copy($config->get('app.file.store.dir').'attachments', $backupDir.'attachments');
        FileUtils::copy($config->get('app.file.store.dir').'logs', $backupDir.'logs');
    }

    /**
     * Uses the mysqldump command line program to back-up the system database into an .sql file in the supplied target directory
     *
     * @param string $backupDir The directory where we will write the .sql back-up file
     * @since 1.1
     */
    public static function backUpDatabase($backupDir)
    {
        $config = ConfigProvider::getInstance();

        $targetFileName = $backupDir.$config->get('db.name').'_'.date("Y-m-d").'.sql';

        exec('mysqldump  --host="'.$config->get('db.hostname').'" --user="'.$config->get('db.username').'" --password="'.$config->get('db.password').'" --opt '.$config->get('db.name').' 2>&1 >'.$targetFileName);
    }
}

?>
