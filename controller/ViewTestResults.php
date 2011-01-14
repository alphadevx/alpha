<?php

// include the config file
if(!isset($config)) {
	require_once '../util/AlphaConfig.inc';
	$config = AlphaConfig::getInstance();
}

// add PHPUnit to the include_path
ini_set('include_path', ini_get('include_path').PATH_SEPARATOR.$config->get('sysRoot').'alpha/lib/PEAR/PHPUnit-3.2.9');
require_once 'PHPUnit/Framework.php';
require_once 'PHPUnit/TextUI/TestRunner.php';

require_once $config->get('sysRoot').'alpha/util/Logger.inc';
require_once $config->get('sysRoot').'alpha/model/person_object.inc';
require_once $config->get('sysRoot').'alpha/model/tag_object.inc';
require_once $config->get('sysRoot').'alpha/controller/AlphaControllerInterface.inc';
require_once $config->get('sysRoot').'alpha/controller/AlphaController.inc';
require_once $config->get('sysRoot').'alpha/tests/Enum_Test.php';
require_once $config->get('sysRoot').'alpha/tests/DEnum_Test.php';
require_once $config->get('sysRoot').'alpha/tests/Sequence_Test.php';
require_once $config->get('sysRoot').'alpha/tests/Boolean_Test.php';
require_once $config->get('sysRoot').'alpha/tests/Date_Test.php';
require_once $config->get('sysRoot').'alpha/tests/Timestamp_Test.php';
require_once $config->get('sysRoot').'alpha/tests/Integer_Test.php';
require_once $config->get('sysRoot').'alpha/tests/Double_Test.php';
require_once $config->get('sysRoot').'alpha/tests/Exceptions_Test.php';
require_once $config->get('sysRoot').'alpha/tests/String_Test.php';
require_once $config->get('sysRoot').'alpha/tests/Text_Test.php';
require_once $config->get('sysRoot').'alpha/tests/Relation_Test.php';
require_once $config->get('sysRoot').'alpha/tests/Tag_Test.php';
require_once $config->get('sysRoot').'alpha/tests/AlphaDAO_Test.php';
require_once $config->get('sysRoot').'alpha/tests/Validator_Test.php';
require_once $config->get('sysRoot').'alpha/tests/AlphaController_Test.php';
require_once $config->get('sysRoot').'alpha/tests/FrontController_Test.php';
require_once $config->get('sysRoot').'alpha/tests/AlphaView_Test.php';

/*
 * we are supressing the display and logging of errors on this page, as we 
 * are only interested in tests that fail and the reasons given for failing
 * 
 */
$config->set('sysTraceLevel', 'FATAL');


