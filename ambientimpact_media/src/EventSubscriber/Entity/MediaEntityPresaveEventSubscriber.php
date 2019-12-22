<?php

namespace Drupal\ambientimpact_media\EventSubscriber\Entity;

use Drupal\file\Entity\File;
use Drupal\Core\Image\ImageFactory;
use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
use Drupal\hook_event_dispatcher\Event\Entity\EntityPresaveEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Media entity hook_entity_presave() event subscriber.
 */
class MediaEntityPresaveEventSubscriber implements EventSubscriberInterface {
  /**
   * The Drupal image factory service.
   *
   * @var \Drupal\Core\Image\ImageFactory
   */
  protected $imageFactory;

  /**
   * Event subscriber constructor; saves dependencies.
   *
   * @param \Drupal\Core\Image\ImageFactory $imageFactory
   *   The Drupal image factory service.
   */
  public function __construct(
    ImageFactory $imageFactory
  ) {
    $this->imageFactory = $imageFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      HookEventDispatcherInterface::ENTITY_PRE_SAVE => 'entityPresave',
    ];
  }

  /**
   * Fixes incorrect stored YouTube thumbnail dimensions.
   *
   * This fixes incorrect stored YouTube thumbnail dimensions by reading the
   * actual thumbnail image dimensions from the file. This is due to a Drupal
   * core bug that always stores YouTube thumbnails as 180x180, despite getting
   * the correct dimensions from the oEmbed data. This fix seems to work because
   * the thumbnail image has already been fetched by Drupal by the time this
   * hook is invoked.
   *
   * Note that a media entity needs to be saved either programmatically or via
   * the Drupal UI for this to take effect.
   *
   * Additionally, this hook currently always returns due to the dimensions
   * being adjusted elsewhere - probably in the Image component - but is present
   * as a fallback in case it's needed. Will be removed once the Drupal core bug
   * is fixed.
   *
   * @param \Drupal\hook_event_dispatcher\Event\Entity\EntityPresaveEvent $event
   *   The event object.
   *
   * @see https://www.drupal.org/project/drupal/issues/3088168
   *   Drupal core issue detailing incorrect YouTube thumbnail dimensions.
   */
  public function entityPresave(EntityPresaveEvent $event) {
    /** @var \Drupal\Core\Entity\EntityInterface A Drupal entity object. */
    $entity = $event->getEntity();

    // Bail if this isn't a remote_video media entity or the thumbnail
    // dimensions don't need fixing.
    if (
      $entity->getEntityTypeId()    !== 'media' ||
      $entity->bundle()             !== 'remote_video' ||
      $entity->thumbnail[0]->width  !== '180' &&
      $entity->thumbnail[0]->height !== '180'
    ) {
      return;
    }

    // Create a File entity from the file ID.
    /** @var \Drupal\file\FileInterface|null A File entity or null if the ID is
        not found. */
    $file = File::load($entity->thumbnail[0]->target_id);

    // If we couldn't load a valid Drupal file entity, skip this entity.
    if (empty($file)) {
      return;
    }

    // Get the file URI so that we can read the stored file.
    $fileURI = $file->getFileUri();

    /** @var \Drupal\Core\Image\ImageInterface An Image instance built from the
        file URI. */
    $imageInstance = $this->imageFactory->get($fileURI);

    $dimensions = [
      'width'   => $imageInstance->getWidth(),
      'height'  => $imageInstance->getHeight(),
    ];

    if ($dimensions['width'] !== null && $dimensions['height'] !== null) {
      $entity->thumbnail[0]->width  = $dimensions['width'];
      $entity->thumbnail[0]->height = $dimensions['height'];
    }
  }
}
