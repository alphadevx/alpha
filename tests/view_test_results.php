<?php

// $Id$

require_once '../../config/config.conf';
require_once $sysRoot.'alpha/model/person_object.inc';
require_once $sysRoot.'alpha/view/person.inc';
require_once $sysRoot.'config/db_connect.inc';
require_once $sysRoot.'alpha/controller/Controller.inc';
require_once $sysRoot.'alpha/tests/Enum_Test.php';
require_once $sysRoot.'alpha/tests/Boolean_Test.php';
require_once $sysRoot.'alpha/tests/Date_Test.php';
require_once $sysRoot.'alpha/tests/Integer_Test.php';
require_once 'PHPUnit.php';

/*
 * we are supressing the display and logging of errors on this page, as we 
 * are only interested in tests that fail and the reasons given for failing
 * 
 */
$sysErrorValidationDisplay = false;
$sysErrorValidationLog = false;
$sysErrorWarningDisplay = false;
$sysErrorWarningLog = false;
$sysErrorPhpDisplay = false;
$sysErrorPhpLog = false;
$sysErrorFrameworkDisplay = false;
$sysErrorFrameworkLog = false;
$sysErrorOtherDisplay = false;
$sysErrorOtherLog = false;

/**
 *
 * Controller which displays all of the unit test results
 * 
 * @package Alpha Core Unit Tests
 * @author John Collins <john@design-ireland.net>
 * @copyright 2006 John Collins 
 * 
 */
class view_test_results extends Controller
{	
	/**
	 * constructor to set up the object
	 */
	function view_test_results() {
		// ensure that the super class constructor is called
		$this->Controller();		
		
		$this->set_visibility("Administrator");
		if(!$this->check_rights()){			
			exit;
		}
		
		// set up the title and meta details
		$this->set_title("Alpha Core Unit Test Results");		
		
		$this->display_page_head();
		
		echo "<h2>Core Complex Data Types</h2>";
		
		//------------------------------------------------
		echo "<h3>Enum:</h3>";
		
		$suite  = new PHPUnit_TestSuite("Enum_Test");
		$result = PHPUnit::run($suite);

		if($result->wasSuccessful())
			echo '<span class="success">'.$result->toHTML().'</span>';
		else
			echo '<span class="warning">'.$result->toHTML().'</span>';
		
		//------------------------------------------------
		echo "<h3>Boolean:</h3>";
		
		$suite  = new PHPUnit_TestSuite("Boolean_Test");
		$result = PHPUnit::run($suite);

		if($result->wasSuccessful())
			echo '<span class="success">'.$result->toHTML().'</span>';
		else
			echo '<span class="warning">'.$result->toHTML().'</span>';		
		
		//------------------------------------------------
		echo "<h3>Date:</h3>";
		
		$suite  = new PHPUnit_TestSuite("Date_Test");
		$result = PHPUnit::run($suite);

		if($result->wasSuccessful())
			echo '<span class="success">'.$result->toHTML().'</span>';
		else
			echo '<span class="warning">'.$result->toHTML().'</span>';		
		
		//------------------------------------------------
		echo "<h3>Integer:</h3>";
		
		$suite  = new PHPUnit_TestSuite("Integer_Test");
		$result = PHPUnit::run($suite);

		if($result->wasSuccessful())
			echo '<span class="success">'.$result->toHTML().'</span>';
		else
			echo '<span class="warning">'.$result->toHTML().'</span>';
		
		$this->display_page_foot();
	}
	
	/**
	 * method to display the page head
	 */
	function display_page_head() {
		global $sysURL;
		global $sysTheme;
		global $sysUseWidgets;
		global $sysRoot;		
		
		echo '<html>';
		echo '<head>';
		echo '<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">';
		echo '<title>'.$this->get_title().'</title>';
		echo '<meta name="Keywords" content="'.$this->get_keywords().'">';
		echo '<meta name="Description" content="'.$this->get_description().'">';
		echo '<meta name="Author" content="john collins">';
		echo '<meta name="copyright" content="copyright ">';
		echo '<meta name="identifier" content="http://'.$sysURL.'/">';
		echo '<meta name="revisit-after" content="7 days">';
		echo '<meta name="expires" content="never">';
		echo '<meta name="language" content="en">';
		echo '<meta name="distribution" content="global">';
		echo '<meta name="title" content="'.$this->get_title().'">';
		echo '<meta name="robots" content="index,follow">';
		echo '<meta http-equiv="imagetoolbar" content="no">';			
		
		echo '<link rel="StyleSheet" type="text/css" href="'.$sysURL.'/config/css/'.$sysTheme.'.css.php">';
		
		if ($sysUseWidgets) {
			echo '<script language="JavaScript" src="'.$sysURL.'/alpha/scripts/addOnloadEvent.js"></script>';
			require_once $sysRoot.'alpha/view/widgets/button.js.php';
		}
		
		echo '</head>';
		echo '<body>';
			
		echo '<h1>'.$this->get_title().'</h1>';
		
		if (isset($_SESSION["current_user"])) {	
			echo '<p>You are logged in as '.$_SESSION["current_user"]->get_displayname().'.  <a href="'.$sysURL.'/alpha/controller/logout.php">Logout</a></p>';
		}else{
			echo '<p>You are not logged in</p>';
		}
		
		echo '<p align="center"><a href="'.$sysURL.'/alpha/controller/ListBusinessObjects.php">Administration Home Page</a></p><br>';
	}
}

$controller = new view_test_results();

?>