<?php

namespace Drupal\ambientimpact_markdown\Plugin\Markdown\CommonMark;

use Drupal\ambientimpact_markdown\AmbientImpactMarkdownEventInterface;
use Drupal\ambientimpact_markdown\Event\Markdown\CommonMark\CreateEnvironmentEvent;
use Drupal\ambientimpact_markdown\Event\Markdown\CommonMark\DocumentParsedEvent;
use Drupal\ambientimpact_markdown\Event\Markdown\CommonMark\DocumentPreParsedEvent;
use Drupal\markdown\Plugin\Markdown\CommonMark\CommonMark as MarkdownCommonMark;
use League\CommonMark\ConfigurableEnvironmentInterface;
use League\CommonMark\Environment;
use League\CommonMark\Event\AbstractEvent as CommonMarkAbstractEvent;
use League\CommonMark\Event\DocumentParsedEvent as CommonMarkDocumentParsedEvent;
use League\CommonMark\Event\DocumentPreParsedEvent as CommonMarkDocumentPreParsedEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * CommonMark Markdown plug-in extended to provide Symfony events.
 *
 * This does not have an annotation so as to not be picked up by Drupal's
 * plug-in system, as we override the Markdown module class.
 *
 * @see \ambientimpact_markdown_markdown_parser_info_alter()
 *   Original Markdown module plug-in classes are replaced in this hook.
 *
 * @see \Drupal\markdown\Plugin\Markdown\CommonMark\CommonMark
 *   Original Markdown module plug-in that we're extending.
 */
class CommonMark extends MarkdownCommonMark {

  /**
   * The Symfony event dispatcher service.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * {@inheritdoc}
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   The Symfony event dispatcher service.
   */
  public function __construct(
    array $configuration, $pluginId, $pluginDefinition,
    EventDispatcherInterface $eventDispatcher
  ) {
    parent::__construct($configuration, $pluginId, $pluginDefinition);

    // Save dependencies.
    $this->eventDispatcher = $eventDispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $pluginId,
    $pluginDefinition
  ) {
    return new static(
      $configuration, $pluginId, $pluginDefinition,
      $container->get('event_dispatcher')
    );
  }

  /**
   * {@inheritdoc}
   *
   * @see $this->registerEnvironmentEventListeners()
   *   Registers CommonMark environment event listeners.
   */
  protected function getEnvironment(): ConfigurableEnvironmentInterface {
    if (!$this->environment) {
      /** @var \League\CommonMark\ConfigurableEnvironmentInterface */
      $environment = parent::getEnvironment();

      $this->registerEnvironmentEventListeners($environment);
    }

    return $this->environment;
  }

  /**
   * Register CommonMark environment event listeners.
   *
   * This triggers Symfony events on various CommonMark events.
   *
   * @param \League\CommonMark\ConfigurableEnvironmentInterface $environment
   *   The CommonMark environment to register event listeners to.
   */
  protected function registerEnvironmentEventListeners(
    ConfigurableEnvironmentInterface $environment
  ): void {

    // CommonMark environment created event.
    if ($this->eventDispatcher->hasListeners(
      AmbientImpactMarkdownEventInterface::COMMONMARK_CREATE_ENVIRONMENT
    )) {
      /** @var \Drupal\ambientimpact_markdown\Event\CreateEnvironmentEvent */
      $createEnvironmentEvent = new CreateEnvironmentEvent($environment);

      $this->eventDispatcher->dispatch(
        AmbientImpactMarkdownEventInterface::COMMONMARK_CREATE_ENVIRONMENT,
        $createEnvironmentEvent
      );
    }

    // CommonMark document pre-parsed event.
    if ($this->eventDispatcher->hasListeners(
      AmbientImpactMarkdownEventInterface::COMMONMARK_DOCUMENT_PRE_PARSED
    )) {

      $environment->addEventListener(
        CommonMarkDocumentPreParsedEvent::class,
        function(CommonMarkAbstractEvent $event) {

          /** @var \Drupal\ambientimpact_markdown\Event\Markdown\CommonMark\DocumentPreParsedEvent */
          $ourEvent = new DocumentPreParsedEvent(
            $event->getDocument(),
            $event->getMarkdown()
          );

          $this->eventDispatcher->dispatch(
            AmbientImpactMarkdownEventInterface::COMMONMARK_DOCUMENT_PRE_PARSED,
            $ourEvent
          );

          $event->replaceMarkdown($ourEvent->getMarkdown());

        }
      );

    }

    // CommonMark document parsed event.
    if ($this->eventDispatcher->hasListeners(
      AmbientImpactMarkdownEventInterface::COMMONMARK_DOCUMENT_PARSED
    )) {

      $environment->addEventListener(
        CommonMarkDocumentParsedEvent::class,
        function(CommonMarkAbstractEvent $event) {

          /** @var \Drupal\ambientimpact_markdown\Event\Markdown\CommonMark\DocumentParsedEvent */
          $ourEvent = new DocumentParsedEvent($event->getDocument());

          $this->eventDispatcher->dispatch(
            AmbientImpactMarkdownEventInterface::COMMONMARK_DOCUMENT_PARSED,
            $ourEvent
          );

        }
      );

    }

  }

}
