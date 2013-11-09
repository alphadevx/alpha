<?php

/**
 * A cron task for backup up the system database and select folders
 *
 * @package alpha::tasks
 * @since 1.1
 * @author John Collins <dev@alphaframework.org>
 * @version $Id$
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2013, John Collins (founder of Alpha Framework).
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
class BackupTask implements AlphaTaskInterface {
	/**
	 * Trace logger
	 *
	 * @var Logger
	 */
	private static $logger = null;

	/**
	 * (non-PHPdoc)
	 * @see alpha/util/AlphaTaskInterface#doTask()
	 */
	public function doTask() {
		global $config;

		self::$logger = new Logger('BackupTask');
		self::$logger->setLogFile($config->get('app.file.store.dir').'logs/tasks.log');

		if(!file_exists($config->get('backup.dir')))
			mkdir($config->get('backup.dir'));

		$targetDir = $config->get('backup.dir').date("Y-m-d").'/';

		if(file_exists($targetDir))
			AlphaFileUtils::deleteDirectoryContents($targetDir);

		if(!file_exists($targetDir))
			mkdir($targetDir);

		$back = new AlphaBackupUtils();
		$back->backUpAttachmentsAndLogs($targetDir);
		$back->backUpDatabase($targetDir);

		$additionalDirectories = explode(',', $config->get('backup.include.dirs'));

		if(count($additionalDirectories) > 0) {
			foreach($additionalDirectories as $additionalDirectory)
				AlphaFileUtils::copy($additionalDirectory, $targetDir.basename($additionalDirectory));
		}

		if($config->get('backup.compress')) {
			AlphaFileUtils::zip($targetDir, $targetDir.date("Y-m-d").'.zip');

			// we can safely remove the uncompressed files now to save space...
			AlphaFileUtils::deleteDirectoryContents($targetDir.'logs');
			rmdir($targetDir.'logs');

			AlphaFileUtils::deleteDirectoryContents($targetDir.'attachments');
			rmdir($targetDir.'attachments');

			unlink($targetDir.$config->get('db.name').'_'.date("Y-m-d").'.sql');

			if(count($additionalDirectories) > 0) {
				foreach($additionalDirectories as $additionalDirectory) {
					AlphaFileUtils::deleteDirectoryContents($targetDir.basename($additionalDirectory));
					rmdir($targetDir.basename($additionalDirectory));
				}
			}
		}
	}

	/**
	 * (non-PHPdoc)
	 * @see alpha/util/AlphaTaskInterface#getMaxRunTime()
	 */
	public function getMaxRunTime() {
		return 180;
	}
}

?>