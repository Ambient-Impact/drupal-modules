<?php

namespace Drupal\ambientimpact_media\EventSubscriber\Theme;

use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
use Drupal\core_event_dispatcher\Event\Theme\ThemeRegistryAlterEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * hook_theme_registry_alter() event subscriber for image link attributes.
 */
class HookThemeRegistryAlterImageLinkAttributesEventSubscriber implements
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
   * Define and alter various image link variables.
   *
   * This defines the 'link_attributes' variable for the 'image_formatter',
   * 'image_formatter_link_to_image_style_formatter', and
   * 'image_caption_formatter' elements.
   *
   * This changes the 'image_formatter_link_to_image_style_formatter' default
   * value for the 'url_attributes' variable from null to an empty array.
   *
   * @param \Drupal\hook_event_dispatcher\Event\Theme\ThemeRegistryAlterEvent $event
   *   The event object.
   */
  public function themeRegistryAlter(ThemeRegistryAlterEvent $event) {
    $themeRegistry = &$event->getThemeRegistry();

    foreach ([
      'image_formatter',
      'image_formatter_link_to_image_style_formatter',
      'image_caption_formatter',
    ] as $type) {
      if (isset($themeRegistry[$type])) {
        $themeRegistry[$type]['variables']['link_attributes'] = [];
      }
    }

    if (isset(
      $themeRegistry['image_formatter_link_to_image_style_formatter']
    )) {
      $themeRegistry['image_formatter_link_to_image_style_formatter']
        ['variables']['url_attributes'] = [];
    }
  }
}
