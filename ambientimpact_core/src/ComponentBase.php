<?php

namespace Drupal\ambientimpact_core;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\ambientimpact_core\ComponentConfigurableTrait;
use Drupal\ambientimpact_core\ComponentLibrariesInterface;
use Drupal\ambientimpact_core\ComponentLibrariesTrait;
use Drupal\ambientimpact_core\ComponentJSSettingsInterface;
use Drupal\ambientimpact_core\ComponentJSSettingsTrait;

/**
 * Base class for implementing Ambient.Impact Component plugins.
 */
class ComponentBase extends PluginBase implements
ContainerFactoryPluginInterface, ConfigurableInterface, ComponentInterface,
ComponentLibrariesInterface, ComponentJSSettingsInterface {
	use ComponentConfigurableTrait;
	use ComponentLibrariesTrait;
	use ComponentJSSettingsTrait;

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
	 * The Drupal services container.
	 *
	 * @var \Symfony\Component\DependencyInjection\ContainerInterface
	 */
	protected $container;

	/**
	 * {@inheritdoc}
	 *
	 * This injects the service container into the constructor.
	 *
	 * @see https://medium.com/oneshoe/drupal-8-dependency-injection-47cc3ee62858
	 */
	public static function create(
		ContainerInterface $container,
		array $configuration, $pluginID, $pluginDefinition
	) {
		return new static(
			$configuration, $pluginID, $pluginDefinition, $container
		);
	}

	/**
	 * Constructs an Ambient.Impact Component object.
	 *
	 * This calls the parent PluginBase __construct, but then calls
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
	 * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
	 *   The Drupal services container.
	 *
	 * @see \Drupal\Component\Plugin\PluginBase
	 *   This is the parent class that the __construct() of is called.
	 */
	public function __construct(
		array $configuration, string $pluginID, array $pluginDefinition,
		ContainerInterface $container
	) {
		$this->container = $container;

		parent::__construct($configuration, $pluginID, $pluginDefinition);

		$this->setConfiguration($configuration);

		// Build the path if it hasn't been built/specified.
		if (empty($this->path)) {
			$this->path = $this->componentsDirectory . '/' . $pluginID;
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function getPath() {
		return $this->path;
	}
}
