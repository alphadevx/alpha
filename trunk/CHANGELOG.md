Alpha Framework Change log
==========================

Version 1.2: November 15th 2012
-------------------------------

2012-11-12 15:36:46 GMT

#42 - added an APIGen template for Alpha Framework

2012-11-05 23:05:20 GMT

#10 - added support for parsing multiple server names for each environment in the servers.ini file.

2012-11-04 16:35:22 GMT

#5 - added an app release target

2012-10-30 15:48:57 GMT

#41 - fixed a case sensitivity bug while attempting to load the TextBox class.

2012-10-30 15:30:35 GMT

#40 - removed references to NewsObject and BlogEntryObject classes.

2012-10-30 15:08:38 GMT

#5 - added a new build.xml for creating Alpha Framework releases in SVN and release artifacts

2012-10-26 14:52:26 GMT

#5 - adding in a VERSION.txt file to track Alpha release versions, and a revised build.xml file for Phing which is still work in progress.

2012-10-24 23:21:51 GMT

#31 - I have added in the cms.url.title.separator setting to enable the app developer to choose the character they want to use as the space separator in article URLs that contain the article title.

2012-10-23 23:16:09 GMT

#14 - added the security.ip.blacklist.filter.enabled config setting for switching on/off the IP blacklist filter.

2012-10-23 23:03:26 GMT

#8 - added a call to php_uname() in the AlphaConfig::loadConfig() method, to further ensure that the hostname of the current server can be determinined.

2012-10-23 15:06:12 GMT

#14 - added an optional IP filter for blocking bad IP addresses from making requests to the application.

2012-10-23 14:23:23 GMT

#39 - added the cms.allow.print.versions config option to switch on/off links to print versions of articles in the CMS

2012-10-07 17:10:56 GMT

#4 - added the implemented of the createForeignIndex() method to the SQLite3 DAO provider, and added the optional BO parameter to the begin/commit/rollback methods in the AlphaDAO class.

2012-10-01 22:45:38 GMT

#4 - fixed a syntax bug in the SQLite provider class.

2012-10-01 22:27:18 GMT

#18 - fixed a bug that was causing the autoloader to be invoked on the string of the requested URL.

2012-08-10 12:40:11 GMT

#4 - added in the first (draft, untested!) createForeignIndex() implementation in the SQLite3 DAO provider, and updated the makeTable() method there to create foreign keys on table creation (the only way that SQLite3 supports).

2012-08-05 20:59:16 GMT

The Image class now sets the filename param in calls to imagepng() and imagejpg()

2012-08-05 20:57:34 GMT

#16 - fixed the path to the KPI logs

2012-08-05 20:56:14 GMT

#16 - fixed the path to the search controller log

2012-08-04 22:07:27 GMT

Fixed a bug where the ViewArticleTitle controller was not loading the article content up front.

2012-08-04 22:06:23 GMT

Fixed a bug in the path to the KPI logs

2012-08-04 15:36:54 GMT

#4 - all existing unit tests are now passing while using SQLite3.

2012-08-03 23:37:11 GMT

#4 - the rollback() method in the SQLite3 provider now silently swallows complaints from the database that you cannot rollback due to a transaction not being in progress.

2012-08-03 22:47:25 GMT

#4 - added implementation of the createUniqueIndex() method in the SQLite3 DAO provider, and fixed some bugs with the loadAllByAttributes() method in the same class.

2012-08-02 22:41:29 GMT

#4 - fixing broken unit tests when using SQLite3.

2012-08-02 21:55:24 GMT

#4 - fixed a bug in the save method of the SQLite3 DAO provider that resulted in the version_num not being incremented on updates.

2012-08-01 22:32:49 GMT

Updated the error handler for uncaught PHP exceptions to also log the source file name.

2012-08-01 22:31:51 GMT

#4 - fixed the result set looping in the AlphaDAOProviderSQLite::loadAllByAttribute() method.

2012-07-31 23:08:19 GMT

#4 - improved the error handling in the constructor of the DEnum class to handle installation scenarios more safely when the DEnum table may not exist or may not be populated yet.

2012-07-31 23:06:01 GMT

