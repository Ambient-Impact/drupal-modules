<?php

namespace Drupal\ambientimpact_core\Commands;

use Consolidation\AnnotatedCommand\CommandData;
use Consolidation\SiteAlias\HostPath;
use Drupal\ambientimpact_core\Commands\AbstractFileSystemCommand;
use Drupal\ambientimpact_core\Commands\MaintenanceModeTrait;
use Drupal\ambientimpact_core\Commands\PrepareRsyncPathTrait;
use Drush\Config\ConfigLocator;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Abstract Drush sync command class.
 */
abstract class AbstractSyncCommand extends AbstractFileSystemCommand {

  use MaintenanceModeTrait;

  use PrepareRsyncPathTrait;

  /**
   * HostPath object representing the path to the source Drupal site root.
   *
   * @var \Consolidation\SiteAlias\HostPath
   */
  protected $sourceEvaluatedPath;

  /**
   * HostPath object representing the path to the target Drupal site root.
   *
   * @var \Consolidation\SiteAlias\HostPath
   */
  protected $targetEvaluatedPath;

  /**
   * Inject alias path parameter options.
   *
   * This sets up the configuration object to include options from the source
   * and target aliases, if any, so that these values may participate in
   * configuration injection.
   *
   * Used for setting up sync commands.
   *
   * @param \Symfony\Component\Console\Input\InputInterface $input
   *   A Symfony input instance.
   *
   * @param string $parameterName
   *
   * @return \Consolidation\SiteAlias\HostPath
   *
   * @see \Drush\Commands\core\RsyncCommands::injectAliasPathParameterOptions()
   *   Copied from the core:rsync Drush command.
   */
  protected function injectAliasPathParameterOptions(
    InputInterface $input, string $parameterName
  ): HostPath {

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
   * Evaluate the path aliases in the source and destination parameters.
   *
   * We do this in the pre-command-event so that we can set up the
   * configuration object to include options from the source and target
   * aliases, if any, so that these values may participate in configuration
   * injection.
   *
   * Used for setting up rsync commands.
   *
   * @param \Symfony\Component\Console\Event\ConsoleCommandEvent $event
   *   The Symfony console command event.
   *
   * @see \Drush\Commands\core\RsyncCommands::preCommandEvent()
   *   Adapted from the core:rsync Drush command.
   */
  public function preCommandEvent(ConsoleCommandEvent $event) {

    /** @var \Symfony\Component\Console\Input\InputInterface A Symfony input instance. */
    $input = $event->getInput();

    $this->sourceEvaluatedPath = $this->injectAliasPathParameterOptions(
      $input, 'source'
    );

    $this->targetEvaluatedPath = $this->injectAliasPathParameterOptions(
      $input, 'target'
    );

  }

  /**
   * Validate that site aliases are both local.
   *
   * @param \Consolidation\AnnotatedCommand\CommandData $commandData
   *
   * @throws \Exception
   *   If the command attempts to sync to or from a remote site.
   */
  public function validate(CommandData $commandData) {

    if (
      $this->sourceEvaluatedPath->isRemote() ||
      $this->targetEvaluatedPath->isRemote()
    ) {
      throw new \Exception(\dt(
        'This command can currently only sync between two local Drupal sites.'
      ));
    }

  }

}
