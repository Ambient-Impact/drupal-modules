<?php

namespace Drupal\ambientimpact_markdown;

/**
 * Interface AmbientImpactMarkdownEventInterface.
 */
interface AmbientImpactMarkdownEventInterface {

  /**
   * The CommonMark environment has been created.
   *
   * @Event
   *
   * @var string
   */
  public const COMMONMARK_CREATE_ENVIRONMENT = 'ambientimpact.markdown.commonmark.create_environment';

  /**
   * A CommonMark document is about to be parsed.
   *
   * @Event
   *
   * @var string
   */
  public const COMMONMARK_DOCUMENT_PRE_PARSED = 'ambientimpact.markdown.commonmark.document_pre_parsed';

  /**
   * A CommonMark document has been parsed.
   *
   * @Event
   *
   * @var string
   */
  public const COMMONMARK_DOCUMENT_PARSED = 'ambientimpact.markdown.commonmark.document_parsed';

}
