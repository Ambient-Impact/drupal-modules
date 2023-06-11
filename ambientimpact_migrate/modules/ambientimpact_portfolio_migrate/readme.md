This module contains the [```d7_node_project```
migration](config/install/migrate_plus.migration.d7_node_project.yml), which
depends on the
[```d7_file_ambientimpact```](../../ambientimpact_migrate/config/install/migrate_plus.migration.d7_file_ambientimpact.yml)
and ```d7_taxonomy_term:project_categories``` migrations, the latter of which is
by the core Migrate framework so it's not included here.

# File field to media field migration

This migrates the previously-migrated image file fields on Project nodes to
media fields. Depends on the [Migrate File Entities to Media Entities
module](https://www.drupal.org/project/migrate_file_to_media).

## Steps

Note that these require [Drush 9 or
higher](https://docs.drush.org/en/master/install/).

1. Detect duplicate files: ```drush migrate:duplicate-file-detection d8_node_project_images_media_step1```
2. Create media entities: ```drush migrate:import d8_node_project_images_media_step1```
3. Map created media entities to Paragraph items: ```drush migrate:import d8_node_project_images_media_step2```
4. Check that the media fields on the Images Paragraph items have been populated correctly; if they have, you can safely delete the ```field_project_images``` field on that bundle.
