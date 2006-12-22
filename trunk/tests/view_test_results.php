<?php

// $Id$

require_once '../../config/config.conf';
require_once $sysRoot.'alpha/model/person_object.inc';
require_once $sysRoot.'alpha/view/person.inc';
require_once $sysRoot.'config/db_connect.inc';
require_once $sysRoot.'alpha/controller/Controller.inc';
require_once $sysRoot.'alpha/tests/Enum_Test.php';
require_once 'PHPUnit.php';

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
		
		
		$this->display_page_foot();
	}	
}

$controller = new view_test_results();

?>