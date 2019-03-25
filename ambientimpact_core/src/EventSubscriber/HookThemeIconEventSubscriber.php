<?php

namespace Drupal\ambientimpact_core\EventSubscriber;

use Drupal\ambientimpact_core\ContainerAwareEventSubscriber;

use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
use Drupal\hook_event_dispatcher\Event\Theme\ThemeEvent;

/**
 * hook_theme() event subscriber class to define 'ambientimpact_icon' element.
 */
class HookThemeIconEventSubscriber
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
   * Defines the 'ambientimpact_icon' theme element.
   *
   * @param \Drupal\hook_event_dispatcher\Event\Theme\ThemeEvent $event
   *   The event object.
   */
  public function theme(ThemeEvent $event) {
    $componentManager =
      $this->container->get('plugin.manager.ambientimpact_component');

    $iconInstance = $componentManager->getComponentInstance('icon');
    $iconConfig   = $iconInstance->getConfiguration();

    $event->addNewTheme('ambientimpact_icon', [
      'variables' => [
        'icon'          => '',
        'bundle'        =>
          $iconConfig['defaults']['bundle'],
        'text'          => '',
        // Can be one of 'visible', 'visuallyHidden', or 'hidden'. The
        // latter two are analogous to the Drupal core .visually-hidden
        // and .hidden classes:
        // https://www.drupal.org/docs/8/theming/upgrading-classes-on-7x-themes-to-8x
        'textDisplay'     =>
          $iconConfig['defaults']['textDisplay'],
        'containerAttributes' => [],
        'containerTag'      =>
          $iconConfig['defaults']['containerTag'],
        'iconAttributes'    => [],
        'useAttributes'     => [],
        'textAttributes'    => [],
        'url'         => '',
        'standalone'      => null,
        'size'          => $iconConfig['defaults']['size'],
      ],
      'template'  => 'ambientimpact-icon',
      // Path is required.
      // @see https://www.drupal.org/project/hook_event_dispatcher/issues/3038311
      'path'      =>
        $this->container->get('module_handler')
          ->getModule('ambientimpact_core')->getPath() . '/templates',
    ]);
  }
}
