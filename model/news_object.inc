<?php

// $Id$

require_once $sysRoot.'model/mysql_DAO.inc';
require_once $sysRoot.'util/handle_error.inc';

/**
 *
 * the news item class for the site
 * 
 * @package Design-Ireland
 * @author John Collins <john@design-ireland.net>
 * @copyright 2006 John Collins
 * 
 * 
 */ 
class news_object extends mysql_DAO
{	
	/**
	 * an array of data display labels for the class properties
	 * @var array
	 */
	var $data_labels = array(
		"OID"=>"News ID#",
		"date_added"=>"Date Added",		
		"title"=>"Title",
		"content"=>"Content"		
	);
	
	/**
	 * the name of the database table for the class
	 * @var string
	 */
	var $TABLE_NAME = "news";
	
	/**
	 * the title of the news item
	 * @var String
	 */
	var $title;
	
	/**
	 * the news content
	 * @var Text;
	 */
	var $content;
	
	/**
	 * the date the news item was added
	 * @var Date
	 */
	var $date_added;
	
	/**
	 * constructor for the class that populates all of the complex types with default values
	 */
	function news_object() {
		$this->title = new String();
		$this->title->set_rule("/\w+/");
		$this->title->set_helper("Please provide a title for the news item.");
		$this->title->set_size(70);
		$this->content = new Text();
		$this->content->set_rule("/\w+/");
		$this->content->set_helper("Please provide some content for the news item.");
		$this->content->set_size(65535);
		$this->date_added = new Date();		
	}
	
	/**
	 * gets the OIDs of the ten most recent news items added to the system (by date), for the home page
	 * @return array an array of the ten OIDs (or less than ten), or boolean false on failure
	 */
	function get_ten_most_recent(){
		$sql_query = "SELECT OID FROM ".$this->TABLE_NAME." ORDER BY date_added DESC LIMIT 0, 10;";		

		$this->last_query = $sql_query;

		$result = mysql_query($sql_query);
		
		if(mysql_error() != '') {
			$error = new handle_error($_SERVER["PHP_SELF"],'Failed to load the OIDs for the ten most recent news items, MySql error is: '.mysql_error().', query: '.$sql_query ,'get_ten_most_recent()');
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
}

?>
