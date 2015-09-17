[![Build Status](https://travis-ci.org/alphadevx/alpha.svg?branch=2.0.0)](https://travis-ci.org/alphadevx/alpha)
[![Coverage Status](https://coveralls.io/repos/alphadevx/alpha/badge.svg?branch=2.0.0&service=github)](https://coveralls.io/github/alphadevx/alpha?branch=2.0.0)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/alphadevx/alpha/badges/quality-score.png?b=2.0.0)](https://scrutinizer-ci.com/g/alphadevx/alpha/?branch=2.0.0)

Alpha Framework (2.0 alpha)
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

Note that this is the working branch for the 2.0 release, currently with **alpha** status so **not suitable for production**.  The stable 1.x release are available from here: http://www.alphaframework.org/article/Download

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

TODO

### History

TODO

Learn more
----------

For further information including installation instructions, please visit the following web page:

http://www.alphaframework.org/article/Documentation

Contact
-------

For bug reports and feature requests, please e-mail: dev@alphaframework.org
