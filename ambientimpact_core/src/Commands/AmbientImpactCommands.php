<?php

namespace Drupal\ambientimpact_core\Commands;

use Consolidation\AnnotatedCommand\CommandData;
use Consolidation\SiteAlias\HostPath;
use Consolidation\SiteAlias\SiteAliasManagerAwareInterface;
use Consolidation\SiteAlias\SiteAliasManagerAwareTrait;
use Drupal\Core\File\FileSystemInterface;
use Drush\Backend\BackendPathEvaluator;
use Drush\Commands\DrushCommands;
use Drush\Config\ConfigLocator;
use Drush\Drush;
use Drush\Exceptions\UserAbortException;
use Robo\Contract\BuilderAwareInterface;
use Robo\LoadAllTasks;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Finder\Finder;

/**
 * Ambient.Impact Drush command file.
 */
class AmbientImpactCommands extends DrushCommands
implements BuilderAwareInterface, SiteAliasManagerAwareInterface {
  use LoadAllTasks;
  use SiteAliasManagerAwareTrait;

  /**
   * The date() format used to generate archive names.
   *
   * If this is changed, you must also ensure self::ARCHIVE_DATE_REGEX is able
   * to pick up the new format to find existing back-ups.
   *
   * @see https://www.php.net/manual/en/function.date.php
   *   Format explanation.
   */
  const ARCHIVE_DATE_FORMAT = 'Y-m-d-U';

  /**
   * Symfony Finder regular expression to find existing back-up archives.
   *
   * Note that if you change self::ARCHIVE_DATE_FORMAT, you must update this as
   * well so that the new format can be found.
   *
   * @see https://regex101.com/
   *   Great tool and reference for interactively building regular expressions.
   */
  const ARCHIVE_DATE_REGEX = '%^(\d{4})-(\d{2})-(\d{2})-(\d+)\.tar\.gz$%';

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
  public $sourceEvaluatedPath;

  /**
   * HostPath object representing the path to the target Drupal site root.
   *
   * @var \Consolidation\SiteAlias\HostPath
   */
  public $targetEvaluatedPath;

  /**
   * Drush back-end evalutator.
   *
   * @var \Drush\Backend\BackendPathEvaluator
   */
  protected $pathEvaluator;

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

  /**
   * Backs up a Drupal site to an archive while pruning older backups.
   *
   * Note that this is currently only works on a local site and assumes a
   * specific directory structure.
   *
   * @command ambientimpact:backup
   *
   * @option path
   *   The path to the back-ups directory.
   *
   * @option group
   *   The group that this back-up is part of. This is used to create a
   *   sub-directory under the 'path' option and to know which back-ups to
   *   prune.
   *
   * @option limit
   *   The number of most recent back-ups to keep in this group. If there are
   *   more back-ups than this, the excess back-ups will be deleted. The default
   *   value of '0' will not prune any back-ups.
   *
   * @option exclude
   *   An array of file and directory patterns to exclude, in the format
   *   recognized by tar.
   *
   * @usage ambientimpact:backup --path='~/drush-backups/scheduled' --group=daily --limit=7
   *   Back up the site to '~/drush-backups/scheduled', using the 'daily' group,
   *   and limit to the 7 most recent back-ups.
   *
   * @aliases ai:backup
   *
   * @todo When Drupal upgrades to Symfony 4.3+, add usage of
   * $finder->ignoreVCSIgnored(true); to simplify ignored files and directories,
   * which tells Symfony to use the existing .gitignore file.
   *
   * @see https://www.gnu.org/software/tar/manual/html_section/tar_49.html
   *   Exclude patterns for tar.
   *
   * @see https://symfony.com/doc/current/components/finder.html
   *   Uses the Symfony Finder component find old archives to prune.
   *
   * @see https://github.com/drush-ops/drush/issues/1779
   *   Drush 9 removed the archive-* commands which is detailed in this GitHub
   *   issue.
   *
   * @see https://gist.github.com/frederickjh/eadfe6ae311a4f6b4015c77fac4a26a9
   *   This command is inspired by this RoboFile by Frederick Henderson.
   *
   * @see https://jeh3.net/keeping-your-drupal-backups-under-control
   *   Loosely based on the logic in this bash script by John E. Herreño.
   */
  public function backup($options = [
    'path'    => self::REQ,
    'group'   => self::REQ,
    'limit'   => '0',
    'exclude' => [
      './vendor',
      'sftp-config.json',
      './drupal/libraries',
      '*/node_modules',
      './drupal/sites/*/files/css',
      './drupal/sites/*/files/js',
      './drupal/sites/*/files/styles',
    ],
  ]) {
    // Robo collection builder instance.
    $collection   = $this->collectionBuilder();

    $projectRoot  = $this->getProjectRoot();
    $groupPath    = $options['path'] . '/' . $options['group'];
    $tempPath     = $collection->tmpDir();

    $archiveName  = \date(self::ARCHIVE_DATE_FORMAT) . '.tar.gz';
    $archivePath  = $groupPath . '/' . $archiveName;

    $dumpName     = 'database.sql';
    $dumpPath     = $tempPath . '/' . $dumpName;

    $tarOptions = [
      '--create', '--gzip',
      // This is required if on Windows so tar doesn't get confused by colons
      // in volume labels, e.g. C:/
      // @see https://stackoverflow.com/a/37996249
      '--force-local',
      // This removes the temporary directory path from the stored files, so
      // they sit in the archive root. Note the removal of the leading slash
      // character so tar recognizes the path.
      // @see https://www.gnu.org/software/tar/manual/html_section/tar_51.html
      '--transform="s,^' . ltrim($tempPath, '/') . '/,./,"',
      // This transforms the stored project files' paths from the full path to
      // placing them all under a 'tree' directory in the archive root. Note the
      // removal of the leading slash character so tar recognizes the path.
      // @see https://www.gnu.org/software/tar/manual/html_section/tar_51.html
      '--transform="s,^' . ltrim($projectRoot, '/') . '/,tree/,"',
      '--file=' . $archivePath,
      $dumpPath,
      $projectRoot . '/.',
    ];

    // Add each exclude item as an '--exclude' parameter.
    foreach ($options['exclude'] as $exclude) {
      $tarOptions[] = '--exclude="' . $exclude . '"';
    }

    // Save $this because we need to pass it to the Drush command closure where
    // $this will have a different value.
    $drushCommand = $this;

    $collection->taskFilesystemStack()
      // Create the temp directory for the database dump to be placed in.
      ->mkdir($tempPath)
      // Create the back-up group directory if it doesn't exist yet.
      ->mkdir($groupPath)
      // Run the Drush 'sql:dump' command to create the database dump file.
      ->addCode(function() use ($drushCommand, $tempPath, $dumpName) {
        $drushCommand->processManager()
          ->drush($drushCommand->selfRecord, 'sql:dump', [], [
            'result-file' => $tempPath . '/' . $dumpName,
          ])
          ->mustRun();
      });

    // Pack the database dump and project files into an archive.
    $collection->taskExecStack()
      ->exec('tar ' . implode(' ', $tarOptions));

    // Delete the temp directory.
    $collection->taskFilesystemStack()
      ->remove($tempPath);

    // If a limit greater than 0 has been set, attempt to find existing archives
    // to delete the oldest over the limit.
    if ($options['limit'] > 0) {
      $collection->addCode(function() use (
        $drushCommand, $options, $groupPath
      ) {
        $archiveFinder = new Finder();

        $archiveFinder
          // Only search for files, not directories.
          ->files()
          // Look for the archive date regular expression as the file names.
          ->name(self::ARCHIVE_DATE_REGEX)
          // Sort by name, which should also sort them by date and time because
          // they're numerical. The argument indicates that natural language
          // sorting should be used, which we might as well.
          ->sortByName(true);

        // Perform the search.
        $archiveFinder->in($groupPath);

        // If we haven't found any existing archives or the number of found
        // archives is at or below the limit, return here.
        if (
          !$archiveFinder->hasResults() &&
          count($archiveFinder) <= $options['limit']
        ) {
          return;
        }

        // An array of found archive names.
        $archiveNames = [];

        foreach ($archiveFinder as $file) {
          $archiveNames[] = $file->getRelativePathname();
        }

        // Reverse the found archives so that the oldest are at the end.
        $archiveNames = \array_reverse($archiveNames);

        // Create a new collection since we need to run this inside
        $archiveCollection = $drushCommand->collectionBuilder();

        $archiveFileSystemStack = $archiveCollection->taskFilesystemStack();

        for ($i = $options['limit']; $i < count($archiveNames); $i++) {
          $archiveFileSystemStack
            // Remove the archive from the file system.
            ->remove($groupPath . '/' . $archiveNames[$i])
            // Log a Drush info message, to be shown if --verbose is passed.
            ->addCode(function() use ($drushCommand, $archiveNames, $i) {
              $drushCommand->logger()->info(dt('Deleting archive @name', [
                '@name' => $archiveNames[$i],
              ]));
            });
        }

        $archiveCollection->run();
      });
    }

    $collection->run();
  }

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
      'updb',
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
      foreach (['css', 'js', 'php', 'styles'] as $path) {
        $options['exclude-paths'][] = $relativeFileDirs['public'] .
          DIRECTORY_SEPARATOR . $path;
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
