<?php

namespace Drupal\ambientimpact_media\EventSubscriber\Preprocess;

use Drupal\hook_event_dispatcher\Event\Preprocess\ImagePreprocessEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Intrinsic ratio template_preprocess_image() event subscriber service class.
 *
 * @see \Drupal\hook_event_dispatcher\Event\Preprocess\ImagePreprocessEvent
 */
class PreprocessImageIntrinsicRatioEventSubscriber
implements EventSubscriberInterface {
  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      ImagePreprocessEvent::name() => 'preprocessField',
    ];
  }

  /**
   * Prepares variables for image templates.
   *
   * Default template: image.html.twig.
   *
   * This adds the calculated 'ratio' variable, if width and height are
   * available.
   *
   * @param \Drupal\hook_event_dispatcher\Event\Preprocess\ImagePreprocessEvent $event
   *   The event object.
   */
  public function preprocessField(ImagePreprocessEvent $event) {
    /* @var \Drupal\hook_event_dispatcher\Event\Preprocess\Variables\ImageEventVariables $variables */
    $variables = $event->getVariables();

    $width  = $variables->get('width');
    $height = $variables->get('height');

    if (!empty($width) && !empty($height)) {
      $variables->set('ratio', $height / $width);
    }
  }
}
