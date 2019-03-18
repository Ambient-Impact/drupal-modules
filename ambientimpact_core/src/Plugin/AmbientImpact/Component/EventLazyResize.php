<?php

namespace Drupal\ambientimpact_core\Plugin\AmbientImpact\Component;

use Drupal\ambientimpact_core\ComponentBase;

/**
 * Lazy resize event component.
 *
 * @Component(
 *   id = "event.lazy_resize",
 *   title = @Translation("Lazy resize event"),
 *   description = @Translation("A throttled resize event triggered on the window.")
 * )
 */
class EventLazyResize extends ComponentBase {
}
