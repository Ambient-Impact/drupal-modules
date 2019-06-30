<?php

namespace Drupal\ambientimpact_ux\Plugin\AmbientImpact\Component;

use Drupal\ambientimpact_core\ComponentBase;

/**
 * Off-canvas panel component.
 *
 * @Component(
 *   id = "offcanvas",
 *   title = @Translation("Off-canvas panel"),
 *   description = @Translation("Provides a wrapper component around <a href='https://frend.co/components/offcanvas/'>Frend Off Canvas</a>.")
 * )
 */
class OffCanvas extends ComponentBase {
  /**
   * {@inheritdoc}
   */
  public function getDemo(): array {
    $baseClass = 'offcanvas-demo-panels';

    $lorem = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.';

    // These are the panels we generate for the demo. The keys are the panel
    // direction, so they must be valid values that can be passed as the
    // 'panelLocation' option to the front-end JavaScript.
    $panels = [
      'left' => [
        'actionLabel' => $this->t('Open left'),
        'panelIntro'  => $this->t('This panel opens from the left side.'),
      ],
      'right' => [
        'actionLabel' => $this->t('Open right'),
        'panelIntro'  => $this->t('This panel opens from the right side.'),
      ],
      'top' => [
        'actionLabel' => $this->t('Open top'),
        'panelIntro'  => $this->t('This panel opens from the top.'),
      ],
      'bottom' => [
        'actionLabel' => $this->t('Open bottom'),
        'panelIntro'  => $this->t('This panel opens from the bottom.'),
      ],
    ];

    // This is the container, hidden both visually and in the accessibility
    // tree.
    $renderArray = [
      '#type'       => 'html_tag',
      '#tag'        => 'div',
      '#attributes' => ['class' => [$baseClass, 'hidden']],
      '#attached'   => [
        'library'     => [
          'ambientimpact_ux/component.offcanvas.demo',
        ],
      ],
    ];

    // Generate the panel render arrays based on the data in $panels.
    foreach ($panels as $panelMachineName => $panel) {
      $renderArray[$panelMachineName] = [
        '#type'       => 'html_tag',
        '#tag'        => 'div',
        '#attributes' => [
          // 'class'                 => [$baseClass . '__' . $panelMachineName],
          // The HTML ID is needed to be able to set the 'aria-controls'
          // attribute on open buttons.
          'id'                    => $baseClass . '-' . $panelMachineName,
          'data-panel-direction'  => $panelMachineName,
          'data-action-label'     => $panel['actionLabel'],
        ],
        'intro' => [
          '#type'     => 'html_tag',
          '#tag'      => 'p',
          '#value'    => $panel['panelIntro'],
        ],
        'lorem' => [
          '#type'     => 'html_tag',
          '#tag'      => 'p',
          '#value'    => $lorem,
        ],
      ];
    }

    return $renderArray;
  }
}
