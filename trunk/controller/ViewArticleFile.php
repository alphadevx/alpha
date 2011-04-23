<?php

// include the config file
if(!isset($config)) {
	require_once '../util/AlphaConfig.inc';
	$config = AlphaConfig::getInstance();
}

require_once $config->get('sysRoot').'alpha/controller/ViewArticle.php';
require_once $config->get('sysRoot').'alpha/exceptions/FileNotFoundException.inc';

/**
 * 
 * Controller used to display a Markdown version of a page article where the name of the
 * .text file containing the Markdown/HTML content is provided
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
class ViewArticleFile extends ViewArticle {
	/**
	 * Trace logger
	 * 
	 * @var Logger
	 * @since 1.0
	 */
	private static $logger = null;
									
	/**
	 * Handle GET requests
	 * 
	 * @param array $params
	 * @since 1.0
	 * @throws ResourceNotFoundException
	 */
	public function doGET($params) {
		self::$logger = new Logger('ViewArticleFile');
			
		global $config;
		
		try {
			// ensure that a file path is provided
			if (!isset($params['file'])) {
				throw new IllegalArguementException('Could not load the article as a file name was not supplied!');
			}
			
			$this->BO = new ArticleObject();

			// just checking to see if the file path is absolute or not
			if(substr($params['file'], 0, 1) == '/')
				$this->BO->loadContentFromFile($params['file']);
			else
				$this->BO->loadContentFromFile($config->get('sysRoot').'alpha/docs/'.$params['file']);
			
		}catch(IllegalArguementException $e) {
			self::$logger->error($e->getMessage());
			throw new ResourceNotFoundException($e->getMessage());
		}catch(FileNotFoundException $e) {
			self::$logger->warn($e->getMessage());
			throw new ResourceNotFoundException('Failed to load the requested article from the file system!');
		}

		$this->setTitle($this->BO->get('title'));
		
		$BOView = AlphaView::getInstance($this->BO);
		
		echo AlphaView::displayPageHead($this, false);
		
		echo $BOView->markdownView();
		
		echo AlphaView::displayPageFoot($this);
	}

	/**
	 * Overidding the parent here as we want an empty footer on file-based articles
	 * 
	 * @return string
	 * @since 1.0
	 */
	public function before_displayPageFoot_callback() {
		return '';
	}
}

// now build the new controller
if(basename($_SERVER['PHP_SELF']) == 'ViewArticleFile.php') {
	$controller = new ViewArticleFile();
	
	if(!empty($_POST)) {			
		$controller->doPOST($_REQUEST);
	}else{
		$controller->doGET($_GET);
	}
}

?>