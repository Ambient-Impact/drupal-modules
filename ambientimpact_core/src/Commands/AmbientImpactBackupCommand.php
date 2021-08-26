<?php

namespace Drupal\ambientimpact_core\Commands;

use Drupal\ambientimpact_core\Commands\AbstractAmbientImpactFileSystemCommand;
use Symfony\Component\Finder\Finder;

/**
 * ambientimpact:backup Drush command.
 *
 * @see self::backup()
 */
class AmbientImpactBackupCommand extends AbstractAmbientImpactFileSystemCommand {

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
   *   recognized by tar. Prepend file and directory paths with "./" to match
   *   relative to the project root, rather than to match anywhere in their
   *   path.
   *
   * @option exclude-generated-files
   *   Whether to exclude generated files, such as compiled Twig templates,
   *   aggregated JavaScript/CSS, and image style derivatives.
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
      './keys',
      'sftp-config.json',
      './drupal/libraries',
      '*/node_modules',
    ],
    'exclude-generated-files' => true,
  ]) {
    // Robo collection builder instance.
    $collection   = $this->collectionBuilder();

    $projectRoot  = $this->getProjectRoot();
    $groupPath    = $options['path'] . DIRECTORY_SEPARATOR . $options['group'];

    /** @var string The temporary directory created by Robo. Note we're creating this in the home directory rather than in the system temp directory to work around mysqldump: Got errno 28 on write. */
    $tempPath = $collection->tmpDir(
      'tmp', $_SERVER['HOME'] . \DIRECTORY_SEPARATOR . 'drush-temp'
    );

    // Build relative public files path by removing the project root path from
    // its absolute path.
    $publicFilesPath = preg_replace(
      '%^' . preg_quote($projectRoot . DIRECTORY_SEPARATOR) . '%',
      '',
      $this->fileSystemService->realPath('public://')
    );

    $archiveName  = \date(self::ARCHIVE_DATE_FORMAT) . '.tar.gz';
    $archivePath  = $groupPath . DIRECTORY_SEPARATOR . $archiveName;

    $dumpName     = 'database.sql';
    $dumpPath     = $tempPath . DIRECTORY_SEPARATOR . $dumpName;

    $dumpOptions  = [
      'result-file' => $dumpPath,
    ];

    // Pass on the verbose and debug options to the sql:dump command for
    // debugging.
    if ($options['verbose']) {
      $dumpOptions['verbose'] = true;
    }
    if ($options['debug']) {
      $dumpOptions['debug'] = true;
    }

    $tarOptions = [
      '--create',
      '--gzip',
      // This is required if on Windows so tar doesn't get confused by colons
      // in volume labels, e.g. C:/
      // @see https://stackoverflow.com/a/37996249
      '--force-local',
      // This removes the temporary directory path from the stored files, so
      // they sit in the archive root. Note the removal of the leading slash
      // character so tar recognizes the path on *nix.
      // @see https://www.gnu.org/software/tar/manual/html_section/tar_51.html
      '--transform="s,^' . preg_quote(
        ltrim($tempPath, '/') . DIRECTORY_SEPARATOR
      ) . ',,"',
      // This transforms the stored project files' paths from the full path to
      // placing them all under a 'tree' directory in the archive root. Note the
      // removal of the leading slash character so tar recognizes the path on
      // *nix.
      // @see https://www.gnu.org/software/tar/manual/html_section/tar_51.html
      '--transform="s,^' . preg_quote(
        ltrim($projectRoot, '/') . DIRECTORY_SEPARATOR
      ) . '\.' . preg_quote(DIRECTORY_SEPARATOR) . ',tree' .
      DIRECTORY_SEPARATOR . ',"',
      // Same as above, but for the root directory - without this, we'd end up
      // with an empty directory structure with the full filesystem path to the
      // Drupal install.
      '--transform="s,^' . preg_quote(
        ltrim($projectRoot, '/') . DIRECTORY_SEPARATOR
      ) . '\.,tree,"',
      '--file=' . $archivePath,
    ];

    // Add generated files to exclude list if set to do so.
    if ($options['exclude-generated-files']) {
      foreach ($this->generatedFileDirectories as $dirName) {
        $options['exclude'][] = '.' . DIRECTORY_SEPARATOR . $publicFilesPath .
          DIRECTORY_SEPARATOR . $dirName;
      }
    }

    // Add each exclude item as an '--exclude' parameter.
    foreach ($options['exclude'] as $exclude) {
      $tarOptions[] = '--exclude="' . $exclude . '"';
    }

    // This adds the SQL dump and project root directory to the tar options.
    // Note that it's important to place this after the '--exclude' options, or
    // tar will complain and won't apply the '--exclude' patterns.
    $tarOptions[] = $dumpPath;
    $tarOptions[] = $projectRoot . '/.';

    // Save $this because we need to pass it to the Drush command closure where
    // $this will have a different value.
    $drushCommand = $this;

    $collection->taskFilesystemStack()
      // Create the back-up group directory if it doesn't exist yet.
      ->mkdir($groupPath)
      // Run the Drush 'sql:dump' command to create the database dump file.
      ->addCode(function() use ($drushCommand, $dumpOptions) {
        $drushCommand->processManager()
          ->drush(
            $drushCommand->siteAliasManager()->getSelf(),
            'sql:dump', [], $dumpOptions
          )
          ->mustRun();
      });

    // Pack the database dump and project files into an archive.
    $collection->taskExecStack()
      ->exec('tar ' . implode(' ', $tarOptions));

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
            ->remove($groupPath . DIRECTORY_SEPARATOR . $archiveNames[$i])
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
