<?php

namespace Drupal\ambientimpact_ux\EventSubscriber\Preprocess;

use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\hook_event_dispatcher\Event\Preprocess\HtmlPreprocessEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * template_preprocess_html() event subscriber service class.
 *
 * @see \Drupal\hook_event_dispatcher\Event\Preprocess\HtmlPreprocessEvent
 */
class PreprocessHTMLEventSubscriber implements EventSubscriberInterface {
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
      HtmlPreprocessEvent::name() => 'preprocessHTML',
    ];
  }

  /**
   * Prepare variables for HTML document templates.
   *
   * This sets a 'data-drupal-theme' attribute on the <html> element with the
   * machine name of the current theme. This is currently used by the Toolbar
   * component so that it can easily identify the current theme, even before
   * drupalSettings becomes available.
   *
   * @param \Drupal\hook_event_dispatcher\Event\Preprocess\HtmlPreprocessEvent $event
   *   Event.
   */
  public function preprocessHTML(HtmlPreprocessEvent $event) {
    /* @var \Drupal\hook_event_dispatcher\Event\Preprocess\Variables\HtmlEventVariables $variables */
    $variables = $event->getVariables();

    $variables->getByReference('html_attributes')->setAttribute(
      'data-drupal-theme',
      $this->themeManager->getActiveTheme()->getName()
    );
  }
}
