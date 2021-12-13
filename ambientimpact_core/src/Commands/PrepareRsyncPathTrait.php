<?php

namespace Drupal\ambientimpact_core\Commands;

/**
 * Drush command trait to prepare paths for rsync.
 */
trait PrepareRsyncPathTrait {

  /**
   * Prepare paths for rsync.
   *
   * This works around an issue on Windows where a drive letter and colon at the
   * start of paths will cause rsync to throw an error because it interprets
   * those as remote URLs/paths. Converting Windows paths to Cygwin-style paths
   * fixes this.
   *
   * If the 'cygpath' executable exists, that will be used to convert the path.
   * If it doesn't exist, we fall back to altering the path ourselves.
   *
   * @param string $path
   *   A path to alter.
   *
   * @return string
   *   Either $path converted to Cygwin-style, or if not a Windows-style path,
   *   will just be unaltered $path.
   *
   * @see https://github.com/mitchellh/vagrant-aws/issues/27#issuecomment-16955400
   *   Description of the problem and the working solution.
   *
   * @see https://beamtic.com/if-command-exists-php
   *   Inspiration on use of the 'where' command on Windows.
   *
   * @see https://cygwin.com/cygwin-ug-net/cygpath.html
   *   cygpath documentation.
   *
   * @see https://github.com/consolidation/Robo/issues/1045
   *   Robo issue opened about this.
   */
  protected function prepareRsyncPath(string $path): string {

    if (\stripos(\PHP_OS, 'win') !== false) {

      // Attempt to determine if the 'cygpath' executable exists using the
      // 'where' utility bundled with Windows. $returnCode will be 0 (zero) if
      // 'cygpath' is found, and 1 if not.
      \exec('where /Q cygpath', $output, $returnCode);

      // If 'cygpath' exists, use it to convert the path.
      if ($returnCode === 0) {
        return \shell_exec('cygpath -u ' . \escapeshellarg($path));
      }
    }

    // This replaces a drive letter at the start of a path (e.g. "C:/") to
    // Cygwin-style (e.g. "/c/"). Note that if a Windows drive letter is not
    // found, \preg_replace_callback() just returns the string unaltered.
    $path = \preg_replace_callback('|^([A-Z]):\\\|', function(array $matches) {
      return '/' . \strtolower($matches[1]) . '/';
    }, $path);

    // If the path separator is a backslash, we're on Windows so replace
    // backslashes with slashes or rsync will get confused.
    if (\DIRECTORY_SEPARATOR === '\\') {
      $path = \str_replace('\\', '/', $path);
    }

    return $path;

  }

}
