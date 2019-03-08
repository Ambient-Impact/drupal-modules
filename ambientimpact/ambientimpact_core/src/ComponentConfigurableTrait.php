<?php

namespace Drupal\ambientimpact_core;

use Drupal\Component\Utility\NestedArray;

/**
 * Configurable trait for Ambient.Impact Component plugins.
 *
 * @see Drupal\Core\Block\BlockBase
 *   Configuration methods based on the ones from BlockBase.
 */
trait ComponentConfigurableTrait {
	/**
	 * {@inheritdoc}
	 */
	public function getConfiguration() {
		return $this->configuration;
	}

	/**
	 * {@inheritdoc}
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
}
