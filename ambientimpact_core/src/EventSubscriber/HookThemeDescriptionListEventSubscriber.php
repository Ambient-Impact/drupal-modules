<?php

namespace Drupal\ambientimpact_core\EventSubscriber;

use Drupal\ambientimpact_core\ContainerAwareEventSubscriber;

use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
use Drupal\hook_event_dispatcher\Event\Theme\ThemeEvent;

/**
 * hook_theme() event subscriber class to define 'description_list' element.
 */
class HookThemeDescriptionListEventSubscriber
extends ContainerAwareEventSubscriber {
  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      HookEventDispatcherInterface::THEME => 'theme',
    ];
  }

  /**
   * Defines the 'description_list' theme element.
   *
   * @param \Drupal\hook_event_dispatcher\Event\Theme\ThemeEvent $event
   *   The event object.
   */
  public function theme(ThemeEvent $event) {
    $event->addNewTheme('description_list', [
      'variables' => [
        'groups'    => [],
        'attributes'  => [],
      ],
      'template'  => 'description-list',
      // Path is required.
      // @see https://www.drupal.org/project/hook_event_dispatcher/issues/3038311
      'path'      =>
        $this->container->get('module_handler')
          ->getModule('ambientimpact_core')->getPath() . '/templates',
    ]);
  }
}
