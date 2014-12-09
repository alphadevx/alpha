<?php

namespace Alpha\Controller;

use Alpha\Util\Logging\Logger;
use Alpha\Util\Logging\LogFile;
use Alpha\Exception\IllegalArguementException;
use Alpha\View\View;

/**
 *
 * Controller used to display a log file, the path for which must be supplied in GET vars
 *
 * @since 1.0
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
class ViewLog extends Controller implements ControllerInterface
{
	/**
	 * The path to the log that we are displaying
	 *
	 * @var string
	 * @since 1.0
	 */
	private $logPath;

	/**
	 * Trace logger
	 *
	 * @var Alpha\Util\Logging\Logger
	 * @since 1.0
	 */
	private static $logger = null;

	/**
	 * The constructor
	 *
	 * @since 1.0
	 */
	public function __construct()
	{
		self::$logger = new Logger('ViewLog');
		self::$logger->debug('>>__construct()');

		// ensure that the super class constructor is called, indicating the rights group
		parent::__construct('Admin');

		$this->setTitle('Displaying the requested log');

		self::$logger->debug('<<__construct');
	}

	/**
	 * Handle GET requests
	 *
	 * @param array $params
	 * @throws Alpha\Exception\IllegalArguementException
	 * @since 1.0
	 */
	public function doGET($params)
	{
		self::$logger->debug('>>doGET($params=['.var_export($params, true).'])');

		try{

			// load the business object (BO) definition
			if (isset($params['logPath']) && file_exists($params['logPath'])) {
				$logPath = $params['logPath'];
			} else {
				throw new IllegalArguementException('No log file available to view!');
			}

			$this->logPath = $logPath;

			echo View::displayPageHead($this);

			$log = new LogFile($this->logPath);
			if (preg_match("/alpha.*/", basename($this->logPath)))
				$log->renderLog(array('Date/time','Level','Class','Message','Client','IP'));
			if (preg_match("/search.*/", basename($this->logPath)))
				$log->renderLog(array('Search query','Search date','Client Application','Client IP'));
			if (preg_match("/feeds.*/", basename($this->logPath)))
				$log->renderLog(array('Business object','Feed type','Request date','Client Application','Client IP'));
			if (preg_match("/tasks.*/", basename($this->logPath)))
				$log->renderLog(array('Date/time','Level','Class','Message'));

			echo View::displayPageFoot($this);
		} catch (IllegalArguementException $e) {
			self::$logger->warn($e->getMessage());

			echo View::displayPageHead($this);

			echo View::displayErrorMessage($e->getMessage());

			echo View::displayPageFoot($this);
		}

		self::$logger->debug('<<doGET');
	}

	/**
	 * Handle POST requests
	 *
	 * @param array $params
	 * @since 1.0
	 */
	public function doPOST($params)
	{
		self::$logger->debug('>>doPOST($params=['.var_export($params, true).'])');

		self::$logger->debug('<<doPOST');
	}

	/**
	 * Use this callback to inject in the admin menu template fragment
	 *
	 * @since 1.2
	 */
	public function after_displayPageHead_callback() {
		$menu = View::loadTemplateFragment('html', 'adminmenu.phtml', array());

		return $menu;
	}
}

?>