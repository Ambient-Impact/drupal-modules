<?php

namespace Drupal\ambientimpact_core\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an Ambient.Impact Component annotation object.
 *
 * @see \Drupal\ambientimpact_core\ComponentPluginManagerInterface
 *
 * @see plugin_api
 *
 * @Annotation
 */
class Component extends Plugin {
  /**
   * The human readable title of the component.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $title;

  /**
   * A brief human readable description of the component.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;
}
