<?php

namespace Drupal\ambientimpact_core\EventSubscriber;

use Drupal\ambientimpact_core\ComponentPluginManager;
use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
use Drupal\hook_event_dispatcher\Event\Theme\LibraryInfoAlterEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * hook_library_info_alter() Component libraries event subscriber class.
 *
 * This registers the libraries defined in individual Ambient.Impact Components,
 * as returned by the 'plugin.manager.ambientimpact_component' service.
 */
class HookLibraryInfoAlterComponentLibrariesEventSubscriber
implements EventSubscriberInterface {
  /**
   * The Ambient.Impact Component plugin manager service.
   *
   * @var \Drupal\ambientimpact_core\ComponentPluginManager
   */
  protected $componentManager;

  /**
   * Event subscriber constructor; saves dependencies.
   *
   * @param \Drupal\ambientimpact_core\ComponentPluginManager $componentManager
   *   The Ambient.Impact Component plugin manager service.
   */
  public function __construct(
    ComponentPluginManager $componentManager
  ) {
    $this->componentManager = $componentManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      HookEventDispatcherInterface::LIBRARY_INFO_ALTER => 'libraryInfoAlter',
    ];
  }

  /**
   * Register Ambient.Impact Component libraries.
   *
   * @param \Drupal\hook_event_dispatcher\Event\Theme\LibraryInfoAlterEvent $event
   *   The event object.
   *
   * @see \Drupal\ambientimpact_core\ComponentPluginManager::getComponentLibraries()
   *   Returns the libraries output by this method.
   *
   * @see https://www.drupal.org/docs/8/creating-custom-modules/adding-stylesheets-css-and-javascript-js-to-a-drupal-8-module#dynamic-css-js
   */
  public function libraryInfoAlter(LibraryInfoAlterEvent $event) {
    $libraries = &$event->getLibraries();

    $componentLibraries = $this->componentManager->getComponentLibraries();

    foreach ($componentLibraries as $machineName => $library) {
      $libraries[$machineName] = $library;
    }
  }
}
