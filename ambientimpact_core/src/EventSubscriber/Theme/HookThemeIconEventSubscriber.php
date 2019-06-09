<?php

namespace Drupal\ambientimpact_core\EventSubscriber\Theme;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\ambientimpact_core\ComponentPluginManager;
use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
use Drupal\hook_event_dispatcher\Event\Theme\ThemeEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * hook_theme() event subscriber class to define 'ambientimpact_icon' element.
 */
class HookThemeIconEventSubscriber implements EventSubscriberInterface {
  /**
   * The Ambient.Impact Component plugin manager service.
   *
   * @var \Drupal\ambientimpact_core\ComponentPluginManager
   */
  protected $componentManager;

  /**
   * The Drupal module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Event subscriber constructor; saves dependencies.
   *
   * @param \Drupal\ambientimpact_core\ComponentPluginManager $componentManager
   *   The Ambient.Impact Component plugin manager service.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The Drupal module handler service.
   */
  public function __construct(
    ComponentPluginManager $componentManager,
    ModuleHandlerInterface $moduleHandler
  ) {
    $this->componentManager = $componentManager;
    $this->moduleHandler = $moduleHandler;
  }

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
    $iconConfig = $this->componentManager->getComponentConfiguration('icon');

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
      'path'      => $this->moduleHandler->getModule('ambientimpact_core')
                     ->getPath() . '/templates',
    ]);
  }
}
