<?php

namespace Drupal\ambientimpact_ux\Plugin\AmbientImpact\Component;

use Drupal\ambientimpact_core\ComponentBase;

/**
 * Scrollbar gutter component.
 *
 * @Component(
 *   id = "scrollbar_gutter",
 *   title = @Translation("Scrollbar gutter"),
 *   description = @Translation("This provides an API and adds a <code>--scrollbar-gutter</code> custom property to the <code>&lt;html&gt;</code> element with the detected thickness of the scrollbar, which is updated lazily on viewport resize. This is based on <a href='https://davidwalsh.name/detect-scrollbar-width'>a technique by David Walsh</a>, as demonstrated in <a href='https://jsfiddle.net/a1m6an3u/'>this JSFiddle</a>. Once the <a href='https://developer.mozilla.org/en-US/docs/Web/CSS/scrollbar-gutter'><code>scrollbar-gutter</code></a> CSS property is supported in all major browsers, this component may become unnecessary.")
 * )
 */
class ScrollbarGutter extends ComponentBase {
}
