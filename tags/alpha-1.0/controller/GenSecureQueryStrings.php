<?php

// include the config file
if(!isset($config)) {
	require_once '../util/AlphaConfig.inc';
	$config = AlphaConfig::getInstance();
}

require_once $config->get('sysRoot').'alpha/controller/AlphaController.inc';
require_once $config->get('sysRoot').'alpha/controller/front/FrontController.inc';
require_once $config->get('sysRoot').'alpha/controller/AlphaControllerInterface.inc';
require_once $config->get('sysRoot').'alpha/view/AlphaView.inc';

/**
 *
 * Controller used to generate secure URLs from the query strings provided
 * 
 * @package alpha::controller
 * @since 1.0
 * @author John Collins <john@design-ireland.net>
 * @version $Id$
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2011, John Collins (founder of Alpha Framework).  
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
class GenSecureQueryStrings extends AlphaController implements AlphaControllerInterface {
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
		self::$logger = new Logger('CacheManager');
		self::$logger->debug('>>__construct()');
		
		global $config;
		
		// ensure that the super class constructor is called, indicating the rights group
		parent::__construct('Admin');
		
		$this->setTitle('Generate Secure Query Strings');
		
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
		
		echo $this->renderForm();
		
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
		
		global $config;

		echo AlphaView::displayPageHead($this);
		
		echo '<p style="width:90%; overflow:scroll;">';
		if(isset($params['QS']))
			echo FrontController::generateSecureURL($params['QS']);
		echo '</p>';
		
		echo $this->renderForm();
		
		echo AlphaView::displayPageFoot($this);
		
		self::$logger->debug('<<doPOST');
	}
	
	/**
	 * Renders the HTML form for generating secure URLs
	 * 
	 * @return string
	 * @since 1.0
	 */
	private function renderForm() {
		global $config;
		
		$html = '<p>Use this form to generate secure (encrypted) URLs which make use of the Front Controller.  Always be sure to specify an action controller'.
			' (act) at a minimum.</p>';
		$html .= '<p>Example 1: to generate a secure URL for viewing article object 00000000001, enter <em>act=ViewArticle&oid=00000000001</em></p>';
		$html .= '<p>Example 2: to generate a secure URL for viewing an Atom news feed of the articles, enter'.
			' <em>act=ViewFeed&bo=ArticleObject&type=Atom</em</p>';

		$html .= '<form action="'.$_SERVER['REQUEST_URI'].'" method="post">';
		$html .= '<input type="text" name="QS" size="100"/>';
		$temp = new Button('submit', 'Generate', 'saveBut');
		$html .= $temp->render();
		$html .= '</form>';
		
		return $html;
	}
}

// now build the new controller if this file is called directly
if ('GenSecureQueryStrings.php' == basename($_SERVER['PHP_SELF'])) {
	$controller = new GenSecureQueryStrings();
	
	if(!empty($_POST)) {			
		$controller->doPOST($_QUERY);
	}else{
		$controller->doGET($_GET);
	}
}

?>