CONTENTS OF THIS FILE
---------------------

* Introduction
* Requirements
* Installation
* Configuration
* Local Development

INTRODUCTION
------------

**This module is under development, use at your own risk.**

The Protected Download module provides a way to grant access to specific files
for anonymous users.

REQUIREMENTS
------------

This module requires no modules outside of Drupal core.

INSTALLATION
------------

* Install as you would normally install a contributed Drupal module. Visit
  https://www.drupal.org/node/1897420 for further information.

CONFIGURATION
-------------

The module has no menu or modifiable settings. There is no UI configuration.
When enabled, the module will provide a new StreamWrapper that you can use. Set
the `file_protected_path` configuration in `settings.php` with the directory
path to use.

LOCAL DEVELOPMENT
-----------------

* Clone
  repository `git clone https://github.com/lucacracco/protected_download.git`
* Open folder `cd protected_download`
* Install the composer plugin
  from [https://gitlab.com/drupalspoons/composer-plugin](https://gitlab.com/drupalspoons/composer-plugin)
* Configure a web server to serve protected_download's `/web` directory as
  docroot. Either of these works fine:
    - `vendor/bin/spoon runserver`
    - Setup Apache/Nginx/Other. A virtual host will work fine. Any domain name
      works.
* Configure a database server and a database.
* Install a testing
  site `vendor/bin/spoon si -- --db-url=mysql://user:pass@localhost/db`. Adjust
  as needed.
  
