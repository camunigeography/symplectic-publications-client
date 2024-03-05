Publications database (Symplectic integration)
==============================================

This is a PHP application which implements a publication database listing viewer whose backend is a Symplectic API endpoint.

Screenshot
----------

![Screenshot](screenshot.png)


Usage
-----

1. Clone the repository.
2. Run `composer install` to install the dependencies.
3. Download and install the famfamfam icon set in /images/icons/
4. Add the Apache directives in httpd.conf (and restart the webserver) as per the example given in .httpd.conf.extract.txt; the example assumes mod_macro but this can be easily removed.
5. Create a copy of the index.html.template file as index.html, and fill in the parameters.
6. Ensure the bookcovers/ folder is writable by the webserver process, to enable staff to upload book covers.
7. Access the page in a browser at a URL which is served by the webserver.


Dependencies
------------

* [FamFamFam Silk Icons set](http://www.famfamfam.com/lab/icons/silk/)


Author
------

Martin Lucas-Smith, Department of Geography, 2014-24.


License
-------

GPL3.

