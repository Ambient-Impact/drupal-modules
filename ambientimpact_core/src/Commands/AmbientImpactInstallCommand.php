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
   *
   * @see https://robo.li/tasks/Composer/
   */
  public function install(array $options = [
    'dev' => false,
  ]) {

    /** @var \Robo\Collection\CollectionBuilder Robo collection builder instance. */
    $collection = $this->collectionBuilder();

    /** @var \Robo\Task\Composer\Install */
    $composerInstall = $collection->taskComposerInstall();

    if ($options['dev'] !== true) {
      $composerInstall->noDev();
    }

    $composerInstall
      ->dir($this->getProjectRoot())
      ->run();

  }

}
