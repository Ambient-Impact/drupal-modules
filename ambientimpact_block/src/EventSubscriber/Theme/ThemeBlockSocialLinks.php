<?php

namespace Drupal\ambientimpact_block\EventSubscriber\Theme;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
use Drupal\core_event_dispatcher\Event\Theme\ThemeEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * hook_theme() event to define the 'ambientimpact_block_social_links' element.
 */
class ThemeBlockSocialLinks implements EventSubscriberInterface {
  /**
   * The Drupal configuration object factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The Drupal module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Event subscriber constructor; saves dependencies.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The Drupal configuration object factory service.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The Drupal module handler service.
   */
  public function __construct(
    ConfigFactoryInterface $configFactory,
    ModuleHandlerInterface $moduleHandler
  ) {
    $this->configFactory = $configFactory;
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      HookEventDispatcherInterface::THEME => 'theme',
    ];
  }

  /**
   * Defines the 'ambientimpact_block_social_links' theme element.
   *
   * @param \Drupal\core_event_dispatcher\Event\Theme\ThemeEvent $event
   *   The event object.
   */
  public function theme(ThemeEvent $event) {
    // Get the default values for the social links block so that we can use them
    // as variable names and default values without hard-coding them here.
    $defaultSocialLinksConfig =
      $this->configFactory->get('ambientimpact_block.social_links')->get();

    // Remove these as they're not needed.
    unset($defaultSocialLinksConfig['langcode']);
    unset($defaultSocialLinksConfig['_core']);

    // This must be defined or it won't make it into the template.
    $defaultSocialLinksConfig['base_class'] = '';

    $event->addNewTheme('ambientimpact_block_social_links', [
      'variables' => $defaultSocialLinksConfig,
      'template'  => 'ambientimpact-block-social-links',
      // Path is required.
      // @see https://www.drupal.org/project/hook_event_dispatcher/issues/3038311
      'path'      => $this->moduleHandler->getModule('ambientimpact_block')
        ->getPath() . '/templates',
    ]);
  }
}
