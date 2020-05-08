<?php

namespace Drupal\ambientimpact_media\EventSubscriber\Theme;

use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
use Drupal\core_event_dispatcher\Event\Theme\ThemeRegistryAlterEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * hook_theme_registry_alter() event subscriber for Animated GIF toggle.
 */
class ThemeRegistryAlterAnimatedGIFToggleEventSubscriber implements
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
   * Define Animated GIF toggle variables for image-related elements.
   *
   * This adds the 'use_animated_gif_toggle' variable to the 'image_formatter',
   * 'image_formatter_link_to_image_style_formatter', and
   * 'image_caption_formatter' items so that that setting can make it through to
   * our preprocess functions.
   *
   * @param \Drupal\core_event_dispatcher\Event\Theme\ThemeRegistryAlterEvent $event
   *   The event object.
   *
   * @see ambientimpact_media_preprocess_image_formatter()
   *   Passes variables to the Animated GIF toggle component.
   *
   * @see ambientimpact_media_preprocess_image_formatter_link_to_image_style_formatter()
   *   Passes variables to the Animated GIF toggle component.
   *
   * @see ambientimpact_media_preprocess_image_caption_formatter()
   *   Passes variables to the Animated GIF toggle component.
   */
  public function themeRegistryAlter(ThemeRegistryAlterEvent $event) {
    $themeRegistry = &$event->getThemeRegistry();

    foreach ([
      'image_formatter',
      'image_formatter_link_to_image_style_formatter',
      'image_caption_formatter',
    ] as $type) {
      if (isset($themeRegistry[$type])) {
        $themeRegistry[$type]['variables']['use_animated_gif_toggle'] = false;
      }
    }
  }
}
