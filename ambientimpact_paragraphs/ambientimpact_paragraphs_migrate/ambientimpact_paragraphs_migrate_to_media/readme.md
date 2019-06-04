This migrates the previously-migrated image file fields on Paragraph items to
media fields. Depends on the [Migrate File Entities to Media Entities
module](https://www.drupal.org/project/migrate_file_to_media). For issues and
more information, see:

* [D7 Image field to D8 Media field [#3043498]](https://www.drupal.org/project/migrate_file_to_media/issues/3043498)
* [File is not attached to media entity [#3031655]](https://www.drupal.org/project/migrate_file_to_media/issues/3031655#comment-13041350)

--------------

# Steps

Note that this requires [Drush 9 or higher](https://docs.drush.org/en/master/install/).

## Images Paragraph type

1. Detect duplicate files: ```drush migrate:duplicate-file-detection ambientimpact_paragraph_images_to_media_step1```
2. Create media entities: ```drush migrate:import ambientimpact_paragraph_images_to_media_step1```
3. Map created media entities to Paragraph items: ```drush migrate:import ambientimpact_paragraph_images_to_media_step2```
