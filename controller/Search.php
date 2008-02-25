<?php

// $Id$

// include the config file
if(!isset($config))
	require_once '../util/configLoader.inc';
$config =&configLoader::getInstance();

require_once $config->get('sysRoot').'alpha/util/db_connect.inc';
require_once $config->get('sysRoot').'alpha/controller/Controller.inc';
require_once $config->get('sysRoot').'alpha/util/handle_error.inc';
require_once $config->get('sysRoot').'alpha/view/View.inc';
require_once $config->get('sysRoot').'alpha/util/log_file.inc';

// load the business object (BO) definition
if (isset($_GET["bo"])) {
	$BO_name = $_GET["bo"];
	
	if (file_exists($config->get('sysRoot').'alpha/model/'.$BO_name.'.inc')) {		
		require_once $config->get('sysRoot').'alpha/model/'.$BO_name.'.inc';
	}elseif (file_exists($config->get('sysRoot').'model/'.$BO_name.'.inc')) {		
		require_once $config->get('sysRoot').'model/'.$BO_name.'.inc';
	}else{
		$error = new handle_error($_SERVER["PHP_SELF"],'Could not load the defination for the BO class '.$BO_name,'GET');
		exit;
	}
}else{
	$error = new handle_error($_SERVER["PHP_SELF"],'No BO available to search!','GET');
	exit;
}

/**
* 
* Generic keyword site search engine controller
* 
* @package Alpha Search
* @author John Collins <john@design-ireland.net>
* @copyright 2006 John Collins
* @todo Need to make the search algorithm with a more efficient one (responsible for 92% of total run time)!
* @todo Exclude common words (and, or, the...) ?
* @todo re-enable loggin of user queries
*
*/
class Search extends Controller
{	
	/**
	 * the name of the BO to be search
	 * @var string BO_name
	 */
	var $BO_name;
	
	/**
	 * an array of the BO attributes to include in the search (default is keywords attribute).  these should be
	 * provided as a pipe-seperated list in the GET variables to this controller.
	 * @var array BO_search_attributes
	 */
	var $BO_search_attributes;
	
	/**
	 * the new default View object used for rendering the objects to create
	 * @var View BO_view
	 */
	var $BO_View;
	
	/**
	 * a PEAR benchmark timer
	 * @var Benchmark_Timer
	 */
	var $timer;
	
	/**
	 * the query to search by
	 * @var string
	 */
	var $query;
	
	/**
	 * an array of the words matched per result
	 * @var array
	 */
	var $word_count_match;
	
	/**
	 * an array of the IDs of the matching results
	 * @var array
	 */
	var $ID_result_array;
	
	/**
	 * an array of the keywords of the matching results
	 * @var array
	 */
	var $result_keywords_array;
	
	/**
	 * the main result count for the query
	 * @var int
	 */
	var $result_count;	
	
	/**
	 * the start number for result list pageination
	 * @var integer 
	 */
	var $start_point;
	
	/**
	 * an array mapping BO class names to "fancy" display names
	 * @var array
	 */
	var $BO_display_names = array(
					"article_object" => "Articles",
					"news_object" => "News Items"
					);
	
	/**
	 * constructor to render the page
	 * @param string $BO_name the name of the BO that we are creating	 
	 */
	function search($BO_name) {
		global $config;
		
		// check the hidden security fields before accepting the form POST data
		if(!$this->check_security_fields()) {
			$error = new handle_error($_SERVER["PHP_SELF"],'This page cannot accept post data from remote servers!','handle_post()','validation');
			exit;
		}
		
		// ensure that the super class constructor is called
		$this->Controller();
		
		$this->BO_name = $BO_name;
		
		switch($this->BO_name) {
			case "article_object":
				$this->BO_search_attributes = array("keywords","title","description");
			break;
			case "news_object":
				$this->BO_search_attributes = array("title","content");
			break;
		}		
		
		// set the start point for the list pagination
		if (isset($_GET["start"]) ? $this->start_point = $_GET["start"]: $this->start_point = 0);
		
		if ($config->get('sysBenchMark')) {
			require_once('Timer.php');
		
			$this->timer = new Benchmark_Timer();
			$this->timer->start();
		}	
	
		if (isset($_GET["search_string"])) {
			$this->query = $_GET["search_string"];
			$this->set_title("Search results for '".$this->query."'");	
		}elseif(isset($_GET["section"])) {			
			$this->set_title("Displaying all articles for the ".$_GET["section"]." section.");
		}else{ 
			$this->set_title("Search for an article");
		}
		
		// set up the meta details		
		$this->set_description("Page to search for an article.");
		$this->set_keywords("search,article");		
		
		$this->display_page_head();
		
		if(!empty($_POST))
			$this->handle_post();
		
		$this->render_delete_form();
		
		if(!empty($_GET["search_string"]))
			$this->do_search();
		
		if(isset($_GET["section"]))
			$this->get_full_section($_GET["section"]);
		
		$this->display_page_foot();	
	}
	
