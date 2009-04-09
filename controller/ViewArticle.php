<?php

// include the config file
if(!isset($config))
	require_once '../util/configLoader.inc';
$config =&configLoader::getInstance();

require_once $config->get('sysRoot').'alpha/view/View.inc';
require_once $config->get('sysRoot').'alpha/util/db_connect.inc';
require_once $config->get('sysRoot').'alpha/controller/Controller.inc';
require_once $config->get('sysRoot').'alpha/model/article_object.inc';
require_once $config->get('sysRoot').'alpha/util/input_filter.inc';
require_once $config->get('sysRoot').'alpha/controller/AlphaControllerInterface.inc';

/**
 * 
 * Controller used to display a Markdown version of an article
 * 
 * @package alpha::controller
 * @author John Collins <john@design-ireland.net>
 * @copyright 2009 John Collins
 * @version $Id$
 *
 */
class ViewArticle extends Controller implements AlphaControllerInterface {
	/**
	 * The article to be rendered
	 * 
	 * @var article_object
	 */
	protected $BO;
	
	/**
	 * Used to set status update messages to display to the user
	 *
	 * @var string
	 */
	private $statusMessage = '';
	
	/**
	 * Trace logger
	 * 
	 * @var Logger
	 */
	private static $logger = null;
	
	/**
	 * constructor to set up the object
	 */
	public function __construct() {
		if(self::$logger == null)
			self::$logger = new Logger('ViewArticle');
		self::$logger->debug('>>__construct()');
		
		global $config;
		
		// ensure that the super class constructor is called, indicating the rights group
		parent::__construct('Public');
		
		$this->BO = new article_object();
		
		self::$logger->debug('<<__construct');
	}
								
	/**
	 * Handle GET requests
	 * 
	 * @param array $params
	 */
	public function doGET($params) {
		global $config;
		
		try{
			// check to see if we need to force a re-direct to the mod_rewrite alias URL for the article
			if($config->get('sysForceModRewriteURLs') && (basename($_SERVER['PHP_SELF']) == 'ViewArticle.php' || basename($_SERVER['PHP_SELF']) == 'FC.php')) {
				// set the correct HTTP header for the response
	    		header('HTTP/1.1 301 Moved Permanently');
	    		
	    		header('Location: '.$this->BO->get('URL'));
	 
			    // we're done here
	    		exit;
			}
			
			// load the business object (BO) definition
			if (isset($params['oid'])) {
				$this->BO->load($params['oid']);
				
				$BOView = View::getInstance($this->BO);
				
				// set up the title and meta details
				$this->setTitle($this->BO->get('title'));
				$this->setDescription($this->BO->get('description'));
				$this->setKeywords($this->BO->get('keywords'));
				
				echo $BOView->displayArticlePageHead($this);
		
				echo $BOView->markdownView();
			}else{
				throw new IllegalArguementException('No article available to view!');
			}
		}catch(IllegalArguementException $e) {
			self::$logger->error($e->getMessage());
		}catch(BONotFoundException $e) {
			self::$logger->warn($e->getMessage());
			echo '<p class="error"><br>Failed to load the requested article from the database!</p>';
		}
		
		echo View::displayPageFoot($this);
	}
	
	/**
	 * Callback used to inject article_object header_content into the page
	 *
	 * @return string
	 */
	public function during_displayPageHead_callback() {
		return $this->BO->get('headerContent');
	}
	
	/**
	 * Callback used to render footer content, including comments, votes and print/PDF buttons when
	 * enabled to do so.
	 * 
	 * @return string
	 */
	public function before_displayPageFoot_callback() {
		global $config;
		
		$html = $this->renderComments();
		
		$rating = $this->BO->getArticleScore();
		$votes = $this->BO->getArticleVotes();
		
		if($config->get('sysCMSDisplayVotes'))
			$html .= '<p>Average Article User Rating: <strong>'.$rating.'</strong> out of 10 (based on <strong>'.count($votes).'</strong> votes)</p>';
		
		if(!$this->BO->checkUserVoted() && $config->get('sysCMSVotingAllowed')) {
			$html .= '<form action="'.$_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'].'" method="post">';
			$html .= '<p>Please rate this article from 1-10 (10 being the best):' .
					'<select name="user_vote">' .
					'<option value="1">1' .
					'<option value="2">2' .
					'<option value="3">3' .
					'<option value="4">4' .
					'<option value="5">5' .
					'<option value="6">6' .
					'<option value="7">7' .
					'<option value="8">8' .
					'<option value="9">9' .
					'<option value="10">10' .
					'</select></p>&nbsp;&nbsp;';
			$temp = new button('submit','Vote!','voteBut');
			$html .= $temp->render();
			
			$html .= View::renderSecurityFields();
			$html .= '<form>';
		}
		
		$html .= '&nbsp;&nbsp;';
		$temp = new button("window.open('".$this->BO->get('printURL')."')",'Open Printer Version','printBut');
		$html .= $temp->render();
		
		$html .= '&nbsp;&nbsp;';
		if($config->get('sysAllowPDFVersions')) {
			$temp = new button("document.location = '".$config->get('sysURL')."alpha/controller/ViewArticlePDF.php?title=".$this->BO->get("title")."';",'Open PDF Version','pdfBut');
			$html .= $temp->render();
		}
		
		// render edit button for admins only
		if (isset($_SESSION['currentUser']) && $_SESSION['currentUser']->inGroup('Admin')) {
			$html .= '&nbsp;&nbsp;';
			$button = new button("document.location = '".FrontController::generateSecureURL('act=Edit&bo='.get_class($this->BO).'&oid='.$this->BO->getID())."'",'Edit','editBut');
			$html .= $button->render();
		}
		
		if($config->get('sysCMSDisplayStandardFooter')) {
			$html .= '<p>Article URL: <a href="'.$this->BO->get('URL').'">'.$this->BO->get('URL').'</a><br>';
			$html .= 'Title: '.$this->BO->get('title').'<br>';
			$html .= 'Author: '.$this->BO->get('author').'<br>';
		}
		$html .= $config->get('sysCMSFooter').'</p>';
		
		return $html;
	}
	
