<?php

// $Id$

/**
* Database Connection File
* 
* Establishes a connection to the main database.  Requires access to config.conf globals!
*
* @package Alpha Config
* @author John Collins <john@design-ireland.net>
* @copyright 2006 John Collins
*
*/

$db_username = "root";
$db_password = "password";
$db_host = "localhost";
$db_name = $sysDB;

$db_link = mysql_connect($db_host, $db_username, $db_password)
	or die("Could not connect to database: " . mysql_error());

// now select $db_name as the current database
mysql_select_db($db_name, $db_link);

?>