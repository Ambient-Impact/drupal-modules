This module provides an overview page and individual pages to view details about
available Ambient.Impact Components. This is primarily intended for
[ambientimpact.com](https://ambientimpact.com/web/components), but is reusable.
Note that this module will display all Component configuration and libraries to
users with the ```view ambientimpact component pages``` permission, so if you
don't want certain roles to have access to this information, make sure they
don't have that permission.

# Requirements

This module makes use of the [Symfony
var-dumper](https://github.com/symfony/var-dumper) being installed and
autoloaded. If you're using
[Composer](https://www.drupal.org/docs/develop/using-composer) to manage your
Drupal project (which you really should), just run ```composer require
"symfony/var-dumper:^3.4"``` in the root of your project, where
```composer.json``` resides.
