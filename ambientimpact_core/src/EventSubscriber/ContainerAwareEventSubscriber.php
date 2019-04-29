<?php

namespace Drupal\ambientimpact_core\EventSubscriber;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Drupal services container-aware event subscriber class.
 *
 * Extend this class to use as an event subscriber with $this->container set
 * for you with the Drupal services container.
 *
 * Don't forget to add your event subscriber service in <module>.services.yml
 * and request the services container as an argument:
 *
 * <module>.example_event_subscriber:
 *   class: \Drupal\<module>\EventSubscriber\ExampleEventSubscriber
 *   arguments: ['@service_container']
 *   tags:
 *     - { name: 'event_subscriber' }
 *
 * @see https://www.drupal.org/docs/8/api/services-and-dependency-injection/services-and-dependency-injection-in-drupal-8
 *   Official documentation.
 *
 * @see https://medium.com/oneshoe/drupal-8-dependency-injection-47cc3ee62858
 *   General Drupal dependency injection information.
 *
 * @see https://github.com/daggerhart/drupal8_examples/blob/master/modules/custom_events/src/EventSubscriber/UserLoginSubscriberWithDI.php
 *   Example of a custom event subscriber with dependency injection.
 */
class ContainerAwareEventSubscriber
implements EventSubscriberInterface, ContainerInjectionInterface {
  /**
   * The Drupal services container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $container;

  /**
   * {@inheritdoc}
   *
   * This injects the Drupal service container into the constructor.
   */
  public static function create(ContainerInterface $container) {
    return new static($container);
  }

  /**
   * Event subscriber constructor; sets $this->container.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The Drupal services container.
   */
  public function __construct(ContainerInterface $container) {
    $this->container = $container;
  }

  /**
   * {@inheritdoc}
   *
   * Override this with your own events.
   */
  public static function getSubscribedEvents() {
    return [];
  }
}
