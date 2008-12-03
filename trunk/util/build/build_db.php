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

echo "Attempting to build table ".$tmpPerson->getTableName()." for class person : \n";

$result = $tmpPerson->makeTable();

$tmpPerson->set("email", "john@design-ireland.net");
$tmpPerson->set("displayname", "Admin");
$tmpPerson->set("password", crypt("password"));
$tmpPerson->setAccessLevel("Administrator");
$tmpPerson->save();

$tmpDEnum = new DEnum();

echo "Attempting to build table ".$tmpDEnum->getTableName()." for class article : \n";

$tmpDEnum->makeTable();

$tmpDEnumItem = new DEnumItem();

echo "Attempting to build table ".$tmpDEnumItem->getTableName()." for class DEnumItem : \n";

$tmpDEnumItem->makeTable();

$tmpArticle = new article_object();

echo "Attempting to build table ".$tmpArticle->getTableName()." for class article : \n";

$tmpArticle->makeTable();

?>