	/**
	 * the main search and sort method for the search
	 */
	function do_search() {
		global $config;
		
		$BO = new $this->BO_name();
	
		if($this->BO_name == "article_object")
			$sql_query = 'SELECT * FROM '.$BO->TABLE_NAME.' WHERE published=\'1\';';
		else
			$sql_query = 'SELECT * FROM '.$BO->TABLE_NAME.';';
	
		// now run the query, and store the result
		$result = mysql_query($sql_query);
		
		if (mysql_error() != '')
			$error = new handle_error($_SERVER["PHP_SELF"],'Search query failed, MySql error is: '.mysql_error().', query: '.$sql_query,'do_search');
			
		// this is an expensive linear algorithm: all keywords in the $user_query_array will be compared
		// with all of the words in the titles and descriptions!
	
		$user_query_array = explode(" ", strtoupper($this->query));
	
		// these parallel arrays will be used to hold the results of the search
		$ID_result_array = array();	
		$result_keywords_array = array();	
		$word_count_match = array();
	
		// looping through the user-supplied keywords	
		$i = 0;
		// use this flag to see if a word match was found in this db row
		$found_word = false;
		// the master result count
		$result_count = 0;	
	
		while ($row = mysql_fetch_assoc($result)) {
			$word_count = 0;		
	
			$ID_result_array[$i] = $row["OID"];			
			$result_keywords_array[$i] = " ";			
			
			// loop through each user-supplied query words
			foreach ($user_query_array as $keyword) {
				// loop through each $BO_search_attributes value
				for ($j = 0; $j < count($this->BO_search_attributes); $j++) {
					// if it is the keywords attribute the seperator is a coma, otherwise a blank space.					
					if ($this->BO_search_attributes[$j] == "keywords")
						$BO_attribute_array = explode(",", $row["keywords"]);
					else
						$BO_attribute_array = preg_split("/[\s,.]+/", $row[$this->BO_search_attributes[$j]]);
										
					// loop through the attribute values from the database and do the comparison
					for ($k = 0; $k < count($BO_attribute_array); $k++) {
						if (trim($keyword) == trim(strtoupper($BO_attribute_array[$k]))) {
							$found_word = true;
							$word_count++;
							$result_keywords_array[$i] .= trim(strtoupper($BO_attribute_array[$k])).' ';			
						}
					}
				}				
			}
			
			$word_count_match[$i] = $word_count;
			$i++;
			if($found_word){
				$result_count++;
				$found_word = false;
			}
		}	
	
		// log the user's search query in a log file
		$search_log = new log_file($config->get('sysRoot').'alpha/util/logs/search_log.log');		
		$search_log->write_line(array($this->query, date("Y-m-d H:i:s"), $_SERVER["HTTP_USER_AGENT"], $_SERVER["REMOTE_ADDR"]));
		
		// now we will peform a sort on the parallel arrays, sorted by matching word count!	
	
		array_multisort($word_count_match, SORT_DESC, SORT_NUMERIC, $ID_result_array, $result_keywords_array);
		
		$this->result_count = $result_count;
		$this->word_count_match = $word_count_match;
		$this->ID_result_array = $ID_result_array;
		$this->result_keywords_array = $result_keywords_array;
		
		$this->display_results();
	}
	
