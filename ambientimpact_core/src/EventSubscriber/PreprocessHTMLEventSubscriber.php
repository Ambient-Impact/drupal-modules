<?php

namespace Drupal\ambientimpact_core\EventSubscriber;

use Drupal\hook_event_dispatcher\Event\Preprocess\HtmlPreprocessEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * template_preprocess_html() event subscriber service class.
 *
 * @see \Drupal\hook_event_dispatcher\Event\Preprocess\HtmlPreprocessEvent
 */
class PreprocessHTMLEventSubscriber implements EventSubscriberInterface {
  /**
   * The Symfony request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Event subscriber constructor; saves dependencies.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *          The Symfony request stack.
   */
  public function __construct(
    RequestStack $requestStack
  ) {
    $this->requestStack = $requestStack;
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
   * This adds a 'use-grid' class to the <html> element if 'disable-grid' is not
   * found in the request query.
   *
   * @param \Drupal\hook_event_dispatcher\Event\Preprocess\HtmlPreprocessEvent $event
   *   Event.
   */
  public function preprocessHTML(HtmlPreprocessEvent $event) {
    /* @var \Drupal\hook_event_dispatcher\Event\Preprocess\Variables\HtmlEventVariables $variables */
    $variables = $event->getVariables();

    $requestQuery = $this->requestStack->getCurrentRequest()->query;

    // If the query parameter is not present, this will return null. If the
    // parameter is present, it will be a string, either empty or not.
    if ($requestQuery->get('disable-grid') === null) {
      // This applies to the <body>. Why isn't this an Attributes object?
      $variables->getByReference('attributes')['class'][] = 'use-grid';
    }
  }
}
