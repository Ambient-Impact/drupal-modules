<?php

namespace Drupal\ambientimpact_core\Commands;

use Consolidation\SiteAlias\SiteAliasInterface;
use Robo\Collection\CollectionBuilder;

/**
 * Drush command trait to set Drupal maintenance mode.
 */
trait MaintenanceModeTrait {

  /**
   * Add a Robo task to set the Drupal maintenance mode state.
   *
   * @param \Consolidation\SiteAlias\SiteAliasInterface $siteAliasRecord
   *   The site alias record for the site to set maintenance mode for.
   *
   * @param \Robo\Collection\CollectionBuilder $collection
   *   The Robo collection to add the maintenance mode task to.
   *
   * @param array $options
   *   The Drush command options of the command using this trait; used to
   *   determine if verbose or debug output is requested.
   *
   * @param bool|boolean $maintenance
   *   True to enable maintenance mode and false to disable it.
   */
  protected function addMaintenanceModeTask(
    SiteAliasInterface $siteAliasRecord,
    CollectionBuilder $collection,
    array $options,
    bool $maintenance = true
  ): void {

    // Save $this because we need to pass it to the Drush command closure where
    // $this will have a different value.
    /** @var \Drush\Commands\DrushCommands */
    $drushCommand = $this;

    $collection->addCode(function() use (
      $drushCommand, $siteAliasRecord, $options, $maintenance
    ) {

      /** @var \Consolidation\SiteProcess\SiteProcess */
      $process = $drushCommand->processManager()->drush(
        $siteAliasRecord,
        'state:set',
        ['system.maintenance_mode', ($maintenance === true ? '1' : '0')],
        ['input-format' => 'integer']
      );

      if ($options['verbose'] || $options['debug']) {
        $process->mustRun($process->showRealtime());

      } else {
        $process->mustRun();
      }

    });

  }

}
