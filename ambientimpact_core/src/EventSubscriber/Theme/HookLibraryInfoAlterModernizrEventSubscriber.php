<?php

namespace Drupal\ambientimpact_core\EventSubscriber\Theme;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
use Drupal\core_event_dispatcher\Event\Theme\LibraryInfoAlterEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * hook_library_info_alter() Modernizr event subscriber class.
 *
 * This replaces Drupal core's Modernizr with our own. We implement all the
 * options core's does, plus others. Note that we only do this if the core
 * Modernizr path is used, so as not to replace another module's override.
 */
class HookLibraryInfoAlterModernizrEventSubscriber
implements EventSubscriberInterface {
  /**
   * The Drupal module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The Drupal logger channel factory service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerChannelFactory;

  /**
   * Event subscriber constructor; saves dependencies.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The Drupal module handler service.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerChannelFactory
   *   The Drupal logger channel factory service.
   */
  public function __construct(
    ModuleHandlerInterface $moduleHandler,
    LoggerChannelFactoryInterface $loggerChannelFactory
  ) {
    $this->moduleHandler = $moduleHandler;
    $this->loggerChannelFactory = $loggerChannelFactory;
  }

   /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      HookEventDispatcherInterface::LIBRARY_INFO_ALTER => 'libraryInfoAlter',
    ];
  }

  /**
   * Replace core's Modernizr with our own.
   *
   * @param \Drupal\core_event_dispatcher\Event\Theme\LibraryInfoAlterEvent $event
   *   The event object.
   */
  public function libraryInfoAlter(LibraryInfoAlterEvent $event) {
    // The extension triggering this event, so that we can determine if this is
    // Drupal core or another extension.
    $extension = $event->getExtension();

    // This is the path to both the core library (relative to the 'core'
    // directory), and our replacement, relative to this module's directory.
    $modernizrPath = 'assets/vendor/modernizr/modernizr.min.js';

    // Ignore anything other than Drupal core.
    if ($extension !== 'core') {
      return;
    }

    $libraries = &$event->getLibraries();

    // Don't do anything if the core Modernizr path doesn't match the default,
    // as that could indicate it's been altered by another extension.
    if (!isset($libraries['modernizr']['js'][$modernizrPath])) {
      return;
    }

    $ourModernizrPath = $this->moduleHandler
      ->getModule('ambientimpact_core')->getPath() . '/' . $modernizrPath;

    // Don't do anything if the modernizr.min.js file isn't found. This can
    // happen if it hasn't been built via Grunt.
    if (!file_exists(\DRUPAL_ROOT . '/'. $ourModernizrPath)) {
      $this->loggerChannelFactory->get('ambientimpact_core')->warning(
        '@modernizrPath cannot be found. You must build Modernizr by running "<code>grunt modernizr</code>".',
        ['@modernizrPath' => $ourModernizrPath]
      );

      return;
    }

    // Read the first line of modernizr.min.js, which contains the version. This
    // is necessary as it's built via Grunt, and we can't guarantee an exact
    // version here.
    // @see https://stackoverflow.com/a/4521969
    //   Description of how this works, including why it's very efficient.
    $firstLine = fgets(fopen(\DRUPAL_ROOT . '/'. $ourModernizrPath, 'r'));

    // Attempt to find the Modernizr version in the first line.
    preg_match('%\/\*!\smodernizr\s(\d+\.\d+\.\d+)\s%', $firstLine, $matches);

    // If we couldn't match the version in the first line, don't proceed.
    if (empty($matches[1])) {
      $this->loggerChannelFactory->get('ambientimpact_core')->warning(
        '@modernizrPath cannot be parsed for a valid version.',
        ['@modernizrPath' => $ourModernizrPath]
      );

      return;
    }

    // Save the settings core's uses. Note the '../' which is required because
    // this is relative to the 'core' directory.
    $libraries['modernizr']['js']['../' . $ourModernizrPath] =
      $libraries['modernizr']['js'][$modernizrPath];

    // Remove the core path.
    unset($libraries['modernizr']['js'][$modernizrPath]);

    // Update the Modernizr version with that parsed from the file.
    $libraries['modernizr']['version'] = 'v' . $matches[1];
  }
}
