<?php

namespace Drupal\ambientimpact_media\EventSubscriber\Media;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\ambientimpact_media\AmbientImpactMediaEventInterface;
use Drupal\ambientimpact_media\Event\Media\OEmbedResourceDataAlterEvent;
use GuzzleHttp\Client as GuzzleClient;

/**
 * YouTube thumbnail oEmbed hook_oembed_resource_data_alter() event subscriber.
 */
class OEmbedResourceDataAlterYouTubeThumbnailEventSubscriber implements EventSubscriberInterface {

  /**
   * Array of thumbnail sizes higher resolution than 'hqdefault' to try.
   *
   * These should be ordered from largest to smallest.
   *
   * @var string[]
   *
   * @see https://stackoverflow.com/a/20542029
   *   List taken from this Stackoverflow answer.
   */
  protected $thumbnailTypes = ['maxresdefault', 'sddefault'];

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      AmbientImpactMediaEventInterface::MEDIA_OEMBED_RESOURCE_DATA_ALTER =>
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

    foreach ($this->thumbnailTypes as $thumbnailName) {

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

        /** @var array|false */
        $imageSizeData = \getimagesizefromstring(
          $response->getBody()->getContents()
        );

        // Check that \getimagesizefromstring() was able to determine the image
        // size. Note the checks for zero, which can happen in some edge cases
        // if it was passed valid image data but it couldn't determine the
        // dimensions due to the image format.
        //
        // @see https://www.php.net/manual/en/function.getimagesize.php
        if (
          !\is_array($imageSizeData) ||
          empty($imageSizeData[0]) ||
          $imageSizeData[0] === 0 ||
          empty($imageSizeData[1]) ||
          $imageSizeData[1] === 0
        ) {
          continue;
        }

        $data['thumbnail_url']    = $testThumbnailURL;
        $data['thumbnail_width']  = $imageSizeData[0];
        $data['thumbnail_height'] = $imageSizeData[1];

        break;

      }

    }

  }

}
