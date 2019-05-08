<?php

namespace Drupal\ambientimpact_core;

/**
 * An interface for defining libraries for Ambient.Impact Component plugins.
 */
interface ComponentLibrariesInterface {
  /**
   * Parse and return any libraries that this component provides.
   *
   * Each component can define its own libraries in a
   * <component name>.libraries.yml file in its component directory. This
   * functions identically to the module file, with one exception: any file
   * paths should be relative to the component's directory, as the parent
   * module's path with be prepended automatically on parsing.
   *
   * @return array
   *
   * @see https://www.drupal.org/docs/8/creating-custom-modules/adding-stylesheets-css-and-javascript-js-to-a-drupal-8-module
   */
  public function getLibraries(): array;
}
