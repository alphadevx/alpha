<?php
/**
 *
 * Description
 * 
 * @package Alpha Admin
 * @author John Collins <john@design-ireland.net>
 * @copyright 2006 John Collins
 * 
 * 
 */
 
require_once '../controller/login.php';

$controller = new login();
$controller->set_name('login.php');
$controller->set_unit_of_work(array('/alpha/controller/login.php','/alpha/controller/ListBusinessObjects.php'));

if(!empty($_POST)) {			
	$controller->handle_post();		
}else{
	$controller->init();
}

?>
