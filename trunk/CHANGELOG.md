Alpha Framework Change log
==========================

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