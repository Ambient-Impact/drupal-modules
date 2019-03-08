<?php

namespace Drupal\ambientimpact_core;

/**
 * An interface for configurable Ambient.Impact Component plugins.
 *
 * \Drupal\Component\Plugin\PluginBase provides a $configuration property
 * but does not provide any getters or setters, so this is copied from
 * \Drupal\Component\Plugin\ConfigurablePluginInterface.
 *
 * @see \Drupal\Component\Plugin\PluginBase
 *   Contains the $configuration property.
 *
 * @see \Drupal\Component\Plugin\ConfigurablePluginInterface
 *   Configuration interface methods based on the core plugin interface but
 *   without extending \Drupal\Component\Plugin\DependentPluginInterface.
 */
interface ComponentConfigurableInterface {
	/**
	 * Gets this plugin's configuration.
	 *
	 * @return array
	 *   An array of this plugin's configuration.
	 */
	public function getConfiguration();

	/**
	 * Sets the configuration for this plugin instance.
	 *
	 * @param array $configuration
	 *   An associative array containing the plugin's configuration.
	 */
	public function setConfiguration(array $configuration);

	/**
	 * Gets default configuration for this plugin.
	 *
	 * @return array
	 *   An associative array with the default configuration.
	 */
	public function defaultConfiguration();
}
