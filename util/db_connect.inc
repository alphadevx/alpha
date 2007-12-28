<?php

// $Id: db_connect.inc 93 2007-12-27 01:40:30Z johnc $

/**
* Database Connection File
* 
* Establishes a connection to the main database.  Requires access to config global!
*
* @package Alpha Config
* @author John Collins <john@design-ireland.net>
* @copyright 2007 John Collins
*
*/

$db_username = $config->get('sysDBUsername');
$db_password = $config->get('sysDBPassword');
$db_host = $config->get('sysDBHost');
$db_name = $config->get('sysDB');

$db_link = mysql_connect($db_host, $db_username, $db_password)
	or die("Could not connect to database: " . mysql_error());

// now select $db_name as the current database
mysql_select_db($db_name, $db_link);

?>