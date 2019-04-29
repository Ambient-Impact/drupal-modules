<?php

namespace Drupal\ambientimpact_core\EventSubscriber;

use Drupal\ambientimpact_core\ContainerAwareEventSubscriber;

use Drupal\hook_event_dispatcher\Event\Preprocess\FieldPreprocessEvent;

/**
 * PhotoSwipe template_preprocess_field() event subscriber service class.
 *
 * @see \Drupal\hook_event_dispatcher\Event\Preprocess\FieldPreprocessEvent
 */
class PreprocessFieldPhotoSwipeEventSubscriber
extends ContainerAwareEventSubscriber {
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
   * This attaches the 'ambientimpact_core/component.photoswipe.field' library
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
      return;
    }

    $componentManager =
      $this->container->get('plugin.manager.ambientimpact_component');
    $config = $componentManager->getComponentConfiguration('photoswipe');
    $fieldAttributeMap  = $config['fieldAttributes'];

    $firstItem  = &$items[0]['content'];
    $attributes = $variables->get('attributes');
    $attached   = $variables->get('#attached', []);

    // Transfer #use_photoswipe and #use_photoswipe_gallery to the the relevant
    // attributes on the field itself, and remove them from the first item.
    $attributes[$fieldAttributeMap['enabled']] = 'true';
    $attributes[$fieldAttributeMap['gallery']] =
      $firstItem['#use_photoswipe_gallery'] ? 'true' : 'false';
    unset($firstItem['#use_photoswipe']);
    unset($firstItem['#use_photoswipe_gallery']);

    // Attach the field library.
    $attached['library'][] = 'ambientimpact_core/component.photoswipe.field';

    $variables->set('attributes', $attributes);
    $variables->set('#attached',  $attached);
  }
}
