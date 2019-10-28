<?php

namespace Drupal\ambientimpact_core\Service;

/**
 * Markup processor service interface.
 */
interface MarkupProcessorInterface {
  /**
   * Process the provided markup, triggering an event so it can be altered.
   *
   * This parses the provided markup using the Symfony DomCrawler and passes the
   * Crawler instance to any event subscribers listening to the
   * 'ambientimpact.markup_process' event.
   *
   * @param string|\Drupal\Core\StringTranslation\TranslatableMarkup $markup
   *   Either a string of markup or a TranslatableMarkup object to process.
   *
   * @return string|\Drupal\Core\StringTranslation\TranslatableMarkup
   *   The processed markup, altered by any event subscribers listening to the
   *   'ambientimpact.markup_process' event. This will either be a string of
   *   markup or a TranslatableMarkup object, depending on what was passed as
   *   the $markup parameter.
   *
   * @throws \InvalidArgumentException
   *   Exception thrown when $markup is not a string or instance of
   *   TranslatableMarkup.
   */
  public function process($markup);
}
