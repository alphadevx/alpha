<?php

// include the config file
if(!isset($config)) {
	require_once '../util/AlphaConfig.inc';
	$config = AlphaConfig::getInstance();
}

require_once $config->get('sysRoot').'alpha/view/AlphaView.inc';
require_once $config->get('sysRoot').'alpha/controller/AlphaController.inc';
require_once $config->get('sysRoot').'alpha/model/ArticleObject.inc';
require_once $config->get('sysRoot').'alpha/util/InputFilter.inc';
require_once $config->get('sysRoot').'alpha/util/helpers/AlphaValidator.inc';
require_once $config->get('sysRoot').'alpha/controller/AlphaControllerInterface.inc';

/**
 * 
 * Controller used to display a Markdown version of an article
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
class ViewArticle extends AlphaController implements AlphaControllerInterface {
	/**
	 * The article to be rendered
	 * 
	 * @var ArticleObject
	 * @since 1.0
	 */
	protected $BO;
	
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
		self::$logger = new Logger('ViewArticle');
		self::$logger->debug('>>__construct()');
		
		global $config;
		
		// ensure that the super class constructor is called, indicating the rights group
		parent::__construct('Public');
		
		$this->BO = new ArticleObject();
		
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
		global $config;
		
		try{
			// check to see if we need to force a re-direct to the mod_rewrite alias URL for the article
			if($config->get('sysForceModRewriteURLs') && basename($_SERVER['PHP_SELF']) == 'ViewArticle.php') {
				// set the correct HTTP header for the response
	    		header('HTTP/1.1 301 Moved Permanently');
	    		
	    		header('Location: '.$this->BO->get('URL'));
	 
			    // we're done here
	    		exit;
			}
			
			// load the business object (BO) definition
			if (isset($params['oid']) && AlphaValidator::isInteger($params['oid'])) {
				$this->BO->load($params['oid']);
				
				$BOView = AlphaView::getInstance($this->BO);
				
				// set up the title and meta details
				$this->setTitle($this->BO->get('title'));
				$this->setDescription($this->BO->get('description'));
				
				echo AlphaView::displayPageHead($this);
		
				echo $BOView->markdownView();
			}else{
				throw new IllegalArguementException('No article available to view!');
			}
		}catch(IllegalArguementException $e) {
			self::$logger->error($e->getMessage());
			throw new ResourceNotFoundException($e->getMessage());
		}catch(BONotFoundException $e) {
			self::$logger->warn($e->getMessage());
			throw new ResourceNotFoundException('The article that you have requested cannot be found!');
		}
		
		echo AlphaView::displayPageFoot($this);
	}
	
	/**
	 * Callback used to inject ArticleObject headerContent into the page
	 *
	 * @return string
	 * @since 1.0
	 */
	public function during_displayPageHead_callback() {
		return $this->BO->get('headerContent');
	}
	
	/**
	 * Callback that inserts the CMS level header
	 * 
	 * @return string
	 * @since 1.0
	 */
	public function insert_CMSDisplayStandardHeader_callback() {
		global $config;
		
		$html = '';
		
		if($config->get('sysCMSDisplayStandardHeader')) {
			$html.= '<p><a href="'.$config->get('sysURL').'">'.$config->get('sysTitle').'</a> &nbsp; &nbsp;';
			$denum = $this->BO->getPropObject('section');
			if(count($denum->getOptions()) > 1)
				$html.= 'Site Section: <em>'.$denum->getDisplayValue().'</em> &nbsp; &nbsp;';
			$html.= 'Date Added: <em>'.$this->BO->getCreateTS()->getDate().'</em> &nbsp; &nbsp;';
			$html.= 'Last Updated: <em>'.$this->BO->getUpdateTS()->getDate().'</em> &nbsp; &nbsp;';
			$html.= 'Revision: <em>'.$this->BO->getVersion().'</em></p>';
		}
		
		$html.= $config->get('sysCMSHeader');
		
		return $html;
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
		
		$html = $this->renderComments();
		
		$rating = $this->BO->getArticleScore();
		
		if($config->get('sysCMSDisplayTags')) {
			$tags = $this->BO->getPropObject('tags')->getRelatedObjects();
			
			if(count($tags) > 0) {
				$html .= '<p>Tags:';
				
				foreach($tags as $tag)
					$html .= ' <a href="'.$config->get('sysURL').'search/q/'.$tag->get('content').'">'.$tag->get('content').'</a>';
				$html .= '</p>';
			}
		}
		
		if($config->get('sysCMSDisplayVotes')) {
			$votes = $this->BO->getArticleVotes();
			$html .= '<p>Average Article User Rating: <strong>'.$rating.'</strong> out of 10 (based on <strong>'.count($votes).'</strong> votes)</p>';
		}
		
		if(!$this->BO->checkUserVoted() && $config->get('sysCMSVotingAllowed')) {
			$html .= '<form action="'.$_SERVER['REQUEST_URI'].'" method="post">';
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
			$temp = new Button('submit','Vote!','voteBut');
			$html .= $temp->render();
			
			$html .= AlphaView::renderSecurityFields();
			$html .= '<form>';
		}
		
		AlphaDAO::disconnect();
		
		$html .= '&nbsp;&nbsp;';
		$temp = new Button("window.open('".$this->BO->get('printURL')."')",'Open Printer Version','printBut');
		$html .= $temp->render();
		
		$html .= '&nbsp;&nbsp;';
		if($config->get('sysAllowPDFVersions')) {
			$temp = new Button("document.location = '".FrontController::generateSecureURL("act=ViewArticlePDF&title=".$this->BO->get("title"))."';",'Open PDF Version','pdfBut');
			$html .= $temp->render();
		}
		
		// render edit button for admins only
		if (isset($_SESSION['currentUser']) && $_SESSION['currentUser']->inGroup('Admin')) {
			$html .= '&nbsp;&nbsp;';
			$button = new Button("document.location = '".FrontController::generateSecureURL('act=Edit&bo='.get_class($this->BO).'&oid='.$this->BO->getID())."'",'Edit','editBut');
			$html .= $button->render();
		}
		
		if($config->get('sysCMSDisplayStandardFooter')) {
			$html .= '<p>Article URL: <a href="'.$this->BO->get('URL').'">'.$this->BO->get('URL').'</a><br>';
			$html .= 'Title: '.$this->BO->get('title').'<br>';
			$html .= 'Author: '.$this->BO->get('author').'</p>';
		}
		
		$html .= $config->get('sysCMSFooter');
		
		return $html;
	}
	
	/**
	 * Method to handle POST requests
	 * 
	 * @param array $params
	 * @since 1.0
	 */
	public function doPOST($params) {
		global $config;
		
		try {
			// check the hidden security fields before accepting the form POST data
			if(!$this->checkSecurityFields())
				throw new SecurityException('This page cannot accept post data from remote servers!');

			
			if(isset($params['voteBut']) && !$this->BO->checkUserVoted()) {
				$vote = new ArticleVoteObject();
				
				if(isset($params['oid'])) {
					$vote->set('articleOID', $params['oid']);
				}else{
					// load article by title?					
					if (isset($params['title'])) {
						$title = str_replace('_', ' ', $params['title']);
					}else{
						throw new IllegalArguementException('Could not load the article as a title or OID was not supplied!');
					}
					
					$this->BO = new ArticleObject();
					$this->BO->loadByAttribute('title', $title);
					$vote->set('articleOID', $this->BO->getOID());
				}
				
				$vote->set('personOID', $_SESSION['currentUser']->getID());
				$vote->set('score', $params['user_vote']);
				
				try {
					$vote->save();

					AlphaDAO::disconnect();
					
					$this->setStatusMessage(AlphaView::displayUpdateMessage('Thank you for rating this article!'));
					
					$this->doGET($params);
				}catch (FailedSaveException $e) {
					self::$logger->error($e->getMessage());
				}
			}
			
			if(isset($params['createBut'])) {
				$comment = new ArticleCommentObject();
				
				// populate the transient object from post data
				$comment->populateFromPost();
				
				// filter the comment before saving				
				$comment->set('content', InputFilter::encode($comment->get('content')));
				
				try {
					$success = $comment->save();
					
					AlphaDAO::disconnect();
					
					$this->setStatusMessage(AlphaView::displayUpdateMessage('Thank you for your comment!'));
					
					$this->doGET($params);
				}catch (FailedSaveException $e) {
					self::$logger->error($e->getMessage());
				}				
			}
			
			if(isset($params['saveBut'])) {			
				$comment = new ArticleCommentObject();
				
				try {
					$comment->load($params['article_comment_id']);
					
					// re-populates the old object from post data
					$comment->populateFromPost();			
					
					$success = $comment->save();
					
					AlphaDAO::disconnect();

					$this->setStatusMessage(AlphaView::displayUpdateMessage('Your comment has been updated.'));
					
					$this->doGET($params);
				}catch (AlphaException $e) {
					self::$logger->error($e->getMessage());
				}
			}
		}catch(SecurityException $e) {
			self::$logger->warn($e->getMessage());
			throw new ResourceNotAllowedException($e->getMessage());
		}
	}
	
	/**
	 * Method for displaying the user comments for the article.
	 * 
	 * @return string
	 * @since 1.0
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
				$view = AlphaView::getInstance($comments[$i]);
				$view->markdownView();
			}
			$html.= ob_get_clean();
		}
		
		if(isset($_SESSION['currentUser']) && $config->get('sysCMSCommentsAllowed')) {
			$comment = new ArticleCommentObject();
			$comment->set('articleOID', $this->BO->getID());
			
			ob_start();
			$view = AlphaView::getInstance($comment);
			$view->createView();
			$html.= ob_get_clean();
		}
		
		return $html;
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