<?php

namespace Drupal\ambientimpact_core;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Url;
use Drupal\Component\Utility\NestedArray;
use Drupal\ambientimpact_core\Annotation\Component as ComponentAnnotation;

/**
 * Plugin manager for Ambient.Impact Component plugins.
 */
class ComponentPluginManager extends DefaultPluginManager {
  /**
   * An associative array of Component instances, keyed by plugin ID.
   *
   * @var array
   */
  protected $componentInstances = [];

  /**
   * The path to the Component HTML endpoint.
   *
   * @var string
   *
   * @see $this->getHTMLEndpointPath()
   *   Sets this property on first call to this method.
   */
  protected $htmlEndpointPath = '';

  /**
   * The route name for the Component HTML endpoint.
   *
   * @var string
   *
   * @see $this->getHTMLEndpointPath()
   *   Uses this.
   */
  protected $htmlEndpointRoute = 'ambientimpact_core.component_html_endpoint';

  /**
   * Creates the discovery object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin
   *   implementations.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cacheBackend
   *   Cache backend instance to use.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler to invoke the alter hook with.
   *
   * @see \Drupal\plugin_type_example\SandwichPluginManager
   *   This method is based heavily on the sandwich manager from the
   *   'examples' module.
   */
  public function __construct(
    \Traversable $namespaces,
    CacheBackendInterface $cacheBackend,
    ModuleHandlerInterface $moduleHandler
  ) {
    parent::__construct(
      // This tells the plugin manager to look for Component plugins in
      // the 'src/Plugin/AmbientImpact/Component' subdirectory of any
      // enabled modules. This also serves to define the PSR-4
      // subnamespace in which Component plugins will live.
      'Plugin/AmbientImpact/Component',

      $namespaces,

      $moduleHandler,

      // The name of the interface that plugins should adhere to. Drupal will
      // enforce this as a requirement. If a plugin does not implement this
      // interface, Drupal will throw an error.
      ComponentInterface::class,

      // The name of the annotation class that contains the plugin
      // definition.
      ComponentAnnotation::class
    );

    // This allows the plugin definitions to be altered by an alter hook.
    // The parameter defines the name of the hook, thus:
    // hook_ambientimpact_component_info_alter().
    // @todo Do we even need this?
    $this->alterInfo('ambientimpact_component_info');

    // This sets the caching method for our plugin definitions. Plugin
    // definitions are discovered by examining the directory defined above,
    // for any classes with an $plugin_definition_annotation_name. The
    // annotations are read, and then the resulting data is cached using the
    // provided cache backend.
    $this->setCacheBackend($cacheBackend, 'ambientimpact_component_info');
  }

  /**
   * Get a Component plugin definition by the plugin ID.
   *
   * @param string $componentID
   *   The plugin ID of the Component.
   *
   * @return array|false
   *   The Component plugin's definition array or false if the Component
   *   doesn't exist.
   */
  public function getComponentDefinition(string $componentID) {
    $definitions = $this->getDefinitions();

    // Check if the Component exists so that we can return false without
    // breaking things. If we were to use
    // Drupal\Component\Plugin\PluginManagerBase::getDefinition() here and a
    // plugin with the ID of $componentID does not exist, we'd get a big ol'
    // PHP white screen of death.
    if (isset($definitions[$componentID])) {
      return $definitions[$componentID];
    } else {
      return false;
    }
  }

  /**
   * Get a Component plugin's instance.
   *
   * Since each Component should only ever have one instance, this returns any
   * existing instance that is found, creating one if the $componentID is an
   * available definition but hasn't been instantiated yet.
   *
   * @param string $componentID
   *   The plugin ID of the Component.
   *
   * @return object|false
   *   The Component plugin instance or false if the $componentID doesn't
   *   exist in the plugin definitions.
   *
   * @see $this->componentInstances
   *   Component instances are stored here, keyed by $componentID.
   */
  public function getComponentInstance(string $componentID) {
    // Return an existing instance if found.
    if (isset($this->componentInstances[$componentID])) {
      return $this->componentInstances[$componentID];
    }

    $definition = $this->getComponentDefinition($componentID);

    if ($definition ===  false) {
      return false;
    }

    // Create the plugin instance.
    $this->componentInstances[$componentID] =
      $this->createInstance($componentID, []);

    return $this->componentInstances[$componentID];
  }

