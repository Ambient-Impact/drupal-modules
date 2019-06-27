<?php

namespace Drupal\ambientimpact_core;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Serialization\SerializationInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for implementing Ambient.Impact Component plugins.
 */
class ComponentBase extends PluginBase implements
ContainerFactoryPluginInterface, ConfigurableInterface, ComponentInterface {
  /**
   * The Drupal module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The Drupal language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The Drupal renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The Drupal YAML serialization class.
   *
   * @var \Drupal\Component\Serialization\SerializationInterface
   */
  protected $yamlSerialization;

  /**
   * The Component HTML cache service.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $htmlCacheService;

  /**
   * The directory in which component directories are located.
   *
   * This is relative to the implementing module's directory.
   *
   * @var string
   */
  protected $componentsDirectory = 'components';

  /**
   * The path to this component's directory.
   *
   * This is relative to the implementing module's directory.
   *
   * If empty, will be built in $this->__construct() with
   * $this->componentsDirectory and the plugin ID.
   *
   * @var string
   *
   * @see $this->componentsDirectory
   *   The directory in which this component's directory is found, relative to
   *   the implementing module's directory.
   */
  protected $path = '';

  /**
   * Whether this Component has any HTML cached.
   *
   * @var null|bool
   */
  protected $hasCachedHTML = null;

  /**
   * This Component's HTML cache ID.
   *
   * @var null|string
   */
  protected $htmlCacheID = null;

  /**
   * Constructs an Ambient.Impact Component object.
   *
   * This calls the parent PluginBase::__construct(), and then calls
   * $this->setConfiguration() to ensure settings are merged over defaults.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   *
   * @param string $pluginID
   *   The plugin_id for the plugin instance.
   *
   * @param array $pluginDefinition
   *   The plugin implementation definition. PluginBase defines this as mixed,
   *   but we should always have an array so the type is set. This can be
   *   changed in the future if need be.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The Drupal module handler service.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The Drupal language manager service.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The Drupal renderer service.
   *
   * @param \Drupal\Component\Serialization\SerializationInterface $yamlSerialization
   *   The Drupal YAML serialization class.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $htmlCacheService
   *   The Component HTML cache service.
   */
  public function __construct(
    array $configuration, string $pluginID, array $pluginDefinition,
    ModuleHandlerInterface $moduleHandler,
    LanguageManagerInterface $languageManager,
    RendererInterface $renderer,
    SerializationInterface $yamlSerialization,
    CacheBackendInterface $htmlCacheService
  ) {
    parent::__construct($configuration, $pluginID, $pluginDefinition);

    // Save dependencies.
    $this->moduleHandler      = $moduleHandler;
    $this->languageManager    = $languageManager;
    $this->renderer           = $renderer;
    $this->yamlSerialization  = $yamlSerialization;
    $this->htmlCacheService   = $htmlCacheService;

    $this->setConfiguration($configuration);

    // Build the path if it hasn't been built/specified.
    if (empty($this->path)) {
      $this->path = $this->componentsDirectory . '/' . $pluginID;
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration, $pluginID, $pluginDefinition
  ) {
    return new static(
      $configuration, $pluginID, $pluginDefinition,
      $container->get('module_handler'),
      $container->get('language_manager'),
      $container->get('renderer'),
      $container->get('serialization.yaml'),
      $container->get('cache.ambientimpact_component_html')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getPath(): string {
    return $this->path;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\Core\Block\BlockBase::setConfiguration()
   *   Copied from this.
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = NestedArray::mergeDeep(
      $this->baseConfigurationDefaults(),
      $this->defaultConfiguration(),
      $configuration
    );
  }

  /**
   * Returns generic default configuration for Component plugins.
   *
   * @return array
   *   An associative array with the default configuration.
   *
   * @todo Is there a point to including the plug-in ID in this?
   */
  protected function baseConfigurationDefaults() {
    return [
      'id' => $this->getPluginId(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries(): array {
    // This component libraries, if any are found.
    $libraries      = [];
    // An array of file array references for ease of manipulation: one index for
    // each 'css' group found, and the 'js' array, if present. At that level
    // they're structured the same, so this avoids repeating code.
    $files          = [];

    // Get the YAML parser.
    $parser         = $this->yamlSerialization;
    // Get the path to the module implementing this component plugin.
    $modulePath     = $this->moduleHandler
      ->getModule($this->pluginDefinition['provider'])->getPath();
    // This is the path to the component from Drupal's root, including the
    // implementing module.
    $componentPath  = $modulePath . '/' . $this->path;

    // This is the full file system path to the file, including the file name
    // and extension.
    $filePath =
      DRUPAL_ROOT . '/' . $componentPath . '/' .
      $this->pluginDefinition['id'] . '.libraries.yml';

    // Don't proceed if the file doesn't exist.
    if (!file_exists($filePath)) {
      return $libraries;
    }

    // Parse the YAML file.
    $libraries = $parser::decode(file_get_contents($filePath));

    foreach ($libraries as &$library) {
      // Save references to each 'css' group array found.
      if (isset($library['css'])) {
        foreach ($library['css'] as &$category) {
          $files[] = &$category;
        }
      }

      if (isset($library['js'])) {
        // Save a reference to the 'js' array, if found.
        $files[] = &$library['js'];

        // If there no dependencies or the framework isn't found in the
        // dependencies, insert it.
        if (
          !isset($library['dependencies']) ||
          is_array($library['dependencies']) &&
          !in_array(
            'ambientimpact_core/framework',
            $library['dependencies']
          )
        ) {
          $library['dependencies'][] = 'ambientimpact_core/framework';
        }

        // If no 'defer' attribute is set, default to true to delay component
        // JavaScript until most other stuff is done executing. This helps the
        // page feel a bit faster to load.
        foreach ($library['js'] as $file => &$fileSettings) {
          if (!isset($fileSettings['attributes']['defer'])) {
            $fileSettings['attributes']['defer'] = true;
          }
        }
      }
    }

    // Prepend the component path to make it relative to the module's directory
    // as opposed to the component's.
    foreach ($files as &$category) {
      foreach (array_keys($category) as $key) {
        // New key.
        $category[$this->path . '/' . $key] = $category[$key];
        // Delete the old key.
        unset($category[$key]);
      }
    }

    return $libraries;
  }

  /**
   * {@inheritdoc}
   */
  public function getJSSettings(): array {
    return [];
  }

  /**
   * Get the Component HTML cache settings.
   *
   * This can be overridden on a per-Component basis to set custom cache
   * invalidation.
   *
   * This supports 'max-age' and 'tags', but 'contexts' is not yet supported.
   *
   * @return array
   *   The Component HTML cache settings with 'max-age' set to permanent, i.e.
   *   only rebuilt on a cache rebuild.
   *
   * @see https://api.drupal.org/api/drupal/core!core.api.php/group/cache
   *   Drupal Cache API documentation.
   *
   * @todo Add support for cache contexts?
   *
   * @see https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Render%21Renderer.php/function/Renderer%3A%3AdoRender
   *   Cache contexts used in the rendering process.
   */
  protected static function getHTMLCacheSettings(): array {
    return [
      'max-age' => Cache::PERMANENT,
    ];
  }

  /**
   * Get this Component's HTML cache ID.
   *
   * This is only built once and stored for subsequent use.
   *
   * @return string
   *   The value of $this->htmlCacheID.
   *
   * @see $this->htmlCacheID
   *   The HTML cache ID is stored here.
   */
  protected function getHTMLCacheID(): string {
    if ($this->htmlCacheID === null) {
      $this->htmlCacheID =
        $this->pluginDefinition['provider'] . ':' .
        $this->pluginDefinition['id'] . ':' .
        $this->languageManager->getCurrentLanguage()->getId();
    }

    return $this->htmlCacheID;
  }

  /**
   * Determine if this Component has any cached HTML available.
   *
   * @return boolean
   *   The value of $this->hasCachedHTML.
   *
   * @see $this->hasCachedHTML
   *   Stores whether this Component has cached HTML.
   */
  protected function hasCachedHTML(): bool {
    if ($this->hasCachedHTML === null) {
      $this->hasCachedHTML = !empty(
        $this->htmlCacheService->get($this->getHTMLCacheID())->data
      );
    }

    return $this->hasCachedHTML;
  }

  /**
   * Get the file system path to this Component's HTML file.
   *
   * @return string
   *   The Component's <component name>.html.twig file path.
   */
  protected function getHTMLPath() {
    // Get the path to the module implementing this component plugin.
    $modulePath = $this->moduleHandler->getModule(
      $this->pluginDefinition['provider']
    )->getPath();

    // This is the path to the component from Drupal's root, including the
    // implementing module.
    $componentPath  = $modulePath . '/' . $this->path;

    // This is the full file system path to the file, including the file name
    // and extension.
    return DRUPAL_ROOT . '/' . $componentPath . '/' .
      $this->pluginDefinition['id'] . '.html.twig';
  }

  /**
   * {@inheritdoc}
   */
  public function hasHTML(): bool {
    return file_exists($this->getHTMLPath());
  }

  /**
   * {@inheritdoc}
   */
  public function getHTML() {
    // Don't proceed if a Twig template doesn't exist.
    if (!$this->hasHTML()) {
      return false;
    }

    // If cached HTML is available, grab that without doing any rendering.
    if ($this->hasCachedHTML()) {
      $html = $this->htmlCacheService->get($this->getHTMLCacheID())->data;

    // If no cached HTML is found, render and cache the HTML.
    } else {
      // Render array containing the file contents as an inline template.
      $renderArray = [
        '#type'     => 'inline_template',
        '#template' => file_get_contents($this->getHTMLPath()),
      ];

      // Render the inline template.
      $html = $this->renderer->renderPlain($renderArray);

      $cacheSettings = static::getHTMLCacheSettings();

      // Set the 'max-age' and 'tags' keys if they're not set.
      if (!isset($cacheSettings['max-age'])) {
        $cacheSettings['max-age'] = Cache::PERMANENT;
      }
      if (!isset($cacheSettings['tags'])) {
        $cacheSettings['tags'] = [];
      }

      // Save the rendered template HTML to the cache.
      $this->htmlCacheService->set(
        $this->getHTMLCacheID(),
        $html,
        $cacheSettings['max-age'],
        $cacheSettings['tags']
      );
    }

    return $html;
  }

  /**
   * {@inheritdoc}
   */
  public function hasDemo(): bool {
    $reflection = new \ReflectionMethod($this, 'getDemo');

    return get_class() !== $reflection->getDeclaringClass()->getName();
  }

  /**
   * {@inheritdoc}
   */
  public function getDemo(): array {
    return [];
  }
}
