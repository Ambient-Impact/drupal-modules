<?php

namespace Drupal\ambientimpact_ux\Plugin\AmbientImpact\Component;

use Drupal\ambientimpact_core\ComponentBase;

/**
 * Content visibility component.
 *
 * @Component(
 *   id = "content_visibility",
 *   title = @Translation("Content visibility"),
 *   description = @Translation("Watches content marked up with certain classes using <a href='https://developer.mozilla.org/en-US/docs/Web/API/Intersection_Observer_API'>Intersection Observer</a>s, and applies classes and triggers events when they enter or leave the view area.")
 * )
 */
class ContentVisibility extends ComponentBase {

  /**
   * Base HTML class to derive BEM classes from.
   */
  protected const BASE_CLASS = 'content-visibility-observe';

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'baseClass'         => self::BASE_CLASS,
      'observeOnceClass'  => self::BASE_CLASS . '--once',
      'observingClass'    => self::BASE_CLASS . '--observing',
      'visibleClass'      => self::BASE_CLASS . '--visible',
      // The default intersection Observer 'threshold' option used when none is
      // specified via the data attribute.
      //
      // @see https://developer.mozilla.org/en-US/docs/Web/API/IntersectionObserver/IntersectionObserver#parameters
      'defaultThreshold'  => 0.6,
      'thresholdDataName' => 'content-visibility-threshold',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getJSSettings(): array {

    /** @var array */
    $config = $this->configuration;

    return [
      'baseClass'         => $config['baseClass'],
      'observeOnceClass'  => $config['observeOnceClass'],
      'observingClass'    => $config['observingClass'],
      'visibleClass'      => $config['visibleClass'],
      'defaultThreshold'  => $config['defaultThreshold'],
      'thresholdDataName' => $config['thresholdDataName'],
    ];

  }

}
