<?php

namespace Drupal\ambientimpact_ux\EventSubscriber\Theme;

use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
use Drupal\core_event_dispatcher\Event\Theme\LibraryInfoAlterEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Gin theme and toolbar hook_library_info_alter() event subscriber.
 *
 * This attaches our Gin component as a dependency to various Gin theme and
 * toolbar libraries.
 */
class LibraryInfoAlterGinEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      HookEventDispatcherInterface::LIBRARY_INFO_ALTER => 'onLibraryInfoAlter',
    ];
  }

  /**
   * Library info alter event handler.
   *
   * @param \Drupal\core_event_dispatcher\Event\Theme\LibraryInfoAlterEvent $event
   *   The event object.
   */
  public function onLibraryInfoAlter(LibraryInfoAlterEvent $event) {

    /** @var string */
    $extension = $event->getExtension();

    if ($extension !== 'gin' && $extension !== 'gin_toolbar') {
      return;
    }

    /** @var array[] */
    $libraries = &$event->getLibraries();

    foreach ([
      'gin', 'gin_toolbar', 'gin_classic_toolbar', 'gin_horizontal_toolbar',
    ] as $libraryName) {

      if (!isset($libraries[$libraryName])) {
        continue;
      }

      $libraries[$libraryName]['dependencies'][] =
        'ambientimpact_ux/component.gin';
    }

  }
}
