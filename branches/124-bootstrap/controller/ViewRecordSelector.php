<?php

// include the config file
if(!isset($config)) {
	require_once '../util/AlphaConfig.inc';
	$config = AlphaConfig::getInstance();

	require_once $config->get('app.root').'alpha/util/AlphaAutoLoader.inc';
}

/**
 * Controller for viewing a RecordSelector widget.
 *
 * @package alpha::controller
 * @since 1.0
 * @author John Collins <dev@alphaframework.org>
 * @version $Id$
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2014, John Collins (founder of Alpha Framework).
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
class ViewRecordSelector extends AlphaController implements AlphaControllerInterface {
	/**
	 * Trace logger
	 *
	 * @var Logger
	 * @since 1.0
	 */
	private static $logger = null;

	/**
	 * Constructor
	 *
	 * @since 1.0
	 */
	public function __construct() {
		self::$logger = new Logger('ViewRecordSelector');
		self::$logger->debug('>>__construct()');

		// ensure that the super class constructor is called, indicating the rights group
		parent::__construct('Public');

		self::$logger->debug('<<__construct');
	}

	/**
	 * Handles get requests
	 *
	 * @param array $params
	 * @since 1.0
	 * @throws ResourceNotFoundException
	 */
	public function doGet($params) {
		self::$logger->debug('>>doGet(params=['.var_export($params, true).'])');

		$relationObject = new Relation();

		try {
			$relationType = $params['relationType'];
			$value = $params['value'];
		}catch (Exception $e) {
			self::$logger->error('Required param missing for ViewRecordSelector controller['.$e->getMessage().']');
			throw new ResourceNotFoundException('File not found');
		}

		if($_GET['relationType'] == 'MANY-TO-MANY') {
			try {
				$relatedClassLeft = $params['relatedClassLeft'];
				$relatedClassLeftDisplayField = $params['relatedClassLeftDisplayField'];
				$relatedClassRight = $params['relatedClassRight'];
				$relatedClassRightDisplayField = $params['relatedClassRightDisplayField'];
				$field = $params['field'];
				$accessingClassName = $params['accessingClassName'];
				$lookupOIDs = $params['lookupOIDs'];
			}catch (Exception $e) {
				self::$logger->error('Required param missing for ViewRecordSelector controller['.$e->getMessage().']');
				throw new ResourceNotFoundException('File not found');
			}

			$relationObject->setRelatedClass($relatedClassLeft, 'left');
			$relationObject->setRelatedClassDisplayField($relatedClassLeftDisplayField, 'left');
			$relationObject->setRelatedClass($relatedClassRight, 'right');
			$relationObject->setRelatedClassDisplayField($relatedClassRightDisplayField, 'right');
			$relationObject->setRelationType($relationType);
			$relationObject->setValue($value);

			$recSelector = new RecordSelector($relationObject, '', $field, $accessingClassName);
			echo $recSelector->renderSelector(explode(',', $lookupOIDs));
		}else{
			try {
				$relatedClass = $params['relatedClass'];
				$relatedClassField = $params['relatedClassField'];
				$relatedClassDisplayField = $params['relatedClassDisplayField'];
			}catch (Exception $e) {
				self::$logger->error('Required param missing for ViewRecordSelector controller['.$e->getMessage().']');
				throw new ResourceNotFoundException('File not found');
			}

			$relationObject->setRelatedClass($relatedClass);
			$relationObject->setRelatedClassField($relatedClassField);
			$relationObject->setRelatedClassDisplayField($relatedClassDisplayField);
			$relationObject->setRelationType($relationType);
			$relationObject->setValue($value);

			$recSelector = new RecordSelector($relationObject);
			echo $recSelector->renderSelector();
		}

		self::$logger->debug('<<__doGet');
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
}

// now build the new controller if this file is called directly
if ('ViewRecordSelector.php' == basename($_SERVER['PHP_SELF'])) {
	$controller = new ViewRecordSelector();

	if(!empty($_POST)) {
		$controller->doPOST($_POST);
	}else{
		$controller->doGET($_GET);
	}
}

?>