<?php

namespace Drupal\ambientimpact_web\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for the 'ambientimpact_web.overview' route.
 */
class WebController extends ControllerBase {
  /**
   * The Drupal menu link tree service.
   *
   * @var \Drupal\Core\Menu\MenuLinkTreeInterface
   */
  protected $menuLinkTreeService;

  /**
   * Controller constructor; saves dependencies.
   *
   * @param \Drupal\Core\Menu\MenuLinkTreeInterface $menuLinkTreeService
   *   The Drupal menu link tree service.
   */
  public function __construct(MenuLinkTreeInterface $menuLinkTreeService) {
    $this->menuLinkTreeService = $menuLinkTreeService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get(
      'menu.link_tree'
    ));
  }

  /**
   * Builds and returns the overview render array.
   *
   * @return array
   *   The overview render array.
   *
   * @see https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Menu!menu.api.php/group/menu
   *   Overview of the menu system and how to get a render array of a menu based
   *   on parameters.
   */
  public function overview() {
    $menuName = 'main';

    // Build the typical default set of menu tree parameters.
    $parameters = $this->menuLinkTreeService
      ->getCurrentRouteMenuTreeParameters($menuName);

    // Set the minumum depth to the second level and lower, so that we only
    // display the sub-sections of this section.
    $parameters->setMinDepth(2);

    // Load the tree based on this set of parameters.
    $tree = $this->menuLinkTreeService->load($menuName, $parameters);

    // Transform the tree using manipulators.
    $manipulators = [
      // Only show links that are accessible for the current user.
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      // Use the default sorting of menu links.
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ];

    $tree = $this->menuLinkTreeService->transform($tree, $manipulators);

    $renderArray = [
      'intro' => [
        '#type'   => 'html_tag',
        '#tag'    => 'p',
        '#value'  => $this->t('Please choose a section from the list:'),
      ],
      'menu'  => $this->menuLinkTreeService->build($tree),
    ];

    return $renderArray;
  }
}
