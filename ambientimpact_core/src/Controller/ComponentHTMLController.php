<?php

namespace Drupal\ambientimpact_core\Controller;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\ambientimpact_core\ComponentPluginManager;

/**
 * Controller for the 'ambientimpact_core.component_html_endpoint' route.
 */
class ComponentHTMLController extends ControllerBase {
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
   * Component HTML route callback.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response object containing the Component HTML wrapped in an object
   *   of key/value pairs, the keys being the front-end lowerCamelCase Component
   *   IDs and the values being a string containing the Component's HTML.
   *
   * @see \Drupal\ambientimpact_core\ComponentPluginManager::getComponentHTML()
   *   Returns the HTML that we respond with.
   */
  public function endpoint() {
    return new JsonResponse($this->componentManager->getComponentHTML());
  }
}
