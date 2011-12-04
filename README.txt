Alpha Framework 1.1
===================

Introduction
------------

The Alpha Web Framework (or simply Alpha) was designed to be an easy to use web application framework, which is capable of supporting a wide variety 
of web applications. The key principal of the Alpha design ethos is to keep it simple, for example by favouring convention over configuration to avoid 
the need for a large upfront configuration effort.

The core of Alpha is designed to support only those key elements of a web application: the data model, the presentation views, and finally the controlling 
logic. These three layers are often called the Model View Controller, or MVC.

The model represents the business model that your application is trying to represent. Each element of the model is referred to as a business object. A 
business object represents something from the real world which you want to track in your system, for example a hotel would have a reservation business 
object, a bank would have a mortgage application business object, and an e-commerce system would have a shopping cart business object. The model layer of 
Alpha handles all of the database logic for creating, reading, updating, and deleting these business objects for you.

The view represents the varied ways which you present your business object data to the end users. Typically this will be in the form of HTML web pages, 
however Alpha supports many other formats such as PDF and RSS news feeds. Web services support will also be included in later versions. Using a process 
called scaffolding, Alpha can generate these views for you automatically from your business objects, enabling you to then customise those views to suit 
your requirements later on in the project. The scaffolding process gives you a major head start in the early phase of your project.

The controller layer is where all of the business logic of your application resides; it is in the controller code that things happen. Think of a controller 
as a middle man: it interacts with the model to populate business objects, which it then feeds into a view which renders the business data to the end user. 
By default, Alpha includes a powerful administration back-end which includes lots of useful controllers for listing business objects, creating new business 
objects, editing and deleting old business objects, and finally viewing other pertinent administration information such as test results and error logs. All 
of the existing controllers supplied with Alpha can be extended to create custom controllers to meet your requirements.

Learn more
----------

For further information including installation instructions, please visit the following web page:

http://www.alphaframework.org/article/Documentation

Contact
-------

For bug reports and feature requests, please e-mail: dev@alphaframework.org