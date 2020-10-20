<?php

namespace Drupal\ambientimpact_markdown\Plugin\Markdown\CommonMark;

use Drupal\ambientimpact_markdown\Plugin\Markdown\CommonMark\CommonMark;
use League\CommonMark\Environment;

/**
 * CommonMark GFM Markdown plug-in extended to provide Symfony events.
 *
 * This does not have an annotation so as to not be picked up by Drupal's
 * plug-in system, as we override the Markdown module class.
 *
 * Note that since PHP does not support extending multiple classes, only
 * multi-level inheritance, we have to redeclare the properties and methods of
 * the original CommonMarkGfm class since we have to extend our own CommonMark
 * class to get the Symfony event functionality. This is mitigated by the fact
 * that the CommonMarkGfm is very simple and short.
 *
 * @see \ambientimpact_markdown_markdown_parser_info_alter()
 *   Original Markdown module plug-in classes are replaced in this hook.
 *
 * @see \Drupal\markdown\Plugin\Markdown\CommonMark\CommonMarkGfm
 *   Original Markdown module plug-in that we're replacing.
 *
 * @see \Drupal\ambientimpact_markdown\Plugin\Markdown\CommonMark\CommonMark
 *   Our CommonMark class that we're extending which provides Symfony events.
 */
class CommonMarkGfm extends CommonMark {

  /**
   * {@inheritdoc}
   */
  protected static $converterClass = '\\League\\CommonMark\\GithubFlavoredMarkdownConverter';

  /**
   * {@inheritdoc}
   */
  protected function createEnvironment() {
    return Environment::createGFMEnvironment();
  }

}
