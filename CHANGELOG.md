# Change Log

## [2.0.3](https://github.com/alphadevx/alpha/tree/2.0.3) (2016-09-12)
[Full Changelog](https://github.com/alphadevx/alpha/compare/2.0.3-RC.1...2.0.3)

**Implemented enhancements:**

- Add a phpinfo\(\) controller to the admin backend [\#292](https://github.com/alphadevx/alpha/issues/292)
- Update to the latest Bootstrap [\#233](https://github.com/alphadevx/alpha/issues/233)

**Merged pull requests:**

- Release/2.0.3 release [\#301](https://github.com/alphadevx/alpha/pull/301) ([alphadevx](https://github.com/alphadevx))
- \#233 - updated the version number to 2.0.3 [\#299](https://github.com/alphadevx/alpha/pull/299) ([alphadevx](https://github.com/alphadevx))
- Feature/233 update to the latest bootstrap [\#298](https://github.com/alphadevx/alpha/pull/298) ([alphadevx](https://github.com/alphadevx))

## [2.0.3-RC.1](https://github.com/alphadevx/alpha/tree/2.0.3-RC.1) (2016-09-09)
[Full Changelog](https://github.com/alphadevx/alpha/compare/2.0.2...2.0.3-RC.1)

**Merged pull requests:**

- Feature/292 phpinfo controller admin backend [\#297](https://github.com/alphadevx/alpha/pull/297) ([alphadevx](https://github.com/alphadevx))

## [2.0.2](https://github.com/alphadevx/alpha/tree/2.0.2) (2016-08-08)
[Full Changelog](https://github.com/alphadevx/alpha/compare/2.0.2-RC.1...2.0.2)

**Implemented enhancements:**

- Place a warning banner on the admin UI if the admin user password matches what is in the config file [\#291](https://github.com/alphadevx/alpha/issues/291)
- Do not use a default login/password in the config file for installations [\#290](https://github.com/alphadevx/alpha/issues/290)
- Remove MCrypt calls from Alpha [\#286](https://github.com/alphadevx/alpha/issues/286)
- Run a security audit using W3AF [\#232](https://github.com/alphadevx/alpha/issues/232)

**Merged pull requests:**

- Release/2.0.2 release [\#296](https://github.com/alphadevx/alpha/pull/296) ([alphadevx](https://github.com/alphadevx))

## [2.0.2-RC.1](https://github.com/alphadevx/alpha/tree/2.0.2-RC.1) (2016-07-25)
[Full Changelog](https://github.com/alphadevx/alpha/compare/2.0.1...2.0.2-RC.1)

**Merged pull requests:**

- Feature/232 run a security audit using w3 af [\#295](https://github.com/alphadevx/alpha/pull/295) ([alphadevx](https://github.com/alphadevx))
- \#286 - removed MCrypt TripleDES calls from the SecurityUtils class and replaced with OpenSSL AES 265 [\#294](https://github.com/alphadevx/alpha/pull/294) ([alphadevx](https://github.com/alphadevx))
- Feature/291 admin password reset warning banner [\#293](https://github.com/alphadevx/alpha/pull/293) ([alphadevx](https://github.com/alphadevx))

## [2.0.1](https://github.com/alphadevx/alpha/tree/2.0.1) (2016-07-04)
[Full Changelog](https://github.com/alphadevx/alpha/compare/2.0.1-RC.1...2.0.1)

**Implemented enhancements:**

- Add 10 more unit tests [\#277](https://github.com/alphadevx/alpha/issues/277)
- The Logger class should log requested URI where available [\#276](https://github.com/alphadevx/alpha/issues/276)
- Alpha should be able to render responsive images via Alpha\View\Widget [\#269](https://github.com/alphadevx/alpha/issues/269)
- There is no need for the SQLite provider to log the lack of a setEnumOptions implementation at INFO level [\#267](https://github.com/alphadevx/alpha/issues/267)
- Add a TagCloud::getPopTags\(\) method [\#266](https://github.com/alphadevx/alpha/issues/266)

**Fixed bugs:**

- The regex for handling $attachURL in the MarkdownFacade class is error prone [\#275](https://github.com/alphadevx/alpha/issues/275)
- The TimestampTest::testGetTimeAway\(\) unit test is failing [\#274](https://github.com/alphadevx/alpha/issues/274)
- Fix Composer config issue affecting the Travis CI build [\#273](https://github.com/alphadevx/alpha/issues/273)
- Child classes of Alpha\Model\Article should be editable via the CMS [\#272](https://github.com/alphadevx/alpha/issues/272)
- ActiveRecord::getBOClassNames\(\) needs to filter out any traits that might be placed in the model packages [\#271](https://github.com/alphadevx/alpha/issues/271)
- Fix broken links rendered by SearchController::renderPageLinks\(\) [\#268](https://github.com/alphadevx/alpha/issues/268)
- Alpha fails silently if it cannot write to alpha.log [\#170](https://github.com/alphadevx/alpha/issues/170)

**Closed issues:**

- Prep 2.0.1 release [\#287](https://github.com/alphadevx/alpha/issues/287)
- Address deprecation warning in File\_Find usage from the Inspector class [\#280](https://github.com/alphadevx/alpha/issues/280)
- Investigate error while attempting to create SQLite indexes [\#82](https://github.com/alphadevx/alpha/issues/82)

**Merged pull requests:**

- Release/2.0.1 release [\#289](https://github.com/alphadevx/alpha/pull/289) ([alphadevx](https://github.com/alphadevx))
- Release/2.0.1 release [\#288](https://github.com/alphadevx/alpha/pull/288) ([alphadevx](https://github.com/alphadevx))
- \#82 - added the optional indexName param to the ActiveRecord::createF… [\#282](https://github.com/alphadevx/alpha/pull/282) ([alphadevx](https://github.com/alphadevx))
- Feature/277 add 10 more unit tests [\#281](https://github.com/alphadevx/alpha/pull/281) ([alphadevx](https://github.com/alphadevx))

## [2.0.1-RC.1](https://github.com/alphadevx/alpha/tree/2.0.1-RC.1) (2016-05-08)
[Full Changelog](https://github.com/alphadevx/alpha/compare/2.0.0...2.0.1-RC.1)

**Merged pull requests:**

- \#170 - added better error checking to the file I/O calls in the LogPr… [\#283](https://github.com/alphadevx/alpha/pull/283) ([alphadevx](https://github.com/alphadevx))
- Feature/272 child classes of article should be editable via the cms [\#279](https://github.com/alphadevx/alpha/pull/279) ([alphadevx](https://github.com/alphadevx))
- feature/276 the logger class should log requested uri where available [\#278](https://github.com/alphadevx/alpha/pull/278) ([alphadevx](https://github.com/alphadevx))

## [2.0.0](https://github.com/alphadevx/alpha/tree/2.0.0) (2016-02-07)
[Full Changelog](https://github.com/alphadevx/alpha/compare/1.2.4...2.0.0)

**Implemented enhancements:**

- Refactor the Logger class to remove globals $\_SESSION and $\_SERVER [\#184](https://github.com/alphadevx/alpha/issues/184)
- Upgrade Alpha to use Markdown 1.6 [\#248](https://github.com/alphadevx/alpha/issues/248)
- Calendar widget should highlight today by default [\#230](https://github.com/alphadevx/alpha/issues/230)
- The InstallController should create the src/Model and src/View directories [\#217](https://github.com/alphadevx/alpha/issues/217)
- Replace existing legacy CRUD controllers with ActiveRecordController [\#211](https://github.com/alphadevx/alpha/issues/211)
- Use password\_hash\(\) and related functions to handle passwords [\#206](https://github.com/alphadevx/alpha/issues/206)
- Add support for X-HTTP-Method-Override and \_METHOD [\#204](https://github.com/alphadevx/alpha/issues/204)
- Rewrite the user documentation and sample code for Alpha 2.0 release [\#203](https://github.com/alphadevx/alpha/issues/203)
- Improve the validation rule of the Boolean class [\#202](https://github.com/alphadevx/alpha/issues/202)
- eval\(\) should not be used [\#196](https://github.com/alphadevx/alpha/issues/196)
- Add /service/admin/ route to FrontController [\#191](https://github.com/alphadevx/alpha/issues/191)
- Ensure that all remaining references to \_GET \_POST \_REQUEST \_SESSION \_SERVER are removed from the code base [\#186](https://github.com/alphadevx/alpha/issues/186)
- Add an ActiveRecordController [\#178](https://github.com/alphadevx/alpha/issues/178)
- Write a new index.php to Bootstrap the FrontController [\#175](https://github.com/alphadevx/alpha/issues/175)
- Updated existing controllers to accept a Request and return a Response [\#171](https://github.com/alphadevx/alpha/issues/171)
- Add Ansible playbook to release for deploying apps [\#169](https://github.com/alphadevx/alpha/issues/169)
- Remove generated API docs from release to reduce package size [\#168](https://github.com/alphadevx/alpha/issues/168)
- Increase unit test coverage to 70% [\#165](https://github.com/alphadevx/alpha/issues/165)
- Add namespaces to Alpha [\#115](https://github.com/alphadevx/alpha/issues/115)

**Fixed bugs:**

- The edit tag link on the list of tags on the edit article screen is broken [\#263](https://github.com/alphadevx/alpha/issues/263)
- The back to record button on the tags screen is broken when visited from edit article [\#262](https://github.com/alphadevx/alpha/issues/262)
- Some of the HTTP security filters are failing due to using the old BONotFoundException class [\#261](https://github.com/alphadevx/alpha/issues/261)
- The "Clear cache" button in the admin backend is mistakenly removing directories from the cache directory [\#260](https://github.com/alphadevx/alpha/issues/260)
- Images rendered via Markdown syntax are being rendered as black boxes [\#259](https://github.com/alphadevx/alpha/issues/259)
- The Back to List button on the article edit screen is broken [\#258](https://github.com/alphadevx/alpha/issues/258)
- HighlightProviderLuminous is throwing a fatal error in the CMS [\#257](https://github.com/alphadevx/alpha/issues/257)
- Code blocks are no longer rendering correctly in the CMS [\#256](https://github.com/alphadevx/alpha/issues/256)
- While viewing a CMS article on the public site I can incorrectly see the admin menu [\#254](https://github.com/alphadevx/alpha/issues/254)
- The standard CMS header is not displaying on articles [\#252](https://github.com/alphadevx/alpha/issues/252)
- Link to edit DEnum items in the admin section appears to be broken [\#250](https://github.com/alphadevx/alpha/issues/250)
- The HTML cache does not appear to be filling up on a new install [\#249](https://github.com/alphadevx/alpha/issues/249)
- The IndexController should set $allowCSSOverrides to true [\#247](https://github.com/alphadevx/alpha/issues/247)
- The default index.phtml template has some invalid markup [\#246](https://github.com/alphadevx/alpha/issues/246)
- CI not building due to APCu now depending on PHP 7 [\#234](https://github.com/alphadevx/alpha/issues/234)
- Validating a HTTPS URL is not working [\#227](https://github.com/alphadevx/alpha/issues/227)
- Validation is not working on TextBox instances [\#226](https://github.com/alphadevx/alpha/issues/226)
- Requests to /a\* are being routed to the ArticleController [\#225](https://github.com/alphadevx/alpha/issues/225)
- The 403 error pages are not rendering correctly [\#221](https://github.com/alphadevx/alpha/issues/221)
- security.encrypt.http.fieldnames support in Alpha 2.0 not working [\#220](https://github.com/alphadevx/alpha/issues/220)
- Support for overloaded active record tables appears to be broken [\#219](https://github.com/alphadevx/alpha/issues/219)
- The DateBox is not rendering data labels in create and edit views [\#216](https://github.com/alphadevx/alpha/issues/216)
- Fix issues with the installer for 2.0 release [\#215](https://github.com/alphadevx/alpha/issues/215)
- Ensure that all controller tests are using access rights correctly [\#207](https://github.com/alphadevx/alpha/issues/207)
- TCPDF generating a blank PDF since upgrading to version 6 [\#205](https://github.com/alphadevx/alpha/issues/205)
- Fix broken navigation in the admin backend [\#192](https://github.com/alphadevx/alpha/issues/192)
- Fix remaining issues with the InstallController [\#190](https://github.com/alphadevx/alpha/issues/190)
- Fix support for secure URIs in FrontController [\#189](https://github.com/alphadevx/alpha/issues/189)
- Remove the $mode param from Controller::getCustomControllerName\(\) [\#188](https://github.com/alphadevx/alpha/issues/188)

**Closed issues:**

- Add a logout button to the new admin menu [\#135](https://github.com/alphadevx/alpha/issues/135)
- ClientBlacklistFilter will have to skip clients with no user agent string provided [\#134](https://github.com/alphadevx/alpha/issues/134)
- Add a unit test for AlphaValidator::isBase64\(\) [\#133](https://github.com/alphadevx/alpha/issues/133)
- Add a unit test for IPBlacklistFilter [\#132](https://github.com/alphadevx/alpha/issues/132)
- Add a unit test for AlphaView::loadTemplateFragment\(\) [\#131](https://github.com/alphadevx/alpha/issues/131)
- Update the sample documentation [\#125](https://github.com/alphadevx/alpha/issues/125)
- Use Bootstrap to generate the default HTML views [\#124](https://github.com/alphadevx/alpha/issues/124)
- Consider using Sir Trevor as the default Markdown editor [\#123](https://github.com/alphadevx/alpha/issues/123)
- The cron tasks are writting to the wrong log directory [\#122](https://github.com/alphadevx/alpha/issues/122)
- The Image widget is creating images in the app root [\#121](https://github.com/alphadevx/alpha/issues/121)
- The app.log.error.mail.address currently has no effect [\#120](https://github.com/alphadevx/alpha/issues/120)
- Add a util class for managing the built-in HTTP server in PHP 5.4 [\#119](https://github.com/alphadevx/alpha/issues/119)
- Some methods are wrongly defined on the AlphaView class as static [\#118](https://github.com/alphadevx/alpha/issues/118)
- Rendering of Booleans as checkboxes is not working correctly when security.encrypt.http.fieldnames = true [\#106](https://github.com/alphadevx/alpha/issues/106)
- The hidden version\_num field needs to have its fieldname encrypted when security.encrypt.http.fieldnames = true [\#105](https://github.com/alphadevx/alpha/issues/105)
- When security.encrypt.http.fieldnames is enabled only decrypted fieldnames should be passed to the controllers [\#104](https://github.com/alphadevx/alpha/issues/104)
- A call to AlphaDAOProviderMySQL::checkTableExists\(\) may not get a result set [\#103](https://github.com/alphadevx/alpha/issues/103)
- Add a AlphaDAO::populateFromArray\($hasArray\) method [\#96](https://github.com/alphadevx/alpha/issues/96)
- Ensure that the model can support MANY-TO-MANY relationships where a DAO is related to itself [\#95](https://github.com/alphadevx/alpha/issues/95)
- Add X-Frame-Options header support to Alpha [\#92](https://github.com/alphadevx/alpha/issues/92)
- Saving DEnums in the admin UI is not displaying the success status message [\#91](https://github.com/alphadevx/alpha/issues/91)
- The AlphaDAOProviderMySQL::loadAllByAttribute\(\) method is ignoring the limit param when the start = 0 [\#90](https://github.com/alphadevx/alpha/issues/90)
- HTML for the admin menu is being renderered outside of the \<html\> tag [\#88](https://github.com/alphadevx/alpha/issues/88)
- Review sample .htaccess file to ensure that directory listing is switched off in Apache [\#87](https://github.com/alphadevx/alpha/issues/87)
- Set the HttpOnly flag on the PHPSESSID cookie [\#85](https://github.com/alphadevx/alpha/issues/85)
- Add a user action log to Alpha [\#84](https://github.com/alphadevx/alpha/issues/84)
- Review write permissions set on folders in the store during installation [\#83](https://github.com/alphadevx/alpha/issues/83)
- Security field methods in AlphaController should not be using MD5 [\#80](https://github.com/alphadevx/alpha/issues/80)
- Obfuscate HTML form field names [\#78](https://github.com/alphadevx/alpha/issues/78)
- Ratproxy reporting "MIME type mismatch on binary file" when downloading a zip file attachment [\#77](https://github.com/alphadevx/alpha/issues/77)
- Unit tests won't run if PHPUnit is already on the include path [\#71](https://github.com/alphadevx/alpha/issues/71)
- Add a test for AlphaDAOProviderFactory::getInstance\(\) [\#70](https://github.com/alphadevx/alpha/issues/70)
- Add  unit test for AlphaDAO::cast\(\) [\#69](https://github.com/alphadevx/alpha/issues/69)
- Add a test for AlphaDAO::getFriendlyClassName\(\) [\#68](https://github.com/alphadevx/alpha/issues/68)
- Add test for AlphaDAO::hasAttribute\(\) [\#67](https://github.com/alphadevx/alpha/issues/67)
- Add a test for AlphaDAO::addToCache\(\) [\#66](https://github.com/alphadevx/alpha/issues/66)
- Add a test for AlphaDAO::removeFromCache\(\) [\#65](https://github.com/alphadevx/alpha/issues/65)
- Add a test for AlphaDAO::loadFromCache\(\) [\#64](https://github.com/alphadevx/alpha/issues/64)
- Add a test for Timestamp::getUnixValue\(\) [\#62](https://github.com/alphadevx/alpha/issues/62)
- Add a unit test for RelationLookup::setValue\(\) [\#61](https://github.com/alphadevx/alpha/issues/61)
- Add a unit test for RelationLookup::loadAllByAttribute\(\) [\#60](https://github.com/alphadevx/alpha/issues/60)
- Add a unit test for RelationLookup::getTableName\(\) [\#59](https://github.com/alphadevx/alpha/issues/59)
- Add a unit test for RelationLookup::\_\_construct\(\) [\#58](https://github.com/alphadevx/alpha/issues/58)
- Add a unit test for Relation::getSide\(\) [\#57](https://github.com/alphadevx/alpha/issues/57)
- Add a unit test for Relation::getRelatedObject\(\) [\#56](https://github.com/alphadevx/alpha/issues/56)
- Add a unit test for Relation::getRelatedObjects\(\) [\#55](https://github.com/alphadevx/alpha/issues/55)
- Add more coverage for Relation::getRelatedClassDisplayFieldValue\(\) [\#54](https://github.com/alphadevx/alpha/issues/54)
- Add a unit test for Relation::getRelatedClass\(\) [\#53](https://github.com/alphadevx/alpha/issues/53)
- Add a unit test for Integer::zeroPad\(\) [\#52](https://github.com/alphadevx/alpha/issues/52)
- Add more test cases to Date\_Test::testPopulateFromString\(\) including error invocation [\#51](https://github.com/alphadevx/alpha/issues/51)
- Add a unit test for Date::getUSValue\(\) [\#50](https://github.com/alphadevx/alpha/issues/50)
- Add a unit test for Date::getUnixValue\(\) [\#49](https://github.com/alphadevx/alpha/issues/49)
- Add a unit test for DEnumItem::loadItems\(\) [\#48](https://github.com/alphadevx/alpha/issues/48)
- Add a test for AlphaController::checkIfAccessingFromSecureURL\(\) [\#47](https://github.com/alphadevx/alpha/issues/47)
- Add a unit test for AlphaController::checkControllerDefExists\(\) method [\#46](https://github.com/alphadevx/alpha/issues/46)
- Add a unit test for FrontController::generateSecureURL\(\) [\#45](https://github.com/alphadevx/alpha/issues/45)
- The generated API docs should contain the version number of Alpha [\#44](https://github.com/alphadevx/alpha/issues/44)
- The folder layout in the 1.2.0 release .zip is incorrect [\#43](https://github.com/alphadevx/alpha/issues/43)
- Add an APIGen template for generating branded Alpha Framework API docs using that tool [\#42](https://github.com/alphadevx/alpha/issues/42)
- ArticleCommentView.inc has a class name sensitivity bug [\#41](https://github.com/alphadevx/alpha/issues/41)
- References to BlogEntryObject and NewsObject should be removed from the ViewFeed class [\#40](https://github.com/alphadevx/alpha/issues/40)
- Add a config setting for allowing print versions or not [\#39](https://github.com/alphadevx/alpha/issues/39)
- The URL seperator in the article URLs in the Alpha CMS should be made configurable [\#31](https://github.com/alphadevx/alpha/issues/31)
- Remove "sys" prefix from configuration settings [\#30](https://github.com/alphadevx/alpha/issues/30)
- Define a generic search interface [\#28](https://github.com/alphadevx/alpha/issues/28)
- Add a unit test to ensure that \_history tables are created automatically [\#26](https://github.com/alphadevx/alpha/issues/26)
- Add a unit test for AlphaDAO::saveHistory\(\) [\#25](https://github.com/alphadevx/alpha/issues/25)
- The manage cache controller should ignore .htaccess files [\#20](https://github.com/alphadevx/alpha/issues/20)
- Auto-loader trying to load requested resource as class [\#18](https://github.com/alphadevx/alpha/issues/18)
- The ViewLog controller should display a more elegant message when the requested log file does not exist [\#17](https://github.com/alphadevx/alpha/issues/17)
- Make the path to the file store directory configurable [\#16](https://github.com/alphadevx/alpha/issues/16)
- Add an IP filter [\#14](https://github.com/alphadevx/alpha/issues/14)
- Convert Alpha to UTF-8 [\#13](https://github.com/alphadevx/alpha/issues/13)
- Abstract out the view layer to support multiple renderers [\#12](https://github.com/alphadevx/alpha/issues/12)
- The servers.ini should support multiple servers per environment [\#10](https://github.com/alphadevx/alpha/issues/10)
- Add an abstracted interface for handling code highlighters [\#9](https://github.com/alphadevx/alpha/issues/9)
- Add a call to php\_uname to the config class [\#8](https://github.com/alphadevx/alpha/issues/8)
- Alpha should be outputting valid HTML5 [\#7](https://github.com/alphadevx/alpha/issues/7)
- Write a release and deploy script in Phing [\#5](https://github.com/alphadevx/alpha/issues/5)
- Add SQLite database support [\#4](https://github.com/alphadevx/alpha/issues/4)
- Add data warehousing support for DAOs [\#3](https://github.com/alphadevx/alpha/issues/3)
- Rename backup directories [\#2](https://github.com/alphadevx/alpha/issues/2)
- Class auto-loader [\#1](https://github.com/alphadevx/alpha/issues/1)
- Update the TCPDF dependancy [\#255](https://github.com/alphadevx/alpha/issues/255)
- The RendererProviderHTML class should allow injecting custom edit/view/delete URIs via the $fields params [\#251](https://github.com/alphadevx/alpha/issues/251)
- Prep 2.0 release [\#235](https://github.com/alphadevx/alpha/issues/235)
- Implement a session provider interface [\#164](https://github.com/alphadevx/alpha/issues/164)
- Update links in changelog to point to Github [\#163](https://github.com/alphadevx/alpha/issues/163)
- Add a test for Timestamp::getTimeAway\(\) [\#154](https://github.com/alphadevx/alpha/issues/154)
- Add a AlphaRendererProviderJSON implementation of AlphaRendererProviderInterface [\#152](https://github.com/alphadevx/alpha/issues/152)
- Add a unit test for the AlphaDAO::toArray\(\) method [\#147](https://github.com/alphadevx/alpha/issues/147)
- The autoloader in Alpha should adhere to PSR-0 [\#114](https://github.com/alphadevx/alpha/issues/114)
- Ensure that the Alpha codebase complies with PSR-1 and PSR-2 [\#112](https://github.com/alphadevx/alpha/issues/112)
- Replace 3rd party libs distributed with Alpha with dependancies in composer.json [\#111](https://github.com/alphadevx/alpha/issues/111)
- Prepare Alpha for use with Composer and Packagist [\#110](https://github.com/alphadevx/alpha/issues/110)
- Add a new abstract email provider interface [\#102](https://github.com/alphadevx/alpha/issues/102)
- Add a PersonObject::addToGroup\($groupName\) conveniance method [\#74](https://github.com/alphadevx/alpha/issues/74)
- Add support for more flexible request routing [\#36](https://github.com/alphadevx/alpha/issues/36)
- Add a HTTP package with Request/Response classes [\#35](https://github.com/alphadevx/alpha/issues/35)
- Logs should contain host name [\#33](https://github.com/alphadevx/alpha/issues/33)
- Add a AlphaDAO::addPropertyToHistoryTable\(\) method [\#24](https://github.com/alphadevx/alpha/issues/24)
- Add a AlphaDAO::getCountOfOldVersions\(\) method [\#23](https://github.com/alphadevx/alpha/issues/23)
- Add a AlphaDAO::loadAllOldVersions\(\) method [\#22](https://github.com/alphadevx/alpha/issues/22)
- Abstract out the logging implementation [\#11](https://github.com/alphadevx/alpha/issues/11)

## [1.2.4](https://github.com/alphadevx/alpha/tree/1.2.4) (2014-10-29)
[Full Changelog](https://github.com/alphadevx/alpha/compare/1.2.3...1.2.4)

**Closed issues:**

- The save\(\) method is prematurely deleting MANY-TO-MANY RelationLookup objects [\#160](https://github.com/alphadevx/alpha/issues/160)
- Add the $constructorArgs param to AlphaDAOProviderInterface::loadAllByAttributes\(\) [\#159](https://github.com/alphadevx/alpha/issues/159)
- The RelationLookup class needs a custom loadAllByAttributes\(\) method [\#158](https://github.com/alphadevx/alpha/issues/158)
- String::isRequired\(\) should be using the AlphaValidator::REQUIRED\_STRING constant [\#157](https://github.com/alphadevx/alpha/issues/157)
- Update the Search controller to use Bootstrap pagination links [\#156](https://github.com/alphadevx/alpha/issues/156)
- Add a filter to SearchProviderInterface::getRelated\(\) to prevent duplicates on a given field [\#155](https://github.com/alphadevx/alpha/issues/155)
- Add a Timestamp::getTimeAway\(\) method [\#153](https://github.com/alphadevx/alpha/issues/153)
- Remove $tableTags params from methods in the AlphaRendererProviderInterface [\#151](https://github.com/alphadevx/alpha/issues/151)
- Make sucess messages dismisable [\#150](https://github.com/alphadevx/alpha/issues/150)
- Fixed the rendering of tags in the RecordSelector widget [\#149](https://github.com/alphadevx/alpha/issues/149)
- Fix StringBox to use disabled rather than readonly property [\#148](https://github.com/alphadevx/alpha/issues/148)
- Add a toArray method to convert a DAO to a hash array [\#146](https://github.com/alphadevx/alpha/issues/146)
- Add a Session level of controller visibility [\#145](https://github.com/alphadevx/alpha/issues/145)
- Error page should be using Bootstrap CSS instead of JQuery UI CSS [\#142](https://github.com/alphadevx/alpha/issues/142)
- TextBox should support security.encrypt.http.fieldnames [\#141](https://github.com/alphadevx/alpha/issues/141)
- We need a flag to suppress overrides.css when required [\#140](https://github.com/alphadevx/alpha/issues/140)
- Validation JS is not handling checkboxes correctly [\#139](https://github.com/alphadevx/alpha/issues/139)
- Ensure that all .js and .css files are minified [\#138](https://github.com/alphadevx/alpha/issues/138)
- Add caching support to the seach engine [\#137](https://github.com/alphadevx/alpha/issues/137)
- Ensure that the correct HTTP cache headers are being used by the image widget [\#136](https://github.com/alphadevx/alpha/issues/136)
- Add a Redis implementation of AlphaCacheProviderInterface [\#109](https://github.com/alphadevx/alpha/issues/109)
- Add an APC implementation of AlphaCacheProviderInterface [\#101](https://github.com/alphadevx/alpha/issues/101)

## [1.2.3](https://github.com/alphadevx/alpha/tree/1.2.3) (2014-04-30)
[Full Changelog](https://github.com/alphadevx/alpha/compare/alpha-1.2.3...1.2.3)

## [alpha-1.2.3](https://github.com/alphadevx/alpha/tree/alpha-1.2.3) (2014-04-22)
[Full Changelog](https://github.com/alphadevx/alpha/compare/alpha-1.2.2...alpha-1.2.3)

## [alpha-1.2.2](https://github.com/alphadevx/alpha/tree/alpha-1.2.2) (2013-10-21)
[Full Changelog](https://github.com/alphadevx/alpha/compare/alpha-1.2.1...alpha-1.2.2)

## [alpha-1.2.1](https://github.com/alphadevx/alpha/tree/alpha-1.2.1) (2012-12-21)
[Full Changelog](https://github.com/alphadevx/alpha/compare/alpha-1.2.0...alpha-1.2.1)

## [alpha-1.2.0](https://github.com/alphadevx/alpha/tree/alpha-1.2.0) (2012-11-15)
[Full Changelog](https://github.com/alphadevx/alpha/compare/alpha-1.1...alpha-1.2.0)

## [alpha-1.1](https://github.com/alphadevx/alpha/tree/alpha-1.1) (2011-12-13)
[Full Changelog](https://github.com/alphadevx/alpha/compare/alpha-1.0...alpha-1.1)

## [alpha-1.0](https://github.com/alphadevx/alpha/tree/alpha-1.0) (2011-03-17)


\* *This Change Log was automatically generated by [github_changelog_generator](https://github.com/skywinder/Github-Changelog-Generator)*