<?php

namespace Drupal\ambientimpact_core;

use Drupal\Core\Cache\Cache;

/**
 * A trait for returning HTML from Ambient.Impact Component plugins.
 *
 * This gives Components the ability to provide a Twig template to pass to the
 * front-end as rendered HTML. The Drupal Cache API is used to cache the
 * rendered HTML for performance.
 *
 * @see https://api.drupal.org/api/drupal/core!core.api.php/group/cache
 *   Drupal Cache API documentation.
 *
 * @todo Add support for cache contexts?
 *
 * @see https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Render%21Renderer.php/function/Renderer%3A%3AdoRender
 *   Cache contexts used in the rendering process.
 */
trait ComponentHTMLTrait {
  /**
   * Whether this Component has any HTML cached.
   *
   * @var null|bool
   */
  protected $hasCachedHTML = null;

  /**
   * This Component's HTML cache ID.
   *
   * @var null|string
   */
  protected $htmlCacheID = null;

  /**
   * Get the Component HTML cache settings.
   *
   * This can be overridden on a per-Component basis to set custom cache
   * invalidation.
   *
   * This supports 'max-age' and 'tags', but 'contexts' is not yet supported.
   *
   * @return array
   *   The Component HTML cache settings with 'max-age' set to permanent, i.e.
   *   only rebuilt on a cache rebuild.
   */
  protected static function getHTMLCacheSettings(): array {
    return [
      'max-age' => Cache::PERMANENT,
    ];
  }

  /**
   * Get this Component's HTML cache ID.
   *
   * This is only built once and stored for subsequent use.
   *
   * @return string
   *   The value of $this->htmlCacheID.
   *
   * @see $this->htmlCacheID
   *   The HTML cache ID is stored here.
   */
  protected function getHTMLCacheID(): string {
    if ($this->htmlCacheID === null) {
      $this->htmlCacheID =
        $this->pluginDefinition['provider'] . ':' .
        $this->pluginDefinition['id'] . ':' .
        $this->languageManager->getCurrentLanguage()->getId();
    }

    return $this->htmlCacheID;
  }

  /**
   * Determine if this Component has any cached HTML available.
   *
   * @return boolean
   *   The value of $this->hasCachedHTML.
   *
   * @see $this->hasCachedHTML
   *   Stores whether this Component has cached HTML.
   */
  protected function hasCachedHTML(): bool {
    if ($this->hasCachedHTML === null) {
      $this->hasCachedHTML = !empty(
        $this->htmlCacheService->get($this->getHTMLCacheID())->data
      );
    }

    return $this->hasCachedHTML;
  }

  /**
   * Get the file system path to this Component's HTML file.
   *
   * @return string
   *   The Component's <component name>.html.twig file path.
   */
  protected function getHTMLPath() {
    // Get the path to the module implementing this component plugin.
    $modulePath = $this->moduleHandler->getModule(
      $this->pluginDefinition['provider']
    )->getPath();

    // This is the path to the component from Drupal's root, including the
    // implementing module.
    $componentPath  = $modulePath . '/' . $this->path;

    // This is the full file system path to the file, including the file name
    // and extension.
    return DRUPAL_ROOT . '/' . $componentPath . '/' .
      $this->pluginDefinition['id'] . '.html.twig';
  }

  /**
   * {@inheritdoc}
   */
  public function hasHTML(): bool {
    return file_exists($this->getHTMLPath());
  }

  /**
   * {@inheritdoc}
   */
  public function getHTML() {
    // Don't proceed if a Twig template doesn't exist.
    if (!$this->hasHTML()) {
      return false;
    }

    // If cached HTML is available, grab that without doing any rendering.
    if ($this->hasCachedHTML()) {
      $html = $this->htmlCacheService->get($this->getHTMLCacheID())->data;

    // If no cached HTML is found, render and cache the HTML.
    } else {
      // Render array containing the file contents as an inline template.
      $renderArray = [
        '#type'     => 'inline_template',
        '#template' => file_get_contents($this->getHTMLPath()),
      ];

      // Render the inline template.
      $html = $this->renderer->renderPlain($renderArray);

      $cacheSettings = static::getHTMLCacheSettings();

      // Set the 'max-age' and 'tags' keys if they're not set.
      if (!isset($cacheSettings['max-age'])) {
        $cacheSettings['max-age'] = Cache::PERMANENT;
      }
      if (!isset($cacheSettings['tags'])) {
        $cacheSettings['tags'] = [];
      }

      // Save the rendered template HTML to the cache.
      $this->htmlCacheService->set(
        $this->getHTMLCacheID(),
        $html,
        $cacheSettings['max-age'],
        $cacheSettings['tags']
      );
    }

    return $html;
  }
}
