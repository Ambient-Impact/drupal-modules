<?php

namespace Drupal\ambientimpact_icon;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\ambientimpact_icon\Annotation\IconBundle as IconBundleAnnotation;

/**
 * Plugin manager for Ambient.Impact Icon Bundle plug-ins.
 */
class IconBundlePluginManager extends DefaultPluginManager {
  /**
   * An associative array of Icon Bundle instances, keyed by plug-in ID.
   *
   * @var array
   */
  protected $iconBundleInstances = [];

  /**
   * Creates the discovery object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plug-in
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
      // This tells the plug-in manager to look for Icon Bundle plug-ins in the
      // 'src/Plugin/AmbientImpact/Icon/Bundle' subdirectory of any enabled
      // modules. This also serves to define the PSR-4 subnamespace in which
      // Icon Bundle plug-ins will live.
      'Plugin/AmbientImpact/Icon/Bundle',

      $namespaces,

      $moduleHandler,

      // The name of the interface that plug-ins should adhere to. Drupal will
      // enforce this as a requirement. If a plug-in does not implement this
      // interface, Drupal will throw an error.
      IconBundleInterface::class,

      // The name of the annotation class that contains the plug-in definition.
      IconBundleAnnotation::class
    );

    // This allows the plug-in definitions to be altered by an alter hook. The
    // parameter defines the name of the hook, thus:
    // hook_ambientimpact_icon_bundle_info_alter().
    // @todo Do we even need this?
    $this->alterInfo('ambientimpact_icon_bundle_info');

    // This sets the caching method for our plug-in definitions. Plugin
    // definitions are discovered by examining the directory defined above, for
    // any classes with an IconBundleAnnotation. The annotations are read, and
    // then the resulting data is cached using the provided cache backend.
    $this->setCacheBackend($cacheBackend, 'ambientimpact_icon_bundle_info');
  }

  /**
   * Get an Icon Bundle plug-in's instance.
   *
   * Since each Icon Bundle should only ever have one instance, this returns
   * any existing instance that is found, creating one if the $iconBundleID is
   * an available definition but hasn't been instantiated yet.
   *
   * @param string $iconBundleID
   *   The plug-in ID of the Icon Bundle.
   *
   * @return object|false
   *   The Icon Bundle plug-in instance or false if the $iconBundleID doesn't
   *   exist in the plug-in definitions.
   *
   * @see $this->iconBundleInstances
   *   Icon Bundle instances are stored here, keyed by $iconBundleID.
   */
  public function getIconBundleInstance(string $iconBundleID) {
    // Return an existing instance if found.
    if (isset($this->iconBundleInstances[$iconBundleID])) {
      return $this->iconBundleInstances[$iconBundleID];
    }

    // Return false if the plug-in with the $iconBundleID is not found in the
    // discovered definitions.
    if (!isset($this->getDefinitions()[$iconBundleID])) {
      return false;
    }

    // Create the plug-in instance.
    $this->iconBundleInstances[$iconBundleID] =
      $this->createInstance($iconBundleID, []);

    return $this->iconBundleInstances[$iconBundleID];
  }

  /**
   * Get instances for multiple Icon Bundle plug-ins.
   *
   * @param array $iconBundleIDs
   *   An array of Icon Bundle IDs to return instances of. If not specified,
   *   defaults to an empty array which returns all discovered bundle plug-ins.
   *
   * @return array
   *   An array of \Drupal\ambientimpact_icon\Plugin\AmbientImpact\Icon\Bundle
   *   instances, one for each plug-in ID specified, or all if not specified.
   */
  public function getIconBundleInstances(array $iconBundleIDs = []) {
    $bundleInstances = [];

    // If no IDs were specified, attempt to get an instance of all discovered
    // plug-ins.
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
