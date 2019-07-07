This module provides a Drupal 7 migration group
([```d7_ambientimpact```](config/install/migrate_plus.migration_group.d7_ambientimpact.yml))
used by various migrations, and a file migration from Drupal 7
([```d7_file_ambientimpact```](config/install/migrate_plus.migration.d7_file_ambientimpact.yml)).

# Using Composer

If you're using [Composer](https://www.drupal.org/docs/develop/using-composer)
to manage your root project, please see the [readme.md](../readme.md) in the top
level of this repository to have dependencies and patches automatically applied.

## Patches

The following patches are provided in this module's ```composer.json```:

* [Drupal core: Migrate "findMigrationDependencies" function throws error when "process" param is null [#2981837]](https://www.drupal.org/project/drupal/issues/2981837)
* [Migrate Plus: Add a LoadEntity process plugin [#3018849]](https://www.drupal.org/project/migrate_plus/issues/3018849#comment-12928073)
