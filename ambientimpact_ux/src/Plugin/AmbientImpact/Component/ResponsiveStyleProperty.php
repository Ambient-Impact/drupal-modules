<?php

namespace Drupal\ambientimpact_ux\Plugin\AmbientImpact\Component;

use Drupal\ambientimpact_core\ComponentBase;

/**
 * Responsive style property component.
 *
 * @Component(
 *   id = "responsive_style_property",
 *   title = @Translation("Responsive style property"),
 *   description = @Translation("Provides an API to query and be notified of changes to a CSS property that changes based on viewport and displacement size. This is primarily intended to be used for <a href='https://developer.mozilla.org/en-US/docs/Web/CSS/Using_CSS_custom_properties'>CSS custom properties</a>, but can be used for any CSS property.")
 * )
 */
class ResponsiveStyleProperty extends ComponentBase {
}
