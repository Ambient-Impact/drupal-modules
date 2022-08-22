<?php

declare(strict_types=1);

namespace Drupal\ambientimpact_media\EventSubscriber\Media;

use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
use Drupal\media_event_dispatcher\Event\Media\OEmbedResourceUrlAlterEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Vimeo thumbnail oEmbed hook_oembed_resource_url_alter() event subscriber.
 */
class OEmbedResourceURLAlterVimeoThumbnailEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      HookEventDispatcherInterface::MEDIA_OEMBED_RESOURCE_DATA_ALTER =>
        'oEmbedResourceURLAlter',
    ];
  }

  /**
   * Get a high resolution thumbnail for Vimeo oEmbed resources.
   *
   * This informs Vimeo to return the thumbnail at the maximum size they
   * support, which is currently 1280 pixels wide.
   *
   * @param \Drupal\media_event_dispatcher\Event\Media\OEmbedResourceUrlAlterEvent $event
   *   The event object.
   */
  public function oEmbedResourceURLAlter(OEmbedResourceUrlAlterEvent $event) {

    /** @var array */
    $parsedURL = &$event->getParsedUrl();

    if (
      \strpos($parsedURL['path'], 'https://vimeo.com/') === 0 &&
      !isset($parsedURL['query']['width'])
    ) {
      $parsedURL['query']['width'] = '1280';
    }

  }

}
