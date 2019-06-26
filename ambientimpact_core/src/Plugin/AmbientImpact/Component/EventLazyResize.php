<?php

namespace Drupal\ambientimpact_core\Plugin\AmbientImpact\Component;

use Drupal\ambientimpact_core\ComponentBase;

/**
 * Event: lazy resize component.
 *
 * @Component(
 *   id = "event.lazy_resize",
 *   title = @Translation("Event: lazy resize"),
 *   description = @Translation("A throttled resize JavaScript event triggered on the window.")
 * )
 */
class EventLazyResize extends ComponentBase {
}
