<?php

namespace Drupal\ambientimpact_ux\EventSubscriber\AmbientImpact;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\ambientimpact_core\ComponentPluginManagerInterface;
use Drupal\ambientimpact_core\Event\DOMCrawlerEvent;

class LinkImageDOMFilterEventSubscriber implements EventSubscriberInterface {
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
   * @param \Drupal\ambientimpact_core\Event\DOMCrawlerEvent $event
   *   The event object.
   */
  public function process(DOMCrawlerEvent $event) {
    $crawler = $event->getCrawler();

    $links = $crawler->filter('a');

    $linkImageComponent = $this->componentManager
      ->getComponentInstance('link.image');

    foreach ($links as $link) {
      $linkImageComponent->processLink($link);
    }
  }
}
