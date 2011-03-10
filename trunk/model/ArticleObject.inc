<?php

require_once $config->get('sysRoot').'alpha/model/AlphaDAO.inc';
require_once $config->get('sysRoot').'alpha/model/ArticleVoteObject.inc';
require_once $config->get('sysRoot').'alpha/model/ArticleCommentObject.inc';
require_once $config->get('sysRoot').'alpha/model/TagObject.inc';
require_once $config->get('sysRoot').'alpha/controller/front/FrontController.inc';

/**
 *
 * An article class for the CMS
 * 
 * @package alpha::model
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
class ArticleObject extends AlphaDAO {
	/**
	 * The article title
	 * 
	 * @var String
	 * @since 1.0
	 */
	protected $title;
	
	/**
	 * The article site section
	 * 
	 * @var DEnum
	 * @since 1.0
	 */
	protected $section;
	
	/**
	 * The description of the article
	 * 
	 * @var String
	 * @since 1.0
	 */
	protected $description;
	
	/**
	 * Optional custom body onload Javascript
	 * 
	 * @var String
	 * @since 1.0
	 */
	protected $bodyOnload;
	
	/**
	 * Any custom HTML header content (e.g. Javascript) for the article
	 * 
	 * @var Text
	 * @since 1.0
	 */
	protected $headerContent;
	
	/**
	 * The article content
	 * 
	 * @var Text
	 * @since 1.0
	 */
	protected $content;
	
	/**
	 * The author of the article
	 * 
	 * @var String
	 * @since 1.0
	 */
	protected $author;
	
	/**
	 * A boolean to control whether the artcile is publically accessible or not
	 * 
	 * @var Boolean
	 * @since 1.0
	 */
	protected $published;
	
	/**
	 * A Relation containing all of the comments on this article
	 *
	 * @var Relation
	 * @since 1.0
	 */
	protected $comments;
	
	/**
	 * A Relation containing all of the votes on this article
	 *
	 * @var Relation
	 * @since 1.0
	 */
	protected $votes;
	
	/**
	 * A Relation containing all of the tags on this article
	 *
	 * @var Relation
	 * @since 1.0
	 */
	protected $tags;
	
	/**
	 * An array of all of the attributes on this BO which are tagged
	 *  
	 * @var array
	 * @since 1.0
	 */
	protected $taggedAttributes = array('title', 'description', 'content');
	
	/**
	 * Path to a .text file where the content of this article is stored (optional)
	 * 
	 * @var string
	 * @since 1.0
	 */
	private $filePath;
	
	/**
	 * An array of data display labels for the class properties
	 * 
	 * @var array
	 * @since 1.0
	 */
	protected $dataLabels = array("OID"=>"Article ID#","title"=>"Title","section"=>"Site Section","description"=>"Description","bodyOnload"=>"Body onload Javascript","content"=>"Content","headerContent"=>"HTML Header Content","author"=>"Author","created_ts"=>"Date Added","updated_ts"=>"Date of last Update","published"=>"Published","URL"=>"URL","printURL"=>"Printer version URL","comments"=>"Comments","votes"=>"Votes","tags"=>"Tags");
	
	/**
	 * The name of the database table for the class
	 * 
	 * @var string
	 * @since 1.0
	 */
	const TABLE_NAME = 'Article';
	
	/**
	 * The URL for this article (transient)
	 * 
	 * @var string
	 * @since 1.0
	 */
	protected $URL;
	
	/**
	 * The print URL for this article (transient)
	 * 
	 * @var string
	 * @since 1.0
	 */
	protected $printURL;
	
	/**
	 * Trace logger
	 * 
	 * @var Logger
	 * @since 1.0
	 */
	private static $logger = null;
	
	/**
	 * The constructor which sets up some housekeeping attributes
	 * 
	 * @since 1.0
	 */
	public function __construct() {
		self::$logger = new Logger('ArticleObject');
		
		// ensure to call the parent constructor
		parent::__construct();
		
		$this->title = new String();
		$this->title->setHelper('Please provide a title for the article.');
		$this->title->setSize(100);
		$this->title->setRule("/\w+/");
		
		$this->section = new DEnum('ArticleObject::section');		
		
		$this->description = new String();
		$this->description->setHelper('Please provide a brief description of the article.');
		$this->description->setSize(200);
		$this->description->setRule("/\w+/");
		$this->bodyOnload = new String();		
		$this->content = new Text();
		$this->content->setHelper('Please provide some content for the article.');
		$this->content->setRule("/\w+/");
		$this->headerContent = new Text();		
		$this->author = new String();
		$this->author->setHelper('Please state the name of the author of this article');
		$this->author->setSize(70);
		$this->author->setRule("/\w+/");
		$this->published = new Boolean(0);
		
		$this->comments = new Relation();
		$this->markTransient('comments');
		$this->comments->setRelatedClass('ArticleCommentObject');
		$this->comments->setRelatedClassField('articleOID');
		$this->comments->setRelatedClassDisplayField('content');
		$this->comments->setRelationType('ONE-TO-MANY');
		
		$this->votes = new Relation();
		$this->markTransient('votes');
		$this->votes->setRelatedClass('ArticleVoteObject');
		$this->votes->setRelatedClassField('articleOID');
		$this->votes->setRelatedClassDisplayField('score');
		$this->votes->setRelationType('ONE-TO-MANY');
		
		$this->tags = new Relation();		
		$this->markTransient('tags');
		$this->tags->setRelatedClass('TagObject');
		$this->tags->setRelatedClassField('taggedOID');
		$this->tags->setRelatedClassDisplayField('content');
		$this->tags->setRelationType('ONE-TO-MANY');
		$this->tags->setTaggedClass(get_class($this));
		
		$this->URL = '';
		$this->printURL = '';
		// mark the URL attributes as transient
		$this->markTransient('URL');
		$this->markTransient('printURL');
		
		// mark title as unique
		$this->markUnique('title');
		
		$this->markTransient('filePath');
		$this->markTransient('taggedAttributes');
	}
	
	/**
	 * After creating a new ArticleObject, tokenize the description field to form a set
	 * of automated tags and save them.
	 * 
	 * @since 1.0
	 */
	protected function after_save_callback() {
		if($this->getVersion() == 1 && $this->tags instanceof Relation) {
			foreach($this->taggedAttributes as $tagged) {
				$tags = TagObject::tokenize($this->get($tagged), get_class($this), $this->getOID());
				foreach($tags as $tag) {
					try {
						$tag->save();
					}catch(ValidationException $e){
						/*
						 * The unique key has most-likely been violated because this BO is already tagged with this
						 * value, so we can ignore in this case.
						 */
					}
				}
			}
		}
	}
	
	/**
	 * Set up the transient URL attributes for the artcile after it has loaded
	 * 
	 * @since 1.0
	 */
	protected function after_loadByAttribute_callback() {
		$this->after_load_callback();
	}
	
	/**
	 * Set up the transient URL attributes for the article after it has loaded
	 * 
	 * @since 1.0
	 */
	protected function after_load_callback() {
		global $config;
		global $front;
		
		// check the config to see if we are using mod_rewrite
		if($config->get('sysUseModRewrite')) {
			// check to see if an alias is registered for the view_article_title controller, otherwise use the long URL version
			if(isset($front) && $front->hasAlias('ViewArticleTitle')) {
				$alias = $front->getControllerAlias('ViewArticleTitle');
				
				$this->URL = $config->get('sysURL').$alias.'/'.str_replace(' ', '_', $this->title->getValue());
			}else{
				$this->URL = $config->get('sysURL').'ViewArticleTitle/title/'.str_replace(' ', '_', $this->title->getValue());
			}
		}else{
			$this->URL = $config->get('sysURL').'alpha/controller/ViewArticle.php?oid='.$this->getID();
		}
		
		// now set up the print version URL
		if($config->get('sysUseModRewrite')) {
			// check to see if an alias is registered for the view_article_title controller, otherwise use the long URL version
			if(isset($front) && $front->hasAlias('ViewArticlePrint')) {
				$alias = $front->getControllerAlias('ViewArticlePrint');
				
				$this->printURL = $config->get('sysURL').$alias.'/'.str_replace(' ', '_', $this->title->getValue());
			}else{
				$this->printURL = $config->get('sysURL').'ViewArticlePrint/title/'.str_replace(' ', '_', $this->title->getValue());
			}
		}else{
			$this->printURL = $config->get('sysURL').'alpha/controller/ViewArticlePrint.php?title='.$this->title->getValue();
		}
	}
	
	/**
	 * Gets an array of the OIDs of the most recent articles added to the system (by date), from the newest 
	 * article to the amount specified by the $limit
	 * 
	 * @return array
	 * @since 1.0
	 * @throws AlphaException
	 */
	public function loadRecentWithLimit($limit){
		$denum = new DEnum('ArticleObject::section');
		$excludeID = $denum->getOptionID('Main');
		
		$sqlQuery = "SELECT OID FROM ".self::TABLE_NAME." WHERE published='1' AND section!='$excludeID' ORDER BY dateAdded DESC LIMIT 0, $limit;";		

		$this->lastQuery = $sqlQuery;
		
		if(!$result = AlphaDAO::getConnection()->query($sqlQuery)) {
			throw new AlphaException('Failed to load the OIDs for the most recent articles, MySql error is ['.AlphaDAO::getConnection()->error.
				'], query ['.$sqlQuery.']');
			return array();
		}
		
		$OIDs = array();
		
		$i = 0;
		while($row = $result->fetch_array(MYSQLI_ASSOC)) {
			$OIDs[$i] = $row['OID'];
			$i++;
		}
		
		return $OIDs;
	}
	
	/**
	 * Generates the location of the attachments folder for this article
	 * 
	 * @return string
	 * @since 1.0
	 */
	public function getAttachmentsLocation() {
		global $config;
		
		return $config->get('sysRoot').'attachments/article_'.$this->getID();
	}
	
	/**
	 * Generates the URL of the attachments folder for this article
	 * 
	 * @return string
	 * @since 1.0
	 */
	public function getAttachmentsURL() {
		global $config;
		
		return $config->get('sysURL').'attachments/article_'.$this->getID();
	}
	
	/**
	 * Generates a secure URL for downloading an attachment file via the ViewAttachment controller
	 * 
	 * @param string $filename
	 * @since 1.0
	 */
	public function getAttachmentSecureURL($filename) {
		global $config;
		
		return FrontController::generateSecureURL('act=ViewAttachment&dir='.$this->getAttachmentsLocation().'&filename='.$filename);
	}
	
	/**
	 * Creates the attachment folder for the article on the server.
	 * 
	 * @since 1.0
	 * @throws AlphaException
	 */
	public function createAttachmentsFolder() {
		// create the attachment directory for the article
		try{
			mkdir($this->getAttachmentsLocation());
		}catch (Exception $e) {
			throw new AlphaException('Unable to create the folder ['.$this->getAttachmentsLocation().'] for the article.');
		}
			
		// ...and set write permissions on the folder
		try{
			chmod($this->getAttachmentsLocation(), 0777);
		}catch (Exception $e) {
			throw new AlphaException('Unable to set write permissions on the folder ['.$this->getAttachmentsLocation().'].');
		}
	}
	
	/**
	 * Method for returning the calculated score for this article
	 * 
	 * @return double
	 * @since 1.0
	 */
	public function getArticleScore() {
		$votes = $this->getArticleVotes();
		
		$score = 0;
		$total_score = 0;
		$vote_count = count($votes);
		
		for($i = 0; $i < $vote_count; $i++){
			$total_score += $votes[$i]->get('score');
		}
		
		if($vote_count > 0)
		$score = $total_score/$vote_count;
		
		return sprintf("%01.2f", $score);
	}
	
	/**
	 * Method for fetching all of the votes for this article
	 * 
	 * @return array An array of ArticleVoteObject objects
	 * @since 1.0
	 */
	public function getArticleVotes() {
		$votes = $this->votes->getRelatedObjects();
		
		return $votes;
	}
	
	/**
	 * Method to determine if the logged-in user has already voted for this article
	 * 
	 * @return boolean True if they have voted already, false otherwise
	 * @since 1.0
	 * @throws AlphaException
	 */
	public function checkUserVoted() {
		// just going to return true if nobody is logged in
		if (!isset($_SESSION['currentUser']))
			return true;
		
		$userID = $_SESSION['currentUser']->getID();
		
		$sqlQuery = "SELECT COUNT(*) AS usersVote FROM ".ArticleVoteObject::TABLE_NAME." WHERE articleOID='".$this->OID."' AND personOID='".$userID."';";		

		$this->lastQuery = $sqlQuery;
		
		if(!$result = AlphaDAO::getConnection()->query($sqlQuery)) {
			throw new AlphaException('Failed to check if the current user voted for the article ['.$this->OID.'], MySql error is ['.AlphaDAO::getConnection()->error.'], query ['.$sqlQuery.']');
			return false;
		}
		
		$row = $result->fetch_array(MYSQLI_ASSOC);
		
		if($row['usersVote'] == "0")
			return false;
		else
			return true;			
	}
	
	/**
	 * Method for fetching all of the comments for this article
	 * 
	 * @return array An array of ArticleCommentObject objects
	 * @since 1.0
	 */
	public function getArticleComments() {
		$comments = $this->comments->getRelatedObjects();		
		
		return $comments;
	}
	
	/**
	 * Loads the content of the ArticleObject from the specified file path
	 * 
	 * @param $filePath
	 * @since 1.0
	 * @throws FileNotFoundException
	 */
	public function loadContentFromFile($filePath) {
		try{
			$this->content->setValue(file_get_contents($filePath));
			$this->filePath = $filePath;
		}catch (Exception $e) {
			throw new FileNotFoundException($e->getMessage());
		}
	}
	
	/**
	 * Returns true if the article content was loaded from a .text file, false otherwise.
	 * 
	 * @return boolean
	 * @since 1.0
	 */
	public function isLoadedFromFile() {
		return ($this->filePath == '' ? false: true);
	}
	
	/**
	 * Returns the timestamp of when the content .text file for this article was last
	 * modified.
	 * 
	 * @return string
	 * @since 1.0
	 * @throws FileNotFoundException
	 */
	public function getContentFileDate() {
		if($this->filePath != '') {
			try{				
				return date("Y-m-d H:i:s", filemtime($this->filePath));
			}catch (Exception $e) {
				throw new FileNotFoundException($e->getMessage());
			}
		}else{
			throw new FileNotFoundException('Error trying to access an article content file when none is set!');
		}
	}
}

?>