README
======

What is couchphpdb?
-------------------

Without prejudice, however with all the implied naivety, maybe even stupidity, yes, this is an attempt to build an Apache CouchDB implementation of yours truly document database in PHP and MySQL. Read again: This is not another PHP library to access CouchDB, it's a variant of CouchDB itself using PHP and MySQL under the hood.

Be aware that this is still just a proof of concept with pre alpha status. In it's current state it's more or less a little demo. Don't run it in production.

Why in god's name?
------------------

Maybe it's just a lil' tribute to the LAMP stack. Kidding aside, more mature it could actually be useful like other variants (think PouchDB). 

How is it done?
---------------

* Documents are stored inspired by FriendFeed's approach to store schemaless data in MySQL [http://backchannel.org/blog/friendfeed-schemaless-mysql]

* PHP framework Symfony 2 and mainly FOSRestBundle act to simulate a CouchDB compatible REST API.

* Last but not least, part of the game is J4p5, a Javascript interpreter for PHP. It translates CouchDB's native Javascript views to PHP.

What's working
--------------

Again, remember, it's a proof of concept. Use it at your own risk (but you know, no risk - no fun).

As you access CouchDB via HTTP only, it's administration interface Futon is 'just' a HTML based web client. Point Futon to couchphpdb, and boom, there you have you're couchphpdb admin interface!

So directly via HTTP or Futon for now you may:

* Create Databases (delete not implemented yet)
* Create, update and delete documents
* Create, update and delete design documents, yes this means you may create native Javascript based map/reduce views!

What's missing
--------------

Well, everything else basically ;-).

Requirements
------------

You need a LAMP stack with at least PHP 5.3.3

Installation
------------

More info on this will come but this should get you started.

* Download or clone
* Set your Apache DocumentRoot to the /web directory
* Make sure mod_rewrite works
* couchphpdb uses Symfony2 and Composer, so to set everything up, run the following commands in the root folder of couchphpdb

    php composer.phar install
    php composer.phar update
    php composer.phar dump-autoload --optimize
    chmod -R 777 app/cache