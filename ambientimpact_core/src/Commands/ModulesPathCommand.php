<?php

namespace Drupal\ambientimpact_core\Commands;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drush\Commands\DrushCommands;
use Drush\Exceptions\CommandFailedException;

/**
 * ambientimpact:module-path Drush command.
 *
 * @see self::modulesPath()
 */
class ModulesPathCommand extends DrushCommands {

  /**
   * The Drupal file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystemService;

  /**
   * The Drupal module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructor; saves dependencies.
   *
   * @param \Drupal\Core\File\FileSystemInterface $fileSystemService
   *   The Drupal file system service.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The Drupal module handler service.
   */
  public function __construct(
    FileSystemInterface     $fileSystemService,
    ModuleHandlerInterface  $moduleHandler
  ) {
    $this->fileSystemService  = $fileSystemService;
    $this->moduleHandler      = $moduleHandler;
  }

  /**
   * Get the path to the modules/ambientimpact directory.
   *
   * This is primarily intended to be used by build tools that need to know the
   * location of the directory, e.g. Sass includes.
   *
   * @command ambientimpact:modules-path
   *
   * @aliases ai:modules-path
   */
  public function modulesPath() {

    /** @var \Drupal\Core\Extension\Extension */
    $module = $this->moduleHandler->getModule('ambientimpact_core');

    /** @var string|false  */
    $path = $this->fileSystemService->realpath($module->getPath() . '/..');

    if ($path === false) {
      throw new CommandFailedException(
        \dt('There was an error building the path.')
      );
    }

    $this->io()->writeln($path);

  }

}
