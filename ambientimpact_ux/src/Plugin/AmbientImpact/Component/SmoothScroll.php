<?php

namespace Drupal\ambientimpact_ux\Plugin\AmbientImpact\Component;

use Drupal\ambientimpact_core\ComponentBase;

/**
 * Smooth scroll component.
 *
 * @Component(
 *   id = "smooth_scroll",
 *   title = @Translation("Smooth scroll"),
 *   description = @Translation("Scrolls smoothly to and from in-page anchor targets - i.e. links that point to an ID on the page like <code>#id</code>. Scrolling takes viewport displacement into account; for example, from the Drupal toolbar. Uses the <a href='https://greensock.com/docs/Plugins/ScrollToPlugin'>GreenSock ScrollToPlugin</a> internally for animation and a technique from the <a href='https://github.com/jonaskuske/smoothscroll-anchor-polyfill/blob/master/index.js'>smoothscroll-anchor-polyfill</a> to ensure back/forward navigation scrolls smoothly.")
 * )
 */
class SmoothScroll extends ComponentBase {
  /**
   * {@inheritdoc}
   */
  public function getDemo(): array {
    $baseClass = 'smooth-scroll-demo';

    $sectionCount = 3;

    $baseTargetID = 'demo-target';

    $links = [
      '#theme'  => 'item_list',
      '#items'  => [],
    ];

    $lorem = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.';

    // Generate the section links, to be used at the top of the demo and
    // replicated in each demo section for navigation.
    for ($i = 1; $i <= $sectionCount; $i++) {
      $links['#items']['section' . $i] = [
        '#markup' => $this->t(
          '<a href="#@targetID">Section @sectionNumber</a>',
          [
            '@targetID'       => $baseTargetID . $i,
            '@sectionNumber'  => $i,
          ]
        ),
      ];
    }

    $renderArray = [
      '#intro'  => [
        'instructions'  => [
          '#type'         => 'html_tag',
          '#tag'          => 'p',
          '#value'        => $this->t('Click a link to be taken to a corresponding section on the page. Note how both clicking a link and using your brower\'s back and forward buttons will smoothly scroll the page.'),
        ],
        'links'         => $links,
      ],
      '#demo'   => [
        '#type'       => 'html_tag',
        '#tag'        => 'div',
        '#attributes' => [
          'class'       => [$baseClass],
        ],
        '#attached'   => [
          'library'     => [
            'ambientimpact_ux/component.smooth_scroll.demo',
          ],
        ],
      ],
    ];

    // Generate the demo sections.
    for ($i = 1; $i <= $sectionCount; $i++) {
      $renderArray['#demo']['section' . $i] = [
        '#type'       => 'container',
        '#attributes' => [
          'id'          => $baseTargetID . $i,
          'class'       => [$baseClass . '__section'],
        ],
        'heading'     => [
          '#type'       => 'html_tag',
          '#tag'        => 'h2',
          '#value'      => $this->t(
            'Section @sectionNumber',
            ['@sectionNumber'  => $i]
          ),
        ],
        'links'       => $links,
      ];

      // Generate a few paragraphs of lorem ipsum.
      for ($j = 0; $j < 4; $j++) {
        $renderArray['#demo']['section' . $i]['paragraph' . $j] = [
          '#type'     => 'html_tag',
          '#tag'      => 'p',
          '#value'    => $lorem,
        ];
      }
    }

    return $renderArray;
  }
}
