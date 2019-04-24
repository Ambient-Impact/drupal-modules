<?php

namespace Drupal\ambientimpact_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\ambientimpact_core\ComponentPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for Ambient.Impact Component plugins.
 */
class ComponentController extends ControllerBase {
  /**
   * The Component plugin manager instance.
   *
   * @var \Drupal\ambientimpact_core\ComponentPluginManager
   */
  protected $componentManager;

  /**
   * Controller constructor.
   *
   * @param \Drupal\ambientimpact_core\ComponentPluginManager $componentManager
   *   The Component plugin manager service. We're injecting this service so
   *   that we can use it to access the Component plugins.
   */
  public function __construct(ComponentPluginManager $componentManager) {
    $this->componentManager = $componentManager;
  }

  /**
   * {@inheritdoc}
   *
   * Override the parent method so that we can inject our Component plugin
   * manager service into the controller.
   */
  public static function create(ContainerInterface $container) {
    // Inject the plugin.manager.ambientimpact_component service that
    // represents our plugin manager as defined in
    // ambientimpact_core.services.yml
    return new static($container->get(
      'plugin.manager.ambientimpact_component'
    ));
  }

  /**
   * Get the Component plugin manager instance.
   *
   * @return \Drupal\ambientimpact_core\ComponentPluginManager
   *
   * @see \Drupal\ambientimpact_core\ComponentPluginManager
   *   Returns an instance of this that was created in static::create()
   */
  public function getComponentManager() {
    return $this->componentManager;
  }

  /**
   * Displays information about all discovered Ambient.Impact Components.
   *
   * @return array
   *   Render API array containing a list of discovered components and their
   *   configuration arrays.
   *
   * @todo The description list is currently hard-coded as 'html_tag'
   * elements as 'description_list' does not support child element arrays on
   * 'dt' and 'dd' items; if it later supports them, change to
   * 'description_list'?
   */
  public function componentList() {
    $renderArray      = [];
    $componentManager = $this->getComponentManager();

    $renderArray['intro'] = [
      '#type'   => 'html_tag',
      '#tag'    => 'p',
      '#value'  => $this->t(
        'This lists all discovered Ambient.Impact Component plugins and some information about each plugin.'
      ),
    ];

    $renderArray['component_plugins'] = [
      '#type'   => 'html_tag',
      '#tag'    => 'dl',
    ];

    // Get the list of all the plugins defined in the system from the plugin
    // manager. Note that at this point, what we have are definitions of
    // plugins, not plugin instances.
    $pluginDefinitions = $componentManager->getDefinitions();

    foreach ($pluginDefinitions as $pluginID => $pluginDefinition) {
      // Create an instance of this Component plugin.
      $plugin = $componentManager->getComponentInstance($pluginID);

      $renderArray['component_plugins'][
        $pluginDefinition['id'] . '_term'
      ] = [
        '#type'   => 'html_tag',
        '#tag'    => 'dt',
        '#value'  => $this->t('@title (@id)', [
          '@id'     => $pluginDefinition['id'],
          '@title'  => $pluginDefinition['title'],
        ]),
      ];

      $renderArray['component_plugins'][
        $pluginDefinition['id'] . '_description'
      ] = [
        '#type'   => 'html_tag',
        '#tag'    => 'dd',
        '#value'  => '',

        'description' => [
          '#type'   => 'html_tag',
          '#tag'    => 'p',
          '#value'  => $pluginDefinition['description'],
        ],

        'configuration' => [
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

        'libraries' => [
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
    }

    return $renderArray;
  }
}
