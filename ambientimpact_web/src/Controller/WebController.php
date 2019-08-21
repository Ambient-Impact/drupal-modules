<?php

namespace Drupal\ambientimpact_web\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller for the 'ambientimpact_web.overview' route.
 */
class WebController extends ControllerBase {
  /**
   * Builds and returns the overview render array.
   *
   * @return array
   *   The overview render array.
   */
  public function overview() {
    $renderArray = [
      '#markup' => $this->t('Please choose a section from the list.'),
    ];

    return $renderArray;
  }
}
