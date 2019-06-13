This document assumes you've already set up a connection to the legacy Drupal 7
database in your settings.php.

The following modules are required:

* Drupal core's Migrate and Migrate Drupal
* [Migrate Plus](https://www.drupal.org/project/migrate_plus)
* [Migrate Tools](https://www.drupal.org/project/migrate_tools)
* [Migrate File Entities to Media Entities](https://www.drupal.org/project/migrate_file_to_media)

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
