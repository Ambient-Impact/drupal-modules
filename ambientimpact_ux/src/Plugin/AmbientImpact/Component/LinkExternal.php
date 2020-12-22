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
    $class = 'external-link';

    return [
      // Class added to all external links, provided in case it's needed by
      // other code to find these links.
      'externalClass' => $class,

      // Attributes to be added to external links.
      'attributes' => [
        'class'   => [
          $class,
        ],
        'target'  => [
          '_blank'
        ],
        // window.opener security hardening.
        //
        // @see https://dev.to/ben/the-targetblank-vulnerability-by-example
        'rel'     => [
          'noopener',
          'noreferrer',
        ],
      ],
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

      // If the path and query are both empty, this is an internal link to a
      // target on the current page or it could be a link with an empty href. We
      // have to check for this as Url::fromUri() will throw an exception if we
      // try to pass it an empty path and query.
      if (empty($parsed['path']) && empty($parsed['query'])) {
        return false;
      }

      $url = Url::fromUri($parsed['path'], [
        'query'     => $parsed['query'],
        'fragment'  => $parsed['fragment'],
      ]);
    }

    return $url->isExternal();
  }

  /**
   * Alter link attributes array, adding our own attributes to it.
   *
   * @param array &$attributes
   *   An array of attributes, keyed by attribute name and containing either an
   *   array of values for each attribute or a string that's space-delimited.
   */
  protected function alterLinkAttributes(array &$attributes) {
    foreach ($this->getConfiguration()['attributes'] as $name => $values) {
      if (isset($attributes[$name])) {
        // If the attribute is already an array, just save it to a variable.
        if (is_array($attributes[$name])) {
          $attribute = $attributes[$name];

        // Otherwise, assume a string and explode it by spaces.
        } else {
          $attribute = explode(' ', $attributes[$name]);
        }

      // Create a new array if the attribute doesn't already exist.
      } else {
        $attribute = [];
      }

      // Add each value if it doesn't already exist.
      foreach ($values as $value) {
        if (!in_array($value, $attribute)) {
          $attribute[] = $value;
        }
      }

      // Save the new attribute value back to $attributes but don't implode(),
      // as Drupal's Attribute does that for us and can cause exceptions in
      // other code that may be altering this, and if it's a \DOMElement, we
      // implode that ourselves.
      $attributes[$name] = $attribute;
    }
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
   *
   * @see $this->alterLinkAttributes()
   *   Adds our attributes.
   */
  public function processExternalLink(&$link) {
    // Check if this is an instance of \Symfony\Component\DomCrawler\Crawler and
    // extract the \DOMElement from it if so.
    if ($link instanceof Crawler) {
      $link = $link->getNode(0);
    }

    // Check if this is a \DOMElement instance.
    if ($link instanceof \DOMElement) {
      $attributes = [];

      // Grab attribute values from the element if a given attribute exists.
      foreach ([$this->getConfiguration()['attributes']] as $name => $values) {
        if ($link->hasAttribute($name)) {
          $attributes[$name] = $link->getAttribute($name);
        }
      }

      $this->alterLinkAttributes($attributes);

      // Implode the attribute arrays and set the values on the element.
      foreach ($attributes as $name => $value) {
        $link->setAttribute($name, implode(' ', $value));
      }

    // If not, check if this is a Link element render array.
    } else if (
      is_array($link) &&
      isset($link['#type']) &&
      $link['#type'] === 'link'
    ) {
      if (!isset($link['#attributes'])) {
        $link['#attributes'] = [];
      }

      $this->alterLinkAttributes($link['options']['attributes']);

    // If not, check if this is a link settings array, like that found in
    // \hook_link_alter().
    } else if (
      is_array($link) &&
      isset($link['options'])
    ) {
      if (!isset($link['options']['attributes'])) {
        $link['options']['attributes'] = [];
      }

      $this->alterLinkAttributes($link['options']['attributes']);
    }
  }
}
