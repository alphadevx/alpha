<?php

// include the config file
if(!isset($config)) {
	require_once '../util/configLoader.inc';
	$config = configLoader::getInstance();

	require_once $config->get('app.root').'alpha/util/AlphaAutoLoader.inc';
}

/**
 *
 * Controller used to list all DEnums
 *
 * @package alpha::controller
 * @since 1.0
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
class ListDEnums extends ListAll implements AlphaControllerInterface {
	/**
	 * Trace logger
	 *
	 * @var Logger
	 * @since 1.0
	 */
	private static $logger = null;

	/**
	 * constructor to set up the object
	 *
	 * @since 1.0
	 */
	public function __construct() {
		self::$logger = new Logger('ListDEnums');
		self::$logger->debug('>>__construct()');

		// ensure that the super class constructor is called, indicating the rights group
		parent::__construct('Admin');

		$this->BO = new DEnum();

		// make sure that the DEnum tables exist
		if(!$this->BO->checkTableExists()) {
			echo AlphaView::displayErrorMessage('Warning! The DEnum tables do not exist, attempting to create them now...');
			$this->createDEnumTables();
		}

		$this->BOname = 'DEnum';

		$this->BOView = AlphaView::getInstance($this->BO);

		// set up the title and meta details
		$this->setTitle('Listing all DEnums');
		$this->setDescription('Page to list all DEnums.');
		$this->setKeywords('list,all,DEnums');

		self::$logger->debug('<<__construct');
	}

	/**
	 * Handle GET requests
	 *
	 * @param array $params
	 * @since 1.0
	 */
	public function doGET($params) {
		self::$logger->debug('>>doGET($params=['.var_export($params, true).'])');

		echo AlphaView::displayPageHead($this);

		// get all of the BOs and invoke the list_view on each one
		$temp = new DEnum();
		// set the start point for the list pagination
		if (isset($params['start']) ? $this->startPoint = $params['start']: $this->startPoint = 1);

		$objects = $temp->loadAll($this->startPoint);

		AlphaDAO::disconnect();

		$this->BOCount = $this->BO->getCount();

		echo AlphaView::renderDeleteForm();

		foreach($objects as $object) {
			$temp = AlphaView::getInstance($object);
			echo $temp->listView();
		}

		echo AlphaView::displayPageFoot($this);

		self::$logger->debug('<<doGET');
	}

	/**
	 * Handle POST requests
	 *
	 * @param array $params
	 * @since 1.0
	 */
	public function doPOST($params) {
		self::$logger->debug('>>doPOST($params=['.var_export($params, true).'])');

		self::$logger->debug('<<doPOST');
	}

	/**
	 * Method to create the DEnum tables if they don't exist
	 *
	 * @since 1.0
	 */
	private function createDEnumTables() {
		$tmpDEnum = new DEnum();

		echo '<p>Attempting to build table '.DEnum::TABLE_NAME.' for class DEnum : </p>';

		try {
			$tmpDEnum->makeTable();
			echo AlphaView::displayUpdateMessage('Successfully re-created the database table '.DEnum::TABLE_NAME);
			self::$logger->action('Re-created the table '.DEnum::TABLE_NAME);
		}catch(AlphaException $e) {
			echo AlphaView::displayErrorMessage('Failed re-created the database table '.DEnum::TABLE_NAME.', check the log');
			self::$logger->error($e->getMessage());
		}

		$tmpDEnumItem = new DEnumItem();

		echo '<p>Attempting to build table '.DEnumItem::TABLE_NAME.' for class DEnumItem : </p>';

		try {
			$tmpDEnumItem->makeTable();
			echo AlphaView::displayUpdateMessage('Successfully re-created the database table '.DEnumItem::TABLE_NAME);
			self::$logger->action('Re-created the table '.DEnumItem::TABLE_NAME);
		}catch(AlphaException $e) {
			echo AlphaView::displayErrorMessage('Failed re-created the database table '.DEnumItem::TABLE_NAME.', check the log');
			self::$logger->error($e->getMessage());
		}
	}

	/**
	 * Use this callback to inject in the admin menu template fragment
	 *
	 * @since 1.2
	 */
	public function after_displayPageHead_callback() {
		$menu = AlphaView::loadTemplateFragment('html', 'adminmenu.phtml', array());

		return $menu;
	}
}

// now build the new controller if this file is called directly
if ('ListDEnums.php' == basename($_SERVER['PHP_SELF'])) {
	$controller = new ListDEnums();

	if(!empty($_POST)) {
		$controller->doPOST($_POST);
	}else{
		$controller->doGET($_GET);
	}
}

?>