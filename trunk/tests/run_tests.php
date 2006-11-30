<?php
/**
 *
 * Runs all test cases for the framework code
 * 
 * @package SimpleMVC_Tests
 * @author John Collins <john@design-ireland.net>
 * @copyright 2005 John Collins
 * 
 * 
 */
 
require_once 'Controller_Tests.php';
require_once 'DAO_Tests.php';
require_once 'Type_Tests.php';
require_once 'PHPUnit.php';

// include the config file
require_once '../config/config.conf';

?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<title><?= $sysTitle ?></title>
<meta name="Keywords" content="">
<meta name="Description" content="">
<meta name="Author" content="john collins">
<meta name="copyright" content="copyright ">
<meta name="identifier" content="http://<?= $sysURL ?>/">
<meta name="revisit-after" content="7 days">
<meta name="expires" content="never">
<meta name="language" content="en">
<meta name="distribution" content="global">
<meta name="title" content="">
<meta name="robots" content="index,follow">
<meta http-equiv="imagetoolbar" content="no">

<link rel="StyleSheet" type="text/css" href="<?= $sysURL ?>/config/main.css">

</head>
<body>

<h1>Running all PHPUnit Tests for the SimpleMVC Framework</h1>

<h2>Controller_Tests</h2>

<h3>File: <em>/controller/Controller.inc</em></h3>
<?php

$suite  = new PHPUnit_TestSuite("Controller_Tests");
$result = PHPUnit::run($suite);

if($result->wasSuccessful())
	echo '<div class="success">'.$result->toHTML().'</div>';
else
	echo '<div class="warning">'.$result->toHTML().'</div>';

?>

<h2>DAO_Tests</h2>

<h3>File: <em>/model/mysql_DAO.inc</em></h3>
<?php

$suite  = new PHPUnit_TestSuite("DAO_Tests");
$result = PHPUnit::run($suite);

if($result->wasSuccessful())
	echo '<div class="success">'.$result->toHTML().'</div>';
else
	echo '<div class="warning">'.$result->toHTML().'</div>';

?>

<h2>Type_Tests</h2>

<?php

$suite  = new PHPUnit_TestSuite("Type_Tests");
$result = PHPUnit::run($suite);

if($result->wasSuccessful())
	echo '<div class="success">'.$result->toHTML().'</div>';
else
	echo '<div class="warning">'.$result->toHTML().'</div>';

?>
</body>
</html>
