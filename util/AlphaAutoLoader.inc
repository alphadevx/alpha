<?php

require_once $config->get('app.root').'alpha/util/AlphaErrorHandlers.inc';

/**
 *
 * A class for auto-loading classes in the Alpha Framework, as well as
 * custom classses and files in 3rd party libraries.
 *
 * @package alpha::util
 * @since 1.2
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
class AlphaAutoLoader {
	/**
	 * Hash array of the directories to scan
	 *
	 * @var array
	 */
	private static $scannedDirs;

	/**
	 * Logger
	 *
	 * @var Logger
	 */
	private static $logger;

	/**
	 * Sets up the list of directories to scan.
	 *
	 * @since 1.2
	 */
	private static function initFolderList() {
		global $config;

		if(!isset(self::$logger) && class_exists('Logger'))
			self::$logger = new Logger('AlphaAutoLoader');

		self::$scannedDirs = array(
					$config->get('app.root').'alpha/controller/',
					$config->get('app.root').'alpha/controller/front/',
					$config->get('app.root').'alpha/exceptions/',
					$config->get('app.root').'alpha/model/',
					$config->get('app.root').'alpha/model/types/',
					$config->get('app.root').'alpha/view/',
					$config->get('app.root').'alpha/view/widgets/',
					$config->get('app.root').'alpha/view/renderers/',
					$config->get('app.root').'alpha/util/',
					$config->get('app.root').'alpha/util/cache/',
					$config->get('app.root').'alpha/util/codehighlight/',
					$config->get('app.root').'alpha/util/convertors/',
					$config->get('app.root').'alpha/util/feeds/',
					$config->get('app.root').'alpha/util/graphs/',
					$config->get('app.root').'alpha/util/helpers/',
					$config->get('app.root').'alpha/util/metrics/',
					$config->get('app.root').'alpha/util/filters/',
					$config->get('app.root').'alpha/util/search/',
					$config->get('app.root').'alpha/tests/',
					$config->get('app.root').'controller/',
					$config->get('app.root').'model/',
					$config->get('app.root').'view/'
				);
	}

	/**
	 * Load the file based on the class name provided.  The file is assumed to be called "className.[inc|php]".
	 *
	 * @param string $className
	 * @since 1.2
	 */
	public static function loadClass($className) {
		global $config;

		if(!isset(self::$scannedDirs))
			self::initFolderList();

		// lets try to guess the correct dir to save time, based on the name of the class
		$skipDirs = array();

		if(mb_strpos($className, 'Controller') !== false) {
			if(self::loadFile($config->get('app.root').'alpha/controller/', $className)) {
				// it was found and inclued by loadFile() so we can return
				return;
			}else{
				// it was NOT found by loadFile() in this dir, so we can skip a scan of this dir on the full scan
				array_push($skipDirs, $config->get('app.root').'alpha/controller/');
			}

			if(self::loadFile($config->get('app.root').'alpha/controller/front/', $className)) {
				return;
			}else{
				array_push($skipDirs, $config->get('app.root').'alpha/controller/front/');
			}
		}

		if(mb_strpos($className, 'Exception') !== false) {
			if(self::loadFile($config->get('app.root').'alpha/exceptions/', $className)) {
				return;
			}else{
				array_push($skipDirs, $config->get('app.root').'alpha/exceptions/');
			}
		}

		if(mb_strpos($className, 'DAO') !== false) {
			if(self::loadFile($config->get('app.root').'alpha/model/', $className)) {
				return;
			}else{
				array_push($skipDirs, $config->get('app.root').'alpha/model/');
			}
		}

		if(mb_strpos($className, 'Object') !== false) {
			if(self::loadFile($config->get('app.root').'alpha/model/', $className)) {
				return;
			}else{
				array_push($skipDirs, $config->get('app.root').'alpha/model/');
			}
		}

		if(mb_strpos($className, 'View') !== false) {
			if(self::loadFile($config->get('app.root').'alpha/view/', $className)) {
				return;
			}else{
				array_push($skipDirs, $config->get('app.root').'alpha/view/');
			}
		}

		if(mb_strpos($className, 'Utils') !== false) {
			if(self::loadFile($config->get('app.root').'alpha/util/', $className)) {
				return;
			}else{
				array_push($skipDirs, $config->get('app.root').'alpha/util/');
			}
		}

		// do a full folder scan
		foreach (self::$scannedDirs as $dir) {
			if(!in_array($dir, $skipDirs)) {
				$loaded = self::loadFile($dir, $className);

				if($loaded) {
					if(isset(self::$logger))
						self::$logger->debug('Had to do a full scan to find the class ['.$className.'] in the dir ['.$dir.']');

					return;
				}
			}
		}

		if(isset(self::$logger))
			self::$logger->fatal('Unable to load the required class ['.$className.']');
	}

	/**
	 * Loads the lib file from the lib dir based on the filename provided.
	 *
	 * @param string $fileName
	 * @throws LibraryNotInstalledException
	 * @since 1.2
	 */
	public static function loadLib($fileName) {
		global $config;

		// Libraries shipped with Alpha
		$iterator = new RecursiveDirectoryIterator($config->get('app.root').'alpha/lib/');

		foreach (new RecursiveIteratorIterator($iterator) as $name => $file) {
			if($file->getBaseName() == $fileName) {
				if(isset(self::$logger))
					self::$logger->debug('Found the library file ['.$fileName.'] in the location ['.$name.']');

				require_once $name;
				return;
			}
		}

		// custom libs
		$iterator = new RecursiveDirectoryIterator($config->get('app.root').'lib/');

		foreach (new RecursiveIteratorIterator($iterator) as $name => $file) {
			if($file->getBaseName() == $fileName) {
				if(isset(self::$logger))
					self::$logger->debug('Found the library file ['.$fileName.'] in the location ['.$name.']');

				require_once $name;
				return;
			}
		}

		throw new LibraryNotInstalledException('Cannot find the library file ['.$fileName.'] in the lib dir!');
	}

	/**
	 * Attempts to load the file for the class in the directory indicated.
	 *
	 * @param string $dir
	 * @param string $className
	 * @return bool
	 * @since 1.2
	 */
	private static function loadFile($dir, $className) {
		global $config;

		// we're doing .inc first, as most classes in Alpha use this extension
		$path = $dir.$className.'.inc';
		if (file_exists($path) && is_readable($path)) {
			if(isset(self::$logger))
				self::$logger->debug('Loaded the class ['.$className.'] in the location ['.$path.']');
			include_once $path;
			return true;
		}

		$path = $dir.$className.'.php';
		if (file_exists($path) && is_readable($path)) {
			if(isset(self::$logger))
				self::$logger->debug('Loaded the class ['.$className.'] in the location ['.$path.']');
			include_once $path;
			return true;
		}

		return false;
	}
}

spl_autoload_register('AlphaAutoLoader::loadClass');

?>