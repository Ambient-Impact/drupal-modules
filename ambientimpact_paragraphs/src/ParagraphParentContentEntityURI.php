<?php

namespace Drupal\ambientimpact_paragraphs;

use Drupal\Core\Url;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Paragraph entity parent content entity URI class.
 */
class ParagraphParentContentEntityURI {
  /**
   * Paragraph entity URI callback method.
   *
   * This callback returns the parent node's URI to fix an issue in Drupal core
   * that would cause a fatal error when attempting to get the paragraph entity
   * URL, which some modules sometimes do.
   *
   * @param \Drupal\paragraphs\Entity\Paragraph $paragraph
   *   A paragraph entity.
   *
   * @see \Drupal\paragraphs\Entity\Paragraph::getParentEntity()
   *   Gets the parent entity of the paragraph.
   *
   * @see \Drupal\Core\Url::fromRoute()
   *
   * @see https://www.drupal.org/project/paragraphs/issues/2826834
   *   Paragraphs issue detailing the error and linking to related core issues.
   *
   * @todo Should we check if $parent->getEntityTypeId() === 'node' before
   * returning the node route? What if a node is not the parent entity?
   */
  public static function URICallback(Paragraph $paragraph) {
    $parent = $paragraph->getParentEntity();

    return Url::fromRoute('entity.node.canonical', ['node' => $parent->id()]);
  }
}