#4 - fixed bug in binding params to SQLite3 insert/update statements, and fixed a bug with the creation of the OID auto-increment primary key on each SQLite3 table.

2012-07-31 23:02:28 GMT

#4 - AlphaDAO::setEnumOptions() now handles NotImplementedException exceptions coming back from the SQLite3 provider by logging them as warnings.

2012-07-30 23:50:59 GMT

#4 - fixed syntax bugs in the getIndexes() and makeTable() methods, and coverted some database calls to exec() from query() where a result set is not expected.

2012-07-30 23:47:55 GMT

Fixed bug in AlphaDAO::begin() which was actually calling the commit() rather than begin() method in the provider.

2012-07-30 23:46:18 GMT

Fixed invalid path to the logs dir in the Install controller

2012-07-29 22:15:40 GMT

#16 - added in the app.file.store.dir setting to allow us to have the data files stored outside of the web application deployment directory.

2012-07-29 18:48:54 GMT

#30 - re-ordered the settings in the config files, to group together related settings under related headings.

2012-07-29 18:07:07 GMT

#30 - removed the "sys" prefix from the config file settings, and renamed each setting to a namespaced version with a dot seperator.  Mapping of old-to-new config values in the comment here: http://www.assembla.com/spaces/alpha-framework/tickets/30

2012-07-24 21:36:02 GMT

#4 - fixed a syntax bug with the AlphaDAOProviderSQLite::makeHistoryTable() method

2012-07-24 16:07:04 GMT

#4 - added setBO($BO) to AlphaDAOProviderSQLite.

2012-07-24 15:51:26 GMT

#4 - added implementations of begin/commit/rollback to the SQLite DAO provider.

2012-07-05 11:17:41 GMT

#4 - Added initial implementations for reload(), checkRecordExists(), and isTableOverloaded() to the AlphaDAOProviderSQLite provider class.

2012-07-03 10:23:42 GMT

#4 - added initial implementations of AlphaDAOProviderSQLite::getIndexes() and AlphaDAOProviderSQLite::checkIndexes()

2012-06-28 10:36:09 GMT

#4 - Added the AlphaDAOProviderSQLite::findMissingFields() method implementation

2012-06-21 12:04:31 GMT

#4 - added AlphaDAOProviderSQLite::checkTableNeedsUpdate() implementation

2012-06-20 12:22:55 GMT

#4 - added implementations for the methods for checking that tables exists to the SQLite DAO provider

2012-06-20 11:20:13 GMT

#4 - added implementations for getMAX(), getCount(), and getHistoryCount() to the AlphaDAOProviderSQLite class

2012-06-20 10:35:35 GMT

#4 - added the dropTable() and addProperty() implementations to AlphaDAOProviderSQLite.inc

2012-06-19 11:59:06 GMT

#4 - added the makeTable(), makeHistoryTable(), and rebuildTable() implementations to the SQLite DAO provider class.

2012-06-18 10:17:44 GMT

#4 - added implementations for saveHistory(), delete(), and getVersion() to the SQLite DAO provider class.

2012-06-15 12:16:17 GMT

#4 - added the AlphaDAOProviderSQLite::saveAttribute() implementation

2012-06-15 11:12:25 GMT

#4 - added the initial cut of the save() method to the SQLite DAO provider implementation.

2012-06-13 11:11:39 GMT

#4 - Adding implementations of loadAllByDayUpdated(...) and loadAllFieldValuesByAttribute(...) to AlphaDAOProviderSQLite

2012-06-12 14:47:33 GMT

#4 - added the SQLite DAO provider implementations of loadAllByAttribute() and loadAllByAttributes().

2012-06-11 10:47:30 GMT

#4 - added the SQLite implemenations of the loadByAttribute() and loadAll() methods to it's DAO provider class.

2012-05-31 11:48:32 GMT

#4 - added the load() method to the AlphaDAOProviderSQLite class

2012-05-30 09:57:32 GMT

#4 - added the AlphaDAOProviderSQLite::query() method implementation

2012-05-29 15:55:06 GMT

#4 - added implementations to AlphaDAOProviderSQLite for disconnect() and getLastDatabaseError()

2012-05-29 15:41:41 GMT

