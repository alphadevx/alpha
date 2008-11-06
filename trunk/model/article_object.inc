<?php

// $Id$

require_once $config->get('sysRoot').'alpha/model/mysqlDAO.inc';
require_once $config->get('sysRoot').'alpha/util/handle_error.inc';
require_once $config->get('sysRoot').'alpha/model/article_vote_object.inc';
require_once $config->get('sysRoot').'alpha/model/article_comment_object.inc';

/**
 *
 * an article class for publishing online
 * 
 * @package Design-Ireland
 * @author John Collins <john@design-ireland.net>
 * @copyright 2006 John Collins
 * 
 * 
 */ 
class article_object extends mysqlDAO
{
	/**
	 * the article title
	 * @var String
	 */
	var $title;
	
	/**
	 * the article site section
	 * @var DEnum
	 */
	var $section;
	
	/**
	 * the keywords for the article
	 * @var String
	 */
	var $keywords;
	
	/**
	 * the description of the article
	 * @var String
	 */
	var $description;
	
	/**
	 * optional custom body onload Javascript
	 * @var String
	 */
	var $body_onload;
	
	/**
	 * any custom HTML header content (e.g. Javascript) for the article
	 * @var Text;
	 */
	var $header_content;
	
	/**
	 * the article content
	 * @var Text;
	 */
	var $content;
	
	/**
	 * the author of the article
	 * @var String
	 */
	var $author;
	
	/**
	 * the date the article was added
	 * @var Date
	 */
	var $date_added;
	
	/**
	 * the date the article was updated
	 * @var Date
	 */
	var $date_updated;
	
	/**
	 * a boolean to control whether the artcile is publically accessible or not
	 * @var Boolean
	 */
	var $published;
	
	/**
	 * an array of data display labels for the class properties
	 * @var array
	 */
	var $dataLabels = array(
		"OID"=>"Article ID#",		
		"title"=>"Title",
		"section"=>"Site Section",
		"keywords"=>"Keywords",		
		"description"=>"Description",
		"body_onload"=>"Body onload Javascript",
		"content"=>"Content",
		"header_content"=>"HTML Header Content",
		"author"=>"Author",
		"date_added"=>"Date Added",
		"date_updated"=>"Date of last Update",
		"published"=>"Published",
		"URL"=>"URL",
		"printURL"=>"Printer version URL"
	);
	
	/**
	 * the name of the database table for the class
	 * @var string
	 */
	const TABLE_NAME = 'article';
	
	/**
	 * the URL for this article (transient)
	 * @var string
	 */
	var $URL;
	
	/**
	 * the print URL for this article (transient)
	 */
	var $printURL;
	
	/**
	 * constructor for the class
	 */
	function article_object() {
		// ensure to call the parent constructor
		parent::__construct();
		$this->title = new String();
		$this->title->setHelper("Please provide a title for the article.");
		$this->title->setSize(100);
		$this->title->setRule("/\w+/");
		
		$this->section = new DEnum('article_object::section');
		
		$this->keywords = new String();
		$this->keywords->setHelper("Please provide some coma-seperated search engine keywords for the article.");
		$this->keywords->setSize(150);
		$this->keywords->setRule("/\w+/");
		$this->description = new String();
		$this->description->setHelper("Please provide a brief description of the article.");
		$this->description->setSize(200);
		$this->description->setRule("/\w+/");
		$this->body_onload = new String();		
		$this->content = new Text();
		$this->content->setHelper("Please provide some content for the article.");
		$this->content->setRule("/\w+/");
		$this->header_content = new Text();		
		$this->author = new String();
		$this->author->setHelper("Please state the name of the author of this article");
		$this->author->setSize(70);
		$this->author->setRule("/\w+/");
		$this->date_added = new Date();
		$this->date_updated = new Date();
		$this->published = new Boolean(0);
		
		// mark the URL attributes as transient
		$this->markTransient('URL');
		$this->markTransient('printURL');
		
		// mark title as unique
		$this->markUnique('title');
	}
	
