[![Build Status](https://travis-ci.org/alphadevx/alpha.svg?branch=develop)](https://travis-ci.org/alphadevx/alpha)
[![Coverage Status](https://coveralls.io/repos/github/alphadevx/alpha/badge.svg?branch=develop)](https://coveralls.io/github/alphadevx/alpha?branch=develop)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/alphadevx/alpha/badges/quality-score.png?b=develop)](https://scrutinizer-ci.com/g/alphadevx/alpha/?branch=develop)

Alpha Framework (3.0.0)
=================================================================

Introduction
------------

The Alpha Framework is a full-stack MVC framework for PHP.  It provides the following features:

 * A set of complex data types that have built-in validation checks, mappings to database field types, and UI widgets.
 * An active record interface with support for MySQL and SQLite.
 * A rendering interface with support for HTML and JSON (for developing APIs).
 * A caching interface with support for APCu, Redis, and Memcache.
 * A CRUD controller to take care of most of your data management needs.
 * A front controller, abstract controller, and elegant request routing interface.
 * Request/response classes.
 * A full admin UI and installation UI.
 * RSS and Atom feed generation.
 * A built-in Markdown-based CMS with article voting and commenting.
 * A built-in search engine with tagging support (including tag clouds, free-text search, find similar...).
 * Strong security features (short-live form encryption tokens to prevent cross-site requests, encryption tokens on images to prevent cross-site embedding, HTTP request filters to block agents or IPs...).
 * Complete logging solution, including optional user audit trail.
 * Build-in application back-up task along with support for other, custom tasks.

Status
------

The current latest stable release is tested via unit testing and manual testing, and is considered suitable for production usage.

Installation
------------

### Composer

Alpha should be installed using Composer.  Here is a minimum example _composer.json_ file to install the current release:

	{
	    "minimum-stability": "dev",
	    "prefer-stable": true,
	    "require": {
	        "alphadevx/alpha": "3.0.*"
	    },
	    "autoload": {
	        "psr-0": {
	            "": "src/"
	        }
	    }
	}

### Database

Alpha stores its records in a relational database, currently MySQL and SQLite are supported via injectable providers (more will be written in the future).  For SQLite, a file will be written to the filesystem based on your configuration (see the next section), but for MySQL you will need to manually create the database first:

	MariaDB [(none)]> CREATE DATABASE alphaframework DEFAULT CHARACTER SET utf8 DEFAULT COLLATE utf8_general_ci;
	Query OK, 1 row affected (0.00 sec)

You should also ensure that the MySQL user account that you are going to use to access that database has the correct rights granted to it.

### Configuration

Alpha configuration is broken down into three deployment envrionments, which in turn have their own config files:

|Name |Description                               |Config file     |
|-----|------------------------------------------|----------------|
|dev  |Your developer environment (localhost).   |config/dev.ini  |
|test |Your test environment for QA/UAT testing. |config/test.ini |
|pro  |Your production environment.              |config/pro.ini  |

From the _vendor_ directy created by Composer, you should copy those files into your application root directory:

	$ cp -R vendor/alphadevx/alpha/config .

Alpha chooses the correct file to load based on the hostname of the current server.  That hostname to config environment mapping occurs in the _config/servers.ini_ file.  Here is an example of that file:

	; The server names for the three environments
	dev = localhost,alphaframework.dev
	pro = alphaframework.org
	test = unittests,qa.alphaframework.org

Multiple server hostnames can be added per environment, seperated by commas.

The config files themselves are well-commented, but here is a list of the minimum settings you should configure before continuing with your installation:

|Setting                 |Description                                                                              |
|------------------------|-----------------------------------------------------------------------------------------|
|app.url                 |System root URL.                                                                         |
|app.root                |The OS root directory.                                                                   |
|app.file.store.dir      |The path to the directory where files are stored (logs, attachments, cached files etc.). |
|app.title               |The title of the web site.                                                               |
|db.name                 |The name of the main database to use.                                                    |
|db.username             |Database username.                                                                       |
|db.password             |Database password.                                                                       |
|db.hostname             |Database host.                                                                           |
|security.encryption.key |Secret key used for encryption when using the SecurityUtils class.                       |
|app.install.username    |Username used for admin access during intallation when creating the database.            |
|app.install.password    |Password used for admin access during intallation when creating the database.            |

Please review the comments in the config files for addition optional settings.

### Bootstrap

Alpha uses a front controller to route all traffic to a single physical file in your web server configuration.  That file, _index.php_, is contained with the _public_ directory of the framework.  Copy that directory into the root of your application:

	$ cp -R vendor/alphadevx/alpha/public .

Now open that file in your editor to review: it is this file that you will use to add in customer routes to your custom controllers.  You will then need to configure your web server to send all traffic to your _public/index.php_ file (with the exception of static files that are also in _public_), here is an example Apache virtual host configuration:

	<VirtualHost alphaframework.dev:80>
	   DocumentRoot "/home/john/Git/alphaframework/public"
	   ServerName alphaframework.dev


	   <Directory "/home/john/Git/alphaframework/public">
	      Options FollowSymLinks
	      Require all granted

	      RewriteEngine On
	      RewriteBase /
	      RewriteCond %{REQUEST_FILENAME} !-f
	      RewriteCond %{REQUEST_FILENAME} !-d
	      RewriteCond %{DOCUMENT_ROOT}/index\.php -f
	      RewriteRule ^(.*)$  /index.php?page=$1 [QSA]
	   </Directory>
	</VirtualHost>

### Running the installer

Once you have configured your application, your web server, and created an empty database, you should then start up you web and database servers and navigate to the root URL of your application.  Alpha will automatically detect that it is not installed, and will re-direct to the _Alpha\Controller\InstallController_ for you.

The first thing you will see is a prompt for a username and password, you should enter the values for _app.install.username_ and _app.install.password_ from your application config file to gain access.

The InstallController will create any directories required for you (to store log files, the file cache, article attachments etc.), in addition to creating the database tables, indexes, and foreign keys required to support our active records.  Once all of this work is completed, a link to the administration backend will be presented.

Once your application is installed in production, you should set the _app.check.installed_ config value to false to remove the overhead of checking to see if the application is installed on each request.

Models
------

### Data types

The model layer of Alpha consists of an active record implementation, and a set of data types.  The data types are designed to map to corresponding database types seamlessly, as well as providing validation and frontend widgets.  The following data types are included:

| Type                      | Size | Validation rules                      | Description                                                                              |
|---------------------------|------|---------------------------------------|------------------------------------------------------------------------------------------|
| Alpha\Model\Type\Boolean  | 1    | 1/0 or true/false.                    | A boolean value.                                                                         |
| Alpha\Model\Type\Enum     | -    | One of the items from the list.       | A fixed listed of strings to choose from, e.g. weekdays, gender etc.                     |
| Alpha\Model\Type\DEnum    | -    | ID for one of the items from the list.| A Dynamic Enum, allows you to modify the elements in the enum as stored in the database. |
| Alpha\Model\Type\Date     | 10   | A date in ISO YYYY-mm-dd format.      | A date value.                                                                            |
| Alpha\Model\Type\Timestamp| 19   | A timestamp YYYY-mm-dd hh:ii:ss.      | A timestamp value.                                                                       |
| Alpha\Model\Type\Double   | 13   | A valid double value.                 | A double value.                                                                          |
| Alpha\Model\Type\Integer  | 11   | A valid integer value.                | An integer value.                                                                        |
| Alpha\Model\Type\Relation | 11   | ID of the related record, or empty.   | A relation type supporting MANY-TO-ONE, ONE-TO-MANY, ONE-TO-ONE, MANY-TO-MANY types.     |
| Alpha\Model\Type\Sequence | 255  | Sequence in format PREFIX-00000000000.| A database sequence number with a string prefix defined by the developer.                |
| Alpha\Model\Type\SmallText| 255  | A small text value.                   | A small text value (single line).                                                        |
| Alpha\Model\Type\Text     | 65535| A large text value.                   | A large value (paragraphs).                                                              |

### ActiveRecord

An active record then uses these data types to store the attributes of an object we care about in the database. For example, here is how our _Person_ active record makes use of these complex types:

	<?php

	namespace Alpha\Model;

	// ...

	class Person extends ActiveRecord
	{
	    /**
	     * The forum display name of the person.
	     *
	     * @var Alpha\Model\Type\SmallText
	     *
	     * @since 1.0
	     */
	    protected $username;

	    /**
	     * The email address for the person.
	     *
	     * @var Alpha\Model\Type\SmallText
	     *
	     * @since 1.0
	     */
	    protected $email;

	    /**
	     * The password for the person.
	     *
	     * @var Alpha\Model\Type\SmallText
	     *
	     * @since 1.0
	     */
	    protected $password;

	    // ...

	    /**
	     * An array of data display labels for the class properties.
	     *
	     * @var array
	     *
	     * @since 1.0
	     */
	    protected $dataLabels = array('ID' => 'Member ID#',
	                                    'username' => 'Username',
	                                    'email' => 'E-mail Address',
	                                    'password' => 'Password',
	                                    'state' => 'Account state',
	                                    'URL' => 'Your site address',
	                                    'rights' => 'Rights Group Membership',
	                                    'actions' => 'Actions', );
	    /**
	     * The name of the database table for the class.
	     *
	     * @var string
	     *
	     * @since 1.0
	     */
	    const TABLE_NAME = 'Person';

	    /**
	     * The state of the person (account status).
	     *
	     * @var Aplha\Model\Type\Enum
	     *
	     * @since 1.0
	     */
	    protected $state;

		// ...

	    /**
	     * Constructor for the class that populates all of the complex types with default values.
	     *
	     * @since 1.0
	     */
	    public function __construct()
	    {
	        // ...

	        $this->username = new SmallText();
	        $this->username->setRule(Validator::REQUIRED_USERNAME);
	        $this->username->setSize(70);
	        $this->username->setHelper('Please provide a name for display on the website (only letters, numbers, and .-_ characters are allowed!).');

	        $this->email = new SmallText();
	        $this->email->setRule(Validator::REQUIRED_EMAIL);
	        $this->email->setSize(70);
	        $this->email->setHelper('Please provide a valid e-mail address as your username.');

	        $this->password = new SmallText();
	        $this->password->setSize(70);
	        $this->password->setHelper('Please provide a password for logging in.');
	        $this->password->isPassword(true);

	        $this->state = new Enum(array('Active', 'Disabled'));
	        $this->state->setValue('Active');

	        // ...
	    }

	    // ...
	}

As you can see in the example above, each attribute of the _Alpha\Model\Person_ record is defined using a data type in the constructor, along with any additional validation rules, sizes, and helper text to display to the user if the validation rules are broken by their input.  Each attribute will then be mapped to an appropriate column and data type in the database in the _Person_ table, that will be created for you by Alpha.

### ActiveRecord CRUD methods

Once you have an active record defined and the database in place, carrying out typical CRUD operations becomes trivial:

	$record = new Person();
	$record->set('email', 'some@user.com');
	$record->save();

	$record->load(25);
	$record->loadByAttribute('email', 'some@user.com');
	$records = $record->loadAll(0, 25, 'created_ts'); // load the first 25 Person records sorted by created timestamp
	$records = $record->loadAllByAttribute('state', 'Active', 0, 25, 'created_ts');

	$record->delete();

For a full list of the supported CRUD and table management methods, check the notes in the _Alpha/Model/ActiveRecordProviderInterface_.

### Transaction and error handling

Alpha supports database transactions via a number of static methods on the _ActiveRecord_ class.  Here is a typical example:

	try {
	    ActiveRecord::begin();
	    $cart = new Cart();
	    $cart->load(100);
	    $items = $cart->loadItems();

	    foreach ($items as $item) {
	    	$item->set('amount', $item->get('ammount') - 1);
	    	$item->save();
	    }

	    ActiveRecord::commit();
	} catch (AlphaException $e) {
	    self::$logger->error($e->getMessage());
	    ActiveRecord::rollback();
	    // ...
	}

### Object locking and versioning

Each active record in Alpha maintains a version number that is automatically incremented each time the record is saved.  In order to prevent race conditions when two or more seperate threads attempt to save the same record, a version check is performed pre-save and if the version number being saved is older than the version currently in the database, a _Alpha\Exception\LockingException_ is thrown.  Naturally in your application, you should capture that exception an try to handle it gracefully:

	try {
		$record->save();
	} catch (LockingException $e) {
	    // Reload updated record from the database, display something useful to the user and
	    // then let them try to save the record again...
	}

### History

Alpha can optionally maintain the history of each active record for you, by setting the maintainHistory attribute on the record to true.  Alpha will then create and maintain a second database table for the record type, suffixed with "_history", where it will save a copy of the record each time it is updated.  This means that a full history of changes applied to a record can be maintained indefinetly, if you wish to do so in your application.

	$record = new Person();
	$record->setMaintainHistory(true);
	$record->set('email', 'one@test.com');
	$record->save();
	
	$record->set('email', 'two@test.com');
	$record->save();
	
	echo $record->getHistoryCount(); // should be 2
	
	// load version 1 of person record 10
	$record->load(10, 1);
	echo $record->get('email'); // one@test.com
	
	// load version 2 of person record 10
	$record->load(10, 2);
	echo $record->get('email'); // two@test.com

Controllers
-----------

### ControllerInterface

All controllers in Alpha should inherit from the _Controller_ abstract class and implement the _ControllerInterface_.  The interface defines all of the methods that handle a HTTP request, and includes a method for each of the HTTP verbs (doGET, doPOST, doPUT etc.).  Each of these methods accept a _Request_ object as the only parameter, and return a _Response_ object.  Here is a simple example:

	<?php

	namespace My\App\Controller;

	use Alpha\Controller\Controller;
	use Alpha\Controller\ControllerInterface;
	use Alpha\Util\Http\Request;
	use Alpha\Util\Http\Response;

	class HelloController extends Controller implements ControllerInterface
	{
		public function doGET($request)
		{
			$name = $request->getParam('name');

			return new Response(200, 'Hello '.$name, array('Content-Type' => 'text/plain'));
		}
	}

### Routing

Routing a HTTP request to the correct controller is handled by the _FrontController_.  There are two ways to route a request: using a user-friendly URL, or using a secure URL containing an encrypted token that holds the params and controller name for the request.

#### User-friendly URL

In your _index.php_ bootstrap file, you should instantiate a new _FrontController_ to handle all requests.  Routes can then be added to the FrontController like so:

	use Alpha\Controller\Front\FrontController;
	use My\App\Controller\HelloController;

	// ...

	$front = new FrontController();

	$this->addRoute('/hello/{name}', function ($request) {
        $controller = new HelloController();

        return $controller->process($request);
    });

    $request = new Request(); // this will build the request from super global data.
	$response = $front->process($request); // this will map the requested route to the correct controller
	echo $response->send(); // send the response to the client

The _FrontController::process()_ method will take care to map the requested route to the correct controller, while the _Controller::process()_ method called within the addRoute closure will map the HTTP request verb (e.g. GET) to the correct controller method (e.g. doGET).

Note that you can also define default request parameters in the route, effectively making them optional:

	$this->addRoute('/hello/{name}', function ($request) {
        $controller = new HelloController();

        return $controller->process($request);
    })->value('name', 'unknown'); // if the client requests /hello, return "hello unknown"

#### Secure token URL

If you are concerned about passing sensitive information via the query string or as a route parameter, you can generate a secure URL for the controller like so:

	$url = FrontController::generateSecureURL('act=My\App\Controller\HelloController&name=bob');
	// $url now something like "http://www.myapp.com/tk/gKwbKqR7uQq-d07z2y8Nnm1JnW_ZTKIUpT-KUJ7pYHxMouGoosktcIUiLKFz4uR8"

Note that the URL generate will be automatically unencoded and decrypted by the FrontController when requested, using the secret encryption key set in your config file during installation.

Views
-----

Views in Alpha are made up of two parts: _view classes_ that are responsible for marshalling active records to views, and _view templates_ that are responsible for defining the actual view content.

### View classes

A view class should extend the _Alpha\View\View_ class, that defines standard set of methods that are available to a view (createView(), editView(), listView() etc.).  You can then override these methods in case you need to do something specific in your view.  Typically, there is a one-to-one mapping between a view and the corresponding active record that it is rendering, and the view is responsible for marshalling the record data to an underlying template.

Here is an example where we are injecting in a count of the items in a given shopping cart, to be used later on in the template to display cart details:

	<?php

	use Alpha\View\View;
	use My\App\Model\Cart;
	use My\App\Model\Item;
	// ...

	class CartView extends View
	{
	    // ...

	    public function detailedView($fields = array())
	    {
	        // this->record will be set to our cart at this stage during factory instantiation
	        $items = $this->record->getItems();

	        $fields['itemCount'] = count($items);

	        // ...

	        $html = $this->loadTemplate($this->record, 'detail', $fields);

	        return $html;
	    }
	}

### View templates

While you can generate HTML directly in your view class methods and return that, typcially you will want to maintain your HTML in a template where it is easier to manage.  The _View_ class provides two seperate methods for loading a template:

	// Assuming that this->record is a My\App\Model\Cart instance, this will check in the following locations for the template:
	// 1. [app.root]/View/Html/Templates/Cart/detail.phtml (this is where you should place your custom templates)
	// 2. [app.root]/Alpha/View/Renderer/Html/Templates/Cart/detail.phtml
	$html = $this->loadTemplate($this->record, 'detail', $fields);

	// You can also load a fragment template (does not require a record to be passed).  It will check for the file in:
	// 1. [app.root]/View/Html/Fragments/[fileName]
	// 2. [app.root]/Alpha/View/Renderer/Html/Fragments/[fileName]
	$html = $this->loadTemplateFragment('html', 'header.phtml', $fields);

In the template itself, you can reference any of the values passed in the $fields array as a regular variable:

	<p>There are <?= $itemCount ?> items in your shopping cart.</p>

### Widgets

Alpha provides a number of HTML widgets in the _Alpha\View\Widget_ package, that are very convenient for renderer nice interfaces quickly using Twitter Bootstrap CSS and addition JQuery code.

The following widgets are included:

| Widget class                     | Description                | Paramters                                                          |
|----------------------------------|----------------------------|--------------------------------------------------------------------|
| Alpha\View\Widget\Button         | A Bootstrap button.        | Some Javascript to execute when the button is pressed.             |
| Alpha\View\Widget\DateBox        | A Bootstrap date picker.   | An Alpha\Model\Type\Date or Alpha\Model\Type\Timestamp instance.   |
| Alpha\View\Widget\Image          | A scaled, secure image.    | The source path of the original file along with desired dimensions.|
| Alpha\View\Widget\RecordSelector | Used for relating records. | A _Alpha\Model\Type\Relation_ instance.                            |
| Alpha\View\Widget\SmallTextBox   | One-line text input box.   | A _Alpha\Model\Type\SmallText_ instance.                           |
| Alpha\View\Widget\TextBox        | Multi-line text input box. | A _Alpha\Model\Type\Text_ instance.                                |
| Alpha\View\Widget\TagCloud       | A cloud of popular tags.   | The maximum amount of tags to include in the cloud.                |


Utils
-----

Alpha includes many varied utilities in the _Alpha\Util_ package.  The following sections cover some of the highlights.

### Cache

A data cache is provided, that provides a factory and injectable providers that support Memcache, Redis, and APCu.  The classes are provided in the _Alpha\Util\Cache_ package, while the providers implement the _Alpha\Util\Cache\CacheProviderInterface_.  Here is an example using the Redis provider:

	use Alpha\Util\Service\ServiceFactory;

	// ...

	$cache = ServiceFactory::getInstance('Alpha\Util\Cache\CacheProviderRedis','Alpha\Util\Cache\CacheProviderInterface');
	$record = $cache->get($cacheKey);

### Code highlighting

The _Alpha\Util\Code\Highlight\HighlightProviderFactory_ providers objects for converting plain source code into code-highlighted HTML source code.  Providers for the Geshi and Luminous libraries are provided.  Here is an example:

	use Alpha\Util\Service\ServiceFactory;
	
	// ...
	
	$highlighter = ServiceFactory::getInstance('Alpha\Util\Code\Highlight\HighlightProviderGeshi','Alpha\Util\Code\Highlight\HighlightProviderInterface');
	
	$html = $highlighter->highlight($code, 'php');

### Email

Alpha provides an email package with an interface for injecting different email providers.  Here is an example usage:

	use Alpha\Util\Service\ServiceFactory;
	use Alpha\Exception\MailNotSentException;

	// ...

	$mailer = ServiceFactory::getInstance('Alpha\Util\Email\EmailProviderPHP','Alpha\Util\Email\EmailProviderInterface');

	try {
    	$mailer->send('to@mail.com', 'from@mail.com', 'Subject', 'Some HTML...', true);
    } catch (MailNotSentException $e) {
    	// handle error...
    }

### Feeds

Alpha can generate an Atom, RSS, or RSS2 feed based on a list of active records, using the classes in the _Alpha\Util\Feed_ package.  The _FeedController_ is also provided for convience, that has the following route already set-up in the _FrontController_:

	$this->addRoute('/feed/{ActiveRecordType}/{type}', function ($request) {
        $controller = new FeedController();

        return $controller->process($request);
    })->value('type', 'Atom');

If you want to use the feed classes directly in your application, you can do so:

	use Alpha\Util\Feed\Atom;

	// ...

	$feed = new Atom($ActiveRecordType, $title, $url, $description, $pubDate, $ID);
    $feed->setFieldMappings('title', 'URL', 'description', 'created_ts', 'ID');
    $feed->addAuthor('Feed author');
    $feed->loadRecords(20, 'ID');

    $xml = $feed->render();

### Validation

A validation class is provided for handling typical type checks or for testing for common string patterns (email address, URLs etc.).  The class in question is _Alpha\Util\Helper\Validator_, check the _Alpha\Test\Util\Helper\ValidatorTest_ unit test for lots of code examples on how to use this class.

### HTTP

#### Filters

Alpha provides a framework for injecting optional request filters into the front controller, that are run before any request in routed through there.  You can write your own filters that implement the _Alpha\Util\Http\Filter\FilterInterface_, in addition Alpha provides the following build-in filters that you can choose to enable:

|Filter                                           |Purpose                                                                       |
|-------------------------------------------------|------------------------------------------------------------------------------|
|Alpha\Util\Http\Filter\ClientBlacklistFilter     |Block any request from a client matching the blacklisted User-Agent.          |
|Alpha\Util\Http\Filter\IPBlacklistFilter         |Block any request from a client matching the blacklisted IP.                  |
|Alpha\Util\Http\Filter\ClientTempBlacklistFilter |Block too many requests from a given client/IP compination for a short period.|

Registering a filter with the front controller is easy, and should be done on application bootstrap, typlically in your index.php file:

	use Alpha\Controller\Front\FrontController;
	use Alpha\Util\Http\Filter\ClientBlacklistFilter;
	use Alpha\Exception\ResourceNotAllowedException;

	// ...

	try {
		$front = new FrontController();
		$front->registerFilter(new ClientBlacklistFilter());
	} catch (ResourceNotAllowedException $e) {
		// the filters will throw this exception when invoked so you will need to handle, e.g. send 403 response.
	}

#### Sessions

Alpha provides a session abstraction layer to allow the inject of different session providers, which is very useful for example if you want to store your session in an array for unit testing.  It is also possible to write providers to store sessions in NoSQL or database backends.  Here is an example of using the default PHP session mechanism:

	use Alpha\Util\Service\ServiceFactory;

	// ...

	$session = ServiceFactory::getInstance('Alpha\Util\Http\Session\SessionProviderPHP', 'Alpha\Util\Http\Session\SessionProviderInterface');
	$session->set('somekey', 'somevalue'); // you can also pass complex types
	echo $session->get('somekey');

#### Request and Response

Rather than working with PHP super-globals such as _GET and _POST directly, Alpha provides Request and Response classes in the _Alpha\Util\Http_ package to encapsulate those globals.

The attributes of the Request objects (HTTP headers, HTTP verbs, request body etc.) are set during the object instantiation via the __construct($overrides) method, based on the available super-global values.  Note that you can override any of the super-global values via the $overrides hash array parameter, which is useful for unit testing.

The Response constructor expects a HTTP response code, body, and optional array of HTTP headers during construction.  Here is an example:

	use Alpha\Util\Http\Response;

	// ...

	$response = new Response(200, '<p>hello world!</p>', array('Content-Type' => 'text/html'));
	$response->send();

### Logging

A logging class is provided with a standard set of methods for logging at different levels:

	use Alpha\Util\Logging\Logger;

	// ...

	$logger = new Logger('ClassName');
    $logger->debug('Debug information');
    $logger->info('Notable information');
    $logger->warn('Something went wrong');
    $logger->error('A serious error');
    $logger->fatal('A fatal error');
    $logger->sql('SELECT * FROM...');
    $logger->action('Action carried out by the current user logged in the ActionLog table');

The log that is written to is defined by the following configuration properties:

	$config->get('app.file.store.dir').'logs/'.$config->get('app.log.file');

Alpha also provides a KPI class, or _Key Performance Indicator_, that is useful for logging time-sensitive entries that can indicate how your application is performing in production.  Here is an example usage:

	use Alpha\Util\Logging\KPI;

	// ...

	$KPI = new KPI('KPIName');
	// ...
	$KPI->logStep('something happened');
	// ...
	$KPI->logStep('something else happened');
	// ...
	$KPI->log();

Each KPI is logged, along with timings and session IDs, in a seperate log file in the following location:

	$config->get('app.file.store.dir').'logs/kpi-'.$KPIName.'.csv';

### Search

A simple search engine is provided to use in your applications, that is made up of a seach controller and an abstract search API on the backend that searches the main active record database for matching records, based on tags attached to those records.

The key classes are:

|Class                                     |Description                                                                     |
|------------------------------------------|--------------------------------------------------------------------------------|
|Alpha\Controller\SearchController         |Handles search queries sent via HTTP requests.                                  |
|Alpha\Util\Search\SearchProviderInterface |The main search API.                                                            |
|Alpha\Util\Search\SearchProviderTags      |Implements the SearchProviderInterface using Tag active records.                |
|Alpha\Model\Tag                           |Contains a simple string value that helps to describe the related active record.|
|Alpha\Controller\TagController            |Used to edit tags on a single record, and manage all tags in the admin backend. |

A typical use case is contained in the content management system included with Alpha.  When an Article record is saved, a callback is used to generate a set of Tag records based on the content of the Article (excluding stop-words contained in the _stopwords-large.ini_ or _stopwords-small.ini_ files), and those Tag records are related to the Article via a ONE-TO-MANY Relation type, and saved to the main database.  Searches are then conducted against the Tag table, and once matching Tags are found we load the related Article records.

For example code, please view the _Alpha\Controller\SearchController_ class and the _Alpha\Test\Controller\SearchControllerTest_ and _Alpha\Test\Util\Search\SearchProviderTagsTest_ unit tests.

### Security

Alpha provides a static utility class with two methods for encrypting or decrypting data:

	use Alpha\Util\Security\SecurityUtils;

	// ...

	$encrypted = SecurityUtils::encrypt('Some data');
	$decrypted = SecurityUtils::decrypt($encrypted);

The class makes use of the OpenSSL extension in PHP to handle encryption, using the AES 256 algorithm.  The secret key used is what you set in the _security.encryption.key_ setting in your config file, which is unique to your application.

### Error handling

It is best practice to build in your own error handling into your application using try/catch blocks, however in the event of an uncaught exception or a generic (non-fatal) PHP error, Alpha provides the _Alpha\Util\ErrorHandlers_ class that should be used in your bootsrap file like so:

	set_exception_handler('Alpha\Util\ErrorHandlers::catchException');
	set_error_handler('Alpha\Util\ErrorHandlers::catchError', $config->get('php.error.log.level'));

The _catchException()_ method captures an uncaught Exception object and logs it to the application log (message and stacktrace).  The 
_catchError()_ method captures a general PHP error and converts it to an _Alpha\Exception\PHPException_ object, which is then thrown to be captured and logged by _catchException()_.

Contact
-------

For bug reports and feature requests, please e-mail: dev@alphaframework.org

On Twitter: [@alphaframework](https://twitter.com/alphaframework)
