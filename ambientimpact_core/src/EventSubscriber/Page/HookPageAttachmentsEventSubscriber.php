<?php

namespace Drupal\ambientimpact_core\EventSubscriber\Page;

use Drupal\ambientimpact_core\ComponentPluginManager;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
use Drupal\hook_event_dispatcher\Event\Page\PageAttachmentsEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * hook_page_attachments() event subscriber class.
 */
class HookPageAttachmentsEventSubscriber implements EventSubscriberInterface {
  /**
   * The Ambient.Impact Component plugin manager service.
   *
   * @var \Drupal\ambientimpact_core\ComponentPluginManager
   */
  protected $componentManager;

  /**
   * The Drupal theme manager service.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * The Drupal state system manager.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $stateManager;

  /**
   * Event subscriber constructor; saves dependencies.
   *
   * @param \Drupal\ambientimpact_core\ComponentPluginManager $componentManager
   *   The Ambient.Impact Component plugin manager service.
   *
   * @param \Drupal\Core\Theme\ThemeManagerInterface $themeManager
   *   The Drupal theme manager service.
   *
   * @param \Drupal\Core\State\StateInterface $stateManager
   *   The Drupal state system manager.
   */
  public function __construct(
    ComponentPluginManager $componentManager,
    ThemeManagerInterface $themeManager,
    StateInterface $stateManager
  ) {
    $this->componentManager = $componentManager;
    $this->themeManager = $themeManager;
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

    if ($this->themeManager->getActiveTheme()->getName() === 'seven') {
      $attached['library'][] = 'ambientimpact_core/component.seven';
    }
  }
}
