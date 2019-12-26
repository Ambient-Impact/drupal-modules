<?php

namespace Drupal\ambientimpact_media\Event\Media;

use Drupal\hook_event_dispatcher\Event\EventInterface;
use Symfony\Component\EventDispatcher\Event;
use Drupal\ambientimpact_media\AmbientImpactMediaEventInterface;

/**
 * hook_oembed_resource_data_alter() event.
 *
 * Provides a Hook Event Dispatcher event for this hook.
 *
 * @see https://www.drupal.org/project/drupal/issues/3042423
 *   This hook won't be invoked unless this Drupal core patch is applied.
 *
 * @see ambientimpact_media_oembed_resource_data_alter()
 *   Event is triggered in this hook implementation.
 */
class OEmbedResourceDataAlterEvent extends Event implements EventInterface {
  /**
   * The oEmbed data, parsed into an array.
   *
   * @var array
   */
  protected $data;

  /**
   * The oEmbed URL that $this->data was retrieved from.
   *
   * @var string
   */
  protected $url;

  /**
   * OEmbedResourceDataAlterEvent constructor.
   *
   * @param array &$data
   *   The oEmbed data, parsed into an array.
   *
   * @param string $url
   *   The oEmbed URL that $data was retrieved from.
   */
  public function __construct(array &$data, string $url) {
    $this->data = &$data;
    $this->url  = $url;
  }

  /**
   * Get the parsed oEmbed data for this item as an array.
   *
   * @return array
   */
  public function &getData(): array {
    return $this->data;
  }

  /**
   * Get the URL that the oEmbed data was retrieved from.
   *
   * @return string
   */
  public function getURL(): string {
    return $this->url;
  }

  /**
   * {@inheritdoc}
   */
  public function getDispatcherType() {
    return AmbientImpactMediaEventInterface::OEMBED_RESOURCE_DATA_ALTER;
  }
}
