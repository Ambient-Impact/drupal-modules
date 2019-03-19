<?php

namespace Drupal\ambientimpact_core\Plugin\AmbientImpact\Component;

use Drupal\ambientimpact_core\ComponentBase;

/**
 * Menu has an active item component.
 *
 * @Component(
 *   id = "menu_has_active_item",
 *   title = @Translation("Menu has an active item"),
 *   description = @Translation("Adds and removes a CSS class from menus when a menu item is being interacted with, allowing non-interacted items to be styled differently.")
 * )
 */
class MenuHasActiveItem extends ComponentBase {
}
