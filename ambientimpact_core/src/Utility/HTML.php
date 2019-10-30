<?php

namespace Drupal\ambientimpact_core\Utility;

use Drupal\Component\Utility\Html as DrupalHtml;

/**
 * Provides DOMDocument helpers for parsing and serializing HTML strings.
 *
 * @see \Drupal\Component\Utility\Html
 *   Extends this Drupal core class.
 *
 * @ingroup utility
 */
class HTML extends DrupalHtml {
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
  ) {
    return htmlspecialchars($text, $flags, $encoding, $doubleEncode);
  }
}