#4 - added the AlphaDAOProviderSQLite::getConnection() method, along with the new sysDBFilePath configuration property

2012-05-28 16:23:57 GMT

#4 - added in a stub implementation of the AlphaDAOProviderSQLite class, and the NotImplementedException class for handling when a method is not implemented by a provider.

2012-05-16 14:49:36 GMT

#2 - the date format used in the names of the backup directories and file names has changed from dd-mm-yyyy to yyyy-mm-dd

2012-05-10 13:58:19 GMT

#3 - added the AlphaDAO::getHistoryCount() method for returning a count of the history records for the current DAO

2012-05-09 12:32:55 GMT

#3 - added the AlphaDAO::saveHistory() method, which is called internally in the model layer when a business object has been flagged for history retention.

2012-05-08 11:51:46 GMT

#3 - added code to the model layer for checking for the existance of the _history tables, and creating them when they do not exist

2012-05-04 11:01:54 GMT

#12 - added the $provider property to the AlphaView class, along with the AlphaView::setProvider() method to set it to a specific implementation of the AlphaRendererProviderInterface is you want to.

2012-05-02 11:04:19 GMT

#12 - rearranged the folders and files under alpha/view/renderers to support the structure proposed for this ticket, and made any necessary code changes to support this.

2012-05-02 10:26:34 GMT

#12 - removed the old templates directory

2012-05-01 22:17:21 GMT

#12 - added the sysRendererProviderName config parameter.

2012-05-01 22:12:07 GMT

#12 - finished off the remaining todo items in AlphaView to get the class to use the AlphaRendererProviderHTML implementation.

2012-04-25 22:54:26 GMT

#12- AlphaView::display[Update/Error]Message() now use the implementations from the AlphaRendererProviderHTML class.

2012-04-25 22:37:35 GMT

#12 - adding the admin menu HTML fragment to the create/edit article controllers.

2012-04-25 22:28:33 GMT

#12- AlphaView::displayPageFoot() now uses the implementation from the AlphaRendererProviderHTML implementation.

2012-04-24 22:26:19 GMT

#12- AlphaView::displayPageHead() now uses the implementation from the AlphaRendererProviderHTML implementation, and the renderMenu and renderStatus params have been removed from that method.

2012-04-24 22:21:20 GMT

#12 - added in the adminmenu.phtml fragment template that contains the HTML for the backend admin menu, and modified the admin and generic CRUD controllers to use this.

2012-04-24 22:19:22 GMT

#12 - added in the adminmenu.phtml fragment template that contains the HTML for the backend admin menu, and modified the admin and generic CRUD controllers to use this.

2012-02-21 22:08:56 GMT

#12 - the main view methods in AlphaView now inject in the rendering provider (currently only HTML) via the new AlphaRendererProviderFactory

2012-02-21 16:03:53 GMT

#12 - added the remaining methods to AlphaRendererProviderHTML

2012-02-21 15:54:52 GMT

#12 - added the remaining static methods to AlphaRendererProviderHTML

2012-02-17 14:35:22 GMT

#12 - added initial implementation of AlphaRendererProviderHTML::displayPageHead and AlphaRendererProviderHTML::displayPageFoot

2012-02-17 14:26:49 GMT

#12 - added initial implementation of AlphaRendererProviderHTML::adminView

2012-02-17 12:19:55 GMT

#12 - added initial implementation of AlphaRendererProviderHTML::detailedView

2012-02-17 12:16:59 GMT

#12 - added initial implementation of AlphaRendererProviderHTML::listView

2012-02-17 11:58:52 GMT

#12 - added initial implementation of AlphaRendererProviderHTML::editView

2012-02-17 11:36:48 GMT

#12 - added initial implementation of AlphaRendererProviderHTML::createView along with supporting methods

2012-02-16 19:52:05 GMT

#12 - added a stub implementation of AlphaRendererProviderHTML (real implementation to follow in stages)

2012-02-16 19:50:56 GMT

#12 - copied the existing HTML templates to their new location

2012-02-16 19:29:16 GMT

#12 - added in the interface and factory class for the new alpha::view::renderer package

2012-02-14 20:44:40 GMT

