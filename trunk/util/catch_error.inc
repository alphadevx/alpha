<?php

// load config
if(!isset($config)) {
	require_once 'AlphaConfig.inc';
	$config = AlphaConfig::getInstance();
}

require_once $config->get('sysRoot').'alpha/exceptions/PHPException.inc';
require_once $config->get('sysRoot').'alpha/util/Logger.inc';

/**
 *
 * A pair of static functions for capturing normal PHP errors and unhandled Alpha exceptions
 * 
 * @package Alpha Core Util
 * @author John Collins <john@design-ireland.net>
 * @copyright 2008 John Collins
 * @version $Id$
 * 
 */
function catchException($e) {
	$logger = new Logger(get_class($e));		
	$logger->error($e->getMessage()."\n [stacktrace]: \n".$e->getTraceAsString());
}
	
function catchError($error_no, $msg, $file, $line) {
	$e = new PHPException();
	$e->setMessage('[PHP error]: '.$msg);
	$e->setFile($file);
	 
	throw $e;
}

set_exception_handler('catchException');
set_error_handler('catchError', $config->get('sysPHPErrorLogLevel'));


?>