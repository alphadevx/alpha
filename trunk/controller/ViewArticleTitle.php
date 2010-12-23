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
 * @author John Collins <john@design-ireland.net>
 * @package alpha::controller
 * @copyright 2009 John Collins
 * @version $Id$
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
	 * Handle GET requests
	 * 
	 * @param array $params
	 */
	public function doGET($params) {
		global $config;
		
		if(self::$logger == null)
			self::$logger = new Logger('ViewArticleTitle');
		
		try {
			// it may have already been loaded by a doPOST call
			if($this->BO->isTransient()) {
				// ensure that a title is provided
				if (isset($params['title'])) {
					$title = str_replace('_', ' ', $params['title']);
				}else{
					throw new IllegalArguementException('Could not load the article as a title was not supplied!');
				}
				
				$this->BO = new article_object();
				$this->BO->loadByAttribute('title', $title);
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