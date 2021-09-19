<?php

namespace Drupal\ambientimpact_media\EventSubscriber\Theme;

use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
use Drupal\core_event_dispatcher\Event\Theme\ThemeRegistryAlterEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Add 'tracks' variable \hook_theme_registry_alter() event subscriber.
 *
 * This adds the 'tracks' variable to 'file_audio' and 'file_video'.
 *
 * @see file-audio.html.twig
 * @see file-video.html.twig
 */
class ThemeRegistryAlterTracksVariableEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      HookEventDispatcherInterface::THEME_REGISTRY_ALTER => 'onThemeRegistryAlter',
    ];
  }

  /**
   * Theme registry alter event handler.
   *
   * @param \Drupal\core_event_dispatcher\Event\Theme\ThemeRegistryAlterEvent $event
   *   The event object.
   */
  public function onThemeRegistryAlter(ThemeRegistryAlterEvent $event) {

    /** @var array */
    $themeRegistry = &$event->getThemeRegistry();

    foreach (['file_audio', 'file_video'] as $key) {
      $themeRegistry[$key]['variables']['tracks'] = [];
    }

  }

}
