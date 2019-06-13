<?php

namespace Drupal\ambientimpact_media\Element;

use Drupal\Core\Render\Element\RenderElement;

/**
 * Provides a media play overlay render element.
 *
 * @RenderElement("media_play_overlay")
 */
class MediaPlayOverlay extends RenderElement {
  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [
      '#theme'    => 'media_play_overlay',
    ];
  }
}
