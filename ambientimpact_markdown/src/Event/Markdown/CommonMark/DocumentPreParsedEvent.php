<?php

namespace Drupal\ambientimpact_markdown\Event\Markdown\CommonMark;

use Drupal\ambientimpact_markdown\Event\Markdown\CommonMark\AbstractDocumentEvent;
use League\CommonMark\Block\Element\Document;
use League\CommonMark\Input\MarkdownInputInterface;

/**
 * CommonMark document pre-parsed event.
 *
 * @see https://commonmark.thephpleague.com/1.5/customization/event-dispatcher/
 */
class DocumentPreParsedEvent extends AbstractDocumentEvent {

  /**
   * The Markdown for this document.
   *
   * @var \League\CommonMark\Input\MarkdownInputInterface
   */
  protected $markdown;

  /**
   * Constructs this event object.
   *
   * @param \League\CommonMark\Block\Element\Document $document
   *   The CommonMark document.
   *
   * @param \League\CommonMark\Input\MarkdownInputInterface $markdown
   *   The Markdown for this document.
   */
  public function __construct(
    Document $document,
    MarkdownInputInterface $markdown
  ) {

    parent::__construct($document);

    $this->markdown = $markdown;
  }

  /**
   * Get the Markdown for this document.
   *
   * @return League\CommonMark\Input\MarkdownInputInterface
   */
  public function getMarkdown(): MarkdownInputInterface {
    return $this->markdown;
  }

  /**
   * Set the Markdown for this document.
   *
   * @param League\CommonMark\Input\MarkdownInputInterface $markdown
   */
  public function setMarkdown(MarkdownInputInterface $markdown): void {
    $this->markdown = $markdown;
  }

}
