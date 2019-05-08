<?php

namespace Drupal\ambientimpact_core;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Url;

/**
 * Base class for implementing Ambient.Impact Icon Bundle plugins.
 */
class IconBundleBase extends PluginBase
implements IconBundleInterface, ContainerFactoryPluginInterface {
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
   * The Drupal services container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $container;

  /**
   * {@inheritdoc}
   *
   * Override the parent method so that we can inject our dependencies into
   * the constructor.
   *
   * @see https://medium.com/oneshoe/drupal-8-dependency-injection-47cc3ee62858
   */
  public static function create(
    ContainerInterface $container,
    array $configuration, $pluginID, $pluginDefinition
  ) {
    return new static(
      $configuration, $pluginID, $pluginDefinition, $container
    );
  }

  /**
   * Constructs an Ambient.Impact Icon Bundle object.
   *
   * This saves the Drupal module_handler service instance to a property.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   *
   * @param string $pluginID
   *   The plugin_id for the plugin instance.
   *
   * @param array $pluginDefinition
   *   The plugin implementation definition. PluginBase defines this as mixed,
   *   but we should always have an array so the type is set. This can be
   *   changed in the future if need be.
   *
   * @see \Drupal\Component\Plugin\PluginBase
   *   This is the parent class that the __construct() of is called.
   */
  public function __construct(
    array $configuration, string $pluginID, array $pluginDefinition,
    ContainerInterface $container
  ) {
    $this->container = $container;

    parent::__construct($configuration, $pluginID, $pluginDefinition);
  }

  /**
   * {@inheritdoc}
   */
  public function getPath(bool $absolute = true) {
    $path = $this->path . '/' . $this->pluginDefinition['id'] . '.svg';

    if ($absolute === true) {
      $path = $this->container->get('module_handler')
        ->getModule($this->pluginDefinition['provider'])->getPath()
      . '/' . $path;
    }

    return $path;
  }

  /**
   * {@inheritdoc}
   */
  public function getURL(bool $absolute = false) {
    $urlObject    = Url::fromUri('base:' . $this->getPath());
    // Get the CSS/JS query string so that we can force browsers to re-
    // download the bundle when the cache is cleared.
    // @todo Should we use some other metric for this, e.g. last modified
    // time? That way only changed bundles are re-downloaded.
    $queryString  = $this->container->get('state')
              ->get('system.css_js_query_string');

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
  public function isUsed() {
    return $this->used;
  }
}