  /**
   * Get a Component plugin instance's configuration
   *
   * @param string $componentID
   *   The plugin ID of the Component.
   *
   * @return array
   *   The Component's configuration or an empty array if the Component was
   *   not found.
   */
  public function getComponentConfiguration(string $componentID) {
    $instance = $this->getComponentInstance($componentID);

    if ($instance !== false) {
      return $instance->getConfiguration();
    } else {
      return [];
    }
  }

  /**
   * Get all libraries defined by components.
   *
   * @return array
   *   A libraries array in the format hook_library_info_build() expects.
   *
   * @see https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!theme.api.php/function/hook_library_info_build
   * @see https://www.drupal.org/docs/8/creating-custom-modules/adding-stylesheets-css-and-javascript-js-to-a-drupal-8-module
   */
  public function getComponentLibraries() {
    $libraries = [];

    foreach ($this->getDefinitions() as $componentID => $definition) {
      $instance = $this->getComponentInstance($componentID);

      if ($instance === false) {
        continue;
      }

      $libraries = NestedArray::mergeDeep(
        $libraries,
        $instance->getLibraries()
      );
    }

    return $libraries;
  }

  /**
   * Get JavaScript settings from all available components.
   *
   * @return array
   *   An array of component settings to pass to drupalSettings, keyed by the
   *   component plugin ID. Components that return an empty array are ignored.
   *
   * @see \Drupal\ambientimpact_core\ComponentJSSettingsInterface::getJSSettings()
   * @see \Drupal\ambientimpact_core\ComponentJSSettingsTrait::getJSSettings()
   */
  public function getComponentJSSettings() {
    $jsSettings = [];

    foreach ($this->getDefinitions() as $componentID => $definition) {
      $instance = $this->getComponentInstance($componentID);

      if ($instance === false) {
        continue;
      }

      $instanceJSSettings = $instance->getJSSettings();

      // If we just get an empty array or other value that equates to
      // empty, skip this component.
      if (empty($instanceJSSettings)) {
        continue;
      }

      // Use the camelized version of the ID so that the front-end settings are
      // matched by the framework to the components, which declare themselves
      // using lowerCamelCase.
      $jsSettings[static::camelizeComponentID($componentID)] =
        $instanceJSSettings;
    }

    return $jsSettings;
  }

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
   * @see \Drupal\ambientimpact_core\EventSubscriber\HookPageAttachmentsEventSubscriber::pageAttachments()
   *   Passes the path to the front-end.
   */
  public function getHTMLEndpointPath() {
    if (empty($this->htmlEndpointPath)) {
      $this->htmlEndpointPath =
        Url::fromRoute($this->htmlEndpointRoute)->toString();
    }

    return $this->htmlEndpointPath;
  }

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
  public function getComponentHTML() {
    $html = [];

    foreach ($this->getDefinitions() as $componentID => $definition) {
      $instance = $this->getComponentInstance($componentID);

      if ($instance === false) {
        continue;
      }

      $instanceHTML = $instance->getHTML();

      // If we just get an empty string or other value that equates to empty,
      // skip this component.
      if (empty($instanceHTML)) {
        continue;
      }

      // Use the camelized version of the ID so that the front-end settings are
      // matched by the framework to the components, which declare themselves
      // using lowerCamelCase.
      $html[static::camelizeComponentID($componentID)] = $instanceHTML;
    }

    return $html;
  }

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
  public static function camelizeComponentID(string $id) {
    $camelized  = [];
    $exploded   = explode('.', $id);

    foreach ($exploded as $part) {
      $camelized[] = lcfirst(strtr(ucwords(strtr($part, [
        '_' => ' ',
      ])), [
        ' ' => '',
      ]));
    }

    return implode('.', $camelized);
  }
}
