<?php

namespace Drupal\ambientimpact_core;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Serialization\SerializationInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\ambientimpact_core\ComponentConfigurableTrait;
use Drupal\ambientimpact_core\ComponentLibrariesInterface;
use Drupal\ambientimpact_core\ComponentLibrariesTrait;
use Drupal\ambientimpact_core\ComponentJSSettingsInterface;
use Drupal\ambientimpact_core\ComponentJSSettingsTrait;
use Drupal\ambientimpact_core\ComponentHTMLInterface;
use Drupal\ambientimpact_core\ComponentHTMLTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for implementing Ambient.Impact Component plugins.
 */
class ComponentBase extends PluginBase implements
ContainerFactoryPluginInterface, ConfigurableInterface, ComponentInterface,
ComponentLibrariesInterface, ComponentJSSettingsInterface,
ComponentHTMLInterface {
  use ComponentConfigurableTrait;
  use ComponentLibrariesTrait;
  use ComponentJSSettingsTrait;
  use ComponentHTMLTrait;

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
}
