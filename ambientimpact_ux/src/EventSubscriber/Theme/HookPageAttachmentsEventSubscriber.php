<?php

namespace Drupal\ambientimpact_ux\EventSubscriber\Theme;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
use Drupal\core_event_dispatcher\Event\Theme\PageAttachmentsEvent;
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
   * The Drupal current user account proxy service.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $accountProxy;

  /**
   * Event subscriber constructor; saves dependencies.
   *
   * @param \Drupal\Core\Theme\ThemeManagerInterface $themeManager
   *   The Drupal theme manager service.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $accountProxy
   *   The Drupal current user account proxy service.
   */
  public function __construct(
    ThemeManagerInterface $themeManager,
    AccountProxyInterface $accountProxy
  ) {
    $this->themeManager = $themeManager;
    $this->accountProxy = $accountProxy;
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
   * - The 'claro' component if the current theme is Claro.
   *
   * - The 'seven' component if the current theme is Seven.
   *
   * - The 'to_top' component on every page, regardless of theme. This is done
   *   because it provides a useful UX improvement.
   *
   * - The 'contextual' component, if the current user has permission to access
   *   contextual links. Note that this has to be done globally because
   *   attaching to the render array or element info of 'contextual_links' will
   *   be ignored, probably because of how contextual links are rendered
   *   separately from the page and fetched via Ajax.
   *
   * @param \Drupal\hook_event_dispatcher\Event\Page\PageAttachmentsEvent $event
   *   The event object.
   *
   * @see \contextual_page_attachments()
   *   Drupal core contextual links library attached in this hook.
   */
  public function pageAttachments(PageAttachmentsEvent $event) {
    $attached = &$event->getAttachments()['#attached'];

    switch ($this->themeManager->getActiveTheme()->getName()) {
      case 'claro':
        $attached['library'][] = 'ambientimpact_ux/component.claro';

        break;

      case 'seven':
        $attached['library'][] = 'ambientimpact_ux/component.seven';

        break;
    }

    $attached['library'][] = 'ambientimpact_ux/component.to_top';

    if ($this->accountProxy->hasPermission('access contextual links')) {
      $attached['library'][] = 'ambientimpact_ux/component.contextual';
    }
  }
}
