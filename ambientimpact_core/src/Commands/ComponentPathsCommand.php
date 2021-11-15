<?php

namespace Drupal\ambientimpact_core\Commands;

use Consolidation\OutputFormatters\StructuredData\PropertyList;
use Drupal\ambientimpact_core\ComponentPluginManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drush\Commands\DrushCommands;
use Drush\Exceptions\CommandFailedException;

/**
 * ambientimpact:component-paths Drush command.
 *
 * @see self::componentPaths()
 */
class ComponentPathsCommand extends DrushCommands {

  /**
   * The Ambient.Impact Component plug-in manager service.
   *
   * @var \Drupal\ambientimpact_core\ComponentPluginManagerInterface
   */
  protected $componentManager;

  /**
   * The Drupal file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystemService;

  /**
   * Constructor; saves dependencies.
   *
   * @param \Drupal\Core\File\FileSystemInterface $fileSystemService
   *   The Drupal file system service.
   *
   * @param \Drupal\ambientimpact_core\ComponentPluginManagerInterface $componentManager
   *   The Ambient.Impact Component plug-in manager service.
   */
  public function __construct(
    FileSystemInterface             $fileSystemService,
    ComponentPluginManagerInterface $componentManager
  ) {
    $this->fileSystemService  = $fileSystemService;
    $this->componentManager   = $componentManager;
  }

  /**
   * Get paths for all available Component providers' components directories.
   *
   * This is primarily intended to be used by build tools, e.g. Sass includes,
   * so that they don't have to hard code paths.
   *
   * @command ambientimpact:component-paths
   *
   * @aliases ai:component-paths
   *
   * @option absolute
   *   Whether the paths should be absolute. If false, paths will be relative to
   *   the Drupal root.
   *
   * @return \Consolidation\OutputFormatters\StructuredData\PropertyList
   */
  public function componentPaths(
    array $options = ['format' => 'json', 'absolute' => true]
  ): PropertyList {

    /** @var array */
    $relativePaths = $this->componentManager->getComponentPaths();

    if ($options['absolute'] === false) {
      return new PropertyList($relativePaths);
    }

    $absolutePaths = [];

    foreach ($relativePaths as $relativePath) {

      /** @var string|false  */
      $absolutePath = $this->fileSystemService->realpath($relativePath);

      if ($absolutePath === false) {
        throw new CommandFailedException(\dt(
          'There was an error building the absolute path for "@relativePath".',
          ['@relativePath' => $relativePath]
        ));
      }

      $absolutePaths[] = $absolutePath;

    }

    return new PropertyList($absolutePaths);

  }

}
