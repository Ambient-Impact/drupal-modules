<?php

namespace Drupal\ambientimpact_media\EventSubscriber\Theme;

use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
use Drupal\core_event_dispatcher\Event\Theme\ThemeRegistryAlterEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * hook_theme_registry_alter() event subscriber for Intrinsic ratio.
 */
class ThemeRegistryAlterIntrinsicRatioEventSubscriber implements
EventSubscriberInterface {
  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      HookEventDispatcherInterface::THEME_REGISTRY_ALTER =>
        'themeRegistryAlter',
    ];
  }

  /**
   * Define various intrinsic ratio variables for image-related elements.
   *
   * This defines the 'constrain_width' variable for the 'image', 'image_style',
   * and 'image_caption_formatter' elements.
   *
   * @param \Drupal\core_event_dispatcher\Event\Theme\ThemeRegistryAlterEvent $event
   *   The event object.
   */
  public function themeRegistryAlter(ThemeRegistryAlterEvent $event) {
    $themeRegistry = &$event->getThemeRegistry();

    foreach ([
      'image',
      'image_style',
      'image_caption_formatter',
    ] as $type) {
      if (isset($themeRegistry[$type])) {
        $themeRegistry[$type]['variables']['constrain_width'] = true;
      }
    }
  }
}
