<?php

// include the config file
if(!isset($config)) {
	require_once '../util/AlphaConfig.inc';
	$config = AlphaConfig::getInstance();
}

require_once $config->get('sysRoot').'alpha/controller/ViewArticle.php';

/**
 * 
 * Controller used to display a Markdown version of a page article where the title is provided in GET vars
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
class ViewArticleTitle extends ViewArticle {
	/**
	 * Trace logger
	 * 
	 * @var Logger
	 */
	private static $logger = null;
	
	/**
	 * Constructor to set up the object
	 * 
	 * @since 1.0
	 */
	public function __construct() {
		self::$logger = new Logger('ViewArticleTitle');
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
		self::$logger->debug('>>doGET($params=['.var_export($params, true).'])');
		
		global $config;
		
		try {
			// it may have already been loaded by a doPOST call
			if($this->BO->isTransient()) {
				// ensure that a title is provided
				if (isset($params['title'])) {
					$title = str_replace('_', ' ', $params['title']);
				}else{
					throw new IllegalArguementException('Could not load the article as a title was not supplied!');
				}
				
				$this->BO = new ArticleObject();
				$this->BO->set('title', $title);
				// this is effectively a lazy-load
				$this->BO->loadByAttribute('title', $title, false, array('OID', 'version_num', 'created_ts', 'updated_ts', 'author', 'published'));
				if(!$this->BO->get('published'))
					throw new BONotFoundException('Attempted to load an article which is not published yet');
				$this->BO->set('tags', $this->BO->getOID());
			}
						
		}catch(IllegalArguementException $e) {
			self::$logger->warn($e->getMessage());
			throw new ResourceNotFoundException('The file that you have requested cannot be found!');
		}catch(BONotFoundException $e) {
			self::$logger->warn($e->getMessage());
			throw new ResourceNotFoundException('The article that you have requested cannot be found!');
		}
		
		$this->setTitle($this->BO->get('title'));
		$this->setDescription($this->BO->get('description'));
		
		$BOView = AlphaView::getInstance($this->BO);
		
		echo AlphaView::displayPageHead($this);
		
		echo $BOView->markdownView();
		
		echo AlphaView::displayPageFoot($this);
		
		self::$logger->debug('<<doGET');
	}	
}

// now build the new controller
if(basename($_SERVER['PHP_SELF']) == 'ViewArticleTitle.php') {
	$controller = new ViewArticleTitle();
	
	if(!empty($_POST)) {			
		$controller->doPOST($_REQUEST);
	}else{
		$controller->doGET($_GET);
	}
}

?>