	/**
	 * a method to return all of the articles for the section indicator provided
	 * @param string $section the section to display all of the articles for
	 */
	function get_full_section($section) {
		
		$BO = new $this->BO_name();
	
		$sql_query = 'SELECT * FROM '.$BO->TABLE_NAME.' WHERE section=\''.$section.'\' AND published=\'1\' ORDER BY date_added DESC;';
	
		// now run the query, and store the result
		$result = mysql_query($sql_query);
		
		if (mysql_error() != '')
			$error = new handle_error($_SERVER["PHP_SELF"],'Search query failed, MySql error is: '.mysql_error().', query: '.$sql_query,'do_search');
			
		$ID_result_array = array();	
		$word_count_match = array();
		
		$i = 0;
		$result_count = 0;	
	
		while ($row = mysql_fetch_assoc($result)) {	
			$ID_result_array[$i] = $row["OID"];			
			$word_count_match[$i] = 1;
			$i++;
			$result_count++;			
		}	
	
		$this->result_count = $result_count;
		$this->ID_result_array = $ID_result_array;
		$this->word_count_match = $word_count_match;
		$this->display_results();
	}
	
	/**
	 * method to display the HTML output from the search
	 */
	function display_results() {
		global $config;
		
		$this->render_page_links();
		
		echo '<h2>There were '.$this->result_count.' result(s) in total found in <em>'.$this->BO_display_names[$this->BO_name].'</em></h2>';	
		
		$BO = new $this->BO_name();				
		
		if(($this->result_count - $this->start_point < 10) ? $end_loop_i = $this->result_count : $end_loop_i = $this->start_point+$config->get('sysListPageAmount'));
				
		for ($i = $this->start_point; $i < $end_loop_i; $i++) {
			if ($this->word_count_match[$i] != 0) {
				if(!isset($_GET["section"]))
					echo '<p>Matching Word Count: '.$this->word_count_match[$i].' [<em>'.$this->result_keywords_array[$i].'</em>]</p>';
				
				$BO->load_object($this->ID_result_array[$i]);
				
				$BO_View = View::get_instance($BO);
				
				$BO_View->list_view();			
			}
		}	
	}
	
	/**
	 * method to display the page head
	 */
	function display_page_head() {
		global $config;
		
		echo '<html>';
		echo '<head>';
		echo '<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">';
		echo '<title>'.$this->get_title().'</title>';
		echo '<meta name="Keywords" content="'.$this->get_keywords().'">';
		echo '<meta name="Description" content="'.$this->get_description().'">';
		echo '<meta name="Author" content="john collins">';
		echo '<meta name="copyright" content="copyright ">';
		echo '<meta name="identifier" content="http://'.$config->get('sysURL').'/">';
		echo '<meta name="revisit-after" content="7 days">';
		echo '<meta name="expires" content="never">';
		echo '<meta name="language" content="en">';
		echo '<meta name="distribution" content="global">';
		echo '<meta name="title" content="'.$this->get_title().'">';
		echo '<meta name="robots" content="index,follow">';
		echo '<meta http-equiv="imagetoolbar" content="no">';			
		
		echo '<link rel="StyleSheet" type="text/css" href="'.$config->get('sysURL').'/config/css/'.$config->get('sysTheme').'.css.php">';
		
		if ($config->get('sysUseWidgets')) {
			echo '<script language="JavaScript" src="'.$config->get('sysURL').'/alpha/scripts/addOnloadEvent.js"></script>';
			
			require_once $config->get('sysRoot').'alpha/view/widgets/form_validator.js.php';			
			require_once $config->get('sysRoot').'alpha/view/widgets/button.js.php';			
			require_once $config->get('sysRoot').'alpha/view/widgets/string_box.js.php';			
		}
		
		echo '</head>';
		echo '<body>';
			
		echo '<h1>'.$this->get_title().'</h1>';		
		
		if (isset($_SESSION["current_user"])) {	
			echo '<p>You are logged in as '.$_SESSION["current_user"]->get_displayname().'.  <a href="'.$config->get('sysURL').'/logout/controller/logout.php">Logout</a></p>';
		}else{
			echo '<p>You are not logged in</p>';
		}
	}
	
