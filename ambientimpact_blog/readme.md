This Drupal module contains a blog post content type and related Views.

**Warning**: while this is generally production-ready, it's not guaranteed to
maintain a stable API and may occasionally contain bugs, being a
work-in-progress. Stable releases may be provided at a later date.

----

# Requirements

* [Drupal 9](https://www.drupal.org/download) ([Drupal 8 is end-of-life](https://www.drupal.org/psa-2021-11-30))

* [Composer](https://getcomposer.org/)

## Drupal dependencies

Before attempting to install this, you must add the Composer repositories as
described in the installation instructions for these dependencies:

* The [`ambientimpact_views`](https://github.com/Ambient-Impact/drupal-ambientimpact-views) module.

----

# Installation

## Composer

Ensure that you have your Drupal installation set up with the correct Composer
installer types such as those provided by [the `drupal/recommended-project`
template](https://www.drupal.org/docs/develop/using-composer/starting-a-site-using-drupal-composer-project-templates#s-drupalrecommended-project).
If you're starting from scratch, simply requiring that template and following
[the Drupal.org Composer
documentation](https://www.drupal.org/docs/develop/using-composer/starting-a-site-using-drupal-composer-project-templates)
should get you up and running.

Then, in your root `composer.json`, add the following to the `"repositories"`
section:

```json
"drupal/ambientimpact_blog": {
  "type": "vcs",
  "url": "https://github.com/Ambient-Impact/drupal-ambientimpact-blog.git"
}
```

Then, in your project's root, run `composer require
"drupal/ambientimpact_blog:1.x-dev@dev"` to have Composer install the module and
its required dependencies for you.
