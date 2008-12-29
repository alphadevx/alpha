<?php

// include the config file
if(!isset($config))
	require_once '../util/configLoader.inc';
$config =&configLoader::getInstance();

require_once $config->get('sysRoot').'alpha/view/View.inc';
require_once $config->get('sysRoot').'alpha/util/db_connect.inc';
require_once $config->get('sysRoot').'alpha/controller/Controller.inc';
require_once $config->get('sysRoot').'alpha/model/article_object.inc';

/**
* 
* Controller used to edit an existing article
* 
* @author John Collins <john@design-ireland.net>
* @package Design-Ireland
*
*/
class edit_article_object extends Controller
{
	/**
	 * the new article to be edited
	 * @var article_object
	 */
	var $BO;
				
	/**
	 * constructor that renders the page and intercepts POST messages	 
	 */
	function edit_article_object() {
		global $config;
		
		// load the business object (BO) definition
		require_once $config->get('sysRoot').'alpha/model/article_object.inc';
		
		// ensure that a OID is also provided
		if (isset($_GET["oid"])) {
			$BO_oid = $_GET["oid"];
		}else{
			$error = new handle_error($_SERVER["PHP_SELF"],'Could not load the article as an oid was not supplied!','GET');
			exit;
		}
		
		// ensure that the super class constructor is called
		$this->Controller();
		
		$this->set_visibility('Administrator');
		if(!$this->check_rights()) {
			exit;
		}
		
		$this->BO = new article_object();
		$this->BO->load($BO_oid);
		
		if(!empty($_POST)) {			
			$this->handle_post();
			exit;
		}	
		
		// set up the title and meta details
		$this->set_title($this->BO->get("title")." (editing)");		
		
		$this->display_page_head();
		
		$view = View::getInstance($this->BO);
		
		$view->edit_view();
		
		$this->display_page_foot();
	}
	
	/**
	 * method to display the page head
	 */
	function display_page_head() {
		if(method_exists($this, 'before_display_page_head_callback'))
			$this->before_display_page_head_callback();
		
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
		
		echo '
			<script type="text/javascript" src="'.$config->get('sysURL').'/alpha/lib/jquery/jquery.pack.js"></script>
			<script type="text/javascript" src="'.$config->get('sysURL').'/alpha/lib/markitup/jquery.markitup.pack.js"></script>
			<script type="text/javascript" src="'.$config->get('sysURL').'/alpha/lib/markitup/sets/markdown/set.js"></script>
			<link rel="stylesheet" type="text/css" href="'.$config->get('sysURL').'/alpha/lib/markitup/skins/simple/style.css" />
			<link rel="stylesheet" type="text/css" href="'.$config->get('sysURL').'/alpha/lib/markitup/sets/markdown/style.css" />
			<script type="text/javascript">
			$(document).ready(function() {
				$(\'#text_field_content_0\').markItUp(mySettings);
			});
			</script>
			</head>
			<body>';

		if ($config->get('sysUseWidgets') && isset($this->BO)) {
			echo '<script language="JavaScript" src="'.$config->get('sysURL').'/alpha/scripts/addOnloadEvent.js"></script>';
			require_once $config->get('sysRoot').'alpha/view/widgets/button.js.php';
			require_once $config->get('sysRoot').'alpha/view/widgets/StringBox.js.php';
			require_once $config->get('sysRoot').'alpha/view/widgets/TextBox.js.php';
		
			require_once $config->get('sysRoot').'alpha/view/widgets/form_validator.js.php';
		
			echo '<script type="text/javascript">';
			$validator = new form_validator($this->BO);
			echo '</script>';
		}
		
		if(method_exists($this, 'during_display_page_head_callback'))
			$this->during_display_page_head_callback();
		
		echo '</head>';
		echo '<body>';
			
		echo '<h1>'.$this->get_title().'</h1>';
		
		if (isset($_SESSION["current_user"])) {	
			echo '<p>You are logged in as '.$_SESSION["current_user"]->getDisplayname().'.  <a href="'.$config->get('sysURL').'/alpha/controller/logout.php">Logout</a></p>';
		}else{
			echo '<p>You are not logged in</p>';
		}
		
		if(method_exists($this, 'after_display_page_head_callback'))
			$this->after_display_page_head_callback();
	}
	
	/**
	 * method to handle POST requests
	 */
	function handle_post() {
		global $config;
		
		// check the hidden security fields before accepting the form POST data
		if(!$this->check_security_fields()) {
			$error = new handle_error($_SERVER["PHP_SELF"],'This page cannot accept post data from remote servers!','handle_post()','validation');
			exit;
		}
		
		if (isset($_POST["saveBut"])) {
			echo "<pre>";
			//var_dump(file_get_contents('php://input'));
			var_dump($_POST);
			exit;		
			
			// populate the transient object from post data
			$this->BO->populateFromPost();
			
			$success = $this->BO->save();
			
			$this->BO->load($this->BO->getID());
			
			// set up the title and meta details
			$this->set_title($this->BO->get("title")." (editing)");		
		
			$this->display_page_head();		
			
			if($success) {
				echo '<p class="success">Article '.$this->BO->get_ID().' saved successfully.</p>';
			}
			
			$view = View::getInstance($this->BO);
		
			$view->edit_view();
		
			$this->display_page_foot();
		}
		
		if(isset($_POST["uploadBut"])) {
						
			// upload the file to the attachments directory
			$success = move_uploaded_file($_FILES['userfile']['tmp_name'], $this->BO->get_attachments_location().'/'.$_FILES['userfile']['name']);
			
			// set up the title and meta details
			$this->set_title($this->BO->get("title")." (editing)");		
		
			$this->display_page_head();
			
			if(!$success)
				$error = new handle_error($_SERVER["PHP_SELF"],'Error :- could not move file: '.$success,'handle_post()','framework');
			
			// set read/write permissions on the file
			$success = chmod($this->BO->get_attachments_location().'/'.$_FILES['userfile']['name'], 0666);
			
			if (!$success)
				$error = new handle_error($_SERVER["PHP_SELF"],'Unable to set read/write permissions on the file '.$this->BO->get_attachments_location().'/'.$_FILES['userfile']['name'].'.','handle_post()','framework');
			
			if($success) {
				echo '<p class="success">File uploaded successfully.</p>';
			}
			
			$view = View::getInstance($this->BO);
		
			$view->edit_view();
		
			$this->display_page_foot();
		}
		
		if (!empty($_POST["file_to_delete"])) {			
					
			$success = unlink($this->BO->get_attachments_location().'/'.$_POST["file_to_delete"]);
			
			// set up the title and meta details
			$this->set_title($this->BO->get("title")." (editing)");		
		
			$this->display_page_head();
			
			if(!$success)
				$error = new handle_error($_SERVER["PHP_SELF"],'Error :- could not delete the file: '.$_POST["file_to_delete"],'handle_post()','framework');
			
			if($success) {
				echo '<p class="success">'.$_POST["file_to_delete"].' deleted successfully.</p>';
			}
			
			$view = View::getInstance($this->BO);
		
			$view->edit_view();
		
			$this->display_page_foot();
		}
		
		if (isset($_POST["cancelBut"])) {
			$this->abort();			
			header('Location: '.$config->get('sysURL'));
		}
	}	
}

// now build the new controller
if(basename($_SERVER["PHP_SELF"]) == "edit_article_object.php")
	$controller = new edit_article_object();

?>
