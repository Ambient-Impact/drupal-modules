<?php

namespace Drupal\ambientimpact_core\Utility;

use Drupal\Core\Template\AttributeHelper as DrupalAttributeHelper;

/**
 * Helper class to handle style attributes, extending core's AttributeHelper.
 *
 * @see \Drupal\Core\Template\AttributeHelper
 *
 * @see https://ambientimpact.com/web/snippets/css-ruleset-terminology
 *
 * @todo Move most of this logic to a class that extends
 *   \Drupal\Core\Template\Attribute
 */
class AttributeHelper extends DrupalAttributeHelper {

  /**
   * Parse a style attribute value into an associative array.
   *
   * @param string $value
   *   The string value of a style attribute to parse.
   *
   * @return array
   *   An associative array where the keys are CSS property names and the values
   *   are the corresponding property values.
   *
   * @throws \InvalidArgumentException
   *   Exception thrown if $value is not properly formed CSS.
   *
   * @see static::serializeStyleArray()
   *   Serializes an associative array into a style attribute value string.
   */
  public static function parseStyleAttribute(string $value): array {

    if (\strpos($value, ':') === false) {
      throw new \InvalidArgumentException(
        'This does not look like a valid style attribute value: "' .
        $value . '"'
      );
    }

    /** @var array */
    $declarationsSplit = \explode(';', $value);

    /** @var array */
    $properties = [];

    foreach ($declarationsSplit as $declaration) {

      // Trim white-space from the beginning of the string, but not the end as
      // white-space is allowed and potentially meaningful in CSS custom
      // properties.
      $declarationTrimmed = \ltrim($declaration);

      if (\strlen($declarationTrimmed) === 0) {
        continue;
      }

      // Split declarations into property name and value pairs.
      $declarationSplit = \explode(':', $declarationTrimmed);

      if (count($declarationSplit) !== 2) {
        throw new \InvalidArgumentException(
          'Invalid declaration found: "' . \implode(
            ':', $declarationSplit
          ) . '"'
        );
      }

      $properties[$declarationSplit[0]] = $declarationSplit[1];

    }

    return $properties;

  }

  /**
   * Serialize an associative array into a style attribute value string.
   *
   * @param array $styles
   *   An associative array where the keys are CSS property names and the values
   *   are the corresponding property values.
   *
   * @return string
   *   A serialized string built from $styles, ready to be used as a style
   *   attribute value.
   *
   * @see static::parseStyleAttribute()
   *   Parses style attribute values.
   */
  public static function serializeStyleArray(array $styles): string {

    if (count($styles) === 0) {
      throw new \InvalidArgumentException(
        'Style array cannot be empty.'
      );
    }

    $declarations = [];

    foreach ($styles as $name => $value) {
      $declarations[] = $name . ':' . $value;
    }

    return \implode(';', $declarations);

  }

}
