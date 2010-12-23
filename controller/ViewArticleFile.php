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
 * @author John Collins <john@design-ireland.net>
 * @package alpha::controller
 * @copyright 2009 John Collins
 * @version $Id$
 *
 */
class ViewArticleFile extends ViewArticle {
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
		if(self::$logger == null)
			self::$logger = new Logger('ViewArticleFile');
			
		global $config;
		
		try {
			// ensure that a title is provided
			if (isset($params['file'])) {
				$title = basename($params['file']);
				$title = str_replace('_', ' ', $title);
				$title = str_replace('.text', '', $title);
			}else{
				throw new IllegalArguementException('Could not load the article as a file name was not supplied!');
			}
			
			$this->BO = new article_object();
			$this->BO->set('title', $title);
			// just checking to see if the file path is absolute or not
			if(substr($params['file'], 0, 1) == '/')
				$this->BO->loadContentFromFile($params['file']);
			else
				$this->BO->loadContentFromFile($config->get('sysRoot').'/alpha/docs/'.$params['file']);
			
		}catch(IllegalArguementException $e) {
			self::$logger->error($e->getMessage());
			exit;
		}catch(FileNotFoundException $e) {
			self::$logger->warn($e->getMessage());
			echo '<p class="error"><br>Failed to load the requested article from the file system!</p>';
		}

		$this->setTitle($this->BO->get('title'));
		
		$BOView = AlphaView::getInstance($this->BO);
		
		echo AlphaView::displayPageHead($this, false);
		
		echo $BOView->markdownView();
		
		echo AlphaView::displayPageFoot($this);
	}

	public function before_displayPageFoot_callback() {
		
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