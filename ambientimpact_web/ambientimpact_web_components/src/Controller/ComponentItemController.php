<?php

namespace Drupal\ambientimpact_web_components\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\ambientimpact_core\ComponentPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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

    $renderArray = [
      '#theme'          => 'ambientimpact_component_item',
      '#machineName'    => $componentMachineName,
      '#description'    => $pluginDefinition['description'],
      '#configuration'  => [
        '#type'   => 'details',
        '#title'  => $this->t('Configuration'),

        'output'  => [
          '#type'   => 'html_tag',
          '#tag'    => 'pre',
          '#value'  => print_r(
            $plugin->getConfiguration(), true
          ),
        ],
      ],
      '#libraries'      => [
        '#type'   => 'details',
        '#title'  => $this->t('Libraries'),

        'output'  => [
          '#type'   => 'html_tag',
          '#tag'    => 'pre',
          '#value'  => print_r(
            $plugin->getLibraries(), true
          ),
        ],
      ],
    ];

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
