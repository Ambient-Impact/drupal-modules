<?php

namespace Drupal\ambientimpact_core;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\ambientimpact_core\Annotation\IconBundle as IconBundleAnnotation;

/**
 * Plugin manager for Ambient.Impact Icon Bundle plugins.
 */
class IconBundlePluginManager extends DefaultPluginManager {
  /**
   * An associative array of Icon Bundle instances, keyed by plugin ID.
   *
   * @var array
   */
  protected $iconBundleInstances = [];

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
      // This tells the plugin manager to look for Icon Bundle plugins in the
      // 'src/Plugin/AmbientImpact/Icon/Bundle' subdirectory of any enabled
      // modules. This also serves to define the PSR-4 subnamespace in which
      // Icon Bundle plugins will live.
      'Plugin/AmbientImpact/Icon/Bundle',

      $namespaces,

      $moduleHandler,

      // The name of the interface that plugins should adhere to. Drupal will
      // enforce this as a requirement. If a plugin does not implement this
      // interface, Drupal will throw an error.
      IconBundleInterface::class,

      // The name of the annotation class that contains the plugin definition.
      IconBundleAnnotation::class
    );

    // This allows the plugin definitions to be altered by an alter hook. The
    // parameter defines the name of the hook, thus:
    // hook_ambientimpact_icon_bundle_info_alter().
    // @todo Do we even need this?
    $this->alterInfo('ambientimpact_icon_bundle_info');

    // This sets the caching method for our plugin definitions. Plugin
    // definitions are discovered by examining the directory defined above, for
    // any classes with an IconBundleAnnotation. The annotations are read, and
    // then the resulting data is cached using the provided cache backend.
    $this->setCacheBackend($cacheBackend, 'ambientimpact_icon_bundle_info');
  }

  /**
   * Get an Icon Bundle plugin's instance.
   *
   * Since each Icon Bundle should only ever have one instance, this returns
   * any existing instance that is found, creating one if the $iconBundleID is
   * an available definition but hasn't been instantiated yet.
   *
   * @param string $iconBundleID
   *   The plugin ID of the Icon Bundle.
   *
   * @return object|false
   *   The Icon Bundle plugin instance or false if the $iconBundleID doesn't
   *   exist in the plugin definitions.
   *
   * @see $this->iconBundleInstances
   *   Icon Bundle instances are stored here, keyed by $iconBundleID.
   */
  public function getIconBundleInstance(string $iconBundleID) {
    // Return an existing instance if found.
    if (isset($this->iconBundleInstances[$iconBundleID])) {
      return $this->iconBundleInstances[$iconBundleID];
    }

    // Return false if the plugin with the $iconBundleID is not found in the
    // discovered definitions.
    if (!isset($this->getDefinitions()[$iconBundleID])) {
      return false;
    }

    // Create the plugin instance.
    $this->iconBundleInstances[$iconBundleID] =
      $this->createInstance($iconBundleID, []);

    return $this->iconBundleInstances[$iconBundleID];
  }

  /**
   * Get instances for multiple Icon Bundle plugins.
   *
   * @param array $iconBundleIDs
   *   An array of Icon Bundle IDs to return instances of. If not specified,
   *   defaults to an empty array which returns all discovered bundle plugins.
   *
   * @return array
   *   An array of \Drupal\ambientimpact_core\Plugin\AmbientImpact\Icon\Bundle
   *   instances, one for each plugin ID specified, or all if not specified.
   */
  public function getIconBundleInstances(array $iconBundleIDs = []) {
    $bundleInstances = [];

    // If no IDs were specified, attempt to get an instance of all discovered
    // plugins.
    if (empty($iconBundleIDs)) {
      foreach ($this->getDefinitions() as $pluginID => $definition) {
        $bundleInstances[$pluginID] =
          $this->getIconBundleInstance($pluginID);
      }

    // If IDs were specified, attempt to fetch each one.
    } else {
      foreach ($iconBundleIDs as $pluginID) {
        $bundle = $this->getIconBundleInstance($pluginID);

        if ($bundle !== false) {
          $bundleInstances[$pluginID] = $bundle;
        }
      }
    }

    return $bundleInstances;
  }
}
