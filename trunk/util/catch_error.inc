<?php

// load config
if(!isset($config))
	require_once 'configLoader.inc';
$config =&configLoader::getInstance();

require_once $config->get('sysRoot').'alpha/exceptions/PHPException.inc';

/**
 *
 * A pair of static functions for capturing normal PHP errors as a PHPException
 * 
 * @package Alpha Core Util
 * @author John Collins <john@design-ireland.net>
 * @copyright 2008 John Collins
 * @version $Id$
 * 
 */
function catchException($e) {	
	$e->displayError();
}
	
function catchError($error_no, $msg, $file, $line) {
	$e = new PHPException();
	$e->setMessage('PHP error: '.$msg);
	$e->setFile($file);
	 
	throw $e;
}

set_exception_handler('catchException');
set_error_handler('catchError', $config->get('sysPHPErrorLogLevel'));


?>