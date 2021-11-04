<?php

namespace Drupal\ambientimpact_web\EventSubscriber\Theme;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\core_event_dispatcher\Event\Theme\ThemeEvent;
use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Defines the 'web_snippets_links' theme element.
 */
class ThemeWebSnippetsLinks implements EventSubscriberInterface {

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
      HookEventDispatcherInterface::THEME => 'onTheme',
    ];
  }

  /**
   * Defines the 'web_snippets_links' theme element.
   *
   * @param \Drupal\core_event_dispatcher\Event\Theme\ThemeEvent $event
   *   The event object.
   */
  public function onTheme(ThemeEvent $event) {

    $event->addNewTheme('web_snippets_links', [
      'variables' => [
        'items'     => [],
      ],
      'template'  => 'web-snippets-links',
      // We have to build the 'path' key as it's required by
      // hook_event_dispatcher.
      //
      // @see https://www.drupal.org/project/hook_event_dispatcher/issues/3038311
      'path'      => $this->moduleHandler ->getModule('ambientimpact_web')
        ->getPath() . '/templates/web-snippets',
    ]);

  }

}
