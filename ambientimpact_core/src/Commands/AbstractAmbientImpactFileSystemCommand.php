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
abstract class AbstractAmbientImpactFileSystemCommand extends DrushCommands implements BuilderAwareInterface, SiteAliasManagerAwareInterface {

  use LoadAllTasks;

  use SiteAliasManagerAwareTrait;

  /**
   * The Drupal file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystemService;

  /**
   * The current site's own (@self) alias record.
   *
   * @var \Consolidation\SiteAlias\SiteAliasInterface
   */
  protected $selfRecord;

  /**
   * HostPath object representing the path to the source Drupal site root.
   *
   * @var \Consolidation\SiteAlias\HostPath
   */
  protected $sourceEvaluatedPath;

  /**
   * HostPath object representing the path to the target Drupal site root.
   *
   * @var \Consolidation\SiteAlias\HostPath
   */
  protected $targetEvaluatedPath;

  /**
   * Drush back-end evalutator.
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
   * Constructor; saves $this->selfRecord and sets 'path.drush-script'.
   *
   * @param \Drupal\Core\File\FileSystemInterface $fileSystemService
   *   The Drupal file system service.
   */
  public function __construct(FileSystemInterface $fileSystemService) {
    // Set the site alias manager.Note that we have to use Drush::aliasManager()
    // to get the siteAliasManager because of this bug which still seems to be
    // occurring as of Drush 9.7.1:
    //
    // @see https://github.com/drush-ops/drush/issues/3394
    $this->setSiteAliasManager(Drush::aliasManager());

    // Save the @self alias record.
    $this->selfRecord = $this->siteAliasManager()->getSelf();

    // If no path to Drush is set, set it to the local one in the
    // '../vendor/bin' directory because
    // \Drush\SiteAlias\ProcessManager::drushScript() only checks the 'vendor'
    // directory inside the Drupal root, but not where it's actually installed
    // according to the root composer.json.
    if (empty($this->selfRecord->get('paths.drush-script'))) {
      $this->selfRecord->set(
        'paths.drush-script',
        $this->selfRecord->root() . '/../vendor/bin/drush'
      );
    }

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