/**
 *
 * Controller which displays all of the unit test results
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
class ViewTestResults extends AlphaController implements AlphaControllerInterface {	
	/**
	 * Trace logger
	 * 
	 * @var Logger
	 * @since 1.0
	 */
	private static $logger = null;
	
	/**
	 * The constructor
	 * 
	 * @since 1.0
	 */
	public function __construct() {
		self::$logger = new Logger('ViewTestResults');
		self::$logger->debug('>>__construct()');
		
		// ensure that the super class constructor is called, indicating the rights group
		parent::__construct('Admin');
		
		// set up the title and meta details
		$this->setTitle('Alpha Core Unit Test Results');	
		
		self::$logger->debug('<<__construct');
	}
	
	/**
	 * Handle GET requests
	 * 
	 * @param array $params
	 * @since 1.0
	 */
	public function doGET($params) {
		self::$logger->debug('>>doGET($params=['.print_r($params, true).'])');
		
		echo AlphaView::displayPageHead($this);
		
		$runningTime = 0;
		$testCount = 0;
		
		echo '<h2>Core Complex Data Types</h2>';
		
		//------------------------------------------------
		echo '<h3>Enum:</h3>';
		
		$suite = new PHPUnit_Framework_TestSuite();
		$suite->addTestSuite('Enum_Test');
		$result = $suite->run();
		$runningTime+=$result->time();
		$testCount+=$result->count();
				
		$this->printTestResult($result);
		
		echo '<p>Running time: '.$runningTime.'</p>';
		
		//------------------------------------------------
		echo '<h3>DEnum:</h3>';
		
		$suite = new PHPUnit_Framework_TestSuite();
		$suite->addTestSuite('DEnum_Test');
		$result = $suite->run();
		$runningTime+=$result->time();
		$testCount+=$result->count();
				
		$this->printTestResult($result);
		
		echo '<p>Running time: '.$runningTime.'</p>';
		
		//------------------------------------------------
		echo '<h3>Sequence:</h3>';
		
		$suite = new PHPUnit_Framework_TestSuite();
		$suite->addTestSuite('Sequence_Test');
		$result = $suite->run();
		$runningTime+=$result->time();
		$testCount+=$result->count();
				
		$this->printTestResult($result);
		
		echo '<p>Running time: '.$runningTime.'</p>';
		
		//------------------------------------------------
		echo '<h3>Boolean:</h3>';
		
		$suite = new PHPUnit_Framework_TestSuite();
		$suite->addTestSuite('Boolean_Test');
		$result = $suite->run();
		$runningTime+=$result->time();
		$testCount+=$result->count();
				
		$this->printTestResult($result);
		
		echo '<p>Running time: '.$runningTime.'</p>';
		
		//------------------------------------------------
		echo '<h3>Date:</h3>';
		
		$suite = new PHPUnit_Framework_TestSuite();
		$suite->addTestSuite('Date_Test');
		$result = $suite->run();
		$runningTime+=$result->time();
		$testCount+=$result->count();
				
		$this->printTestResult($result);
		
		echo '<p>Running time: '.$runningTime.'</p>';
		
		//------------------------------------------------
		echo '<h3>Timestamp:</h3>';
		
		$suite = new PHPUnit_Framework_TestSuite();
		$suite->addTestSuite('Timestamp_Test');
		$result = $suite->run();
		$runningTime+=$result->time();
		$testCount+=$result->count();
				
		$this->printTestResult($result);
		
		echo '<p>Running time: '.$runningTime.'</p>';
		
		//------------------------------------------------
		echo '<h3>Integer:</h3>';
		
		$suite = new PHPUnit_Framework_TestSuite();
		$suite->addTestSuite('Integer_Test');
		$result = $suite->run();
		$runningTime+=$result->time();
		$testCount+=$result->count();
				
		$this->printTestResult($result);
		
		echo '<p>Running time: '.$runningTime.'</p>';
		
		//------------------------------------------------
		echo '<h3>Double:</h3>';
		
		$suite = new PHPUnit_Framework_TestSuite();
		$suite->addTestSuite('Double_Test');
		$result = $suite->run();
		$runningTime+=$result->time();
		$testCount+=$result->count();
				
		$this->printTestResult($result);
		
		echo '<p>Running time: '.$runningTime.'</p>';
		
		//------------------------------------------------
		echo '<h3>String:</h3>';
		
		$suite = new PHPUnit_Framework_TestSuite();
		$suite->addTestSuite('String_Test');
		$result = $suite->run();
		$runningTime+=$result->time();
		$testCount+=$result->count();
				
		$this->printTestResult($result);
		
		echo '<p>Running time: '.$runningTime.'</p>';
		
		//------------------------------------------------
		echo '<h3>Text:</h3>';
		
		$suite = new PHPUnit_Framework_TestSuite();
		$suite->addTestSuite('Text_Test');
		$result = $suite->run();
		$runningTime+=$result->time();
		$testCount+=$result->count();
				
		$this->printTestResult($result);
		
		echo '<p>Running time: '.$runningTime.'</p>';
		
		//------------------------------------------------
		echo '<h3>Relation:</h3>';
		
		$suite = new PHPUnit_Framework_TestSuite();
		$suite->addTestSuite('Relation_Test');
		$result = $suite->run();
		$runningTime+=$result->time();
		$testCount+=$result->count();
				
		$this->printTestResult($result);
		
		echo '<p>Running time: '.$runningTime.'</p>';
		
		//------------------------------------------------
		echo '<h3>Exception Handling:</h3>';
		
		$suite = new PHPUnit_Framework_TestSuite();
		$suite->addTestSuite('Exceptions_Test');
		$result = $suite->run();
		$runningTime+=$result->time();
		$testCount+=$result->count();
				
		$this->printTestResult($result);
		
		echo '<p>Running time: '.$runningTime.'</p>';
		
		//------------------------------------------------
		echo '<h3>Tag:</h3>';
		
		$suite = new PHPUnit_Framework_TestSuite();
		$suite->addTestSuite('Tag_Test');
		$result = $suite->run();
		$runningTime+=$result->time();
		$testCount+=$result->count();
				
		$this->printTestResult($result);
		
		echo '<p>Running time: '.$runningTime.'</p>';
		
		//------------------------------------------------
		echo '<h3>MySQL AlphaDAO:</h3>';
		
		$suite = new PHPUnit_Framework_TestSuite();
		$suite->addTestSuite('AlphaDAO_Test');
		$result = $suite->run();
		$runningTime+=$result->time();
		$testCount+=$result->count();
				
		$this->printTestResult($result);
		
		echo '<p>Running time: '.$runningTime.'</p>';
		
		//------------------------------------------------
		echo '<h3>Validator helper:</h3>';
		
		$suite = new PHPUnit_Framework_TestSuite();
		$suite->addTestSuite('Validator_Test');
		$result = $suite->run();
		$runningTime+=$result->time();
		$testCount+=$result->count();
				
		$this->printTestResult($result);
		
		echo '<p>Running time: '.$runningTime.'</p>';
		
		//------------------------------------------------
		echo '<h3>AlphaController:</h3>';
		
		$suite = new PHPUnit_Framework_TestSuite();
		$suite->addTestSuite('AlphaController_Test');
		$result = $suite->run();
		$runningTime+=$result->time();
		$testCount+=$result->count();
				
		$this->printTestResult($result);
		
		echo '<p>Running time: '.$runningTime.'</p>';
		
		//------------------------------------------------
		echo '<h3>FrontController:</h3>';
		
		$suite = new PHPUnit_Framework_TestSuite();
		$suite->addTestSuite('FrontController_Test');
		$result = $suite->run();
		$runningTime+=$result->time();
		$testCount+=$result->count();
				
		$this->printTestResult($result);
		
		echo '<p>Running time: '.$runningTime.'</p>';
		
		//------------------------------------------------
		echo '<h3>AlphaView:</h3>';
		
		$suite = new PHPUnit_Framework_TestSuite();
		$suite->addTestSuite('AlphaView_Test');
		$result = $suite->run();
		$runningTime+=$result->time();
		$testCount+=$result->count();
				
		$this->printTestResult($result);
		
		echo '<p>Running time: '.$runningTime.'</p>';
		
		echo '<h3>Total tests ran: '.$testCount.'</h3>';
		echo '<h3>Total running time: '.$runningTime.'</h3>';
		
		echo AlphaView::displayPageFoot($this);
		self::$logger->debug('<<doGET');
	}
	
	/**
	 * Handle POST requests
	 * 
	 * @param array $params
	 * @since 1.0
	 */
	public function doPOST($params) {
		self::$logger->debug('>>doPOST($params=['.print_r($params, true).'])');
		
		self::$logger->debug('<<doPOST');
	}
	
	/**
	 * Prints the test result HTML & CSS for the passed PHPUnit test result
	 * 
	 * @param PHPUnit_Framework_TestResult $result
	 * @since 1.0
	 */
	private function printTestResult($result) {
		if($result->wasSuccessful()) {
			echo '<div class="ui-state-highlight ui-corner-all" style="padding: 0pt 0.7em;">';
			echo '<p><span class="ui-icon ui-icon-info" style="float: left; margin-right: 0.3em;"></span>';
			echo '<strong>Success</strong><pre>';
		}else{
			echo '<div class="ui-state-error ui-corner-all" style="padding: 0pt 0.7em;">'; 
			echo '<p><span class="ui-icon ui-icon-alert" style="float: left; margin-right: 0.3em;"></span>';
			echo '<strong>Fail</strong><pre>';
		}
			
		$report = new PHPUnit_TextUI_ResultPrinter();		
		$report->printResult($result);
		echo '</pre></p></div>';
	}
}

// now build the new controller if this file is called directly
if ('ViewTestResults.php' == basename($_SERVER['PHP_SELF'])) {
	$controller = new ViewTestResults();
	
	if(!empty($_POST)) {			
		$controller->doPOST($_POST);
	}else{
		$controller->doGET($_GET);
	}
}

?>