	/**
	 * set up the transient URL attributes for the artcile after it has loaded
	 */
	function after_load_callback() {
		global $config;
		global $front;
		
		// check the config to see if we are using mod_rewrite
		if($config->get("sysUseModRewrite")) {
			// check to see if an alias is registered for the view_article_title controller, otherwise use the long URL version
			if(isset($front) && $front->has_alias('view_article_title')) {
				$alias = $front->get_controller_alias('view_article_title');
				
				$this->URL = $config->get('sysURL').'/'.$alias.'/'.str_replace(' ', '_', $this->title->getValue());
			}else{
				$this->URL = $config->get('sysURL').'/view_article_title/title/'.str_replace(' ', '_', $this->title->getValue());
			}
		}else{
			$this->URL = $config->get('sysURL').'/alpha/controller/view_article.php?oid='.$this->getID();
		}
		
		// now set up the print version URL
		if($config->get("sysUseModRewrite")) {
			// check to see if an alias is registered for the view_article_title controller, otherwise use the long URL version
			if(isset($front) && $front->has_alias('view_article_print')) {
				$alias = $front->get_controller_alias('view_article_print');
				
				$this->printURL = $config->get('sysURL').'/'.$alias.'/'.str_replace(' ', '_', $this->title->getValue());
			}else{
				$this->printURL = $config->get('sysURL').'/view_article_print/title/'.str_replace(' ', '_', $this->title->getValue());
			}
		}else{
			$this->printURL = $config->get('sysURL').'/alpha/controller/view_article_print.php?title='.$this->title->getValue();
		}
	}
	
	/**
	 * gets the OIDs of the two most recent articles added to the system (by date)
	 * @return array an array of the two OIDs, or boolean false on failure
	 */
	function get_two_most_recent(){
		$denum = new DEnum('article_object::section');
		$excludeID = $denum->getOptionID('Main');
		
		$sql_query = "SELECT OID FROM ".self::TABLE_NAME." WHERE published='1' AND section!='$excludeID' ORDER BY date_added DESC LIMIT 0, 2;";		

		$this->last_query = $sql_query;

		$result = mysql_query($sql_query);
		
		if(mysql_error() != '') {
			$error = new handle_error($_SERVER["PHP_SELF"],'Failed to load the OIDs for the two most recent articles, MySql error is: '.mysql_error().', query: '.$sql_query ,'get_two_most_recent()');
			return false;
		}
		
		$OIDs = array();
		
		$i = 0;
		while ($row = mysql_fetch_assoc($result)) {
			$OIDs[$i] = $row["OID"];
			$i++;
		}
		
		return $OIDs;
	}
	
	/**
	 * gets the OIDs of the ten most recent articles added to the system (by date), for the category section provided
	 * @return array an array of the ten OIDs (or less than ten), or boolean false on failure
	 * @param string $section the article category section to fetch the ten articles from 
	 */
	function get_ten_most_recent($section){
		$sql_query = "SELECT OID FROM ".self::TABLE_NAME." WHERE published='1' AND section='".$section."' ORDER BY date_added DESC LIMIT 0, 10;";		

		$this->last_query = $sql_query;

		$result = mysql_query($sql_query);
		
		if(mysql_error() != '') {
			$error = new handle_error($_SERVER["PHP_SELF"],'Failed to load the OIDs for the ten most recent articles from the section '.$section.', MySql error is: '.mysql_error().', query: '.$sql_query ,'get_ten_most_recent()');
			return false;
		}
		
		$OIDs = array();
		
		$i = 0;
		while ($row = mysql_fetch_assoc($result)) {
			$OIDs[$i] = $row["OID"];
			$i++;
		}
		
		return $OIDs;
	}
	
	/**
	 * generates the location of the attachments folder for this article
	 * @return string returns the location of the attachments folder
	 */
	function get_attachments_location() {
		global $config;
		
		return $config->get('sysRoot')."attachments/article_".$this->get_Id();
	}
	
	/**
	 * generates the URL of the attachments folder for this article
	 * @return string returns the URL of the attachments folder
	 */
	function get_attachments_URL() {
		global $config;
		
		return $config->get('sysURL')."/attachments/article_".$this->getID();
	}
	
