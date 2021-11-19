<?php

namespace Drupal\ambientimpact_core\Commands;

use Consolidation\SiteAlias\SiteAliasManagerAwareInterface;
use Consolidation\SiteAlias\SiteAliasManagerAwareTrait;
use Drupal\Core\File\FileSystemInterface;
use Drush\Backend\BackendPathEvaluator;
use Drush\Commands\DrushCommands;
use Drush\Drush;
use Robo\Contract\BuilderAwareInterface;
use Robo\LoadAllTasks;

/**
 * Abstract Ambient.Impact file system Drush command class.
 */
abstract class AbstractFileSystemCommand extends DrushCommands implements BuilderAwareInterface, SiteAliasManagerAwareInterface {

  use LoadAllTasks;

  use SiteAliasManagerAwareTrait;

  /**
   * The Drupal file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystemService;

  /**
   * Drush back-end evaluator.
   *
   * @var \Drush\Backend\BackendPathEvaluator
   */
  protected $pathEvaluator;

  /**
   * Array of public file directory names that contain generated files.
   *
   * @var array
   */
  protected $generatedFileDirectories = ['css', 'js', 'php', 'styles'];

  /**
   * Constructor; saves dependencies.
   *
   * @param \Drupal\Core\File\FileSystemInterface $fileSystemService
   *   The Drupal file system service.
   */
  public function __construct(FileSystemInterface $fileSystemService) {

    $this->pathEvaluator = new BackendPathEvaluator();

    $this->fileSystemService = $fileSystemService;

  }

  /**
   * Get the actual project root, of which Drupal is in.
   *
   * By default this is one directory higher than the Drupal root, but this
   * method can be overridden to provide a different path.
   *
   * @param string $name
   *   The site alias name to get from the site alias manager. If none is
   *   provided, defaults to '@self'.
   *
   * @return string
   *   The filesystem path to the project root.
   *
   * @todo Can this infer the project root from a Drupal site's path to
   * vendor/bin/drush?
   */
  protected function getProjectRoot(string $name = '@self'): string {
    return \realpath($this->siteAliasManager()->get($name)->root() . '/..');
  }

}
