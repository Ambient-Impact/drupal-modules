<?php

namespace Drupal\ambientimpact_ux\Plugin\AmbientImpact\Component;

use Drupal\ambientimpact_core\ComponentBase;
use Drupal\Component\Utility\Html;

/**
 * To top component.
 *
 * @Component(
 *   id = "to_top",
 *   title = @Translation("To top"),
 *   description = @Translation("Scroll to the top of the page via a button-style link that shows and hides based on scroll direction and distance to the top of the page.")
 * )
 */
class ToTop extends ComponentBase {
  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'topAnchorID' => Html::getUniqueId('top'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getJSSettings(): array {
    $config = $this->configuration;

    return [
      'topAnchorID' => $config['topAnchorID'],
    ];
  }
}
