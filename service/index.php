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
$controller->setName(Front_Controller::generate_secure_URL('act=Login'));

$controller->setUnitOfWork(array(Front_Controller::generate_secure_URL('act=Login'), Front_Controller::generate_secure_URL('act=ListBusinessObjects')));

if(!empty($_POST)) {			
	$controller->doPOST($_POST);
}else{
	$controller->doGET($_GET);
}

?>