#1 - added alpha/util/fitlers to list of dirs to inspect when auto-loading, and added an explicit include to the AlphaErrorHandlers class from AlphaAutoLoader

2012-02-14 20:28:45 GMT

#1 - finished switching the framework over to using the class auto-loader class class

2012-02-12 20:32:21 GMT

#1 - fixed a bug in the auto-loader class AlphaAutoLoader, and started to switch the framework over to using this class

2012-02-12 20:26:24 GMT

Removed an old obsolete version of Markdown from the lib dir

2012-02-10 16:10:03 GMT

#1 - added the first (untested) draft of the class auto-loader

2012-02-01 21:12:54 GMT

Updated license date

2012-02-01 20:03:35 GMT

#9 - Added an abstracted interface for handling code highlighters

2012-01-31 20:39:48 GMT

#9 - removed Geshi from the Alpha repo

2012-01-28 18:15:41 GMT

Removed Update:/Error: prefixes from status messages

2012-01-28 18:14:58 GMT

The StringBox widget no longer renders values from POST for password fields

2012-01-28 18:14:11 GMT

Added CSS rules for JQuery UI checkboxes

2012-01-26 21:40:01 GMT

Changed the page_go.png icon to be an external link arrow icon

2012-01-26 21:38:43 GMT

Booleans now render as fancy checkboxes

2012-01-26 20:20:59 GMT

AlphaDAO::populateFromPost() will accept "on" as a valid Boolean when dealing with checkboxes

2012-01-04 19:21:00 GMT

Fixed a bug in the AlphaDAOProviderMySQL::load() method where the Relation value on a MANY-TO-ONE type was not being set on loading the DAO

2012-01-02 19:35:19 GMT

AlphaView::renderBooleanField() now displays "Yes/No" in the drop-downs, and always renders a data label even when the table tags are not in use

2012-01-02 17:42:56 GMT

Added the IntegrationException class

2012-01-02 17:42:12 GMT

Fixed a bug in the AlphaDAOProviderMySQL::createForeignIndex() method where the $tableName variable was not instantiated when accessed

2012-01-02 16:19:05 GMT

Fixed the REQUIRED_TEXT and REQUIRED_STRING regex to allow a broader set of string chars

2012-01-02 16:01:36 GMT

Fixed the REQUIRED_TEXT and REQUIRED_STRING regex to allow a broader set of string chars

2012-01-02 15:35:03 GMT

Added support for CLI hostname values to be used in server.ini as well as domain names

2012-01-02 15:33:15 GMT

Added the tmhOAuth library for handling Twitter OAuth connections in applications

2012-01-02 15:30:38 GMT

Updated the FrontController class to handle URL params seperated by ? and & as well as / characters

2012-01-02 13:04:38 GMT

Added the AlphaDAO::__wakeup() method to set up a Logger instance on a de-serialized DAO

2012-01-02 12:25:22 GMT

Adding missing SVN ID property

2011-12-13 20:34:36 GMT

Adding in revised 1.1 API docs

2011-12-13 20:23:32 GMT

Checking in updated change log

2011-12-13 20:22:06 GMT

Removing outdated 1.1 API docs

2011-12-13 20:20:32 GMT

Small error logging fix

Version 1.1: December 11th 2011
-------------------------------

2011-12-13 19:52:58 GMT

The TagCloud widget now honours the $limit parameter even when loading the array of tags from the cache

2011-12-08 21:31:01 GMT

Added a conditional check to ensure tags are populated on the ArticleObject class before attempting to render them

2011-12-08 21:30:14 GMT

Added support for the BlogEntryObject class to feeds

2011-12-08 21:25:12 GMT

Adding in some missing Loggers on model classes

2011-12-08 21:18:22 GMT

Added some PHP 5.3 compatibility fixes

2011-12-08 21:15:48 GMT

Adding in some lissing Loggers on model classes

2011-12-08 21:06:18 GMT

Adding missing Id property

2011-12-08 20:56:32 GMT

Adding missing Id property

2011-12-06 20:53:56 GMT

Added a change log

2011-12-04 16:31:50 GMT

Adding the 1.1 API docs

2011-12-04 16:18:50 GMT

