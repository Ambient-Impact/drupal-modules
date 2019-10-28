<?php

namespace Drupal\ambientimpact_ux\EventSubscriber\AmbientImpact;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\ambientimpact_core\ComponentPluginManagerInterface;
use Drupal\ambientimpact_core\Event\DOMFilterEvent;

/**
 * Link: external DOM filter process event subscriber.
 *
 * This passes any found links to the 'link.external' component to process.
 */
class LinkExternalDOMFilterEventSubscriber implements EventSubscriberInterface {
  /**
   * The Ambient.Impact Component plug-in manager service.
   *
   * @var \Drupal\ambientimpact_core\ComponentPluginManagerInterface
   */
  protected $componentManager;

  /**
   * Event subscriber constructor; saves dependencies.
   *
   * @param \Drupal\ambientimpact_core\ComponentPluginManagerInterface $componentManager
   *   The Ambient.Impact Component plug-in manager service.
   */
  public function __construct(
    ComponentPluginManagerInterface $componentManager
  ) {
    $this->componentManager = $componentManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      'ambientimpact.dom_filter_process' => 'process',
    ];
  }

  /**
   * DOM filter event handler.
   *
   * This uses the Symfony DomCrawler to find any external links and pass them
   * to the 'link.external' component for processing.
   *
   * @param \Drupal\ambientimpact_core\Event\DOMFilterEvent $event
   *   The event object.
   *
   * @see \Drupal\ambientimpact_ux\Plugin\AmbientImpact\Component\LinkExternal::isURIExternal()
   *   Method used to check if a URI is external.
   *
   * @see \Drupal\ambientimpact_ux\Plugin\AmbientImpact\Component\LinkExternal::processExternalLink()
   *   Links are passed to this method to be processed.
   *
   * @see https://symfony.com/doc/3.4/components/dom_crawler.html
   *   Symfony DomCrawler documentation.
   */
  public function process(DOMFilterEvent $event) {
    $crawler = $event->getCrawler();

    $links = $crawler->filter('a');

    $linkExternalComponent = $this->componentManager
      ->getComponentInstance('link.external');

    foreach ($links as $link) {
      // Ignore links that appear to be internal.
      if (!$linkExternalComponent->isURIExternal($link->getAttribute('href'))) {
        continue;
      }

      $linkExternalComponent->processExternalLink($link);
    }
  }
}
