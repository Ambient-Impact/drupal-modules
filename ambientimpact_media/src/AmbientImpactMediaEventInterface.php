<?php

namespace Drupal\ambientimpact_media;

/**
 * Interface AmbientImpactMediaEventInterface.
 */
interface AmbientImpactMediaEventInterface {
  /**
   * Alters the information provided by the oEmbed resource url.
   *
   * @Event
   *
   * @see ambientimpact_media_oembed_resource_data_alter()
   * @see hook_oembed_resource_data_alter()
   *
   * @var string
   *
   * @see https://www.drupal.org/project/drupal/issues/3042423
   *   Requires this Drupal core patch.
   */
  const OEMBED_RESOURCE_DATA_ALTER = 'ambientimpact.oembed.data_alter';
}
