This Drupal module contains various
[Paragraph](https://www.drupal.org/project/paragraphs) entity types, their
fields, display modes, and supporting code.

**Warning**: while this is generally production-ready, it's not guaranteed to
maintain a stable API and may occasionally contain bugs, being a
work-in-progress. Stable releases may be provided at a later date.

----

# Requirements

* [Drupal 9](https://www.drupal.org/download) ([Drupal 8 is end-of-life](https://www.drupal.org/psa-2021-11-30))

* [Composer](https://getcomposer.org/)

----

# Installation

## Composer

### Set up

Ensure that you have your Drupal installation set up with the correct Composer
installer types such as those provided by [the `drupal/recommended-project`
template](https://www.drupal.org/docs/develop/using-composer/starting-a-site-using-drupal-composer-project-templates#s-drupalrecommended-project).
If you're starting from scratch, simply requiring that template and following
[the Drupal.org Composer
documentation](https://www.drupal.org/docs/develop/using-composer/starting-a-site-using-drupal-composer-project-templates)
should get you up and running.

### Repository

In your root `composer.json`, add the following to the `"repositories"` section:

```json
"drupal/ambientimpact_paragraphs": {
  "type": "vcs",
  "url": "https://github.com/Ambient-Impact/drupal-ambientimpact-paragraphs.git"
}
```

### Patching

This provides [one or more patches](#patches). These can be applied automatically by the the
[`cweagans/composer-patches`](https://github.com/cweagans/composer-patches/tree/1.x)
Composer plug-in, but some set up is required before installing this module.
Notably, you'll need to [enable patching from
dependencies](https://github.com/cweagans/composer-patches/tree/1.x#allowing-patches-to-be-applied-from-dependencies) (such as this module 🤓). At
a minimum, you should have these values in your root `composer.json` (merge with
existing keys as needed):


```json
{
  "require": {
    "cweagans/composer-patches": "^1.7.0"
  },
  "config": {
    "allow-plugins": {
      "cweagans/composer-patches": true
    }
  },
  "extra": {
    "enable-patching": true,
    "patchLevel": {
      "drupal/core": "-p2"
    }
  }
}

```

**Important**: The 1.x version of the plug-in is currently required because it
allows for applying patches from a dependency; this is not implemented nor
planned for the 2.x branch of the plug-in.

### Installing

Once you've completed all of the above, run `composer require
"drupal/ambientimpact_paragraphs:1.x-dev@dev"` in the root of your project to
have Composer install this and its required dependencies for you.

# Patches

The following patches are supplied (see [Patching](#patching) above):

* [GeSHi Filter module](https://www.drupal.org/project/geshifilter):

  * [Theme functions are deprecated in geshifield [#3137937]](https://www.drupal.org/project/geshifilter/issues/3137937#comment-13895126)