	/**
	 * overrides the Controller display_page_foot method to display addition content, such as a search box
	 */
	function display_page_foot() {
		global $config;
		
		$this->render_page_links();
		
		echo '<center>';
		echo '<p>Try searching other content by choosing from the drop-down below:</p>';
		echo '<form action="Search.php" method="GET">';
		echo 'Search: <select name="bo" value="'.$this->BO_name.'"/>';
		foreach(array_keys($this->BO_display_names) as $key)
			echo '<option value="'.$key.'"'.($this->BO_name == $key ? " selected" : "").'>'.$this->BO_display_names[$key].'</option>';
		echo '</select>';
		
		if ($config->get('sysUseWidgets')){
			$query = new String($this->query);
			$query->set_helper("Please enter a term to search for!");
			
			echo " for: ";
			$temp = new string_box($query, "", "search_string", "", 20, false);
			$temp = new button("submit", "Search", "");
		}else{
			
			echo '<input name="search_string" type="text" size="40"/>';
			echo '<input type="submit" value="Search"/>';			
		}
		
		View::render_security_fields();
		
		echo '</form>';
		echo '</center>';
		
		if ($config->get('sysBenchMark') && !empty($this->query)) {
			$this->timer->stop();
			echo '<br><p align="center"><small>Time taken: <strong>'.$this->timer->TimeElapsed().'</strong> secs.</small></p>';
		}	
		
		echo '<br>';
		echo '<p align="center"><small>DesIre Search Engine 2.0</small></p>';
		echo '<br>';
		
		echo '</body>';
		echo '</html>';
	}
	
	/**
	 * method for rendering the pagination links 
	 */
	function render_page_links() {
		global $config;
		
		$end = ($this->start_point+$config->get('sysListPageAmount'));
		
		if($end > $this->result_count)
			$end = $this->result_count;
		
		if ($this->start_point > 9)
			echo '<p align="center">Displaying '.($this->start_point+1).' to '.$end.' of <strong>'.$this->result_count.'</strong>.&nbsp;&nbsp;';		
		else
			echo '<p align="center">Displaying &nbsp;'.($this->start_point+1).' to '.$end.' of <strong>'.$this->result_count.'</strong>.&nbsp;&nbsp;';		
				
		if ($this->start_point > 0) {
			echo '<a href="'.$_SERVER["PHP_SELF"].'?bo='.$this->BO_name.'&start='.($this->start_point-$config->get('sysListPageAmount')).'&var1='.$_REQUEST["var1"].'&var2='.$_REQUEST["var2"].(isset($_REQUEST["section"]) ? '&section='.$_REQUEST["section"]: '&search_string='.$this->query).(isset($_GET["BO_search_attributes"]) ? "&BO_search_attributes=".$_GET["BO_search_attributes"]: "").'">&lt;&lt;-Previous</a>&nbsp;&nbsp;';
		}else{
			echo '&lt;&lt;-Previous&nbsp;&nbsp;';
		}
		$page = 1;
		for ($i = 0; $i < $this->result_count; $i+=$config->get('sysListPageAmount')) {
			if($i != $this->start_point)
				echo '&nbsp;<a href="'.$_SERVER["PHP_SELF"].'?bo='.$this->BO_name.'&start='.$i.'&var1='.$_REQUEST["var1"].'&var2='.$_REQUEST["var2"].(isset($_REQUEST["section"]) ? '&section='.$_REQUEST["section"]: '&search_string='.$this->query).'">'.$page.'</a>&nbsp;';
			else
				echo '&nbsp;'.$page.'&nbsp;';
			$page++;
		}
		if ($this->result_count > $end) {
			echo '&nbsp;&nbsp;<a href="'.$_SERVER["PHP_SELF"].'?bo='.$this->BO_name.'&start='.($this->start_point+$config->get('sysListPageAmount')).'&var1='.$_REQUEST["var1"].'&var2='.$_REQUEST["var2"].(isset($_REQUEST["section"]) ? '&section='.$_REQUEST["section"]: '&search_string='.$this->query).(isset($_GET["BO_search_attributes"]) ? "&BO_search_attributes=".$_GET["BO_search_attributes"]: "").'">Next-&gt;&gt;</a>';
		}else{
			echo '&nbsp;&nbsp;Next-&gt;&gt;';
		}
		echo '</p>';
	}
	
	/**
	 * method to handle POST requests
	 */
	function handle_post() {		
		
		// check the hidden security fields before accepting the form POST data
		if(!$this->check_security_fields()) {
			$error = new handle_error($_SERVER["PHP_SELF"],'This page cannot accept post data from remote servers!','handle_post()','validation');
			exit;
		}
		
		if (!empty($_POST["delete_oid"])) {
			
			$temp = new $this->BO_name();
			
			$temp->load_object($_POST["delete_oid"]);			
					
			$success = $temp->delete_object();
					
			if($success) {
				echo '<p class="success">'.$this->BO_name.' '.$_POST["delete_oid"].' deleted successfully.</p>';
			}
		}
	}
}

// now build the new controller
if(basename($_SERVER["PHP_SELF"]) == "Search.php")
	$controller = new Search($BO_name);

?>