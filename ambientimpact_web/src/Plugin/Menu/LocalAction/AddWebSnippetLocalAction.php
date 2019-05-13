<?php

namespace Drupal\ambientimpact_web\Plugin\Menu\LocalAction;

use Drupal\Core\Menu\LocalActionDefault;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Routing\RouteProviderInterface;
// use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

class AddWebSnippetLocalAction extends LocalActionDefault {
  /**
   * The route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a AddWebSnippetLocalAction object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   *
   * @param string $pluginID
   *   The plugin_id for the plugin instance.
   *
   * @param mixed $pluginDefinition
   *   The plugin implementation definition.
   *
   * @param \Drupal\Core\Routing\RouteProviderInterface $routeProvider
   *   The route provider to load routes by name.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The route match service.
   */
  public function __construct(
    array $configuration,
    $pluginID,
    $pluginDefinition,
    RouteProviderInterface $routeProvider,
    RouteMatchInterface $routeMatch
  ) {
    parent::__construct(
      $configuration,
      $pluginID,
      $pluginDefinition,
      $routeProvider
    );

    $this->routeMatch = $routeMatch;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $pluginID,
    $pluginDefinition
  ) {
    return new static(
      $configuration,
      $pluginID,
      $pluginDefinition,
      $container->get('router.route_provider'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  // public function getTitle(Request $request = null) {
  //   if ($this->routeMatch->getRouteName() === 'entity.node.canonical') {
  //     $node = $this->routeMatch->getParameter('node');

  //     if ($node !== null) {
  //       // dpm($node->getType());

  //       if ($node->getType() !== 'web_snippet') {
  //         return '';
  //       }
  //     }
  //   }

  //   return (string) $this->pluginDefinition['title'];
  //   // return new TranslatableMarkup('New snippet');

  //   // dpm($this->getRouteName());
  //   // dpm($this->routeMatch->getRouteName());
  //   // dpm($this->routeMatch->getParameters());
  //   // dpm($this->routeMatch->getParameter('node'));

  //   // return $this->pluginDefinition['route_name'];
  // }

  /**
   * Checks access for a specific request.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(AccountInterface $account) {
    if ($this->routeMatch->getRouteName() === 'entity.node.canonical') {
      $node = $this->routeMatch->getParameter('node');

      if (
        $node !== null &&
        $node->getType() !== 'web_snippet'
      ) {
        dpm('Not a snippet.');
        return AccessResult::forbidden();
        // return AccessResult::allowedIf($node->getType() === 'web_snippet');
      }
    }

    return AccessResult::allowed();
  }
}
