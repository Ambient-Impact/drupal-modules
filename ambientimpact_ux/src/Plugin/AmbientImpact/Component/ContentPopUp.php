<?php

namespace Drupal\ambientimpact_ux\Plugin\AmbientImpact\Component;

use Drupal\ambientimpact_core\ComponentBase;

/**
 * Content pop-up component.
 *
 * @Component(
 *   id = "content_popup",
 *   title = @Translation("Content pop-up"),
 *   description = @Translation("This component displays content either in a tooltip (on wider screens, e.g. computers and tablets) or a panel that slides in from the bottom (on narrower screens, e.g. phones), switching between the two dynamically as the viewport changes width. Depends on the <a href='/web/components/tooltip'>Tooltip</a> and <a href='/web/components/offcanvas'>Off-canvas</a> components.")
 * )
 */
class ContentPopUp extends ComponentBase {
  /**
   * {@inheritdoc}
   */
  public function getDemo(): array {
    $baseClass = 'content-popup-demo-panels';

    $content = [
      'dark'  => [
        'label'   => $this->t('Dark theme'),
        'content' => $this->t('<a href="https://www.imdb.com/title/tt0080684/quotes/qt0358573">It is a dark time for the Rebellion.</a> Although the Death Star has been destroyed, Imperial troops have driven the Rebel forces from their hidden base and pursued them across the galaxy. Evading the dreaded Imperial Starfleet, a group of freedom fighters led by Luke Skywalker has established a new secret base on the remote ice world of Hoth.'),
      ],
      'light' => [
        'label'   => $this->t('Light theme'),
        'content' => $this->t('<a href="https://www.imdb.com/title/tt3748528/quotes/qt3188182">I\'m one with the Force and the Force is with me.</a> I\'m one with the Force and the Force is with me. I\'m one with the Force and the Force is with me. I\'m one with the Force and the Force is with me. I\'m one with the Force and the Force is with me. I\'m one with the Force and the Force is with me. I\'m one with the Force and the Force is with me. I\'m one with the Force and the Force is with me.'),
      ],
    ];

    $renderArray = [
      '#intro'  => [
        'instructions' => [
          '#type'       => 'html_tag',
          '#tag'        => 'p',
          '#value'      => $this->t('Move your pointer over the following buttons if you have a mouse or other pointing device, tap them if using a touch device, or if using the keyboard for navigation, tab over to the buttons and into the pop-up content as it appears. If the buttons or panels fail to appear, there may be a problem executing JavaScript in your browser.'),
        ],
      ],
      '#demo'   => [
        '#type'       => 'html_tag',
        '#tag'        => 'div',
        '#attributes' => [
          'class'           => [$baseClass, 'hidden'],
          'data-base-class' => $baseClass,
        ],
        '#attached'   => [
          'library'     => [
            'ambientimpact_ux/component.content_popup.demo',
          ],
        ],
      ],
    ];

    // Generate the panel render arrays based on the data in $content.
    foreach ($content as $themeName => $contentItem) {
      $renderArray['#demo'][$themeName] = [
        '#type'       => 'html_tag',
        '#tag'        => 'div',
        '#attributes' => [
          'data-content-popup-theme'  => $themeName,
          'data-action-label'         => $contentItem['label'],
        ],
        'content' => [
          '#type'     => 'html_tag',
          '#tag'      => 'p',
          '#value'    => $contentItem['content'],
        ],
      ];
    }

    return $renderArray;
  }
}
