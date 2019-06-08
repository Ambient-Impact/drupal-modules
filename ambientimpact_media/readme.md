This module provides functionality relating to various media types, i.e. images,
video, etc.

# Migrate

In Drupal 7, the [Embedded Media Field
module](https://www.drupal.org/project/emfield) was used to embed videos in web
snippets, but as that module does not have a Drupal 8 port, the [Video Embed
Field module](https://www.drupal.org/project/video_embed_field) was chosen to
replace it. Video Embed Field does have [a migrate plug-in from the Drupal 6
Embedded Media Field
module](https://git.drupalcode.org/project/video_embed_field/blob/8.x-2.x/src/Plugin/migrate/cckfield/EmvideoField.php),
but not for migrating from Drupal 7. Thankfully, the blog post [Migrating Drupal
7 File Entities to Drupal 8 Media
Entities](https://www.previousnext.com.au/blog/migrating-drupal-7-file-entities-drupal-8-media-entities)
by Jibran Ijaz details how to do this, and was adapted for the
[```d7_file_entity_vimeo```](config/optional/migrate_plus.migration.d7_file_entity_vimeo.yml)
and
[```d7_file_entity_youtube```](config/optional/migrate_plus.migration.d7_file_entity_youtube.yml)
migrations, the [Vimeo](src/Plugin/migrate/process/Vimeo.php) and
[YouTube](src/Plugin/migrate/process/YouTube.php) process plug-ins, and the
[FileEntity source plug-in](src/Plugin/migrate/source/FileEntity.php).
