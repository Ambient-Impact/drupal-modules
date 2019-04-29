<?php

namespace Drupal\ambientimpact_core\EventSubscriber;

use Drupal\ambientimpact_core\EventSubscriber\ContainerAwareEventSubscriber;

use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
use Drupal\hook_event_dispatcher\Event\Page\PageTopEvent;

/**
 * hook_page_top() event subscriber class.
 */
class HookPageTopEventSubscriber extends ContainerAwareEventSubscriber {
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
   * @param \Drupal\hook_event_dispatcher\Event\Page\PageTopEvent $event
   *   The event object.
   */
  public function pageTop(PageTopEvent $event) {
    $build = &$event->getBuild();
    $toTopConfig =
      $this->container->get('plugin.manager.ambientimpact_component')
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
