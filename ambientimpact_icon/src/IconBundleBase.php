<?php

namespace Drupal\ambientimpact_icon;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Url;

/**
 * Base class for implementing Ambient.Impact Icon Bundle plug-ins.
 */
class IconBundleBase extends PluginBase
implements IconBundleInterface, ContainerFactoryPluginInterface {
  /**
   * The Drupal module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The Drupal state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The path to the icon bundle relative to the root directory of the module.
   *
   * @var string
   */
  protected $path = 'icons';

  /**
   * Whether the bundle is in use in the current request.
   *
   * @var boolean
   *
   * @see $this->markUsed
   */
  protected $used = false;

  /**
   * Constructs an Ambient.Impact Icon Bundle object.
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
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The Drupal module handler service.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The Drupal state service.
   */
  public function __construct(
    array $configuration, string $pluginID, array $pluginDefinition,
    ModuleHandlerInterface $moduleHandler,
    StateInterface $state
  ) {
    parent::__construct($configuration, $pluginID, $pluginDefinition);

    // Save dependencies.
    $this->moduleHandler  = $moduleHandler;
    $this->state          = $state;
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
      $container->get('module_handler'),
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getPath(bool $absolute = true): string {
    $path = $this->path . '/' . $this->pluginDefinition['id'] . '.svg';

    if ($absolute === true) {
      $path = $this->moduleHandler->getModule(
        $this->pluginDefinition['provider']
      )->getPath() . '/' . $path;
    }

    return $path;
  }

  /**
   * {@inheritdoc}
   */
  public function getURL(bool $absolute = false): string {
    $urlObject    = Url::fromUri('base:' . $this->getPath());
    // Get the CSS/JS query string so that we can force browsers to re-
    // download the bundle when the cache is cleared.
    // @todo Should we use some other metric for this, e.g. last modified
    // time? That way only changed bundles are re-downloaded.
    $queryString  = $this->state->get('system.css_js_query_string');

    if ($queryString !== null) {
      $urlObject->setOption('query', [$queryString => null]);
    }

    $urlObject->setOption('absolute', $absolute);

    return $urlObject->toString();
  }

  /**
   * {@inheritdoc}
   */
  public function markUsed() {
    $this->used = true;
  }

  /**
   * {@inheritdoc}
   */
  public function isUsed(): bool {
    return $this->used;
  }
}
