<?php

namespace Drupal\ambientimpact_core\Plugin\Filter;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Drupal\ambientimpact_core\Event\DOMCrawlerEvent;

/**
 * Provides a filter to alter the DOM tree of input text.
 *
 * On its own, this does nothing but parse input text into a DOM and dispatch
 * events. Event subscribers can then respond, altering the DOM.
 *
 * @Filter(
 *   id = "ambientimpact_dom",
 *   title = @Translation("Ambient.Impact: DOM tree manipulation"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE
 * )
 */
class DOMFilter extends FilterBase implements ContainerFactoryPluginInterface {
  /**
   * The Drupal/Symfony event dispatcher service.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Constructs this filter object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plug-in instance.
   *
   * @param string $pluginID
   *   The plugin_id for the plug-in instance.
   *
   * @param array $pluginDefinition
   *   The plug-in implementation definition. PluginBase defines this as mixed,
   *   but we should always have an array so the type is set.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface
   *   The Drupal/Symfony event dispatcher service.
   */
  public function __construct(
    array $configuration, string $pluginID, array $pluginDefinition,
    EventDispatcherInterface $eventDispatcher
  ) {
    parent::__construct($configuration, $pluginID, $pluginDefinition);

    // Save dependencies.
    $this->eventDispatcher = $eventDispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration, $pluginID, $pluginDefinition
  ) {
    return new static(
      $configuration, $pluginID, $pluginDefinition,
      $container->get('event_dispatcher')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langCode) {

    // If there aren't any listeners attached to the event or $text is empty,
    // just return the text as-is without parsing it so that we don't do
    // unnecessary work and avoid Symfony DomCrawler throwing an error in the
    // latter case.
    if (
      !$this->eventDispatcher->hasListeners(
        'ambientimpact.dom_filter_process'
      ) ||
      empty($text)
    ) {
      return new FilterProcessResult($text);
    }

    // Create the crawler, ensuring that $text is first cast to a string in case
    // a preceding filter has provided a renderable object rather than a string.
    // An example of this is the Markdown module.
    /** @var \Symfony\Component\DomCrawler\Crawler */
    $crawler = new Crawler((string) $text);

    // Set the crawler to the <body> element so that only its children are
    // rendered without the <body> wrapping them.
    $crawler = $crawler->filter('body');

    // Create the event object.
    $event = new DOMCrawlerEvent($crawler);

    // Dispatch the event with the event object.
    $this->eventDispatcher->dispatch(
      'ambientimpact.dom_filter_process',
      $event
    );

    // Get the crawler with any modifications listeners/subscribers may have
    // made to it.
    $crawler = $event->getCrawler();

    // Render the crawler and return it as the filter result.
    return new FilterProcessResult($crawler->html());
  }
}
