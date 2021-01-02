<?php

namespace Drupal\ambientimpact_icon\Plugin\Filter;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a filter to render [icon] tags as Ambient.Impact Icons.
 *
 * @Filter(
 *   id = "ambientimpact_icon",
 *   title = @Translation("Ambient.Impact: render [icon] tags"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE
 * )
 */
class IconFilter extends FilterBase implements ContainerFactoryPluginInterface {
  /**
   * The Drupal renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs this filter object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plug-in instance.
   *
   * @param string $pluginID
   *   The plugin_id for the plug-in instance.
   *
   * @param array $pluginDefinition
   *   The plug-in implementation definition. PluginBase defines this as mixed,
   *   but we should always have an array so the type is set.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The Drupal renderer service.
   */
  public function __construct(
    array $configuration, string $pluginID, array $pluginDefinition,
    RendererInterface $renderer
  ) {
    parent::__construct($configuration, $pluginID, $pluginDefinition);

    // Save dependencies.
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration, $pluginID, $pluginDefinition
  ) {
    return new static(
      $configuration, $pluginID, $pluginDefinition,
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langCode) {
    // Find all valid tags, matching the bundle, name, text, and any additional
    // options as named capture groups. The latter currently only supports the
    // 'standalone' keyword.
    preg_match_all(
      '%\[icon:(?\'bundle\'[^:\]]+):(?\'name\'[^:\]]+):(?\'text\'[^:\]]+)(:(?\'options\'[^:\]]+))?\]%',
      $text, $matches, PREG_SET_ORDER
    );

    $search   = [];
    $replace  = [];

    // Save the original string and the rendered icon to the $search and
    // $replace arrays.
    foreach ($matches as $key => $match) {
      $renderArray = [
        '#type'   => 'ambientimpact_icon',
        '#icon'   => $match['name'],
        '#bundle' => $match['bundle'],
        '#text'   => $match['text'],
      ];

      if (isset($match['options'])) {
        /** @var string[] */
        $options = \explode('&', $match['options']);

        if (\in_array('text=hidden', $options)) {
          $renderArray['#textDisplay'] = 'hidden';

        } else if (\in_array('text=visuallyHidden', $options)) {
          $renderArray['#textDisplay'] = 'visuallyHidden';

        } else if (\in_array('text=visible', $options)) {
          $renderArray['#textDisplay'] = 'visible';
        }

        if (\in_array('standalone', $options)) {
          $renderArray['#standalone'] = true;
        }
      }

      $search[] = $match[0];

      // Render the icon.
      // @todo What if there are multiple instances of the same [icon] tag? Will
      // the render system return cached HTML or will we be potentially
      // rendering something multiple times?
      $replace[] = $this->renderer->render($renderArray);
    }

    // Replace all tags with their rendered output.
    return new FilterProcessResult(str_replace($search, $replace, $text));
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = false) {
    if ($long === true) {
      return $this->t('<code>[icon:bundle:name:text:options]</code> tags are rendered; replace <code>bundle</code>, <code>name</code>, and <code>text</code> with the icon bundle, icon name, and text to associate with this icon, all of which are required. The <code>options</code> parameter is optional, and supports a <code>standalone</code> keyword and a <code>text</code> option (<code>text=hidden</code>, <code>text=visuallyHidden</code>, and , <code>text=visible</code> are supported). Multiple options can be joined together with <code>&</code>, like so: <code>standalone&text=hidden</code>');

    } else {
      return $this->t('<code>[icon]</code> tags are rendered.');
    }
  }
}