	/**
	 * Method to handle POST requests
	 * 
	 * @param array $params
	 */
	public function doPOST($params) {
		global $config;
		
		try {
			// check the hidden security fields before accepting the form POST data
			if(!$this->checkSecurityFields()) {
				throw new SecurityException('This page cannot accept post data from remote servers!');
			}
			
			if(isset($params['voteBut']) && !$this->BO->check_user_voted()) {
				$vote = new article_vote_object();
				$vote->set('article_oid', $params['oid']);
				$vote->set('person_oid', $_SESSION['currentUser']->getID());
				$vote->set('score', $params['user_vote']);
				try {
					$vote->save();
					$this->statusMessage = '<p class="success">Thank you for rating this article!</p>';
					$this->doGET($params);
				}catch (FailedSaveException $e) {
					self::$logger->error($e->getMessage());
				}
			}
			
			if(isset($params['createBut'])) {
				$comment = new article_comment_object();
				
				// populate the transient object from post data
				$comment->populateFromPost();
				
				// filter the comment before saving
				$filter = new input_filter($comment->getPropObject('content'));
				$comment->set('content', $filter->encode());
				
				try {
					$success = $comment->save();			
					$this->statusMessage = '<p class="success">Thank you for your comment!</p>';
					$this->doGET($params);
				}catch (FailedSaveException $e) {
					self::$logger->error($e->getMessage());
				}				
			}
			
			if(isset($params['saveBut'])) {			
				$comment = new article_comment_object();
				
				try {
					$comment->load($params['article_comment_id']);
					
					// re-populates the old object from post data
					$comment->populateFromPost();			
					
					// filter the comment before saving
					$filter = new input_filter($comment->getPropObject('content'));
					$comment->set('content', $filter->encode());
					
					$success = $comment->save();			
					$this->statusMessage = '<p class="success">Your comment has been updated.</p>';
					$this->doGET($params);
				}catch (AlphaException $e) {
					self::$logger->error($e->getMessage());
				}
			}
		}catch(SecurityException $e) {
			echo '<p class="error"><br>'.$e->getMessage().'</p>';								
			self::$logger->warn($e->getMessage());
		}
	}
	
	/**
	 * Method for displaying the user comments for the article.
	 * 
	 * @todo remove output buffering around old article comment view objects
	 * @return string
	 */
	private function renderComments() {
		global $config;
		
		$html = '';
		
		$comments = $this->BO->getArticleComments();
		$comment_count = count($comments);
		
		if($config->get('sysCMSDisplayComments') && $comment_count > 0) {
			$html .= '<h2>There are ['.$comment_count.'] user comments for this article</h2>';
			
			ob_start();
			for($i = 0; $i < $comment_count; $i++) {
				$view = View::getInstance($comments[$i]);
				$view->markdownView();
			}
			$html.= ob_get_clean();
		}
		
		if(isset($_SESSION['currentUser']) && $config->get('sysCMSCommentsAllowed')) {
			$comment = new article_comment_object();
			$comment->set('article_oid', $this->BO->getID());
			
			ob_start();
			$view = View::getInstance($comment);
			$view->createView();
			$html.= ob_get_clean();
		}
		
		return $html;
	}
	
	/**
	 * Used to set any status messages we want to render for the user
	 * 
	 * @param $message
	 */
	public function setStatus($message) {
		$this->statusMessage = $message;
	}
	
	/**
	 * Gets the status message
	 * 
	 * @return string
	 */
	public function getStatus() {
		return $this->statusMessage;
	}
}

// now build the new controller
if(basename($_SERVER['PHP_SELF']) == 'ViewArticle.php') {
	$controller = new ViewArticle();
	
	if(!empty($_POST)) {			
		$controller->doPOST($_REQUEST);
	}else{
		$controller->doGET($_GET);
	}
}

?>