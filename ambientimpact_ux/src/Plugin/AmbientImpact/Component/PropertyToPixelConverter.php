<?php

declare(strict_types=1);

namespace Drupal\ambientimpact_ux\Plugin\AmbientImpact\Component;

use Drupal\ambientimpact_core\ComponentBase;

/**
 * Property to pixel converter component.
 *
 * @Component(
 *   id = "property_to_pixel_converter",
 *   title = @Translation("Property to pixel converter"),
 *   description = @Translation("Abstracts reading a value from an element's styles and converting it to pixels. This is especially useful when pixels are required for something in JavaScript but padding, margins, etc. are defined in CSS in <code>em</code>s, for example.")
 * )
 */
class PropertyToPixelConverter extends ComponentBase {
}
