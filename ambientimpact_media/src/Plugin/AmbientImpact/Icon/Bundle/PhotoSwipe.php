<?php

namespace Drupal\ambientimpact_media\Plugin\AmbientImpact\Icon\Bundle;

use Drupal\ambientimpact_icon\IconBundleBase;

/**
 * PhotoSwipe icon bundle.
 *
 * @IconBundle(
 *   id = "photoswipe",
 *   title = @Translation("PhotoSwipe"),
 *   description = @Translation("The PhotoSwipe icon set."),
 *   license = @Translation("MIT"),
 *   url = "https://photoswipe.com/"
 * )
 */
class PhotoSwipe extends IconBundleBase {
	/**
	 * {@inheritdoc}
	 */
	protected $path = 'components/photoswipe/icons';
}
