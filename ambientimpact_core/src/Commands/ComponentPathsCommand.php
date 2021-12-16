<?php

namespace Drupal\ambientimpact_core\Commands;

use Consolidation\OutputFormatters\StructuredData\PropertyList;
use Drupal\ambientimpact_core\ComponentPluginManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drush\Commands\DrushCommands;
use Drush\Exceptions\CommandFailedException;
use Drush\Utils\StringUtils;

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
   * @option providers
   *   A comma-separated list of one or more providers to limit paths to. Each
   *   entry can either be an exact provider name or a wildcard pattern, such as
   *   <info>example_*</info>, where all providers starting with
   *   <info>example_</info> will match.
   *
   * @option absolute
   *   Whether the paths should be absolute. If false, paths will be relative to
   *   the Drupal root.
   *
   * @return \Consolidation\OutputFormatters\StructuredData\PropertyList
   *
   * @usage ambientimpact:component-paths
   *   Get all component paths across all enabled providers.
   *
   * @usage ambientimpact:component-paths --providers=example_module
   *   Get the component path for the <info>example_module</info> provider.
   *
   * @usage ambientimpact:component-paths --providers=example_module,another_example_*
   *   Get the component paths for the <info>example_module</info> provider and
   *   any providers that begin with <info>another_example_</info>.
   */
  public function componentPaths(array $options = [
    'absolute'  => true,
    'format'    => 'json',
    'providers' => [],
  ]): PropertyList {

    /** @var array */
    $relativePaths = $this->componentManager->getComponentPaths(
      StringUtils::csvToArray($options['providers'])
    );

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
