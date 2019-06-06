<?php

namespace Drupal\ambientimpact_media\Plugin\migrate\source;

use Drupal\Core\Database\Query\Condition;
use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\d7\FieldableEntity;

/**
 * Drupal 7 file_entity migrate source plug-in.
 *
 * @see https://www.previousnext.com.au/blog/migrating-drupal-7-file-entities-drupal-8-media-entities
 *   Based on code from this blog post.
 *
 * @MigrateSource(
 *   id = "d7_file_entity",
 *   source_provider = "file",
 *   source_module = "file_entity"
 * )
 */
class FileEntity extends FieldableEntity {
  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('file_managed', 'f')
      ->fields('f')
      ->orderBy('f.fid');

    if (isset($this->configuration['type'])) {
      $query->condition('f.type', $this->configuration['type']);
    }

    // Filter by scheme(s), if configured.
    if (isset($this->configuration['scheme'])) {
      $schemes = array();

      // Accept either a single scheme or an array of schemes.
      foreach ((array) $this->configuration['scheme'] as $scheme) {
        $schemes[] = rtrim($scheme) . '://';
      }

      $schemes = array_map([$this->getDatabase(), 'escapeLike'], $schemes);

      // The uri LIKE 'public://%' OR uri LIKE 'private://%'.
      $conditions = new Condition('OR');

      foreach ($schemes as $scheme) {
        $conditions->condition('uri', $scheme . '%', 'LIKE');
      }

      $query->condition($conditions);
    }

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Get Field API field values.
    foreach (
      array_keys($this->getFields('file', $row->getSourceProperty('type')))
      as $field
    ) {
      $fid = $row->getSourceProperty('fid');

      $row->setSourceProperty(
        $field, $this->getFieldValues('file', $field, $fid)
      );
    }
    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return array(
      'fid'       => $this->t('File ID'),
      'uid'       => $this->t('The {users}.uid who added the file. If set to 0, this file was added by an anonymous user.'),
      'filename'  => $this->t('File name'),
      'uri'       => $this->t('The URI to access the file'),
      'filemime'  => $this->t('File MIME Type'),
      'status'    => $this->t('The published status of a file.'),
      'timestamp' => $this->t('The time that the file was added.'),
      'type'      => $this->t('The type of this file.'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['fid']['type'] = 'integer';

    return $ids;
  }
}
