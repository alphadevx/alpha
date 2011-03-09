<?php

// include the config file
if(!isset($config))
	require_once '../util/AlphaConfig.inc';
$config = AlphaConfig::getInstance();

require_once $config->get('sysRoot').'alpha/controller/Login.php';

$controller = new Login();
$controller->setName('Login');
$controller->setUnitOfWork(array('Login', 'ListBusinessObjects'));

if(!empty($_POST)) {
	$controller->doPOST($_POST);
}else{
	$controller->doGET($_GET);
}

?>