This respository contains Drupal modules that provide a framework for back-end
and front-end development which includes many PHP, JavaScript, and Sass
utilities, UX improvements and widgets, base styles, and libraries; for more
information, see [the component system
explainer](component_explainer.md).
These are used across multiple sites, including
[ambientimpact.com](https://ambientimpact.com/).

While not required for these modules to operate, the
[```ambientimpact_base```](https://gitlab.com/Ambient.Impact/drupal-themes)
theme integrates heavily with these modules.

**Warning**: while these are generally production-ready, they're not guaranteed
to maintain a stable API and may occasionally contain bugs, being a
work-in-progress. Stable releases may be provided at a later date.

-----------------

Cross-platform browser testing done via
[BrowserStack](https://www.browserstack.com/).

<img src="browserstack-logo.svg" alt="BrowserStack logo" width="128" />

# Using Composer

If you're using [Composer](https://www.drupal.org/docs/develop/using-composer)
to manage your root project (which you really should), assuming your project is
using
[```drupal-composer/drupal-project```](https://github.com/drupal-composer/drupal-project)
and has installed both
[```wikimedia/composer-merge-plugin```](https://github.com/wikimedia/composer-merge-plugin)
and [```cweagans/composer-patches```](https://github.com/cweagans/composer-patches),
you must add the following to your root ```composer.json```:

```
"extra": {
  "merge-plugin": {
    "include": [
      "drupal/modules/ambientimpact/*/composer.json",
      "drupal/modules/ambientimpact/*/*/composer.json"
    ],
    "merge-extra": true,
    "merge-extra-deep": true
  }
}
```

Once everything is configured, running [```composer
install```](https://getcomposer.org/doc/03-cli.md#install-i) in your project
root is all you have to do.

## Notes

* The ```merge-plugin``` item should already exist by default in your root ```composer.json```, so you'll have to merge it in manually.
* If you install this to a different location, update the path accordingly.
* You must define patches in your root ```composer.json``` and not in an external file via the ```patches-file``` setting for ```wikimedia/composer-merge-plugin``` to be able to merge in patches from dependencies.
* If your Drupal project was already installed manually or via Drush, you can use [grasmash/composerize-drupal](https://github.com/grasmash/composerize-drupal) to convert it to Composer.

## Third-party front-end libraries

These are managed via Composer like back-end dependencies. See
[third-party_libraries.md](third-party_libraries.md) for more information.
