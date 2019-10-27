<?php

namespace Drupal\ambientimpact_ux\Plugin\AmbientImpact\Component;

use Drupal\Core\Url;
use Drupal\Component\Utility\UrlHelper;
use Symfony\Component\DomCrawler\Crawler;
use Drupal\ambientimpact_core\ComponentBase;

/**
 * Link: external component.
 *
 * @Component(
 *   id = "link.external",
 *   title = @Translation("Link: external"),
 *   description = @Translation("Marks links to external websites with a class and causes them to open in a new tab.")
 * )
 */
class LinkExternal extends ComponentBase {
  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'externalClass' => 'external-link',
    ];
  }

  /**
   * Checks if a provided URI is external.
   *
   * @param string $uri
   *   The URI to test.
   *
   * @return boolean
   *   True if external, false otherwise.
   *
   * @see \Drupal\Core\Url::fromUserInput()
   * @see \Drupal\Core\Url::fromUri()
   * @see \Drupal\Core\Url::isExternal()
   * @see \Drupal\Component\Utility\UrlHelper::parse()
   */
  public function isURIExternal(string $uri): bool {
    // Check if the first character of the $uri is '/', '#', or '?' before
    // attempting to pass it to \Drupal\Core\Url::fromUserInput() as anything
    // else will throw an exception.
    if (
      (strpos($uri, '/') === 0) ||
      (strpos($uri, '#') === 0) ||
      (strpos($uri, '?') === 0)
    ) {
      $url = Url::fromUserInput($uri);

    // If it doesn't look like an internal URI, parse it and pass it to
    // \Drupal\Core\Url::fromUri() to be sure and have Drupal verify whether it
    // is indeed external or not.
    } else {
      $parsed = UrlHelper::parse($uri);

      $url = Url::fromUri($parsed['path'], [
        'query'     => $parsed['query'],
        'fragment'  => $parsed['fragment'],
      ]);
    }

    return $url->isExternal();
  }

  /**
   * Process an external link to add a class and target="_blank" attribute.
   *
   * @param mixed &$link
   *  This can be one of several things:
   *  - \Symfony\Component\DomCrawler\Crawler instance.
   *
   *  - \DOMElement instance.
   *
   *  - A Link element ('#type' => 'link') render array.
   *
   *  - A link settings array, as found in \hook_link_alter().
   *
   * @see https://symfony.com/doc/3.4/components/dom_crawler.html
   *   Symfony DomCrawler documentation.
   *
   * @see https://www.php.net/manual/en/class.domelement.php
   *   PHP \DOMElement documentation.
   *
   * @see \Drupal\Core\Render\Element\Link
   *   Drupal Link render element.
   *
   * @see \hook_link_alter()
   */
  public function processExternalLink(&$link) {
    $externalClass = $this->getConfiguration()['externalClass'];

    // Check if this is an instance of \Symfony\Component\DomCrawler\Crawler and
    // extract the \DOMElement from it if so.
    if ($link instanceof Crawler) {
      $link = $link->getNode(0);
    }

    // Check if this is a \DOMElement instance.
    if ($link instanceof \DOMElement) {
      if ($link->hasAttribute('class')) {
        $classes = explode(' ', $link->getAttribute('class'));
      } else {
        $classes = [];
      }

      $classes[] = $externalClass;

      $link->setAttribute('target', '_blank');

      $link->setAttribute('class', implode(' ', $classes));

    // If not, check if this is a Link element render array.
    } else if (
      is_array($link) &&
      isset($link['#type']) &&
      $link['#type'] === 'link'
    ) {
      $link['#attributes']['target']  = '_blank';
      $link['#attributes']['class'][] = $externalClass;

    // If not, check if this is a link settings array, like that found in
    // \hook_link_alter().
    } else if (
      is_array($link) &&
      isset($link['options'])
    ) {
      $link['options']['attributes']['target']  = '_blank';
      $link['options']['attributes']['class'][] = $externalClass;
    }
  }
}
