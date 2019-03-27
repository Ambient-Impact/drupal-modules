<?php

namespace Drupal\ambientimpact_core;

/**
 * A trait for defining libraries for Ambient.Impact Component plugins.
 */
trait ComponentLibrariesTrait {
	/**
	 * {@inheritdoc}
	 */
	public function getLibraries() {
		// This component libraries, if any are found.
		$libraries		= [];
		// An array of file array references for ease of manipulation: one index
		// for each 'css' group found, and the 'js' array, if present. At that
		// level they're structured the same, so this avoids repeating code.
		$files			= [];

		// Get the YAML parser service.
		$parser			= $this->container->get('serialization.yaml');
		// Get the path to the module implementing this component plugin.
		$modulePath		= $this->container->get('module_handler')
			->getModule($this->pluginDefinition['provider'])->getPath();
		// This is the path to the component from Drupal's root, including the
		// implementing module.
		$componentPath	= $modulePath . '/' . $this->path;

		// This is the full file system path to the file, including the file
		// name and extension.
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
					if (!isset($fileSettings['defer'])) {
						$fileSettings['defer'] = true;
					}
				}
			}
		}

		// Prepend the component path to make it relative to the module's
		// directory as opposed to the component's.
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
}