Some minor comment changes

2011-12-04 16:18:10 GMT

Incrementing version numbers to 1.1

2011-12-04 16:05:22 GMT

Removed the unused after_checkIndexes_callback() callback

2011-12-04 15:26:05 GMT

Replacing all references to john@design-ireland.net with dev@alphaframework.org in the doc bloc comments

2011-12-04 15:14:05 GMT

Replacing all references to john@design-ireland.net with dev@alphaframework.org in the doc bloc comments

2011-12-04 15:12:54 GMT

Replacing all references to john@design-ireland.net with dev@alphaframework.org in the doc bloc comments

2011-12-04 15:10:55 GMT

Replacing all references to john@design-ireland.net with dev@alphaframework.org in the doc bloc comments

2011-12-04 14:39:39 GMT

Moved the 1.0 docs to api/1.0

2011-12-04 14:30:26 GMT

Fixed broken Javascript IDs on buttons for updating article comments

2011-12-04 14:29:55 GMT

Removed MySQLi dependancies from the getCommentCount() method

2011-12-04 14:29:16 GMT

Fixed broken rel set-ups for votes and comments on articles

2011-12-04 14:05:34 GMT

Added nofollow to the links generated for attachments

2011-12-04 14:04:28 GMT

Removed local MySQLi dependancies

2011-11-26 18:40:30 GMT

Fixed a bug in the AlphaDAO::save() method where a non-existant $this->BO attribute was being accessed

2011-11-26 18:39:32 GMT

Fixed Relation::getRelatedObjects() to return an empty array for ONE-TO-MANY relationships where the ONE side is not populated with an OID value

2011-11-26 14:03:20 GMT

Added the sysCacheProviderName and sysDBProviderName settings

2011-11-26 13:49:58 GMT

Finished tidying up the AlphaDAOProviderInterface to remove methods that are not going to be implemented there, and tidying up the comments in AlphaDAOProviderMySQL to use doc links to the interface doc blocks

2011-11-26 13:47:31 GMT

Moved the MySQL implementation of AlphaDAO::getMAX() to AlphaDAOProviderMySQL::getMAX()

2011-11-26 13:13:42 GMT

Removed the protected AlphaDAO::bindResult() method, as it is now implemented privately as AlphaDAOProviderMySQL::bindResult()

2011-11-26 13:12:04 GMT

Removed the protected AlphaDAO::bindParams() method, as it is now implemented privately as AlphaDAOProviderMySQL::bindParams()

2011-11-26 13:09:35 GMT

Removed the protected AlphaDAO::findOffendingValue() method, as it is now implemented privately as AlphaDAOProviderMySQL::findOffendingValue()

2011-11-23 19:59:33 GMT

Added an exception message to AlphaDAO::getConnection() to indicate that it is deprecated, moved the AlphaDAO::addProperty() and AlphaDAO::findMissingFields() implementations to AlphaDAOProviderMySQL, and removed the protected AlphaDAO::checkIndexes() method which is now implemented as a private method of the AlphaDAOProviderMySQL class.

2011-11-23 19:54:53 GMT

Small logging fix

2011-11-13 15:02:31 GMT

Removing MySQLi API dependancies from the BadRequest::getBadRequestCount() method

2011-11-13 14:51:22 GMT

Added the createUniqueIndex method to the AlphaDAOProviderInterface, and modified all of the unique index methods to support an optional third field name on a composite unique index

2011-11-13 13:53:55 GMT

Fixed the last issue affecting the MySQL DAO tests

2011-11-13 13:51:13 GMT

Updated the checkRecordExists() method to move the implementations out of the AlphaDAO class

2011-11-13 13:43:47 GMT

Updated the delete() method to move the implementations out of the AlphaDAO class

2011-11-13 13:18:04 GMT

Fixed the broken Tags unit tests

2011-11-08 19:44:58 GMT

Made the AlphaDAO::createForeignIndex method public, added it to the AlphaDAOProviderInterface, and moved its MySQL implementation to the AlphaDAOProviderMySQL class

2011-10-04 21:23:47 IST

Fixed the getOptions() and getItemCount() methods to use the new DAO provider API

2011-10-04 21:22:59 IST

