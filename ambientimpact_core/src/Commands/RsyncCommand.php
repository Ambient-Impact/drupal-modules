<?php

namespace Drupal\ambientimpact_core\Commands;

use Consolidation\AnnotatedCommand\CommandData;
use Consolidation\AnnotatedCommand\Events\CustomEventAwareInterface;
use Consolidation\AnnotatedCommand\Events\CustomEventAwareTrait;
use Consolidation\SiteAlias\SiteAliasInterface;
use Drupal\ambientimpact_core\Commands\AbstractSyncCommand;
use Drush\Exceptions\UserAbortException;
use Robo\Collection\CollectionBuilder;
use Symfony\Component\Console\Event\ConsoleCommandEvent;

/**
 * ambientimpact:rsync Drush command.
 *
 * @see self::rsync()
 */
class RsyncCommand extends AbstractSyncCommand implements CustomEventAwareInterface {

  use CustomEventAwareTrait;

  /**
   * Rsync a local Drupal site to another local Drupal site.
   *
   * Note that this is has been developed and tested only on local sites and
   * assumes a specific project structure where the Composer root is one
   * directory above the Drupal root.
   *
   * This is built on Robo's rsync task to sync not just the Drupal codebase,
   * but also the project root's files, including composer.json, composer.lock,
   * the vendor directory, etc.
   *
   * @command ambientimpact:rsync
   *
   * @param $source
   *   A site alias and optional path. See rsync documentation and
   *   example.site.yml.
   *
   * @param $target
   *   A site alias and optional path. See rsync documentation and
   *   example.site.yml.
   *
   * @option exclude-files
   *   Whether to exclude public and private files from the rsync.
   *
   * @option exclude-generated-files
   *   Whether to exclude generated files, such as compiled Twig templates,
   *   aggregated JavaScript/CSS, and image style derivatives.
   *
   * @option exclude-vcs
   *   Whether to exclude version control directories, i.e. .git, .svn, and .hg
   *   directories.
   *
   * @option exclude-paths
   *   File paths to pass to Robo's rsync command to exclude from syncing.
   *
   * @option delete
   *   Whether to delete files in $target not present in $source.
   *
   * @option target-maintenance
   *   Whether to put the target site into maintenance mode during the rsync.
   *
   * @usage ambientimpact:rsync @staging @live
   *   Rsync @staging to @live using default options.
   *
   * @usage ambientimpact:rsync @live @staging --no-delete --no-exclude-files
   *   Rsync @live to @staging without deleting files on @staging and including
   *   public and private files.
   *
   * @aliases ai:rsync
   */
  public function rsync(string $source, string $target, array $options = [
    'exclude-files' => true,
    'exclude-generated-files' => true,
    'exclude-vcs' => true,
    'exclude-paths' => [
      'sites/*/settings.database.php',
      'sites/*/settings.local.php',
    ],
    'delete' => true,
    'target-maintenance' => true,
  ]): void {

    if (
      !$this->getConfig()->simulate() &&
      !$this->io()->confirm(\dt(
        'Rsync files from !source to !target?', [
          '!source' => $this->sourceEvaluatedPath
            ->fullyQualifiedPathPreservingTrailingSlash(),
          '!target' => $this->targetEvaluatedPath->fullyQualifiedPath()
      ]))
    ) {
      throw new UserAbortException();
    }

    /** @var \Robo\Collection\CollectionBuilder Robo collection builder instance. */
    $collection = $this->collectionBuilder();

    /** @var string The full path to the project root directory. */
    $projectRoot  = $this->getProjectRoot();

    /** @var string[] Paths to public and private files relative to the project root. */
    $relativeFileDirs = [];

    // Build relative file directory paths by removing the project root path
    // from their absolute paths.
    foreach (['public', 'private'] as $type) {
      $relativeFileDirs[$type] = \preg_replace(
        '%^' . \preg_quote($projectRoot . \DIRECTORY_SEPARATOR) . '%',
        '',
        $this->fileSystemService->realPath($type . '://')
      );
    }

    /** @var \Drush\Commands\DrushCommands Copy of $this for use in closures. */
    $drushCommand = $this;

    /** @var \Drush\Config\DrushConfig Configuration for this command. */
    $config = $this->getConfig();

    if ($options['target-maintenance']) {
      $this->addMaintenanceModeTask(
        $this->targetRecord, $collection, $options, true
      );
    }

    /** @var \Robo\Task\Remote\Rsync The Robo rsync task. */
    $rsyncTask = $collection->taskRsync()
      ->fromPath($this->prepareRsyncPath(
        $this->getProjectRoot($source) . \DIRECTORY_SEPARATOR
      ))
      ->toPath($this->prepareRsyncPath(
        $this->getProjectRoot($target)
      ))
      ->archive();

    if ($options['verbose'] || $options['debug']) {
      $rsyncTask->verbose()->progress();

    // @see https://github.com/consolidation/robo/issues/629
    } else {
      $rsyncTask->silent(true)->printOutput(false);
    }

    if ($config->simulate()) {
      $rsyncTask->dryRun();
    }

    if ($options['delete']) {
      $rsyncTask->delete();
    }

    // Add public and private file directory paths to the list of paths to
    // exclude if set to do so.
    if ($options['exclude-files']) {
      foreach ($relativeFileDirs as $type => $path) {
        $options['exclude-paths'][] = $path;
      }
    }

    // Add generated file paths if they're to be excluded.
    if ($options['exclude-generated-files']) {
      foreach ($this->generatedFileDirectories as $dirName) {
        $options['exclude-paths'][] = $relativeFileDirs['public'] .
          \DIRECTORY_SEPARATOR . $dirName;
      }
    }

    if (count($options['exclude-paths']) > 0) {
      $rsyncTask->exclude($options['exclude-paths']);
    }

    if ($options['exclude-vcs']) {
      $rsyncTask->excludeVcs();
    }

    // Add post-rsync commands.
    $this->addSyncTasks($collection, $this->getSyncCommands(
      'ambientimpact-rsync-post-commands', [
        // This first cache rebuild is necessary so that Drupal doesn't throw an
        // error if a new module or theme was added by the rsync and then set to
        // be enabled in the subsequent config import.
        //
        // @todo Change this to "cache:clear bin default" so we don't clear
        //   render and other caches.
        'cache-rebuild-pre'   => [$this->targetRecord, 'cache:rebuild'],
        'config-import'       => [$this->targetRecord, 'config:import'],
        'updatedb'            => [
          $this->targetRecord, 'updatedb', [], ['cache-clear' => false]
        ],
        // Cache rebuild a second time after everything is done.
        'cache-rebuild-post'  => [$this->targetRecord, 'cache:rebuild'],
      ]
    ));

    if ($options['target-maintenance']) {
      $this->addMaintenanceModeTask(
        $this->targetRecord, $collection, $options, false
      );
    }

    $collection->run();

  }

  /**
   * {@inheritdoc}
   *
   * @hook command-event ambientimpact:rsync
   *
   * @see \Drupal\ambientimpact_core\Commands\AbstractSyncCommand::preCommandEvent()
   *   Uses this parent method.
   */
  public function preCommandEvent(ConsoleCommandEvent $event): void {
    parent::preCommandEvent($event);
  }

  /**
   * {@inheritdoc}
   *
   * @hook validate ambientimpact:rsync
   *
   * @see \Drupal\ambientimpact_core\Commands\AbstractSyncCommand::validate()
   *   Uses this parent method.
   */
  public function validate(CommandData $commandData): void {
    parent::validate($commandData);
  }

}
