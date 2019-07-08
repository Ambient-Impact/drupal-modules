Third-party front-end libraries are managed using
[Composer](https://getcomposer.org/) via [Asset
Packagist](https://asset-packagist.org/) as recommended by the [Drupal.org
Composer
documentation](https://www.drupal.org/docs/develop/using-composer/using-composer-to-install-drupal-and-manage-dependencies#third-party-libraries).
For more information on using Composer, see this project's
[readme.md](readme.md#using-composer) and the [Drupal.org Composer
documentation](https://www.drupal.org/docs/develop/using-composer).

# ```drupal-root/libraries``` versus ```module/assets/vendor```

Because of the way that the recommended Composer configuration works, libraries
are always installed to ```drupal-root/libraries```, which could cause issues if
another module installs a different version of that library to that same
location. Ideally, libraries would be stored in ```assets/vendor``` inside of
each module's directory to avoid such conflicts, but this does not seem
straightforward to achieve with the current Composer workflow.
