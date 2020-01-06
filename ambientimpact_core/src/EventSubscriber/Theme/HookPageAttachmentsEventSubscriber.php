<?php

namespace Drupal\ambientimpact_core\EventSubscriber\Theme;

use Drupal\ambientimpact_core\ComponentPluginManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
use Drupal\core_event_dispatcher\Event\Theme\PageAttachmentsEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * hook_page_attachments() event subscriber class.
 */
class HookPageAttachmentsEventSubscriber implements EventSubscriberInterface {
  /**
   * The Ambient.Impact Component plug-in manager service.
   *
   * @var \Drupal\ambientimpact_core\ComponentPluginManagerInterface
   */
  protected $componentManager;

  /**
   * The Drupal state system manager.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $stateManager;

  /**
   * Event subscriber constructor; saves dependencies.
   *
   * @param \Drupal\ambientimpact_core\ComponentPluginManagerInterface $componentManager
   *   The Ambient.Impact Component plug-in manager service.
   *
   * @param \Drupal\Core\State\StateInterface $stateManager
   *   The Drupal state system manager.
   */
  public function __construct(
    ComponentPluginManagerInterface $componentManager,
    StateInterface $stateManager
  ) {
    $this->componentManager = $componentManager;
    $this->stateManager = $stateManager;
  }

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
   * - Framework JavaScript settings to drupalSettings.
   *
   * - Component JavaScript settings to drupalSettings; these need to be here
   *   rather than on individual elements the actual component libraries are
   *   usually attached to because they may use shared global settings and/or
   *   counters.
   *
   * @param \Drupal\core_event_dispatcher\Event\Theme\PageAttachmentsEvent $event
   *   The event object.
   */
  public function pageAttachments(PageAttachmentsEvent $event) {
    $attached = &$event->getAttachments()['#attached'];

    $attached['drupalSettings']['AmbientImpact'] = [
      'framework' => [
        'cache'   => [
          'assetKey'  => $this->stateManager->get('system.css_js_query_string'),
        ],
        'html'    => [
          'endpointPath'  => $this->componentManager->getHTMLEndpointPath(),
          'haveHTML'      => $this->componentManager
                              ->getComponentNamesWithHTML(),
        ],
      ],
      'components'  => $this->componentManager->getComponentJSSettings(),
    ];

    $attached['library'][] = 'ambientimpact_core/core';
  }
}
