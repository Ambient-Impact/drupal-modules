<?php

namespace Drupal\ambientimpact_markdown\Event\Markdown\CommonMark;

use League\CommonMark\ConfigurableEnvironmentInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Create CommonMark environment event.
 *
 * @see https://commonmark.thephpleague.com/1.5/customization/event-dispatcher/
 */
class CreateEnvironmentEvent extends Event {

  /**
   * The CommonMark environment.
   *
   * @var \League\CommonMark\ConfigurableEnvironmentInterface
   */
  protected $environment;

  /**
   * Constructs this event object.
   *
   * @param \League\CommonMark\ConfigurableEnvironmentInterface $environment
   *   The CommonMark environment.
   */
  public function __construct(ConfigurableEnvironmentInterface $environment) {
    $this->environment = $environment;
  }

  /**
   * Get the CommonMark environment.
   *
   * @return \League\CommonMark\ConfigurableEnvironmentInterface
   *   The CommonMark environment.
   */
  public function getEnvironment() {
    return $this->environment;
  }

}
