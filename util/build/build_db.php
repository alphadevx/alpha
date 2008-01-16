<?php

/**
* Make Tables
* 
* Builds all of the database tables for the business objects.
* @package Alpha Util
* @author John Collins <john@design-ireland.net>
* @copyright 2006 John Collins
* @todo
* This script must be secured for admin use only!
*
*/

// include the config file
if(!isset($config))
	require_once '../../util/configLoader.inc';
$config =&configLoader::getInstance();

// include the database connection file
include("../../../alpha/util/db_connect.inc");

// include the person class file
include("../../model/person_object.inc");

// include the article class file
include("../../model/article_object.inc");

$tmpPerson = new person_object();

echo "Attempting to build table ".$tmpPerson->TABLE_NAME." for class person : \n";

$result = $tmpPerson->make_table();

$tmpPerson->set("email", "john@design-ireland.net");
$tmpPerson->set("displayname", "Admin");
$tmpPerson->set_password("password");
$tmpPerson->set_access_level("Administrator");
$tmpPerson->save_object();

if($result)
	echo "Successfully re-created the database table ".$tmpPerson->TABLE_NAME."\n";
else
	echo "QUERY FAILED : ".$tmpPerson->last_query."\n";
	
$tmpArticle = new article_object();

echo "Attempting to build table ".$tmpArticle->TABLE_NAME." for class article : \n";

$result = $tmpArticle->make_table();

if($result)
	echo "Successfully re-created the database table ".$tmpArticle->TABLE_NAME."\n";
else
	echo "QUERY FAILED : ".$tmpArticle->last_query."\n";	

$tmpDEnum = new DEnum();

echo "Attempting to build table ".$tmpDEnum->TABLE_NAME." for class article : \n";

$result = $tmpDEnum->make_table();

if($result)
	echo "Successfully re-created the database table ".$tmpDEnum->TABLE_NAME."\n";
else
	echo "QUERY FAILED : ".$tmpDEnum->last_query."\n";	

$tmpDEnumItem = new DEnumItem();

echo "Attempting to build table ".$tmpDEnumItem->TABLE_NAME." for class DEnumItem : \n";

$result = $tmpDEnumItem->make_table();

if($result)
	echo "Successfully re-created the database table ".$tmpDEnumItem->TABLE_NAME."\n";
else
	echo "QUERY FAILED : ".$tmpDEnumItem->last_query."\n";	

?>