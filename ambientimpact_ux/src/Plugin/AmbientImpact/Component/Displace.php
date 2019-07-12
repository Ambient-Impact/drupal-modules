<?php

namespace Drupal\ambientimpact_ux\Plugin\AmbientImpact\Component;

use Drupal\ambientimpact_core\ComponentBase;

/**
 * Displace component.
 *
 * @Component(
 *   id = "displace",
 *   title = @Translation("Displace"),
 *   description = @Translation("Provides a wrapper around <code>Drupal.displace</code> and sets <a href='https://developer.mozilla.org/en-US/docs/Web/CSS/Using_CSS_custom_properties'>CSS custom properties</a> on the <code>html</code> element when displacement changes.")
 * )
 */
class Displace extends ComponentBase {
}
