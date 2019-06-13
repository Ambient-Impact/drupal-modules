<?php

namespace Drupal\ambientimpact_core\Template;

use Drupal\Component\Utility\NestedArray;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * ambientimpact_icon Twig extension.
 *
 * @see https://drupal.stackexchange.com/a/271772
 *   This class is based on this Stack Exchange answer.
 *
 * @see https://twig.symfony.com/doc/1.x/advanced.html
 */
class IconTwigExtension extends \Twig_Extension {
  /**
   * The Drupal renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Event subscriber constructor; saves dependencies.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The Symfony service container.
   *
   * @see https://stackoverflow.com/a/24938384
   *   Twig extensions don't seem to correctly pass specific services, so we
   *   must pass the service container itself for dependency injection to work.
   */
  public function __construct(
    ContainerInterface $container
  ) {
    $this->renderer = $container->get('renderer');
  }

  /**
   * Returns an array of functions to declare to Twig.
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
   *
   * @param string $iconName
   *   The name of the icon to use from the given bundle.
   *
   * @param string $bundle
   *   The icon bundle containing $iconName.
   *
   * @param mixed $text
   *   The text content for this icon. Can be a string or an object that can be
   *   printed, like \Drupal\Core\StringTranslation\TranslatableMarkup, the
   *   latter allowing markup to be used inside icons.
   *
   * @param array $options
   *   Additional options to pass to the icon render element.
   *
   * @see ambientimpact-icon.html.twig
   *   Contains information on icon variables/options.
   */
  public function renderIcon(
    string $iconName, string $bundle, $text, array $options = []
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

    // Merge #type, #icon, #bundle, and #text on top of any provided options.
    $renderArray = NestedArray::mergeDeep(
      $renderArray,
      [
        '#type'   => 'ambientimpact_icon',
        '#icon'   => $iconName,
        '#bundle' => $bundle,
        '#text'   => $text,
      ]
    );

    // Render.
    return $this->renderer->render($renderArray);
  }
}
