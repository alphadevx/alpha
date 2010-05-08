<?php

/**
 *
 * Invokes a login controller for logging in to the admin backend
 * 
 * @package alpha::service
 * @author John Collins <john@design-ireland.net>
 * @copyright 2009 John Collins
 * @version $Id$
 * 
 */

// include the config file
if(!isset($config))
	require_once '../util/configLoader.inc';
$config =&configLoader::getInstance();

require_once $config->get('sysRoot').'alpha/controller/Login.php';

$controller = new Login();
$controller->setName(FrontController::generateSecureURL('act=Login&no-forceframe=true'));
$controller->setUnitOfWork(array(FrontController::generateSecureURL('act=Login&no-forceframe=true'), FrontController::generateSecureURL('act=ListBusinessObjects')));

if(!empty($_POST)) {
	$controller->doPOST($_POST);
}else{
	$controller->doGET($_GET);
}

?>