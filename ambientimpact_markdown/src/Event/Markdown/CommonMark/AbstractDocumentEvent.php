<?php

namespace Drupal\ambientimpact_markdown\Event\Markdown\CommonMark;

use League\CommonMark\Block\Element\Document;
use Symfony\Component\EventDispatcher\Event;

/**
 * Base class for CommonMark document events.
 *
 * While CommonMark provides its own document events, it uses its own event
 * model unrelated to Symfony. This event acts as a bridge between the Symfony
 * event dispatcher and CommonMark's.
 *
 * @see https://commonmark.thephpleague.com/1.5/customization/event-dispatcher/
 *
 * @todo Should this also provide a method to get the environment object?
 */
abstract class AbstractDocumentEvent extends Event {

  /**
   * The CommonMark document.
   *
   * @var \League\CommonMark\Block\Element\Document
   */
  protected $document;

  /**
   * Constructs this event object.
   *
   * @param \League\CommonMark\Block\Element\Document $document
   *   The CommonMark document.
   */
  public function __construct(Document $document) {
    $this->document = $document;
  }

  /**
   * Get the CommonMark document.
   *
   * @return \League\CommonMark\Block\Element\Document
   *   The CommonMark document.
   */
  public function getDocument() {
    return $this->document;
  }

}