Added the getLastDatabaseError() method to the DAO API

2011-10-04 20:55:09 IST

Updated the checkTableNeedsUpdate() and getIndexes() methods to move the implementations out of the AlphaDAO class

2011-09-27 21:15:01 IST

Begun the refactoring at the Model layer to abstract out the hard dependancy on MySQL

2011-09-27 21:12:40 IST

Added a new CustomQueryException class for issues with custom SQL provided to the API

2011-09-15 20:49:29 IST

Added the doTagSearch() method to make extending the Search controller more flexible

2011-09-14 22:15:18 IST

Tags will now also tokenize on the hyphen (-) character

2011-09-13 21:00:48 IST

Fixed a bug in the rendering of the re-create tags buttons on the TagManager screen

2011-09-12 21:31:36 IST

Fixed a broken edit button on the DEnum list view

2011-09-12 20:38:02 IST

Reverted change that removed extra seperator at the end of each line

2011-09-12 20:37:16 IST

Fixed some broken references to AlphaFileUtils

2011-07-23 11:43:44 IST

Added a blog and global copyright nottice

2011-07-21 20:58:07 IST

Added support for opening the Search controller with a GET request without any search query, just to render the search box

2011-07-19 20:38:33 IST

Added cache support to the TagCloud widget

2011-07-18 20:27:57 IST

Added the alpha::util::cache package, which uses dependancy injection to choose the configured cache implemementation at runtime

2011-07-14 19:16:08 IST

Fixed a bug in the AlphaImageUtils::saveImage() method which was attempting to access a parameter that did not exist

2011-07-14 19:13:33 IST

Added another die() condition to the AlphaConfig::loadConfig() method

2011-06-25 12:29:26 IST

Fixed a bug in the logic of the AlphaView::renderTextField method where $tableTags = false

2011-06-18 18:04:44 IST

The AlphaView::renderTextField method now always encodes output with InputFilter::encode when in view mode

2011-06-18 18:03:14 IST

Fixed the logic in the AlphaDAO::loadFromCache method which was not loading the non-object properties from the cache

2011-06-18 16:50:10 IST

Seeting the default HTML doc type to HTML 4.01 Transitional

2011-06-18 14:02:02 IST

Added the maxlength attribute to textarea tags rendered by the TextBox widget

2011-06-18 13:58:13 IST

Changed the OPTIONAL_HTTP_URL rule

2011-06-18 13:52:36 IST

Removed the hard-coded greeting from the end of the message generated by PersonObject::sendMail()

2011-06-13 21:17:02 IST

Added the AlphaKPI::logStep method

2011-05-25 20:37:35 IST

Renamed AlphaKDP to AlphaKPI

2011-05-25 20:33:30 IST

Added the AlphaKDP class for tracking and logging Key Decision Points

2011-05-25 20:31:50 IST

The Logfile class now no longer includes an extra seperator on the end of each line when it is logging

2011-05-21 16:40:28 IST

Added settings for the new system back-up cron task

2011-05-21 16:35:22 IST

Renamed the AlphaFileUtil class to AlphaFileUtils for consistentancy sake, and added the copy and zip methods

2011-05-21 16:34:19 IST

Added a missing include to the AlphaErrorHandlers file

2011-05-21 16:33:29 IST

The AlphaCronManager nows looks at the alpha/tasks dir as well as the tasks dir when looking for cron tasks to run

2011-05-21 16:31:53 IST

Added a backup utils class

2011-05-21 16:31:00 IST

Added a BackupTask for carrying out system backups

2011-04-30 17:27:28 IST

Fixed a bug in the AlphaDAO::loadByAttribute() method where incompletely loaded BOs where being stored in Memcache

2011-04-25 15:59:26 IST

Made the AlphaView::renderEnumField and AlphaView::renderDEnumField methods static

2011-04-23 17:04:33 IST

The ViewArticleFile controller no longer uses the file name in the title of the page rendered

2011-04-23 17:03:44 IST

Removed Memcached access from the AlphaDAO::getVersion() method which should always hit the database directly

2011-04-23 17:02:46 IST

Added support for the "is checked" validation rule when dealing with checkboxes

