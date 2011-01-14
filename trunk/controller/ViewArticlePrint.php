<?php

// include the config file
if(!isset($config)) {
	require_once '../util/AlphaConfig.inc';
	$config = AlphaConfig::getInstance();
}

require_once $config->get('sysRoot').'alpha/controller/ViewArticle.php';

/**
 * 
 * Controller used to display a printer-friendly version of an article where the title is provided in GET vars
 * 
 * @package alpha::controller
 * @since 1.0
 * @author John Collins <john@design-ireland.net>
 * @version $Id$
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2010, John Collins (founder of Alpha Framework).  
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
class ViewArticlePrint extends ViewArticle {
	/**
	 * Trace logger
	 * 
	 * @var Logger
	 * @since 1.0
	 */
	private static $logger = null;
	
	/**
	 * Constructor to set up the object
	 * 
	 * @since 1.0
	 */
	public function __construct() {
		self::$logger = new Logger('ViewArticlePrint');
		self::$logger->debug('>>__construct()');
		
		global $config;
		
		// ensure that the super class constructor is called, indicating the rights group
		parent::__construct('Public');
		
		self::$logger->debug('<<__construct');
	}
			
	/**
	 * Handle GET requests
	 * 
	 * @param array $params
	 * @since 1.0
	 * @throws ResourceNotFoundException
	 */
	public function doGET($params) {
		self::$logger->debug('>>doGET($params=['.print_r($params, true).'])');
		
		global $config;
		
		try {
			// ensure that a title is provided
			if (isset($params['title'])) {
				$title = str_replace('_', ' ', $params['title']);
			}else{
				throw new IllegalArguementException('Could not load the article as a title was not supplied!');
			}
			
			$this->BO = new article_object();
			$this->BO->loadByAttribute('title', $title);
			
		}catch(IllegalArguementException $e) {
			self::$logger->error($e->getMessage());
			throw new ResourceNotFoundException($e->getMessage());
		}catch(BONotFoundException $e) {
			self::$logger->error($e->getMessage());
			throw new ResourceNotFoundException($e->getMessage());
		}
		
		$this->setTitle($this->BO->get('title'));
		
		$BOView = AlphaView::getInstance($this->BO);
		
		// disabling force-frames
		$_GET['no-forceframe'] = true;
		
		echo AlphaView::displayPageHead($this);
		
		echo $BOView->markdownView();
		
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
		self::$logger->debug('>>doPOST($params=['.print_r($params, true).'])');
		
		self::$logger->debug('<<doPOST');		
	}
	
	/**
	 * Injects the print CSS into the page header
	 * 
	 * @return string
	 * @since 1.0
	 */
	public function during_displayPageHead_callback() {
		global $config;
		
		return '<link rel="StyleSheet" type="text/css" href="'.$config->get('sysURL').'alpha/css/print.css">';
	}
	
	/**
	 * Callback used to render footer content, including comments, votes and print/PDF buttons when
	 * enabled to do so.
	 * 
	 * @return string
	 * @since 1.0
	 */
	public function before_displayPageFoot_callback() {
		global $config;
		
		$rating = $this->BO->getArticleScore();
		$votes = $this->BO->getArticleVotes();
		
		$html = '';
		
		if($config->get('sysCMSDisplayVotes'))
			$html .= '<p>Average Article User Rating: <strong>'.$rating.'</strong> out of 10 (based on <strong>'.count($votes).'</strong> votes)</p>';
		
		$html .= '<p>Article URL: <a href="'.$this->BO->get('URL').'">'.$this->BO->get('URL').'</a><br>';
		$html .= 'Title: '.$this->BO->get('title').'<br>';
		$html .= 'Author: '.$this->BO->get('author').'<br>';
		$html .= $config->get('sysCMSFooter').'</p>';
		
		return $html;
	}
}

// now build the new controller
if(basename($_SERVER['PHP_SELF']) == 'ViewArticlePrint.php') {
	$controller = new ViewArticlePrint();
	
	if(!empty($_POST)) {			
		$controller->doPOST($_REQUEST);
	}else{
		$controller->doGET($_GET);
	}
}

?>