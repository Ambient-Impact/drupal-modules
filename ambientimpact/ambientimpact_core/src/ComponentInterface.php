<?php

namespace Drupal\ambientimpact_core;

/**
 * An interface for all Ambient.Impact Component plugins.
 */
interface ComponentInterface {
	/**
	 * Get the path to this component, relative to the module directory.
	 *
	 * @return string
	 */
	public function getPath();
}
