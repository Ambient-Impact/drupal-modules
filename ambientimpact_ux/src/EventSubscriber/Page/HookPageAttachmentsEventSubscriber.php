<?php

namespace Drupal\ambientimpact_ux\EventSubscriber\Page;

use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
use Drupal\hook_event_dispatcher\Event\Page\PageAttachmentsEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * hook_page_attachments() event subscriber class.
 */
class HookPageAttachmentsEventSubscriber implements EventSubscriberInterface {
  /**
   * The Drupal theme manager service.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * Event subscriber constructor; saves dependencies.
   *
   * @param \Drupal\Core\Theme\ThemeManagerInterface $themeManager
   *   The Drupal theme manager service.
   */
  public function __construct(
    ThemeManagerInterface $themeManager
  ) {
    $this->themeManager = $themeManager;
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
   * - The 'seven' component if the current theme is Seven.
   *
   * - The 'to_top' component on every page, regardless of theme. This is done
   *   because it provides a useful UX improvement.
   *
   * @param \Drupal\hook_event_dispatcher\Event\Page\PageAttachmentsEvent $event
   *   The event object.
   */
  public function pageAttachments(PageAttachmentsEvent $event) {
    $attached = &$event->getAttachments()['#attached'];

    if ($this->themeManager->getActiveTheme()->getName() === 'seven') {
      $attached['library'][] = 'ambientimpact_ux/component.seven';
    }

    $attached['library'][] = 'ambientimpact_ux/component.to_top';
  }
}
