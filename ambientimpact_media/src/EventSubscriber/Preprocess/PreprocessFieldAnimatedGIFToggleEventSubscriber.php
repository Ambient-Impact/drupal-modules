<?php

namespace Drupal\ambientimpact_media\EventSubscriber\Preprocess;

use Drupal\ambientimpact_core\ComponentPluginManagerInterface;
use Drupal\hook_event_dispatcher\Event\Preprocess\FieldPreprocessEvent;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Animated GIF toggle template_preprocess_field() event subscriber class.
 *
 * @see \Drupal\hook_event_dispatcher\Event\Preprocess\FieldPreprocessEvent
 */
class PreprocessFieldAnimatedGIFToggleEventSubscriber
implements EventSubscriberInterface {
  use StringTranslationTrait;

  /**
   * The Ambient.Impact Component plugin manager service.
   *
   * @var \Drupal\ambientimpact_core\ComponentPluginManagerInterface
   */
  protected $componentManager;

  /**
   * The string translation service.
   *
   * @var \Drupal\Core\StringTranslation\TranslationManager
   */
  protected $stringTranslation;

  /**
   * Event subscriber constructor; saves dependencies.
   *
   * @param \Drupal\ambientimpact_core\ComponentPluginManagerInterface $componentManager
   *   The Ambient.Impact Component plugin manager service.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $stringTranslation
   *   The Drupal string translation service.
   */
  public function __construct(
    ComponentPluginManagerInterface $componentManager,
    TranslationInterface $stringTranslation
  ) {
    $this->componentManager   = $componentManager;
    $this->stringTranslation  = $stringTranslation;
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
      // Check if the field formatter has added the
      // #animated_gif_toggle_used_in_array key to the first item or that it
      // equates to empty() to avoid unnecessary work.
      empty($items[0]['content']['#animated_gif_toggle_used_in_array'])
    ) {
      // Remove the #animated_gif_toggle_used_in_array property from the first
      // item as it's no longer needed.
      if (isset($items[0]['content']['#animated_gif_toggle_used_in_array'])) {
        unset($items[0]['content']['#animated_gif_toggle_used_in_array']);
      }

      return;
    }

    $config = $this->componentManager
      ->getComponentConfiguration('animated_gif_toggle');

    $fieldAttributeMap = $config['fieldAttributes'];

    $firstItem  = &$items[0]['content'];
    $attached   = $variables->get('#attached', []);

    foreach ($items as $delta => &$item) {
      if (
        isset($item['content']['#use_animated_gif_toggle']) &&
        $item['content']['#use_animated_gif_toggle'] === true
      ) {
        // Mark as toggle enabled.
        $item['attributes']->setAttribute(
          $fieldAttributeMap['enabled'], 'true'
        );

        // Provide the URL to the original animated GIF so the front-end can
        // toggle to it regardless of what the field links to.
        $item['attributes']->setAttribute(
          $fieldAttributeMap['url'],
          $item['content']['#animated_gif_toggle_url']
        );

        // Add a title attribute with a helpful hint.
        // @todo Can this be moved to the link itself? Neither
        // image-formatter.html.twig nor
        // image-formatter-link-to-image-style-formatter.html.twig allow adding
        // arbitrary attributes to the links.
        if (!$item['attributes']->offsetExists('title')) {
          $item['attributes']->setAttribute('title', $this->t(
            'Play or pause this animated GIF'
          ));
        }
      }
    }

    // Remove the #animated_gif_toggle_used_in_array property from the first
    // item as it's no longer needed.
    unset($firstItem['#animated_gif_toggle_used_in_array']);

    // Attach the field library.
    $attached['library'][] =
      'ambientimpact_media/component.animated_gif_toggle';

    $variables->set('#attached',  $attached);
  }
}
