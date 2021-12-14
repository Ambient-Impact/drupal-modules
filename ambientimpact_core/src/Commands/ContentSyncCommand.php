<?php

namespace Drupal\ambientimpact_core\Commands;

use Consolidation\AnnotatedCommand\CommandData;
use Drupal\ambientimpact_core\Commands\AbstractSyncCommand;
use Drupal\Core\File\FileSystemInterface;
use Drush\Config\DrushConfig;
use Drush\Exceptions\UserAbortException;
use Robo\Collection\CollectionBuilder;
use Robo\Task\Remote\Rsync;
use Symfony\Component\Console\Event\ConsoleCommandEvent;

/**
 * ambientimpact:content-sync Drush command.
 *
 * @see self::contentSync()
 */
class ContentSyncCommand extends AbstractSyncCommand {

  /**
   * {@inheritdoc}
   */
  public function __construct(FileSystemInterface $fileSystemService) {

    parent::__construct($fileSystemService);

    // Remove the 'styles' directory from the generated file directories as we
    // do actually want to sync image style derivatives in this command.
    \array_splice($this->generatedFileDirectories, \array_search(
      'styles', $this->generatedFileDirectories
    ), 1);

  }

  /**
   * Sync content from a local Drupal site to another local Drupal site.
   *
   * Note that this is has been developed and tested only on local sites and
   * assumes a specific project structure where the Composer root is one
   * directory above the Drupal root.
   *
   * @command ambientimpact:content-sync
   *
   * @param string $source
   *   A site alias and optional path. See rsync documentation and
   *   example.site.yml.
   *
   * @param string $target
   *   A site alias and optional path. See rsync documentation and
   *   example.site.yml.
   *
   * @option exclude-generated-files
   *   Whether to exclude generated files, such as compiled Twig templates and
   *   aggregated JavaScript/CSS.
   *
   * @option exclude-paths
   *   Paths to pass to Robo's rsync command to exclude from syncing. These are
   *   relative to the public/private file directories.
   *
   * @option delete
   *   Whether to delete files in $target not present in $source.
   *
   * @option target-maintenance
   *   Whether to put the target site into maintenance mode during the rsync.
   *
   * @option target-post-drush-commands
   *   An array of Drush commands to run on $target after rsync has been
   *   performed.
   *
   * @usage ambientimpact:content-sync @live @staging
   *   Sync content from @live to @staging using default options.
   *
   * @usage ambientimpact:content-sync @live @staging --no-delete --exclude-paths=styles/thumbnail,oembed_thumbnails
   *   Sync content from @live to @staging, without deleting files on @staging
   *   that don't exist on @live, and excluding the 'styles/thumbnail' and
   *   'oembed_thumbnails' paths.
   *
   * @aliases ambientimpact:csync, ai:content-sync, ai:csync
   *
   * @throws \Drush\Exceptions\UserAbortException
   *   If the user does not confirm the sync.
   *
   * @todo Add arguments and options to target-post-drush-commands, or
   *   generalize them so that any shell commands can be specified.
   *
   * @todo Can we use the @link https://www.drush.org/deploycommand/ deploy
   *   command @endlink in 'target-post-drush-commands' to condense it and allow
   *   the use of deploy hooks?
   */
  public function contentSync(string $source, string $target, array $options = [
    'exclude-generated-files' => true,
    'exclude-paths' => [],
    'delete' => true,
    'target-maintenance' => true,
    'target-post-drush-commands' => [
      'config:import',
      'cache:rebuild',
    ],
  ]): void {

    if (
      !$this->getConfig()->simulate() &&
      !$this->io()->confirm(\dt(
        'Sync content from !source to !target?', [
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
    $projectRoot = $this->getProjectRoot();

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

    /** @var \Consolidation\SiteAlias\SiteAliasInterface The source site alias record. */
    $sourceRecord = $this->siteAliasManager()->getAlias($source);

    /** @var \Consolidation\SiteAlias\SiteAliasInterface The target site alias record. */
    $targetRecord = $this->siteAliasManager()->getAlias($target);

    if ($options['target-maintenance']) {
      $this->addMaintenanceModeTask($targetRecord, $collection, $options, true);
    }

    foreach ($relativeFileDirs as $type => $path) {

      $this->addRsyncTask(
        $this->prepareRsyncPath(
          $this->getProjectRoot($source) . \DIRECTORY_SEPARATOR .
          $relativeFileDirs[$type] . \DIRECTORY_SEPARATOR
        ),
        $this->prepareRsyncPath(
          $this->getProjectRoot($target) . \DIRECTORY_SEPARATOR .
          $relativeFileDirs[$type]
        ),
        $collection, $options
      );

      // Add generated file paths if they're to be excluded. Note that since the
      // generated file directory names are relative to the finel directory, we
      // don't need to build a path and can just provide the array as-is.
      //
      // @todo Does this make sense applied to private files? Can we apply this
      //   just to public files if not?
      if ($options['exclude-generated-files']) {
        $collection->exclude($this->generatedFileDirectories);
      }

    }

    // Add the sql:sync Drush command task.
    $collection->addCode(function() use (
      $drushCommand, $targetRecord, $source, $target
    ) {
      $drushCommand->processManager()->drush(
        $targetRecord, 'sql:sync', [$source, $target]
      )
      ->mustRun();
    });

    // Add the Drush commands to be run on the target site after the sync.
    foreach ($options['target-post-drush-commands'] as $command) {
      $collection->addCode(function() use (
        $drushCommand, $command, $targetRecord
      ) {
        $drushCommand->processManager()->drush(
          $targetRecord, $command, []
        )
        ->mustRun();
      });
    }

    if ($options['target-maintenance']) {
      $this->addMaintenanceModeTask(
        $targetRecord, $collection, $options, false
      );
    }

    $collection->run();

  }

  /**
   * Add a Robo rsync task given paths and options.
   *
   * @param string $fromPath
   *   Path to rsync from.
   *
   * @param string $toPath
   *   Path to rsync to.
   *
   * @param \Robo\Collection\CollectionBuilder $collection
   *   Robo collection to add the rsync task to
   *
   * @param array $options
   *   Drush command options; used to determine if verbose output should be
   *   enabled.
   */
  protected function addRsyncTask(
    string $fromPath, string $toPath, CollectionBuilder $collection,
    array $options
  ): void {

    /** @var \Drush\Config\DrushConfig Configuration for this command. */
    $config = $this->getConfig();

    /** @var \Robo\Task\Remote\Rsync The Robo rsync task. */
    $rsyncTask = $collection->taskRsync()
      ->fromPath($fromPath)
      ->toPath($toPath)
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

  }

  /**
   * {@inheritdoc}
   *
   * @hook command-event ambientimpact:content-sync
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
   * @hook validate ambientimpact:content-sync
   *
   * @see \Drupal\ambientimpact_core\Commands\AbstractSyncCommand::validate()
   *   Uses this parent method.
   */
  public function validate(CommandData $commandData): void {
    parent::validate($commandData);
  }

}
