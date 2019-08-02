This document attempts to detail the process of upgrading
[ambientimpact.com](https://ambientimpact.com/) from Drupal 7 to Drupal 8.

# Overview

The [Upgrading to Drupal 8 documentation on
Drupal.org](https://www.drupal.org/docs/8/upgrade) is recommended reading before
going any further as it explains many of the key concepts and the how and why of
upgrading/migration.

There are a few important concepts to keep in mind:

* The migration process requires you to install a separate Drupal 8 site, rather than upgrading the Drupal 7 site in place. This makes it easier to compare sites side by side and allows you to run and roll back migrations multiple times.
* Drupal core's migration process currently requires that the new site be completely empty, without any content (nodes) created before the migration is run. Attempting to run migrations after even a single node has been created manually [will cause errors](https://www.drupal.org/project/migrate_plus/issues/2843323). While it's theoretically possible to put together a custom workflow to migrate content into a site that already has user-created content, that's out of the scope of this document.

# Setting up

## Database

After installing a new Drupal 8 site, you'll need to add a connection to the
legacy Drupal 7 database in your settings.php:

```
// The legacy, local Drupal 7 database to migrate from.
$databases['migrate_drupal7']['default'] = [
  'database'  => '',
  'username'  => '',
  'password'  => '',
  'driver'    => 'mysql',
  'host'      => '',
  'port'      => '',
];
```

You'll have to fill in the details for your database. If you're developing
locally on Acquia DevDesktop like I did, here's what I used:

```
// The legacy, local Drupal 7 database to migrate from.
$databases['migrate_drupal7']['default'] = [
  'database'  => 'ambientimpact_drupal7_dev',
  'username'  => 'drupaluser',
  'password'  => '',
  'driver'    => 'mysql',
  'host'      => '127.0.0.1',
  'port'      => 33067,
];
```

Note the lack of a password - DevDesktop automatically sets up the
```drupaluser``` user without a password, which is fine for local development
but should *never* be done for a site accessible over the internet.

## Modules

The following core and contrib modules need to be installed:

* Drupal core's Migrate and Migrate Drupal
* [Migrate Plus](https://www.drupal.org/project/migrate_plus)
* [Migrate Tools](https://www.drupal.org/project/migrate_tools)
* [Migrate File Entities to Media Entities](https://www.drupal.org/project/migrate_file_to_media)

Additionally, the relevant modules from this repository need to be installed:

* Ambient.Impact - Migrate (```ambientimpact_migrate```)
* Ambient.Impact - Paragraphs migrate (```ambientimpact_paragraphs_migrate```)
* Ambient.Impact - Web migrate (```ambientimpact_web_migrate```)
* Ambient.Impact - Portfolio migrate (```ambientimpact_portfolio_migrate```)

# Resources and guides

This section lists various resources used to build these working migrations in
the hope that they'll be useful to others who are attempting migrations.

## Drupal.org documentation

* [Upgrading to Drupal 8](https://www.drupal.org/docs/8/upgrade)
* [Migrate API](https://www.drupal.org/docs/8/api/migrate-api)
* [List of core Migrate process plugins](https://www.drupal.org/docs/8/api/migrate-api/migrate-process-plugins/list-of-core-migrate-process-plugins)
* [List of process plugins provided by Migrate Plus](https://www.drupal.org/docs/8/api/migrate-api/migrate-process-plugins/list-of-process-plugins-provided-by-migrate-plus)

## Drupal.org issue queue searches

* [Drupal core](https://www.drupal.org/project/issues/search/drupal?text=&assigned=&submitted=&project_issue_followers=&version%5B%5D=8.x&component%5B%5D=migration+system&issue_tags_op=%3D&issue_tags=)
* [Migrate Plus](https://www.drupal.org/project/issues/migrate_plus?text=&status=All&priorities=All&categories=All&version=8.x&component=All)
* [Paragraphs](https://www.drupal.org/project/issues/paragraphs?text=migrate&status=Open&priorities=All&categories=All&version=8.x&component=All)
* [Entity Reference Revisions](https://www.drupal.org/project/issues/entity_reference_revisions?text=paragraph+migrate&status=All&priorities=All&categories=All&version=8.x&component=All) (used by Paragraphs)

## Drupal.org issues

* [Paragraphs: Migration templates for automated migration from Drupal 7 version [#2841593]](https://www.drupal.org/project/paragraphs/issues/2841593)
* [Paragraphs: Field collections deriver and base migration [#2911244]](https://www.drupal.org/project/paragraphs/issues/2911244) (is for Drupal 7 field collections, but was useful to look at)
* [Paragraphs: Migration plugin source for D7_Paragraph? [#2917749]](https://www.drupal.org/project/paragraphs/issues/2917749) (some resources linked in comments)

## Third-party

* [Building a custom migration in Drupal 8 series on deninet.com](https://deninet.com/blog/2017/05/29/building-custom-migration-drupal-8-part-1-getting-started); [Part 4: Files and Content](https://deninet.com/blog/2017/06/13/building-custom-migration-drupal-8-part-4-files-and-content) and [Part 5: Paragraphs](https://deninet.com/blog/2017/06/18/building-custom-migration-drupal-8-part-5-paragraphs) were especially useful in building custom migrations.
* [Migrating Content References in Drupal 8 | Mediacurrent](https://www.mediacurrent.com/blog/migrating-content-references-drupal-8/)
* [Migrating Paragraphs in Drupal 8 – Four Kitchens](https://www.fourkitchens.com/blog/development/migrating-paragraphs-drupal-8/)
* [Drupal 7 to 8 Migration: Step by Step Notes | IAC Web Development Resources](https://webdev.iac.gatech.edu/drupal-8-migration)
* [Drupal 7 to Drupal 8 Migration - 101 and Observations | Tanay Sai](http://www.tanay.co.in/blog/drupal-7-drupal-8-migration-101-and-observations.html)
* [Custom Drupal-to-Drupal Migrations with Migrate Tools | Drupalize.Me](https://drupalize.me/blog/201604/custom-drupal-drupal-migrations-migrate-tools)

The following were not used in this migration but are listed because they may be
useful for other migrations:

* [A slick migration trick - convert columns to multi-value field with subfields](https://www.freelock.com/blog/john-locke/2017-12/slick-migration-trick-convert-columns-multi-value-field-subfields)
* [Migration of CSV Data into Paragraphs | MTech](https://www.mtech-llc.com/blog/charlotte-leon/migration-csv-data-paragraphs)
* [Troubleshooting a Drupal 8 Migration | MTech](https://www.mtech-llc.com/blog/lucas-hedding/troubleshooting-drupal-8-migration)
* [Drupal 8 CSV Migration | U.Group - Advanced Technology & Creative Design](https://u.group/thinking/drupal-8-csv-migration/)

## Tools and modules

* [Configuration development](https://www.drupal.org/project/config_devel)
* [Config Delete](https://www.drupal.org/project/config_delete)
* [Migrate Devel](https://www.drupal.org/project/migrate_devel)

# Migrations

The migrations are broken down into several discrete groups requiring their own
workflows. Note that commands are in the [Drush
9+](https://docs.drush.org/en/master/install/) format, i.e. with colon (```:```)
characters; most can be run in Drush 8 by replacing the colon with a dash
(```-```).

Most of these migrations require the [```ambientimpact_migrate```
module](ambientimpact_migrate) to be enabled. Before you enable it, you have to
provide the absolute path to the Drupal 7 root directory by editing the
```source_base_path: ''``` line in
[```ambientimpact_migrate/config/install/migrate_plus.migration.d7_file_ambientimpact.yml```](ambientimpact_migrate/config/install/migrate_plus.migration.d7_file_ambientimpact.yml).
Note that this path is the root of the Drupal 7 install, not containing the
```sites/<site>/files``` path. If you've already enabled the module, uninstall
it, make the edit, and then install it again. Alternatively, you can install
```ambientimpact_migrate``` and then edit the configuration manually using the
[Devel module](https://www.drupal.org/project/devel)'s Config editor.

All other migrations not listed here were handled using the migrations created
by Drupal core's upgrade process; for example, various site settings and other
basic stuff not requiring custom configuration and code.

## Drupal 7 public file entities to Drupal 8 file entities

Copy all relevant files from the Drupal 7 ```sites/<site>/files``` directory to
the Drupal 8 ```sites/<site>/files``` directory; the
```sites/<site>/files/paragraphs``` directory holds images and animated GIFs for
web snippets, and the ```sites/<site>/files/project_images``` directory holds
portfolio project image files.

Once files are copied, run the following command:

```
drush migrate:import d7_file_ambientimpact
```

## Drupal 7 taxonomy vocabularies and terms

There doesn't seem to be an out of the box way to migrate specific vocabularies,
so the following will migrate all vocabularies:

```
drush migrate:import d7_taxonomy_vocabulary
```

Then, you can import all the terms in the web tags and project categories
vocabularies:

```
drush migrate:import d7_taxonomy_term:web_tags,d7_taxonomy_term:project_categories
```

## Drupal 7 portfolio project nodes to Drupal 8 portfolio project nodes

Enable the [```ambientimpact_porfolio_migrate```
module](ambientimpact_porfolio/ambientimpact_porfolio_migrate) and then run:

```
drush migrate:import d7_node_project
```

## Drupal 7 Paragraph items to Drupal 8 Paragraph items

Install the
[```ambientimpact_paragraphs_migrate```](ambientimpact_paragraphs/ambientimpact_paragraphs_migrate)
and [```ambientimpact_media```](ambientimpact_media) modules.

Make sure to run ```d7_file_ambientimpact```.

Apply the [LoadEntity process plug-in patch for Migrate
Plus](https://www.drupal.org/project/migrate_plus/issues/3018849#comment-12928073).

Then, run the following:

```
drush migrate:import d7_file_entity_vimeo,d7_file_entity_youtube,d7_paragraph_animated_gifs,d7_paragraph_code,d7_paragraph_images,d7_paragraph_text,d7_paragraph_video
```

See the
[```ambientimpact_paragraphs_migrate```](ambientimpact_paragraphs/ambientimpact_paragraphs_migrate)
module for more information.

## Drupal 7 web snippet nodes to Drupal 8 web snippet nodes

Install the [```ambientimpact_web_migrate```
module](ambientimpact_web/ambientimpact_web_migrate) and then run the following:

```
drush migrate:import d7_node_web_snippet
```

## Drupal 8 public file entities to media entities

This is detailed in [the ```ambientimpact_paragraphs_migrate```
module](ambientimpact_paragraphs/ambientimpact_paragraphs_migrate/readme.md#file-field-to-media-field-migration)

## tl;dr

This is a quick and dirty reference used during development. This assumes public
files have been copied over, all modules have been enabled, and vocabularies
already exist or have been migrated.

### Migrate

Absolutely everything:

```
drush migrate:import d7_file_ambientimpact,d7_file_entity_vimeo,d7_file_entity_youtube,d7_taxonomy_term:web_tags,d7_taxonomy_term:project_categories,d7_node_project,d7_paragraph_animated_gifs,d7_paragraph_code,d7_paragraph_images,d7_paragraph_text,d7_paragraph_video,d7_node_web_snippet

drush migrate:duplicate-file-detection d8_paragraph_animated_gifs_media_step1

drush migrate:duplicate-file-detection d8_paragraph_images_media_step1

drush migrate:import d8_paragraph_animated_gifs_media_step1,d8_paragraph_animated_gifs_media_step2,d8_paragraph_images_media_step1,d8_paragraph_images_media_step2
```

Just portfolio projects:

```
drush migrate:import d7_file_ambientimpact,d7_taxonomy_term:project_categories,d7_node_project
```

Just web snippets:

```
drush migrate:import d7_file_ambientimpact,d7_file_entity_vimeo,d7_file_entity_youtube,d7_taxonomy_term:web_tags,d7_paragraph_animated_gifs,d7_paragraph_code,d7_paragraph_images,d7_paragraph_text,d7_paragraph_video,d7_node_web_snippet

drush migrate:duplicate-file-detection d8_paragraph_animated_gifs_media_step1

drush migrate:duplicate-file-detection d8_paragraph_images_media_step1

drush migrate:import d8_paragraph_animated_gifs_media_step1,d8_paragraph_animated_gifs_media_step2,d8_paragraph_images_media_step1,d8_paragraph_images_media_step2
```

### Roll back

Absolutely everything:

```
drush migrate:rollback d8_paragraph_animated_gifs_media_step2,d8_paragraph_animated_gifs_media_step1,d8_paragraph_images_media_step2,d8_paragraph_images_media_step1,d7_node_web_snippet,d7_paragraph_animated_gifs,d7_paragraph_code,d7_paragraph_images,d7_paragraph_text,d7_paragraph_video,d7_node_project,d7_file_entity_vimeo,d7_file_entity_youtube,d7_file_ambientimpact,d7_taxonomy_term:web_tags,d7_taxonomy_term:project_categories
```

Just portfolio projects:

```
drush migrate:rollback d7_node_project,d7_taxonomy_term:project_categories,d7_file_ambientimpact
```

Just web snippets:

```
drush migrate:rollback d8_paragraph_animated_gifs_media_step2,d8_paragraph_animated_gifs_media_step1,d8_paragraph_images_media_step2,d8_paragraph_images_media_step1,d7_node_web_snippet,d7_paragraph_animated_gifs,d7_paragraph_code,d7_paragraph_images,d7_paragraph_text,d7_paragraph_video,d7_file_entity_vimeo,d7_file_entity_youtube,d7_file_ambientimpact,d7_taxonomy_term:web_tags
```
