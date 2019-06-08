# Requirements

## [```d7_paragraph_images```](config/install/migrate_plus.migration.d7_paragraph_images.yml) migration

This requires the
[```d7_file_ambientimpact```](../../ambientimpact_migrate/config/install/migrate_plus.migration.d7_file_ambientimpact.yml)
migration to have been run beforehand.

## [```d7_paragraph_video```](config/install/migrate_plus.migration.d7_paragraph_video.yml) migration

This requires the
[```d7_file_entity_vimeo```](../../ambientimpact_media/config/optional/migrate_plus.migration.d7_file_entity_vimeo.yml)
and
[```d7_file_entity_youtube```](../../ambientimpact_media/config/optional/migrate_plus.migration.d7_file_entity_youtube.yml)
migrations from the [```ambientimpact_media```](../../ambientimpact_media)
module to have been run beforehand and the [LoadEntity process plug-in patch for
Migrate
Plus](https://www.drupal.org/project/migrate_plus/issues/3018849#comment-12928073)
applied. Once the Paragraph items have been migrated, you can safely delete/roll
back the ```d7_file_entity_vimeo``` and ```d7_file_entity_youtube``` migrations,
as those media entities are no longer needed ([Video Embed
Field](https://www.drupal.org/project/video_embed_field) stores the video URL
directly in the field).

# File field to media field migration

This migrates the previously-migrated image file fields on Paragraph items to
media fields. Depends on the [Migrate File Entities to Media Entities
module](https://www.drupal.org/project/migrate_file_to_media). For issues and
more information, see:

* [D7 Image field to D8 Media field [#3043498]](https://www.drupal.org/project/migrate_file_to_media/issues/3043498)
* [File is not attached to media entity [#3031655]](https://www.drupal.org/project/migrate_file_to_media/issues/3031655#comment-13041350)

## Steps

Note that these require [Drush 9 or
higher](https://docs.drush.org/en/master/install/).

### Animated GIFs Paragraph type

1. Detect duplicate files: ```drush migrate:duplicate-file-detection d8_paragraph_animated_gifs_media_step1```
2. Create media entities: ```drush migrate:import d8_paragraph_animated_gifs_media_step1```
3. Map created media entities to Paragraph items: ```drush migrate:import d8_paragraph_animated_gifs_media_step2```
4. Check that the media fields on the Animated GIFs Paragraph items have been populated correctly; if they have, you can safely delete the ```field_animated_gifs_migrated``` field on that bundle.

### Images Paragraph type

1. Detect duplicate files: ```drush migrate:duplicate-file-detection d8_paragraph_images_media_step1```
2. Create media entities: ```drush migrate:import d8_paragraph_images_media_step1```
3. Map created media entities to Paragraph items: ```drush migrate:import d8_paragraph_images_media_step2```
4. Check that the media fields on the Images Paragraph items have been populated correctly; if they have, you can safely delete the ```field_images_migrated``` field on that bundle.