	/**
	 * creates the attachment folder for the article on the server
	 */
	function create_attachments_folder() {
		// create the attachment directory for the article
		$success = mkdir($this->get_attachments_location());
			
		if (!$success) {
			$error = new handle_error($_SERVER["PHP_SELF"],'Unable to create the folder '.$this->get_attachments_location().' for the article.','create_attachments_folder()','framework');
		}
			
		// ...and set write permissions on the folder
		$success = chmod($this->get_attachments_location(), 0777);
			
		if (!$success) {
			$error = new handle_error($_SERVER["PHP_SELF"],'Unable to set write permissions on the folder '.$this->get_attachments_location().'.','create_attachments_folder()','framework');
		}
	}
	
	/**
	 * populates an article_object from the database table by the article title
	 * @param string $title The title of the article to load
	 * @return bool True if no database error occured, else false and error is stored in $this->last_query
	 */
	function load_by_title($title)
	{
		if(method_exists($this, 'after_load_callback'))
				$this->after_load_callback();
		
		$sql_query = "SELECT * FROM ".self::TABLE_NAME." WHERE title = '$title';";

		$this->last_query = $sql_query;
		
		$result = mysql_query($sql_query);
		
		if(mysql_num_rows($result) == 0) {
			$error = new handle_error($_SERVER["PHP_SELF"],'Failed to load article '.$title.', not found in database.' ,'load_by_title($title)','framework');
			return false;
		}
		
		$row = mysql_fetch_assoc($result);
		
		$properties = get_class_vars(get_class($this));

		foreach(array_keys($properties) as $prop) {
			if (get_class($this->$prop) != false) {	
				if (strtoupper(get_class($this->$prop)) != 'DATE' && strtoupper(get_class($this->$prop)) != 'TIMESTAMP') {					
					$this->$prop->setValue(stripslashes($row[$prop]));
				}else{
					$this->$prop->populateFromString(stripslashes($row[$prop]));
				}
			}
			if($prop == "OID"){				
				$this->OID = $row[$prop];
			}	
			if($prop == "version_num"){				
				$this->version_num = $row[$prop];
			}
		}		
		
		if(mysql_error() == '' && $this->setEnumOptions()) {
			if(method_exists($this, 'after_load_callback'))
				$this->after_load_callback();
			return true;
		}else{			
			$this->last_query .= " Error: ".mysql_error();
			//$error = new handle_error($_SERVER["PHP_SELF"],'Failed to load article, MySql error is: '.mysql_error().', query: '.$this->last_query,'load_by_title($title)','framework');
			return false;
		}		
	}
	
	/**
	 * method for returning the calculated score for this article
	 * @return double the average score
	 */
	function get_score() {
		$votes = $this->get_votes();
		
		$score = 0;
		$total_score = 0;
		$vote_count = count($votes);
		
		for($i = 0; $i < $vote_count; $i++){
			$total_score += $votes[$i]->get("score");
		}
		
		if($vote_count > 0)
		$score = $total_score/$vote_count;
		
		return sprintf("%01.2f", $score);
	}
	
	/**
	 * method for fetching all of the votes for this article
	 * @return array an array of article_vote_object objects
	 */
	function get_votes() {
		$sql_query = "SELECT OID FROM article_vote WHERE article_oid='".$this->OID."';";		

		$this->last_query = $sql_query;

		$result = mysql_query($sql_query);
		
		if(mysql_error() != '') {
			$error = new handle_error($_SERVER["PHP_SELF"],'Failed to load the votes for the article '.$this->OID.', MySql error is: '.mysql_error().', query: '.$sql_query ,'get_votes()');
			return false;
		}
		
		$votes = array();
		
		$i = 0;
		while ($row = mysql_fetch_assoc($result)) {
			$temp = new article_vote_object();
			$temp->load_object($row["OID"]);
			$votes[$i] = $temp;
			$i++;
		}
		
		return $votes;
	}
	
