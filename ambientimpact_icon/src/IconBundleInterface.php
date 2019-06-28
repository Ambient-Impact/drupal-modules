<?php

namespace Drupal\ambientimpact_icon;

/**
 * An interface for all Ambient.Impact Icon Bundle plug-ins.
 */
interface IconBundleInterface {
  /**
   * Get the path to this Icon Bundle's SVG file.
   *
   * @param bool $absolute
   *   If true, includes the path to the module providing the Icon Bundle
   *   plugin. If false, is relative to the module's directory. Defaults to
   *   true.
   *
   * @return string
   *   The path to the Icon Bundle. If $absolute is true (the default), this
   *   includes the path to the module providing the Icon Bundle plugin.
   */
  public function getPath(bool $absolute): string;

  /**
   * Get the URL to this bundle's SVG file.
   *
   * @param bool $absolute
   *   Whether to include the scheme and domain name in the URL. This is
   *   passed to the \Drupal\Core\Url instance as the 'absolute' option.
   *   Defaults to false.
   *
   * @return string
   *   The path to the bundle SVG file.
   *
   * @see https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Url.php/function/Url%3A%3AfromUri
   */
  public function getURL(bool $absolute): string;

  /**
   * Mark this bundle as being in use in the current request.
   */
  public function markUsed();

  /**
   * Returns whether this bundle is in use in the current request.
   *
   * @return boolean
   *   True if at least one Drupal\ambientimpact_icon\Element\Icon element
   *   has been rendered with this bundle, false otherwise.
   */
  public function isUsed(): bool;
}
