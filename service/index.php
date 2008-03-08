<?php

// $Id$

/**
 *
 * Description
 * 
 * @package Alpha Admin
 * @author John Collins <john@design-ireland.net>
 * @copyright 2008 John Collins
 * 
 * 
 */

// include the config file
if(!isset($config))
	require_once '../util/configLoader.inc';
$config =&configLoader::getInstance();

require_once $config->get('sysRoot').'alpha/controller/login.php';

$controller = new login();
$controller->set_name(Front_Controller::generate_secure_URL('act=login'));

$controller->set_unit_of_work(array(Front_Controller::generate_secure_URL('act=login'), Front_Controller::generate_secure_URL('act=ListBusinessObjects')));

if(!empty($_POST)) {			
	$controller->handle_post();		
}else{
	$controller->init();
}

?>
