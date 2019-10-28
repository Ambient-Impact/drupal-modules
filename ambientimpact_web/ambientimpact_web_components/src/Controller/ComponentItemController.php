<?php

namespace Drupal\ambientimpact_web_components\Controller;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Yaml\Yaml;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\ambientimpact_core\ComponentPluginManagerInterface;
use Drupal\ambientimpact_core\Service\MarkupProcessorInterface;

/**
 * Controller for the 'ambientimpact_web_components.component_item' route.
 */
class ComponentItemController extends ControllerBase {
  /**
   * The Ambient.Impact Component plug-in manager service.
   *
   * @var \Drupal\ambientimpact_core\ComponentPluginManagerInterface
   */
  protected $componentManager;

  /**
   * The Ambient.Impact markup processor service.
   *
   * @var \Drupal\ambientimpact_core\Service\MarkupProcessorInterface
   */
  protected $markupProcessor;

  /**
   * Controller constructor; saves dependencies.
   *
   * @param \Drupal\ambientimpact_core\ComponentPluginManagerInterface $componentManager
   *   The Ambient.Impact Component plug-in manager service.
   *
   * @param \Drupal\ambientimpact_core\Service\MarkupProcessorInterface $markupProcessor
   */
  public function __construct(
    ComponentPluginManagerInterface $componentManager,
    MarkupProcessorInterface $markupProcessor
  ) {
    $this->componentManager = $componentManager;
    $this->markupProcessor  = $markupProcessor;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.ambientimpact_component'),
      $container->get('ambientimpact.markup_processor')
    );
  }

  /**
   * Builds and returns the Component item render array.
   *
   * In addition to outputting definition data about a Component, this also uses
   * the Symfony Yaml component to output the configuration and libraries as
   * YAML.
   *
   * @param string $componentMachineName
   *   The machine name of the Component to display.
   *
   * @return array
   *   The Component item render array.
   *
   * @todo Should we take more care not to accidentally expose sensitive data?
   * Can we white-list certain definition keys to be shown and ignore the rest?
   *
   * @todo Use GeSHi to display YAML with syntax highlighting.
   */
  public function componentItem(string $componentMachineName) {
    $pluginDefinitions = $this->componentManager->getDefinitions();

    // If the Component plug-in doesn't exist, throw a 404.
    // @see https://www.drupal.org/node/1616360
    if (!isset($pluginDefinitions[$componentMachineName])) {
      throw new NotFoundHttpException();
    }

    $componentInstance =
      $this->componentManager->getComponentInstance($componentMachineName);

    // If the Component plug-in can't be instantiated for any reason, throw a
    // 404.
    // @see https://www.drupal.org/node/1616360
    if ($componentInstance === false) {
      throw new NotFoundHttpException();
    }

    $pluginDefinition = $pluginDefinitions[$componentMachineName];

    // These items need to be dumped via the Symfony VarDumper.
    $dumps = [
      'definition'  => [
        'title' => $this->t('Definition'),
        'dump'  => $pluginDefinition,
      ],
      'configuration' => [
        'title' => $this->t('Configuration'),
        'dump'  => $componentInstance->getConfiguration(),
      ],
      'libraries' => [
        'title' => $this->t('Libraries'),
        'dump'  => $componentInstance->getLibraries(),
      ],
    ];

    // Plug-in titles and descriptions need to be rendered as strings, otherwise
    // Symfony Yaml::dump() will list them as 'null'.
    foreach (['title', 'description'] as $key) {
      if (method_exists($dumps['definition']['dump'][$key], '__toString')) {
        $dumps['definition']['dump'][$key] =
          $dumps['definition']['dump'][$key]->__toString();
      }
    }

    // Remove the 'id' from the configuration as it's redundant and already in
    // the plug-in definition.
    if (isset($dumps['configuration']['dump']['id'])) {
      unset($dumps['configuration']['dump']['id']);
    }

    $renderArray = [
      '#theme'          => 'ambientimpact_component_item',
      '#machineName'    => $componentMachineName,
      // Run the description markup through the markup processor service so that
      // it can be manipulated by various components.
      '#description'    => $this->markupProcessor
        ->process($pluginDefinition['description']),
    ];

    if ($componentInstance->hasDemo() === true) {
      $renderArray['#demoLink'] = [
        '#type'   => 'link',
        '#title'  => $this->t('View demo'),
        '#url'    => Url::fromRoute(
          'ambientimpact_web_components.component_item_demo',
          ['componentMachineName' => $pluginDefinition['id']]
        ),
      ];
    }

    // Dump away.
    foreach ($dumps as $key => $data) {
      $renderArray['#' . $key] = [
        '#type'   => 'details',
        '#title'  => $data['title'],

        'dump'     => [
          '#type'   => 'html_tag',
          '#tag'    => 'pre',
          '#value'  => Yaml::dump($data['dump'], 5, 2),
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
