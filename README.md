[![Build Status](https://travis-ci.org/alphadevx/alpha.svg?branch=2.0.0)](https://travis-ci.org/alphadevx/alpha)
[![Coverage Status](https://coveralls.io/repos/alphadevx/alpha/badge.svg?branch=2.0.0&service=github)](https://coveralls.io/github/alphadevx/alpha?branch=2.0.0)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/alphadevx/alpha/badges/quality-score.png?b=2.0.0)](https://scrutinizer-ci.com/g/alphadevx/alpha/?branch=2.0.0)

Alpha Framework (2.0 beta)
===========================

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

Note that this is the working branch for the 2.0 release, currently with **beta** status so **not suitable for production**, but is **suitable for new development projects**.  The stable (but deprecated) 1.x releases are available from here: http://www.alphaframework.org/article/Download

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
| Alpha\Model\Type\String   | 255  | A small string value.                 | A string value.                                                                          |
| Alpha\Model\Type\Text     | 65535| A large string value.                 | A string value.                                                                          |

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
	     * @var Alpha\Model\Type\String
	     *
	     * @since 1.0
	     */
	    protected $displayName;

	    /**
	     * The email address for the person.
	     *
	     * @var Alpha\Model\Type\String
	     *
	     * @since 1.0
	     */
	    protected $email;

	    /**
	     * The password for the person.
	     *
	     * @var Alpha\Model\Type\String
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
	    protected $dataLabels = array('OID' => 'Member ID#',
	                                    'displayName' => 'Display Name',
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

	        $this->displayName = new String();
	        $this->displayName->setRule(Validator::REQUIRED_USERNAME);
	        $this->displayName->setSize(70);
	        $this->displayName->setHelper('Please provide a name for display on the website (only letters, numbers, and .-_ characters are allowed!).');

	        $this->email = new String();
	        $this->email->setRule(Validator::REQUIRED_EMAIL);
	        $this->email->setSize(70);
	        $this->email->setHelper('Please provide a valid e-mail address as your username.');

	        $this->password = new String();
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
	        // this->BO will be set to our cart at this stage during factory instantiation
	        $items = $this->BO->getItems();

	        $fields['itemCount'] = count($items);

	        // ...

	        $html = $this->loadTemplate($this->BO, 'detail', $fields);

	        return $html;
	    }
	}

### View templates

TODO

Contact
-------

For bug reports and feature requests, please e-mail: dev@alphaframework.org

On Twitter: [@alphaframework](https://twitter.com/alphaframework)
