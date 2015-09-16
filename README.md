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

TODO

Learn more
----------

For further information including installation instructions, please visit the following web page:

http://www.alphaframework.org/article/Documentation

Contact
-------

For bug reports and feature requests, please e-mail: dev@alphaframework.org
