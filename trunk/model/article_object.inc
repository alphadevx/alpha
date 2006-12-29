<?php

// $Id$

require_once $sysRoot.'alpha/model/mysql_DAO.inc';
require_once $sysRoot.'alpha/util/handle_error.inc';
require_once $sysRoot.'alpha/model/article_vote_object.inc';
require_once $sysRoot.'alpha/model/article_comment_object.inc';

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
class article_object extends mysql_DAO
{
	/**
	 * the article title
	 * @var String
	 */
	var $title;
	
	/**
	 * the article site section
	 * @var Enum
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
	var $data_labels = array(
		"OID"=>"Article ID#",		
		"revision_num"=>"Revision Number",		
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
		"published"=>"Published"		
	);
	
	/**
	 * the name of the database table for the class
	 * @var string
	 */
	var $TABLE_NAME = "article";
	
	/**
	 * constructor for the class
	 */
	function article_object() {
		// ensure to call the parent constructor
		$this->mysql_DAO();
		$this->title = new String();
		$this->title->set_helper("Please provide a title for the article.");
		$this->title->set_size(100);
		$this->title->set_rule("/\w+/");
		$this->section = new Enum(array("Business",
										"Design",
										"Imagery",
										"Browsing",
										"General"));
		$this->keywords = new String();
		$this->keywords->set_helper("Please provide some coma-seperated search engine keywords for the article.");
		$this->keywords->set_size(150);
		$this->keywords->set_rule("/\w+/");
		$this->description = new String();
		$this->description->set_helper("Please provide a brief description of the article.");
		$this->description->set_size(200);
		$this->description->set_rule("/\w+/");
		$this->body_onload = new String();		
		$this->content = new Text();
		$this->content->set_helper("Please provide some content for the article.");
		$this->content->set_rule("/\w+/");
		$this->header_content = new Text();		
		$this->author = new String();
		$this->author->set_helper("Please state the name of the author of this article");
		$this->author->set_size(70);
		$this->author->set_rule("/\w+/");
		$this->date_added = new Date();
		$this->date_updated = new Date();
		$this->published = new Boolean(0);
	}
	
	/**
	 * gets the OIDs of the two most recent articles added to the system (by date)
	 * @return array an array of the two OIDs, or boolean false on failure
	 */
	function get_two_most_recent(){
		$sql_query = "SELECT OID FROM ".$this->TABLE_NAME." WHERE published='1' ORDER BY date_added DESC LIMIT 0, 2;";		

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
		$sql_query = "SELECT OID FROM ".$this->TABLE_NAME." WHERE published='1' AND section='".$section."' ORDER BY date_added DESC LIMIT 0, 10;";		

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
		global $sysRoot;
		
		return $sysRoot."attachments/article_".$this->get_Id();
	}
	
	/**
	 * generates the URL of the attachments folder for this article
	 * @return string returns the URL of the attachments folder
	 */
	function get_attachments_URL() {
		global $sysURL;
		
		return $sysURL."/attachments/article_".$this->get_Id();
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
		$sql_query = "SELECT * FROM ".$this->TABLE_NAME." WHERE title = '$title';";

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
				if (strtoupper(get_class($this->$prop)) != 'DATE') {					
					$this->$prop->set_value(stripslashes($row[$prop]));
				}else{
					$this->$prop->populate_from_string(stripslashes($row[$prop]));
				}
			}
			if($prop == "OID"){				
				$this->OID = $row[$prop];
			}	
			if($prop == "version_num"){				
				$this->version_num = $row[$prop];
			}
		}		
		
		if(mysql_error() == '' && $this->set_enum_options()) {	
			return true;
		}else{			
			$this->last_query .= " Error: ".mysql_error();
			$error = new handle_error($_SERVER["PHP_SELF"],'Failed to load article, MySql error is: '.mysql_error().', query: '.$this->last_query,'load_by_title($title)','framework');
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
}

?>