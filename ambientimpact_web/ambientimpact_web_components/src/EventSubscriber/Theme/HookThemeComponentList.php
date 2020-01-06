<?php

namespace Drupal\ambientimpact_web_components\EventSubscriber\Theme;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
use Drupal\core_event_dispatcher\Event\Theme\ThemeEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * hook_theme() event to define the 'ambientimpact_component_list' element.
 */
class HookThemeComponentList implements EventSubscriberInterface {
  /**
   * The Drupal module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Event subscriber constructor; saves dependencies.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The Drupal module handler service.
   */
  public function __construct(
    ModuleHandlerInterface $moduleHandler
  ) {
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
   * Defines the 'ambientimpact_component_list' theme element.
   *
   * @param \Drupal\core_event_dispatcher\Event\Theme\ThemeEvent $event
   *   The event object.
   */
  public function theme(ThemeEvent $event) {
    $event->addNewTheme('ambientimpact_component_list', [
      'variables' => [
        'list'  => [],
      ],
      'template'  => 'ambientimpact-component-list',
      // Path is required.
      // @see https://www.drupal.org/project/hook_event_dispatcher/issues/3038311
      'path'      => $this->moduleHandler
        ->getModule('ambientimpact_web_components')
                     ->getPath() . '/templates',
    ]);
  }
}
