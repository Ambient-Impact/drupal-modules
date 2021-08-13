<?php

namespace Drupal\ambientimpact_core\Commands;

use Drupal\ambientimpact_core\Commands\AbstractAmbientImpactFileSystemCommand;

/**
 * ambientimpact:install Drush command.
 *
 * @see self::install()
 */
class AmbientImpactInstallCommand extends AbstractAmbientImpactFileSystemCommand {

  /**
   * Install project dependencies.
   *
   * This is currently just a convenience wrapper for 'composer install', saving
   * the need to navigate to the correct directory to run the command.
   *
   * @command ambientimpact:install
   *
   * @option dev
   *   Include development dependencies.
   *
   * @usage ambientimpact:install
   *   Install project dependencies.
   *
   * @usage ambientimpact:install --dev
   *   Install project dependencies, including development dependencies.
   *
   * @aliases ai:install
   */
  public function install(array $options = [
    'dev' => false,
  ]) {

    $composerCommand = 'composer install';

    if ($options['dev'] !== true) {
      $composerCommand .= ' --no-dev';
    }

    /** @var \Consolidation\SiteProcess\SiteProcess */
    $process = $this->processManager()->shell(
      $composerCommand,
      $this->getProjectRoot(),
      null,
      null,
      // This tells Drush to run the process without a timeout.
      null
    );

    // Run the process and output the process' progress in realtime.
    $process->mustRun($process->showRealtime());

  }

}
