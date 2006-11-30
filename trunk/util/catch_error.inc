<?php

// $Id$

/**
 *
 * A static funtion for capturing normal PHP errors
 * 
 * @package Alpha Core Error
 * @author John Collins <john@design-ireland.net>
 * @copyright 2006 John Collins
 * 
 * 
 */
 
// set the error reporting level for this script 
error_reporting(E_ALL); 

require_once $sysRoot.'util/handle_error.inc';

function catch_error($error_no, $msg, $file, $line) {
	global $set_sys_root;	
	
	switch ($error_no) {		
		case E_ERROR:
			$type = "ERROR";
		break;
		case E_WARNING:
			$type = "WARNING";
		break;
		case E_NOTICE:
			$type = "NOTICE";
		break;
		case E_USER_ERROR:
			$type = "ERROR";
		break;
		case E_USER_WARNING:
			$type = "WARNING";
		break;
		case E_USER_NOTICE:
			$type = "NOTICE";
		break;	
		default:
			$type = "OTHER";
		break;
	}	
	
	if ($type != "OTHER")
		$error = new handle_error($file, 'PHP error of type '.$type.' on line '.$line.': '.$msg ,'unknown','php');
}

set_error_handler('catch_error');

?>
