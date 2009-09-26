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
 * @author John Collins <john@design-ireland.net>
 * @package alpha::controller
 * @copyright 2009 John Collins
 * @version $Id$
 *
 */
class ViewArticlePrint extends ViewArticle {								
	/**
	 * Handle GET requests
	 * 
	 * @param array $params
	 */
	public function doGET($params) {
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
			exit;
		}catch(BONotFoundException $e) {
			self::$logger->warn($e->getMessage());
			echo '<p class="error"><br>Failed to load the requested article from the database!</p>';
		}
		
		$this->setTitle($this->BO->get('title'));
		
		$BOView = View::getInstance($this->BO);
		
		echo $BOView->displayArticlePageHead($this, 'print');
		
		echo $BOView->markdownView();
		
		echo View::displayPageFoot($this);
	}
	
	/**
	 * Callback used to render footer content, including comments, votes and print/PDF buttons when
	 * enabled to do so.
	 * 
	 * @return string
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