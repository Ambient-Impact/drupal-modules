<?php

namespace Drupal\ambientimpact_ux\Plugin\AmbientImpact\Component;

use Drupal\ambientimpact_core\ComponentBase;

/**
 * Scroll blocker component.
 *
 * @Component(
 *   id = "scroll_blocker",
 *   title = @Translation("Scroll blocker"),
 *   description = @Translation("Provides an API to temporarily prevent scrolling the viewport or a scrollable element when one or more blocking elements are provided. Layout shifting due to removal of scrollbars on platforms where they take up space is prevented using the scrollbar gutter component. The primary use-case for this is with overlays and other modal elements.")
 * )
 */
class ScrollBlocker extends ComponentBase {
}
