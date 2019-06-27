<?php

namespace Drupal\ambientimpact_media\EventSubscriber\Preprocess;

use Drupal\ambientimpact_core\ComponentPluginManagerInterface;
use Drupal\hook_event_dispatcher\Event\Preprocess\FieldPreprocessEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * PhotoSwipe template_preprocess_field() event subscriber service class.
 *
 * @see \Drupal\hook_event_dispatcher\Event\Preprocess\FieldPreprocessEvent
 */
class PreprocessFieldPhotoSwipeEventSubscriber
implements EventSubscriberInterface {
  /**
   * The Ambient.Impact Component plugin manager service.
   *
   * @var \Drupal\ambientimpact_core\ComponentPluginManagerInterface
   */
  protected $componentManager;

  /**
   * Event subscriber constructor; saves dependencies.
   *
   * @param \Drupal\ambientimpact_core\ComponentPluginManagerInterface $componentManager
   *   The Ambient.Impact Component plugin manager service.
   */
  public function __construct(
    ComponentPluginManagerInterface $componentManager
  ) {
    $this->componentManager = $componentManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      FieldPreprocessEvent::name() => 'preprocessField',
    ];
  }

  /**
   * Prepare variables for field templates.
   *
   * This attaches the 'ambientimpact_media/component.photoswipe.field' library
   * and required attributes to fields that have PhotoSwipe enabled via the
   * field formatter settings.
   *
   * @param \Drupal\hook_event_dispatcher\Event\Preprocess\FieldPreprocessEvent $event
   *   Event.
   */
  public function preprocessField(FieldPreprocessEvent $event) {
    /* @var \Drupal\hook_event_dispatcher\Event\Preprocess\Variables\FieldEventVariables $variables */
    $variables  = $event->getVariables();
    $items      = &$variables->getItems();

    if (
      $variables->get('field_type') !== 'image' ||
      // Check if the field formatter has added the #use_photoswipe key to the
      // first item or that it equates to empty to avoid unnecessary work.
      empty($items[0]['content']['#use_photoswipe'])
    ) {
      // Remove our properties from the first item as they're no longer needed.
      if (isset($items[0]['content']['#use_photoswipe'])) {
        unset($items[0]['content']['#use_photoswipe']);
        unset($items[0]['content']['#use_photoswipe_gallery']);
      }

      return;
    }

    $config = $this->componentManager->getComponentConfiguration('photoswipe');

    $fieldAttributeMap = $config['fieldAttributes'];

    $firstItem  = &$items[0]['content'];
    $attributes = $variables->get('attributes');
    $attached   = $variables->get('#attached', []);

    // Transfer #use_photoswipe and #use_photoswipe_gallery to the relevant
    // attributes on the field itself, and remove them from the first item.
    $attributes[$fieldAttributeMap['enabled']] = 'true';
    $attributes[$fieldAttributeMap['gallery']] =
      $firstItem['#use_photoswipe_gallery'] ? 'true' : 'false';

    // Remove our properties from the first item as they're no longer needed.
    unset($firstItem['#use_photoswipe']);
    unset($firstItem['#use_photoswipe_gallery']);

    // Attach the field library.
    $attached['library'][] = 'ambientimpact_media/component.photoswipe.field';

    $variables->set('attributes', $attributes);
    $variables->set('#attached',  $attached);
  }
}
