<?php

namespace Drupal\ambientimpact_web_components\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\Markup;
use Drupal\ambientimpact_core\ComponentPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;
use Symfony\Component\VarDumper\Cloner\VarCloner;

/**
 * Controller for the 'ambientimpact_web_components.component_item' route.
 */
class ComponentItemController extends ControllerBase {
  /**
   * The Ambient.Impact Component plugin manager service.
   *
   * @var \Drupal\ambientimpact_core\ComponentPluginManager
   */
  protected $componentManager;

  /**
   * Controller constructor; saves dependencies.
   *
   * @param \Drupal\ambientimpact_core\ComponentPluginManager $componentManager
   *   The Ambient.Impact Component plugin manager service.
   */
  public function __construct(ComponentPluginManager $componentManager) {
    $this->componentManager = $componentManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get(
      'plugin.manager.ambientimpact_component'
    ));
  }

  /**
   * Builds and returns the Component item render array.
   *
   * In addition to outputting annotation data about a Component, this also uses
   * the Symfony VarDumper to output the configuration and libraries as user-
   * friendly elements. Note that this makes use of \Drupal\Core\Render\Markup
   * to allow output of the inline JavaScript which presents the following
   * issues that will need revisiting:
   * - \Drupal\Core\Render\Markup is marked as @internal, so it could change or
   *   stop working with any Drupal update.
   * - Inline JavaScript presents a problem with
   *   {@link https://developer.mozilla.org/en-US/docs/Web/HTTP/CSP
   *    Content Security Policy (CSP)}.
   *
   * @param string $componentMachineName
   *   The machine name of the Component to display.
   *
   * @return array
   *   The Component item render array.
   */
  public function componentItem(string $componentMachineName) {
    $pluginDefinitions = $this->componentManager->getDefinitions();

    // If the Component plug-in doesn't exist, throw a 404.
    // @see https://www.drupal.org/node/1616360
    if (!isset($pluginDefinitions[$componentMachineName])) {
      throw new NotFoundHttpException();
    }

    $plugin =
      $this->componentManager->getComponentInstance($componentMachineName);

    $pluginDefinition = $pluginDefinitions[$componentMachineName];

    $dumper = new HtmlDumper();
    $cloner = new VarCloner();

    // These items need to be dumped via the Symfony VarDumper.
    $dumps = [
      'definition'  => [
        'title' => $this->t('Definition'),
        'dump'  => $pluginDefinition,
      ],
      'configuration' => [
        'title' => $this->t('Configuration'),
        'dump'  => $plugin->getConfiguration(),
      ],
      'libraries' => [
        'title' => $this->t('Libraries'),
        'dump'  => $plugin->getLibraries(),
      ],
    ];

    $renderArray = [
      '#theme'          => 'ambientimpact_component_item',
      '#machineName'    => $componentMachineName,
      '#description'    => $pluginDefinition['description'],
    ];

    // Dump away.
    foreach ($dumps as $key => $data) {
      $renderArray['#' . $key] = [
        '#type'   => 'details',
        '#title'  => $data['title'],

        'pre'  => [
          '#type'   => 'html_tag',
          '#tag'    => 'pre',

          'dump'    => [
            '#markup'  => Markup::create($dumper->dump($cloner->cloneVar(
              $data['dump']
            ), true)),
          ],
        ],
      ];
    }

    return $renderArray;
  }

  /**
   * Component item route title callback.
   *
   * @param string $componentMachineName
   *   The machine name of the Component to display.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The Component item route title.
   */
  public function componentItemTitle(string $componentMachineName) {
    $pluginDefinitions = $this->componentManager->getDefinitions();

    return $this->t(
      '@componentName component',
      [
        '@componentName'  => $pluginDefinitions[$componentMachineName]['title'],
      ]
    );
  }
}
