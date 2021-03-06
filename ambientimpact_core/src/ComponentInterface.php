<?php

namespace Drupal\ambientimpact_core;

/**
 * An interface for all Ambient.Impact Component plug-ins.
 */
interface ComponentInterface {
  /**
   * Get the path to this component.
   *
   * @param boolean $absolute
   *   If true, will return the path relative to the Drupal root. If false, will
   *   return the path relative to the implementing module's directory. Defaults
   *   to false.
   *
   * @return string
   */
  public function getPath(bool $absolute = false): string;

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

  /**
   * Return JavaScript settings to pass to the front-end.
   *
   * The default implementation just returns an empty array, which is ignored.
   * Override this to return settings. Don't nest arrays under 'AmbientImpact'
   * or the component name as that's done automatically.
   *
   * @return array
   *
   * @see https://www.drupal.org/docs/8/creating-custom-modules/adding-stylesheets-css-and-javascript-js-to-a-drupal-8-module#configurable
   *
   * @see \Drupal\ambientimpact_core\ComponentPluginManagerInterface::getComponentJSSettings()
   *
   * @see \Drupal\ambientimpact_core\EventSubscriber\Theme\PageAttachmentsEventSubscriber::pageAttachments()
   */
  public function getJSSettings(): array;

  /**
   * Determine if this Component has HTML available.
   *
   * @return boolean
   *   True if the <component name>.html.twig file exists, false otherwise.
   */
  public function hasHTML(): bool;

  /**
   * Get any HTML this Component may have available for the front-end.
   *
   * @return string|bool
   *   If the component has a <component name>.html.twig file in its directory,
   *   it will be rendered and returned, otherwise false is returned.
   *
   * @see https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Render%21RendererInterface.php/function/RendererInterface%3A%3Arender
   *   API documentation for the renderer.
   */
  public function getHTML();

  /**
   * Determine if this Component provides a demo.
   *
   * @return boolean
   *   True if this Component defines its own getDemo() method, false otherwise.
   *
   * @see https://stackoverflow.com/a/5817816
   *   Explains how to determine if a method was defined by the current class or
   *   was inherited.
   */
  public function hasDemo(): bool;

  /**
   * Get a demo render array for this Component.
   *
   * @return array
   *   A render array.
   */
  public function getDemo(): array;
}
