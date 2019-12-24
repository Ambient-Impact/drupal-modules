<?php

namespace Drupal\ambientimpact_media\EventSubscriber\Theme;

use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
use Drupal\hook_event_dispatcher\Event\Theme\ThemeRegistryAlterEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * hook_theme_registry_alter() event subscriber for Remote video.
 */
class HookThemeRegistryAlterRemoteVideoEventSubscriber implements
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
   * Define various remote video variables for image-related elements.
   *
   * This defines the 'use_remote_video_play_icon',
   * 'remote_video_provider_name', and 'remote_video_media_name' variables for
   * the 'image_formatter', 'image_formatter_link_to_image_style_formatter', and
   * 'image_caption_formatter' elements.
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
        $variables = &$themeRegistry[$type]['variables'];

        $variables['use_remote_video_play_icon']  = false;
        $variables['remote_video_provider_name']  = false;
        $variables['remote_video_media_name']     = false;
      }
    }
  }
}
