<?php

namespace Drupal\ambientimpact_core;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Url;
use Drupal\Component\Utility\NestedArray;
use Drupal\ambientimpact_core\Annotation\Component as ComponentAnnotation;
use Drupal\ambientimpact_core\ComponentPluginManagerInterface;

/**
 * Plug-in manager for Ambient.Impact Component plug-ins.
 */
class ComponentPluginManager extends DefaultPluginManager
implements ComponentPluginManagerInterface{
  /**
   * An associative array of Component instances, keyed by plug-in ID.
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
      // This tells the plug-in manager to look for Component plug-ins in the
      // 'src/Plugin/AmbientImpact/Component' subdirectory of any enabled
      // modules. This also serves to define the PSR-4 subnamespace in which
      // Component plug-ins will live.
      'Plugin/AmbientImpact/Component',

      $namespaces,

      $moduleHandler,

      // The name of the interface that plug-ins should adhere to. Drupal will
      // enforce this as a requirement. If a plug-in does not implement this
      // interface, Drupal will throw an error.
      ComponentInterface::class,

      // The name of the annotation class that contains the plug-in definition.
      ComponentAnnotation::class
    );

    // This allows the plug-in definitions to be altered by an alter hook. The
    // parameter defines the name of the hook:
    // hook_ambientimpact_component_info_alter().
    // @todo Do we even need this?
    $this->alterInfo('ambientimpact_component_info');

    // This sets the caching method for our plug-in definitions. Plug-in
    // definitions are discovered by examining the directory defined above, for
    // any classes with a ComponentAnnotation::class. The annotations are read,
    // and then the resulting data is cached using the provided cache backend.
    $this->setCacheBackend($cacheBackend, 'ambientimpact_component_info');
  }

  /**
   * {@inheritdoc}
   */
  public function getComponentDefinition(string $componentID) {
    $definitions = $this->getDefinitions();

    // Check if the Component exists so that we can return false without
    // breaking things. If we were to use
    // \Drupal\Component\Plugin\PluginManagerBase::getDefinition() here and a
    // plug-in with the ID of $componentID does not exist, we'd get a big ol'
    // PHP white screen of death.
    if (isset($definitions[$componentID])) {
      return $definitions[$componentID];
    } else {
      return false;
    }
  }

  /**
   * {@inheritdoc}
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

    // Create the plug-in instance.
    $this->componentInstances[$componentID] =
      $this->createInstance($componentID, []);

    return $this->componentInstances[$componentID];
  }

  /**
   * {@inheritdoc}
   */
  public function getComponentConfiguration(string $componentID): array {
    $instance = $this->getComponentInstance($componentID);

    if ($instance !== false) {
      return $instance->getConfiguration();
    } else {
      return [];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getComponentLibraries(): array {
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
   * {@inheritdoc}
   */
  public function getComponentJSSettings(): array {
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
   * {@inheritdoc}
   */
  public function getHTMLEndpointPath(): string {
    if (empty($this->htmlEndpointPath)) {
      $this->htmlEndpointPath =
        Url::fromRoute($this->htmlEndpointRoute)->toString();
    }

    return $this->htmlEndpointPath;
  }

  /**
   * {@inheritdoc}
   */
  public function getComponentHTML(): array {
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
   * {@inheritdoc}
   */
  public function getComponentNamesWithHTML(): array {
    $haveHTML = [];

    foreach ($this->getDefinitions() as $componentID => $definition) {
      $instance = $this->getComponentInstance($componentID);

      if (
        $instance === false ||
        !$instance->hasHTML()
      ) {
        continue;
      }

      // Use the camelized version of the ID so that the front-end settings are
      // matched by the framework to the components, which declare themselves
      // using lowerCamelCase.
      $haveHTML[] = static::camelizeComponentID($componentID);
    }

    return $haveHTML;
  }

  /**
   * {@inheritdoc}
   */
  public static function camelizeComponentID(string $id): string {
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