	/**
	 * method to determine if the logged-in user has already voted for this article
	 * @return boolean true if they have voted already, false otherwise
	 */
	function check_user_voted() {
		// just going to return true if nobody is logged in
		if (!isset($_SESSION["current_user"]))
			return true;
		
		$userID = $_SESSION["current_user"]->get_ID();
		
		$sql_query = "SELECT COUNT(*) AS users_vote FROM article_vote WHERE article_oid='".$this->OID."' AND person_oid='".$userID."';";		

		$this->last_query = $sql_query;

		$result = mysql_query($sql_query);
		
		if(mysql_error() != '') {
			$error = new handle_error($_SERVER["PHP_SELF"],'Failed to check if the current user voted for the article '.$this->OID.', MySql error is: '.mysql_error().', query: '.$sql_query ,'check_user_voted()');
			return false;
		}
		
		$row = mysql_fetch_assoc($result);
		
		if($row["users_vote"] == "0")
			return false;
		else
			return true;			
	}
	
	/**
	 * method for fetching all of the comments for this article
	 * @return array an array of article_comment_object objects
	 */
	function get_comments() {
		$sql_query = "SELECT OID FROM article_comment WHERE article_oid='".$this->OID."' ORDER BY created_ts;";		

		$this->last_query = $sql_query;

		$result = mysql_query($sql_query);
		
		if(mysql_error() != '') {
			$error = new handle_error($_SERVER["PHP_SELF"],'Failed to load the comments for the article '.$this->OID.', MySql error is: '.mysql_error().', query: '.$sql_query ,'get_comments()');
			return false;
		}
		
		$comments = array();
		
		$i = 0;
		while ($row = mysql_fetch_assoc($result)) {
			$temp = new article_comment_object();
			$temp->load_object($row["OID"]);
			$comments[$i] = $temp;
			$i++;
		}
		
		return $comments;
	}
	
	/**
	 * loads all of the objects of this class into an array, which is returned
	 * this custom version for an article only returns published articles
	 * 
	 * @param integer $start The start of the SQL LIMIT clause
	 * @param integer $limit The amount (limit) of objects to load
	 * @param string $order_by the name of the field to sort the BOs by
	 * @param string $order the order to sort the BOs by, default is DESC
	 * @return array an array containing objects of this type of business object.
	 */
	function load_all($start=0, $limit=0, $order_by="OID", $order="DESC")
	{
		global $config;
		global $front;
		
		// if accessing from the admin backend, use the parent method which includes all articles
		if(basename($_SERVER["PHP_SELF"]) == "ListAll.php" || (isset($front) && $front->get_page_controller() == 'ListAll'))
			return parent::load_all($start, $limit, $order_by, $order);
		
		if ($limit == 0)
			$limit = $config->get('sysListPageAmount');
		
		// exclude 'General' articles when we are accessing from a news feed
		if(basename($_SERVER["PHP_SELF"]) == "view_feed.php" || (isset($_GET["act"]) && $_GET["act"] == "view_feed"))
			$sql_query = "SELECT OID FROM ".self::TABLE_NAME." WHERE published='1' AND section!='General' ORDER BY ".$order_by." ".$order." LIMIT ".$start.", ".$limit.";";
		else
			$sql_query = "SELECT OID FROM ".self::TABLE_NAME." WHERE published='1' ORDER BY ".$order_by." ".$order." LIMIT ".$start.", ".$limit.";";
		
		$this->last_query = $sql_query;

		$result = mysql_query($sql_query);
		
		if(mysql_error() != '') {
			$error = new handle_error($_SERVER["PHP_SELF"],'Failed to load objects, query: '.$this->last_query.'.' ,'load_all()','framework');
			return false;
		}
		
		// now build an array of objects to be returned
		$objects = array();
		$count = 0;
		$BO_Class = get_class($this);
		
		while($row = mysql_fetch_assoc($result)) {
			$obj = new $BO_Class();
			$obj->load_object($row["OID"]);
			$objects[$count] = $obj;
			$count++;			
		}
		
		return $objects;	
	}
}

?>