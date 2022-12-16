<?php

namespace Drupal\ambientimpact_core\Utility;

use Drupal\Component\Utility\Html as DrupalHtml;
use Drupal\Core\Template\Attribute;

/**
 * Provides DOMDocument helpers for parsing and serializing HTML strings.
 *
 * @see \Drupal\Component\Utility\Html
 *   Extends this Drupal core class.
 *
 * @ingroup utility
 */
class Html extends DrupalHtml {

  /**
   * Escapes text by converting special characters to HTML entities.
   *
   * This overrides \Drupal\Component\Utility\Html::escape() to expose all the
   * \htmlspecialchars() parameters, and additionally sets $double_encode to
   * false by default.
   *
   * @param string $text
   *   The input text.
   *
   * @param bool $doubleEncode
   *   If true, will encode already encoded HTML entities, thus double encoding.
   *   Defaults to false.
   *
   * @param int $flags
   *   The flags to pass to \htmlspecialchars(). Defaults to
   *   \ENT_QUOTES | \ENT_SUBSTITUTE to match the value in
   *   \Drupal\Component\Utility\Html::escape().
   *
   * @param string $encoding
   *   The encoding to pass to \htmlspecialchars(). Defaults to 'UTF-8' to match
   *   the value in \Drupal\Component\Utility\Html::escape().
   *
   * @return string
   *   The text with all HTML special characters converted.
   *
   * @see \htmlspecialchars()
   *
   * @see \Drupal\Component\Utility\Html::escape()
   *
   * @ingroup sanitization
   */
  public static function escape(
    $text,
    bool $doubleEncode  = false,
    int $flags          = \ENT_QUOTES | \ENT_SUBSTITUTE,
    string $encoding    = 'UTF-8'
  ): string {
    return htmlspecialchars($text, $flags, $encoding, $doubleEncode);
  }

  /**
   * Get an Attribute object containing an element's parsed class attribute.
   *
   * @param \DOMElement $element
   *   The DOM element to parse and return the class attribute of.
   *
   * @return \Drupal\Core\Template\Attribute
   *   An Attribute object with zero or more classes.
   *
   * @todo Can we store the created Attribute object in a static cache or is the
   *   performance impact negligible?
   */
  public static function getElementClassAttribute(
    \DOMElement $element
  ): Attribute {

    // Note that \DOMElement::getAttribute() always returns a string, including
    // when the attribute does not exist, in which case the string is empty.
    return (new Attribute([]))->addClass(
      \explode(' ', $element->getAttribute('class'))
    );

  }

  /**
   * Set an element's class attribute to the provided Attribute object.
   *
   * @param \DOMElement $element
   *   The DOM element to set the class attribute to.
   *
   * @param \Drupal\Core\Template\Attribute $attributes
   *   An Attribute object with zero or more classes. If no classes are present
   *   in the object, the 'class' attribute on the element will be removed.
   */
  public static function setElementClassAttribute(
    \DOMElement $element, Attribute $attributes
  ): void {

    /** @var string */
    $class = \trim(\implode(' ', $attributes->getClass()->value()));

    // If the generated class attribute value is an empty string, remove the
    // class attribute altogether.
    if (empty($class)) {

      $element->removeAttribute('class');

    } else {

      $element->setAttribute('class', $class);

    }

  }

  /**
   * Determine if a provided element has a class.
   *
   * @param \DOMElement $element
   *   The DOM element to check.
   *
   * @param string $className
   *   The class to check for on the provided DOM element.
   *
   * @return bool
   *   True if the element has the class and false otherwise.
   */
  public static function elementHasClass(
    \DOMElement $element, string $className
  ): bool {

    if (
      !$element->hasAttribute('class') ||
      empty($element->getAttribute('class'))
    ) {
      return false;
    }

    return static::getElementClassAttribute($element)->hasClass($className);

  }

  /**
   * Replace a DOM node with its contents.
   *
   * This essentially unwraps the node, replacing it with its child nodes,
   * preserving their order.
   *
   * @param \DOMNode $node
   *   The DOM node to unwrap.
   *
   * @return bool
   *   True if the operation was successful and false otherwise.
   *
   * @see https://stackoverflow.com/questions/11651365/how-to-insert-node-in-hierarchy-of-dom-between-one-node-and-its-child-nodes/11651813#11651813
   *
   * @see https://api.jquery.com/unwrap/
   *   Functions similarly to the jQuery method except that the parameter we
   *   operate on is the container to be removed, not the children.
   */
  public static function unwrapNode(\DOMNode $node): bool {

    for ($i = 0; $node->childNodes->length > 0; $i++) {

      if (!\is_object($node->parentNode->insertBefore(
        // Note that we always specify index "0" as we're basically removing the
        // first child each time, similar to \array_shift(), and the child list
        // updates each time we do this, akin to removing the bottom most card
        // in a deck of cards on each iteration.
        $node->childNodes->item(0),
        $node
      ))) {
        return false;
      }

    }

    // Remove the now-empty node.
    $node->parentNode->removeChild($node);

    return true;

  }

}
