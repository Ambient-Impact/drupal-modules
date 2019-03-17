<?php

namespace Drupal\ambientimpact_core\Template;

use Drupal\Component\Utility\NestedArray;

/**
 * ambientimpact_icon Twig extension.
 *
 * @see https://drupal.stackexchange.com/a/271772
 *   This class is based on this Stack Exchange answer.
 *
 * @see https://twig.symfony.com/doc/1.x/advanced.html
 *
 * @todo Dependency injection of Drupal container?
 */
class IconTwigExtension extends \Twig_Extension {
  /**
   * {@inheritdoc}
   *
   * This function must return the name of the extension. It must be unique.
   */
  public function getName() {
    return 'ambientimpact_icon';
  }

  /**
   * In this function we can declare the extension callback.
   */
  public function getFunctions() {
    return [
      new \Twig_SimpleFunction(
        'ambientimpact_icon',
        [$this, 'renderIcon'],
        ['is_safe' => ['html']]
      ),
    ];
  }

  /**
   * Render an icon for the Twig extension.
   */
  public function renderIcon(
    string $iconName, string $bundle, string $text, array $options = []
  ) {
    $renderArray = [];

    // If options are passed without the '#' prefix, ensure we add them to the
    // keys to not cause a fatal error with Drupal's renderer.
    foreach ($options as $key => $value) {
      if (mb_strpos($key, '#') !== 0) {
        $renderArray['#' . $key] = $value;
      } else {
        $renderArray[$key] = $value;
      }
    }

    // Merge #typ, #icon, #bundle, and #text on top of any provided options.
    $renderArray = NestedArray::mergeDeep(
      $renderArray,
      [
        '#type'   => 'ambientimpact_icon',
        '#icon'   => $iconName,
        '#bundle' => $bundle,
        '#text'   => $text,
      ]
    );

    // Render without attaching any assets or cache metadata.
    return \Drupal::service('renderer')->renderPlain($renderArray);
  }
}
