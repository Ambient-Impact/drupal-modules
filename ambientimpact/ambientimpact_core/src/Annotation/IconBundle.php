<?php

namespace Drupal\ambientimpact_core\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an Icon Bundle annotation object.
 *
 * @see \Drupal\ambientimpact_core\IconBundlePluginManager
 * @see plugin_api
 *
 * @Annotation
 */
class IconBundle extends Plugin {
	/**
	 * The human readable title of the Icon Bundle.
	 *
	 * @var \Drupal\Core\Annotation\Translation
	 *
	 * @ingroup plugin_translatable
	 */
	public $title;

	/**
	 * A brief human readable description of the Icon Bundle.
	 *
	 * @var \Drupal\Core\Annotation\Translation
	 *
	 * @ingroup plugin_translatable
	 */
	public $description;

	/**
	 * The type of license this bundle is provided under.
	 *
	 * This is usually a human-readable name, such as "GPLv2" or "MIT", but can
	 * also be a URL to the license.
	 *
	 * @var \Drupal\Core\Annotation\Translation
	 *
	 * @ingroup plugin_translatable
	 */
	public $license;

	/**
	 * The URL where this icon bundle can be downloaded from.
	 *
	 * @var string
	 */
	public $url;
}
