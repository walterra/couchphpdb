README
======

What is couchphpdb?
-------------------

Without prejudice, however with all the implied naivety, maybe even stupidity, yes, this is an attempt to build an Apache CouchDB derivative in PHP and MySQL. Read again: This is not another PHP library to access CouchDB, it's a variant of CouchDB itself using PHP and MySQL under the hood.

Be aware that this is still just a proof of concept with pre alpha status. In it's current state it's more or less a little demo. Don't run it in production.

Why in god's name?
------------------

Maybe it's just a lil' tribute to the LAMP stack. Kidding aside, more mature it could actually be useful like other variants (think PouchDB). 

How is it done?
---------------

* Documents are stored inspired by [FriendFeed's approach to store schemaless data in MySQL](http://backchannel.org/blog/friendfeed-schemaless-mysql)

* PHP framework [Symfony 2](https://github.com/symfony/symfony) and mainly [FOSRestBundle](https://github.com/FriendsOfSymfony/FOSRestBundle) act to simulate a CouchDB compatible REST API.

* Last but not least, part of the game is [J4p5](https://github.com/walterra/J4p5Bundle), a Javascript interpreter for PHP. It translates CouchDB's native Javascript views to PHP.

What's working
--------------

Again, remember, it's a proof of concept. Use it at your own risk (but you know, no risk - no fun).

As you access CouchDB via HTTP only, it's administration interface Futon is 'just' a HTML based web client. Point Futon to couchphpdb, and boom, there you have you're couchphpdb admin interface!

So directly via HTTP or Futon for now you may:

* Create and delete Databases
* Create, update and delete documents
* Create, update and delete design documents, yes this means you may create native Javascript based map/reduce views! However, be aware that views are not persistent for now.

What's missing
--------------

Well, everything else basically ;-).

Requirements
------------

You need a LAMP stack with at least PHP 5.3.3

Installation
------------

More info on this will come but this should get you started.

1. Download or clone
2. Set your Apache DocumentRoot to the /web directory
3. Make sure mod_rewrite works
4. copy app/config/parameters.yml.dist to app/config/parameters.yml and adjust the database settings 
5. couchphpdb uses Symfony2 and Composer, so to set everything up, run the following commands in the root folder of couchphpdb

<pre>
php composer.phar install
php composer.phar update
php composer.phar dump-autoload --optimize
chmod -R 777 app/cache
</pre>



