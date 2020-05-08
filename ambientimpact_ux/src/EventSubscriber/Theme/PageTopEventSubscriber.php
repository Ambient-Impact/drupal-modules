<?php

namespace Drupal\ambientimpact_ux\EventSubscriber\Theme;

use Drupal\ambientimpact_core\ComponentPluginManagerInterface;
use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
use Drupal\core_event_dispatcher\Event\Theme\PageTopEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * hook_page_top() event subscriber class.
 */
class PageTopEventSubscriber implements EventSubscriberInterface {
  /**
   * The Ambient.Impact Component plug-in manager service.
   *
   * @var \Drupal\ambientimpact_core\ComponentPluginManagerInterface
   */
  protected $componentManager;

  /**
   * Event subscriber constructor; saves dependencies.
   *
   * @param \Drupal\ambientimpact_core\ComponentPluginManagerInterface $componentManager
   *   The Ambient.Impact Component plug-in manager service.
   */
  public function __construct(
    ComponentPluginManagerInterface $componentManager
  ) {
    $this->componentManager = $componentManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      HookEventDispatcherInterface::PAGE_TOP => 'pageTop',
    ];
  }

  /**
   * Add renderable arrays to the top of the page.
   *
   * This adds the following:
   *
   * - An anchor to the very top of the page for the 'to_top' component to
   *   scroll to.
   *
   * @param \Drupal\core_event_dispatcher\Event\Theme\PageTopEvent $event
   *   The event object.
   */
  public function pageTop(PageTopEvent $event) {
    $build        = &$event->getBuild();
    $toTopConfig  = $this->componentManager
                      ->getComponentConfiguration('to_top');

    $build['top_anchor'] = [
      '#type'       => 'html_tag',
      '#tag'        => 'a',
      '#attributes' => [
        'id'  => $toTopConfig['topAnchorID'],
      ],
      '#weight'     => -999,
    ];
  }
}
