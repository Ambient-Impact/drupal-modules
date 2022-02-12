<?php

namespace Drupal\ambientimpact_core\Commands;

use Consolidation\AnnotatedCommand\CommandData;
use Consolidation\AnnotatedCommand\Events\CustomEventAwareInterface;
use Consolidation\AnnotatedCommand\Events\CustomEventAwareTrait;
use Consolidation\SiteAlias\HostPath;
use Consolidation\SiteAlias\SiteAliasInterface;
use Drupal\ambientimpact_core\Commands\AbstractFileSystemCommand;
use Drupal\ambientimpact_core\Commands\MaintenanceModeTrait;
use Drupal\ambientimpact_core\Commands\PrepareRsyncPathTrait;
use Drush\Config\ConfigLocator;
use Robo\Collection\CollectionBuilder;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Abstract Drush sync command class.
 */
abstract class AbstractSyncCommand extends AbstractFileSystemCommand implements CustomEventAwareInterface {

  use CustomEventAwareTrait;

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
   * The source site alias record.
   *
   * @var \Consolidation\SiteAlias\SiteAliasInterface
   */
  protected SiteAliasInterface $sourceRecord;

  /**
   * The target site alias record.
   *
   * @var \Consolidation\SiteAlias\SiteAliasInterface
   */
  protected SiteAliasInterface $targetRecord;

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
  public function preCommandEvent(ConsoleCommandEvent $event): void {

    /** @var \Symfony\Component\Console\Input\InputInterface A Symfony input instance. */
    $input = $event->getInput();

    $this->sourceEvaluatedPath = $this->injectAliasPathParameterOptions(
      $input, 'source'
    );

    $this->sourceRecord = $this->sourceEvaluatedPath->getSiteAlias();

    $this->targetEvaluatedPath = $this->injectAliasPathParameterOptions(
      $input, 'target'
    );

    $this->targetRecord = $this->sourceEvaluatedPath->getSiteAlias();

  }

  /**
   * Validate that site aliases are both local.
   *
   * @param \Consolidation\AnnotatedCommand\CommandData $commandData
   *
   * @throws \Exception
   *   If the command attempts to sync to or from a remote site.
   */
  public function validate(CommandData $commandData): void {

    if (
      $this->sourceEvaluatedPath->isRemote() ||
      $this->targetEvaluatedPath->isRemote()
    ) {
      throw new \Exception(\dt(
        'This command can currently only sync between two local Drupal sites.'
      ));
    }

  }

  /**
   *
   *
   * @param string $eventName
   *
   * @param array $commands
   *   Existing commands, if any.
   *
   * @return mixed[]
   *
   * @see \Drush\Drupal\Commands\core\ViewsCommands::cacheClear()
   *   Example of how to implement an event handler.
   *
   * @see \Drush\Commands\core\CacheCommands::getTypes()
   *   Example event handler invocation for the above.
   */
  protected function getSyncCommands(
    string $eventName, array $commands = []
  ): array {

    $handlers = $this->getCustomEventHandlers($eventName);

    foreach ($handlers as $handler) {
      $handler($commands, $this->sourceRecord, $this->targetRecord, $this);
    }

    return $commands;

  }

  /**
   * [addPostSyncTasks description]
   *
   * @param \Robo\Collection\CollectionBuilder $collection
   */
  protected function addSyncTasks(
    CollectionBuilder $collection, array $commands
  ): void {

    /** @var \Drush\Commands\DrushCommands Reference to $this for use in closures. */
    $drushCommand = $this;

    foreach ($commands as $name => $command) {

      // If the command is a callable, add it as such.
      if (\is_callable($command)) {

        $collection->addCode(function() use ($command, $drushCommand) {
          \call_user_func_array($command, [$drushCommand]);
        });

      // Otherwise, assume it's an array of arguments to call Drush with.
      } else if (\is_array($command)) {

        $collection->addCode(function() use ($command, $drushCommand) {
          \call_user_func_array(
            [$drushCommand->processManager(), 'drush'], $command
          )->mustRun();
        });

      }

    }

  }

}
