<?php

namespace Drupal\ambientimpact_core\EventSubscriber;

use Drupal\ambientimpact_core\ContainerAwareEventSubscriber;

use Drupal\hook_event_dispatcher\Event\Preprocess\HtmlPreprocessEvent;

/**
 * template_preprocess_html() event subscriber service class.
 *
 * @see \Drupal\hook_event_dispatcher\Event\Preprocess\HtmlPreprocessEvent
 */
class PreprocessHTMLEventSubscriber extends ContainerAwareEventSubscriber {
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

    $requestStack = $this->container->get('request_stack');
    $requestQuery = $requestStack->getCurrentRequest()->query;

    // If the query parameter is not present, this will return null. If the
    // parameter is present, it will be a string, either empty or not.
    if ($requestQuery->get('disable-grid') === null) {
      // This applies to the <body>. Why isn't this an Attributes object?
      $variables->getByReference('attributes')['class'][] = 'use-grid';
    }
  }
}
