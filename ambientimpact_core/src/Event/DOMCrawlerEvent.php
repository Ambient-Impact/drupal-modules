<?php

namespace Drupal\ambientimpact_core\Event;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\EventDispatcher\Event;

/**
 * DOM crawler event.
 *
 * This generic event provides methods to store and retrieve a Symfony
 * DomCrawler instance.
 */
class DOMCrawlerEvent extends Event {
  /**
   * The current Symfony DomCrawler instance.
   *
   * @var \Symfony\Component\DomCrawler\Crawler
   */
  protected $crawler;

  /**
   * Constructs this event object.
   *
   * @param \Symfony\Component\DomCrawler\Crawler $crawler
   *   A Symfony DomCrawler instance to start with.
   */
  public function __construct(Crawler $crawler) {
    $this->crawler = $crawler;
  }

  /**
   * Get the current Symfony DomCrawler instance.
   *
   * @return \Symfony\Component\DomCrawler\Crawler
   *   The current Symfony DomCrawler instance.
   */
  public function getCrawler() {
    return $this->crawler;
  }

  /**
   * Set the current Symfony DomCrawler instance.
   *
   * @param \Symfony\Component\DomCrawler\Crawler $crawler
   *   A Symfony DomCrawler instance.
   */
  public function setCrawler(Crawler $crawler) {
    $this->crawler = $crawler;
  }
}
