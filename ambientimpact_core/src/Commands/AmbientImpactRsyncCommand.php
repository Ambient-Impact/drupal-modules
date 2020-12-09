<?php

namespace Drupal\ambientimpact_core\Commands;

use Consolidation\AnnotatedCommand\CommandData;
use Consolidation\SiteAlias\HostPath;
use Drupal\ambientimpact_core\Commands\AbstractAmbientImpactFileSystemCommand;
use Drush\Config\ConfigLocator;
use Drush\Exceptions\UserAbortException;
use Symfony\Component\Console\Event\ConsoleCommandEvent;

/**
 * ambientimpact:rsync Drush command.
 *
 * @see self::rsync()
 */
class AmbientImpactRsyncCommand extends AbstractAmbientImpactFileSystemCommand {

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
   * @option exclude-paths
   *   File paths to pass to Robo's rsync command to exclude from syncing.
   *
   * @option delete
   *   Whether to delete files in $target not present in $source.
   *
   * @option target-post-drush-commands
   *   An array of Drush commands to run on $target after rsync has been
   *   performed.
   *
   * @usage ambientimpact:rsync @staging @live
   *   Rsync @staging to @live using default options.
   *
   * @usage ambientimpact:rsync @live @staging --no-delete --no-exclude-files
   *   Rsync @live to @staging without deleting files on @staging and including
   *   public and private files.
   *
   * @aliases ai:rsync
   *
   * @todo Add arguments and options to target-post-drush-commands, or
   * generalize them so that any shell commands can be specified.
   *
   * @todo Can we use the @link https://www.drush.org/deploycommand/ deploy
   *   command @endlink in 'target-post-drush-commands' to condense it and allow
   *   the use of deploy hooks?
   */
  public function rsync($source, $target, $options = [
    'exclude-files' => true,
    'exclude-generated-files' => true,
    'exclude-paths' => [
      'sites/*/settings.database.php',
      'sites/*/settings.local.php',
    ],
    'delete' => true,
    'target-post-drush-commands' => [
      // This is necessary so that Drupal doesn't throw an error if a new module
      // or theme was added by the rsync and then set to be enabled in the
      // subsequent config import.
      'cr',
      'config-import',
      'updb',
      // Cache rebuild a second time after everything is done.
      'cr',
    ],
  ]) {
    if (
      !$this->getConfig()->simulate() &&
      !$this->io()->confirm(dt(
        'Rsync files from !source to !target?', [
          '!source' => $this->sourceEvaluatedPath
            ->fullyQualifiedPathPreservingTrailingSlash(),
          '!target' => $this->targetEvaluatedPath->fullyQualifiedPath()
      ]))
    ) {
      throw new UserAbortException();
    }

    // Robo collection builder instance.
    $collection = $this->collectionBuilder();

    $projectRoot  = $this->getProjectRoot();

    // Paths to public and private files relative to the project root.
    $relativeFileDirs = [];

    // Build relative file directory paths by removing the project root path
    // from their absolute paths.
    foreach (['public', 'private'] as $type) {
      $relativeFileDirs[$type] = preg_replace(
        '%^' . preg_quote($projectRoot . DIRECTORY_SEPARATOR) . '%',
        '',
        $this->fileSystemService->realPath($type . '://')
      );
    }

    // Save $this because we need to pass it to the Drush command closure where
    // $this will have a different value.
    $drushCommand = $this;

    $config = $this->getConfig();

    // Get source and target site alias records.
    $sourceRecord = $this->siteAliasManager()->getAlias($source);
    $targetRecord = $this->siteAliasManager()->getAlias($target);

    // Add Robo rsync task.
    $rsyncTask = $collection->taskRsync()
      ->fromPath($this->getProjectRoot($source) . DIRECTORY_SEPARATOR)
      ->toPath($this->getProjectRoot($target))
      ->archive();

    if ($options['verbose'] || $options['debug']) {
      $rsyncTask->verbose()->progress();
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
          DIRECTORY_SEPARATOR . $dirName;
      }
    }

    if (count($options['exclude-paths']) > 0) {
      $rsyncTask->exclude($options['exclude-paths']);
    }

    // If not simulating, add the Drush commands to be run on the target site
    // after the rsync.
    if (!$config->simulate()) {
      foreach ($options['target-post-drush-commands'] as $command) {
        $collection->addCode(function() use (
          $drushCommand, $command, $targetRecord, $sourceRecord, $options
        ) {
          $drushCommand->processManager()->drush(
            $targetRecord, $command, []
          )
          ->mustRun();
        });
      }

    // If simulating, just list the Drush commands we would have run if doing
    // this for real.
    } else {
      $collection->addCode(function() use ($drushCommand, $target, $options) {
        $drushCommand->logger()->notice(dt(
          'The following Drush commands would be run on !target after rsync has completed in non-simulated mode: @commands',
          [
            '!target'   => $target,
            '@commands' => "\n" . implode(
              "\n", $options['target-post-drush-commands']
            ) . "\n",
          ]
        ));
      });
    }

    $collection->run();
  }

  /**
   * Evaluate the path aliases in the source and destination parameters. We do
   * this in the pre-command-event so that we can set up the configuration
   * object to include options from the source and target aliases, if any, so
   * that these values may participate in configuration injection.
   *
   * Used for setting up the ambientimpact:rsync command.
   *
   * @see \Drush\Commands\core\RsyncCommands::preCommandEvent()
   *   Adapted from the core:rsync Drush command.
   *
   * @hook command-event ambientimpact:rsync
   *
   * @param ConsoleCommandEvent $event
   */
  public function preCommandEvent(ConsoleCommandEvent $event) {
    $input = $event->getInput();

    $this->sourceEvaluatedPath = $this->injectAliasPathParameterOptions(
      $input, 'source'
    );
    $this->targetEvaluatedPath = $this->injectAliasPathParameterOptions(
      $input, 'target'
    );
  }

  /**
   * Injects...alias path parameter options, I guess?
   *
   * Used for setting up the ambientimpact:rsync command.
   *
   * @param $input
   *
   * @param $parameterName
   *
   * @return \Consolidation\SiteAlias\HostPath
   *
   * @see \Drush\Commands\core\RsyncCommands::injectAliasPathParameterOptions()
   *   Copied from the core:rsync Drush command.
   */
  protected function injectAliasPathParameterOptions($input, $parameterName) {
    // The Drush configuration object is a ConfigOverlay; fetch the alias
    // context, that already has the options et. al. from the site-selection
    // alias ('drush @site rsync ...'), @self.
    $aliasConfigContext = $this->getConfig()->getContext(
      ConfigLocator::ALIAS_CONTEXT
    );

    $aliasName = $input->getArgument($parameterName);
    $evaluatedPath = HostPath::create($this->siteAliasManager(), $aliasName);

    $this->pathEvaluator->evaluate($evaluatedPath);

    $aliasRecord = $evaluatedPath->getSiteAlias();

    return $evaluatedPath;
  }

  /**
   * Validate that passed aliases are both local for ambientimpact:rsync.
   *
   * @hook validate ambientimpact:rsync
   *
   * @param \Consolidation\AnnotatedCommand\CommandData $commandData
   *
   * @throws \Exception
   */
  public function validate(CommandData $commandData) {
    if (
      $this->sourceEvaluatedPath->isRemote() ||
      $this->targetEvaluatedPath->isRemote()
    ) {
      throw new \Exception(dt(
        'This command can currently only rsync between two local Drupal sites.'
      ));
    }
  }

}