2011-04-23 15:44:13 IST

Added the saveMessage param to the doPOST method so setting custom saved BO messages

2011-04-22 15:52:36 IST

Detail controller will now only use default title/description/keywords if they are not already set at a child level

2011-04-22 14:07:37 IST

Edit controller will now only user default title/description/keywords if they are not already set at a child level

2011-04-19 20:10:49 IST

Added the optional $filterAll paramter to the AlphaDAO::populateFromPost() method in case you want to force a filter of all user supplied fields

2011-04-07 12:43:37 IST

Adjusted the display of the tags in a view screen to use a table <th>

2011-04-07 12:42:58 IST

Added the standard visibility parameter to the Detail controller constructor

2011-04-07 10:53:24 IST

Small but fix with a logger not being initialised in the RelationLookup class

2011-04-07 09:45:17 IST

Added support for "equal to [fieldname]" expression in validation Javascript

2011-04-06 19:36:06 IST

Removed hard-coded table header width from the StringBox::render method

2011-04-06 15:28:28 IST

Removed hard-coded table name from the TagObject::getPopularTagsArray method

2011-04-06 15:27:45 IST

Made the Search::renderResultList method protected (was previously private)

2011-04-06 11:33:44 IST

Removed custom ui.datapicker JQuery plugin as it was causing breakage after upgrading to JQuery UI 1.8

2011-04-06 11:33:21 IST

Disabled animation effect in the validation JQuery script

2011-04-06 11:32:45 IST

Removed custom ui.datapicker JQuery plugin as it was causing breakage after upgrading to JQuery UI 1.8

2011-04-05 19:03:08 IST

Added the AlphaImageUtils class

2011-04-04 16:27:40 IST

Removed an unrequired message about a forum from the PersonView::displayRegisterForm method

2011-04-04 16:26:46 IST

Removed the "You are logged in as..." message from the AlphaView::displayPageHead method (applications should take care of this)

2011-04-04 16:25:30 IST

Fixed a bug in the REQUIRED_USERNAME reg-ex to prevent it from accepting blank values

2011-04-04 16:24:39 IST

Made the Login::personObject attribute protected

2011-03-30 20:05:44 IST

Adding more AlphaDAO::setLastQuery() calls to child classes for SQL logging

2011-03-30 19:51:06 IST

Added support log logging SQL to the log file

2011-03-30 19:40:21 IST

Added an optimization to ViewArticle::before_displayPageFoot_callback() to ensure that the database is only queried for article votes and comments when configured to display them

2011-03-29 20:14:33 IST

Added the renderStatus parameter to the AlphaView::displayPageHead method

2011-03-28 21:02:44 IST

Fixed a broken .css link in the error message page since upgrading to JQuery UI 1.8

2011-03-28 21:02:07 IST

Fixed a broken .css link in the error message page since upgrading to JQuery UI 1.8

2011-03-28 21:00:49 IST

Made the Login::doLoginAndRedirect() method protected (previously it was private)

2011-03-27 20:06:23 IST

Removing unrequired Javascript calls

2011-03-27 17:18:55 IST

Added optional support for caching business objects to Memcache

2011-03-26 16:22:17 GMT

Adding the cupertino theme for JQuery UI

2011-03-22 20:11:22 GMT

Removing the unused insertImage.js file

2011-03-22 20:11:03 GMT

Ensuring that the validation error messages have a high enough z-index to render above JQuery UI buttons

2011-03-22 20:10:15 GMT

Adding in some more JQuery UI themes, and modifying existing themes to be JQuery UI 1.8 compatible

2011-03-22 20:08:09 GMT

Removed JQuery UI 1.7.2 Javascript file

2011-03-22 20:07:50 GMT

Removed JQuery 1.3.2 Javascript file

2011-03-22 19:48:52 GMT

Fixed the setting of dialog defaults since upgrading to JQuery UI 1.8

2011-03-21 21:19:56 GMT

Upgraded to JQuery 1.5.1 and JQuery UI 1.8.11

2011-03-21 21:17:27 GMT

Modified the Button class to use the button widget from JQuery UI

Version 1.0: March 20th 2011
----------------------------
Initial release of the framework.