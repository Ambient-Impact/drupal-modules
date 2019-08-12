<?php

namespace Drupal\ambientimpact_core\Commands;

use Drush\Commands\DrushCommands;
use Drush\Drush;
use Robo\Contract\BuilderAwareInterface;
use Robo\LoadAllTasks;
use Symfony\Component\Finder\Finder;

/**
 * Ambient.Impact Drush command file.
 */
class AmbientImpactCommands extends DrushCommands
implements BuilderAwareInterface {
  use LoadAllTasks;

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
   * The current site's own (@self) alias record.
   *
   * @var \Consolidation\SiteAlias\SiteAliasInterface
   */
  protected $selfRecord;

  /**
   * Constructor; saves $this->selfRecord and sets 'path.drush-script'.
   */
  public function __construct() {
    // Get the @self alias record. Note that we have to use
    // Drush::aliasManager() to get the siteAliasManager because of this bug
    // which still seems to be occurring as of Drush 9.7.1:
    // @see https://github.com/drush-ops/drush/issues/3394
    $this->selfRecord = Drush::aliasManager()->getSelf();

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
  }

  /**
   * Get the actual project root, of which Drupal is in.
   *
   * By default this is one directory higher than the Drupal root, but this
   * method can be overridden to provide a different path.
   *
   * @return string
   *   The filesystem path to the project root.
   */
  protected function getProjectRoot(): string {
    return \realpath($this->selfRecord->root() . '/..');
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
}
