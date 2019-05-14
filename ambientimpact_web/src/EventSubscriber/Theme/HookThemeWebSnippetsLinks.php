<?php

namespace Drupal\ambientimpact_web\EventSubscriber\Theme;

use Drupal\ambientimpact_core\EventSubscriber\ContainerAwareEventSubscriber;

use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
use Drupal\hook_event_dispatcher\Event\Theme\ThemeEvent;

/**
 * hook_theme() event subscriber class to define 'web_snippets_links' element.
 */
class HookThemeWebSnippetsLinks extends ContainerAwareEventSubscriber {
  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      HookEventDispatcherInterface::THEME => 'theme',
    ];
  }

  /**
   * Defines the 'web_snippets_links' theme element.
   *
   * @param \Drupal\hook_event_dispatcher\Event\Theme\ThemeEvent $event
   *   The event object.
   */
  public function theme(ThemeEvent $event) {
    $event->addNewTheme('web_snippets_links', [
      'variables' => [
        'items' => [],
      ],
      'template'  => 'web-snippets-links',
      // Path is required.
      // @see https://www.drupal.org/project/hook_event_dispatcher/issues/3038311
      'path'      =>
        $this->container->get('module_handler')
          ->getModule('ambientimpact_web')->getPath() .
            '/templates/web-snippets',
    ]);
  }
}
