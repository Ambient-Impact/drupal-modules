<?php

namespace Drupal\ambientimpact_ux\Plugin\AmbientImpact\Component;

use Drupal\ambientimpact_core\ComponentBase;

/**
 * Abbreviation component.
 *
 * @Component(
 *   id = "abbr",
 *   title = @Translation("Abbreviation (HTML &lt;abbr&gt; element)"),
 *   description = @Translation("Provides rich, mobile-accessible tooltips for &lt;abbr&gt; elements.")
 * )
 */
class Abbr extends ComponentBase {
  /**
   * {@inheritdoc}
   */
  public function getDemo(): array {
    return [
      '#type'     => 'html_tag',
      '#tag'      => 'p',
      '#value'    => t('The fundamental languages of the web are <abbr title="HyperText Markup Language">HTML</abbr>, <abbr title="Cascading Style Sheets">CSS</abbr>, and JavaScript. These are delivered to browsers using <abbr title="HyperText Transfer Protocol">HTTP</abbr> or <abbr title="HyperText Transfer Protocol Secure">HTTPS</abbr>, the latter of which is preferable because it encrypts data via <abbr title="Transport Layer Security">TLS</abbr> or <abbr title="Secure Sockets Layer">SSL</abbr>.'),
      '#attached' => ['library' => ['ambientimpact_ux/component.abbr']],
    ];
  }
}
