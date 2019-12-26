<?php

namespace Drupal\ambientimpact_media\EventSubscriber\Media;

use Drupal\hook_event_dispatcher\Event\Entity\EntityPresaveEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\ambientimpact_media\AmbientImpactMediaEventInterface;
use Drupal\ambientimpact_media\Event\Media\OEmbedResourceDataAlterEvent;
use GuzzleHttp\Client as GuzzleClient;

class OEmbedResourceDataAlterYouTubeThumbnailEventSubscriber implements
EventSubscriberInterface {
  /**
   * Array of thumbnail sizes higher resolution than 'hqdefault' to try.
   *
   * These should be ordered from largest to smallest.
   *
   * @var array
   *
   * @see https://stackoverflow.com/a/20542029
   *   List taken from this Stackoverflow answer.
   */
  protected $thumbnailTypes = [
    'maxresdefault' => [
      'width'   => 1920,
      'height'  => 1080,
    ],
    'sddefault' => [
      'width'   => 640,
      'height'  => 480,
    ],
  ];

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      AmbientImpactMediaEventInterface::OEMBED_RESOURCE_DATA_ALTER =>
        'oEmbedResourceDataAlter',
    ];
  }

  /**
   * Attempts to fetch the highest resolution YouTube video thumbnail available.
   *
   * This tries to fetch the highest resolution video thumbnail by sending
   * requests via Guzzle for the various formats from highest to lowest, using
   * the first one that doesn't return a 404.
   *
   * @param \Drupal\ambientimpact_media\Event\Media\OEmbedResourceDataAlterEvent $event
   *   The event object.
   */
  public function oEmbedResourceDataAlter(OEmbedResourceDataAlterEvent $event) {
    $data = &$event->getData();

    if (
      $data['provider_name'] !== 'YouTube' ||
      \strpos($data['thumbnail_url'], 'hqdefault.jpg') === false
    ) {
      return;
    }

    $client = new GuzzleClient();

    foreach ($this->thumbnailTypes as $thumbnailName => $thumbnailDimensions) {
      // Replace 'hqdefault' in the thumbnail URL with the current type we're
      // testing for.
      $testThumbnailURL = \str_replace(
        'hqdefault',
        $thumbnailName,
        $data['thumbnail_url']
      );

      // We need to wrap the request in a try {} catch {} because Guzzle will
      // throw an exception on a 404.
      try {
        $response = $client->request('GET', $testThumbnailURL);

      // Got an exception? Skip to the next thumbnail size, assuming this
      // returned a 404 or ran into some other error.
      } catch (\Exception $exception) {
        continue;
      }

      // If this was a 200 response, update the thumbnail URL and dimensions
      // with the higher resolution and break out of the loop.
      if ($response->getStatusCode() === 200) {
        $data['thumbnail_url']    = $testThumbnailURL;
        $data['thumbnail_width']  = $thumbnailDimensions['width'];
        $data['thumbnail_height'] = $thumbnailDimensions['height'];

        break;
      }
    }
  }
}
