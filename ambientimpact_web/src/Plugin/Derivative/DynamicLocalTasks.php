<?php

namespace Drupal\ambientimpact_web\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Component\Utility\NestedArray;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines dynamic local tasks.
 */
class DynamicLocalTasks extends DeriverBase {
  public function __construct(/*RouteMatchInterface $routeMatch*/) {
    // $this->routeMatch = $routeMatch;
    dpm(func_get_args());
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_route_match'),
      $container
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($basePluginDefinition) {
    dpm($this);

    // $this->derivatives['ambientimpact_web.new_web_snippet'] = NestedArray::mergeDeep(
    //     $basePluginDefinition,
    //     []
    //   );

    return $this->derivatives;
  }
}
