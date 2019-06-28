<?php

namespace Drupal\ambientimpact_web_components\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\ambientimpact_core\ComponentPluginManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;
use Symfony\Component\VarDumper\Cloner\VarCloner;

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
   * Controller constructor; saves dependencies.
   *
   * @param \Drupal\ambientimpact_core\ComponentPluginManagerInterface $componentManager
   *   The Ambient.Impact Component plug-in manager service.
   */
  public function __construct(ComponentPluginManagerInterface $componentManager) {
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
   *
   * @todo Determine what to do about inline JavaScript/CSS from Symfony
   * VarDumper.
   *
   * @todo Should we take more care not to accidentally expose sensitive data
   * via the Symfony VarDumper? Can we white-list certain definition keys to
   * be shown and ignore the rest? Can we use something else that poses less
   * risks, like GeSHi, to display this data?
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
        'dump'  => $componentInstance->getConfiguration(),
      ],
      'libraries' => [
        'title' => $this->t('Libraries'),
        'dump'  => $componentInstance->getLibraries(),
      ],
    ];

    // Remove the 'id' from the configuration as it's redundant and already in
    // the plug-in definition.
    if (isset($dumps['configuration']['dump']['id'])) {
      unset($dumps['configuration']['dump']['id']);
    }

    $renderArray = [
      '#theme'          => 'ambientimpact_component_item',
      '#machineName'    => $componentMachineName,
      '#description'    => $pluginDefinition['description'],
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

        // The Symfony VarDumper creates a <pre> element so we don't need to.
        'dump'    => [
          '#markup'  => Markup::create($dumper->dump($cloner->cloneVar(
            $data['dump']
          ), true)),
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
