<?php

namespace Drupal\ambientimpact_core\EventSubscriber;

use Drupal\ambientimpact_core\ContainerAwareEventSubscriber;

use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
use Drupal\hook_event_dispatcher\Event\Page\PageAttachmentsEvent;

/**
 * hook_page_attachments() event subscriber class.
 */
class HookPageAttachmentsEventSubscriber
extends ContainerAwareEventSubscriber {
  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      HookEventDispatcherInterface::PAGE_ATTACHMENTS => 'pageAttachments',
    ];
  }

  /**
   * Attach assets to the page element itself.
   *
   * This adds the following:
   *
   * - 'ambientimpact_core/core' library, which contains common and layout CSS.
   *
   * - The 'to_top' component on every page, regardless of theme. This is done
   *   because it provides a useful UX improvement.
   *
   * - 'ambientimpact_core/component.seven' library if the current theme is
   *   Seven.
   *
   * - Framework JavaScript settings to drupalSettings.
   *
   * - Component JavaScript settings to drupalSettings; these need to be here
   *   rather than on individual elements the actual component libraries are
   *   usually attached to because they may use shared global settings and/or
   *   counters.
   *
   * @param \Drupal\hook_event_dispatcher\Event\Page\PageAttachmentsEvent $event
   *   The event object.
   */
  public function pageAttachments(PageAttachmentsEvent $event) {
    $attached     = &$event->getAttachments()['#attached'];
    $activeTheme  = $this->container->get('theme.manager')->getActiveTheme();
    $componentManager =
      $this->container->get('plugin.manager.ambientimpact_component');

    $attached['drupalSettings']['AmbientImpact'] = [
      'framework' => [
        'cache'   => [
          'assetKey'  =>
            $this->container->get('state')->get('system.css_js_query_string'),
        ],
        'html'    => [
          'endpointPath' => $componentManager->getHTMLEndpointPath(),
        ],
      ],
      'components'  => $componentManager->getComponentJSSettings()
    ];

    $attached['library'][] = 'ambientimpact_core/core';
    $attached['library'][] = 'ambientimpact_core/component.to_top';

    if ($activeTheme->getName() === 'seven') {
      $attached['library'][] = 'ambientimpact_core/component.seven';
    }
  }
}
