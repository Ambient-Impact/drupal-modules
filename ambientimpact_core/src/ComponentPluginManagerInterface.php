<?php

namespace Drupal\ambientimpact_core;

/**
 * Defines an interface for Ambient.Impact Component plug-in managers.
 */
interface ComponentPluginManagerInterface {
  /**
   * Get a Component plug-in definition by the plug-in ID.
   *
   * @param string $componentID
   *   The plug-in ID of the Component.
   *
   * @return array|false
   *   The Component plug-in's definition array or false if the Component
   *   doesn't exist.
   */
  public function getComponentDefinition(string $componentID);

  /**
   * Get a Component plug-in's instance.
   *
   * Since each Component should only ever have one instance, this returns any
   * existing instance that is found, creating one if the $componentID is an
   * available definition but hasn't been instantiated yet.
   *
   * @param string $componentID
   *   The plug-in ID of the Component.
   *
   * @return object|false
   *   The Component plug-in instance or false if the $componentID doesn't
   *   exist in the plug-in definitions.
   *
   * @see $this->componentInstances
   *   Component instances are stored here, keyed by $componentID.
   */
  public function getComponentInstance(string $componentID);

  /**
   * Get a Component plug-in instance's configuration
   *
   * @param string $componentID
   *   The plug-in ID of the Component.
   *
   * @return array
   *   The Component's configuration or an empty array if the Component was
   *   not found.
   */
  public function getComponentConfiguration(string $componentID): array;

  /**
   * Get all libraries defined by components.
   *
   * @return array
   *   A libraries array in the format hook_library_info_build() expects.
   *
   * @see https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!theme.api.php/function/hook_library_info_build
   * @see https://www.drupal.org/docs/8/creating-custom-modules/adding-stylesheets-css-and-javascript-js-to-a-drupal-8-module
   */
  public function getComponentLibraries(): array;

  /**
   * Get JavaScript settings from all available components.
   *
   * @return array
   *   An array of component settings to pass to drupalSettings, keyed by the
   *   component plug-in ID. Components that return an empty array are ignored.
   *
   * @see \Drupal\ambientimpact_core\ComponentJSSettingsInterface::getJSSettings()
   * @see \Drupal\ambientimpact_core\ComponentJSSettingsTrait::getJSSettings()
   */
  public function getComponentJSSettings(): array;

  /**
   * Get the Component HTML endpoint path.
   *
   * This gets the path from Drupal via the route system.
   *
   * @return string
   *   The Component HTML endpoint path.
   *
   * @see $this->htmlEndpointPath
   *   Stores the path so we only have to build it once.
   *
   * @see $this->htmlEndpointRoute
   *   Contains the route name.
   *
   * @see \Drupal\ambientimpact_core\EventSubscriber\Theme\PageAttachmentsEventSubscriber::pageAttachments()
   *   Passes the path to the front-end.
   */
  public function getHTMLEndpointPath(): string;

  /**
   * Get HTML from all available Components.
   *
   * @return array
   *   An array of key/value pairs, where the keys are the lowerCamelCase ID
   *   of the Component and the value being the rendered HTML of that Component.
   *   Components that do not provide any HTML are not included in this.
   *
   * @see static::camelizeComponentID()
   *   Converts Component ID to lowerCamelCase.
   */
  public function getComponentHTML(): array;

  /**
   * Get an array of Component IDs that have HTML in lowerCamelCase format.
   *
   * @return array
   *   An array containing zero or more lowerCamelCase Component IDs that have
   *   HTML available.
   */
  public function getComponentNamesWithHTML(): array;

  /**
   * Convert a Component ID to lowerCamelCase format.
   *
   * This is copied from the Symfony Container::camelize() method, with the
   * following changes:
   *
   * - The first character is lowercased.
   *
   * - The only characters used for delimiters are underscores (_), so periods
   *   (.) are left as-is.
   *
   * - Periods (.) are used to denote a sub-component or namespace, so they're
   *   treated as starting a new lowercase portion.
   *
   * @param  string $id
   *   The Component ID to camelize.
   *
   * @return string
   *   The Component ID in lowerCamelCase.
   *
   * @see https://api.drupal.org/api/drupal/vendor%21symfony%21dependency-injection%21Container.php/function/Container%3A%3Acamelize
   *   The Symfony method that inspired this.
   */
  public static function camelizeComponentID(string $id): string;
}
