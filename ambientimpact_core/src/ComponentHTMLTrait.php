<?php

namespace Drupal\ambientimpact_core;

/**
 * A trait for returning Component HTML for Ambient.Impact Component plugins.
 */
trait ComponentHTMLTrait {
  /**
   * Get any HTML this Component may have available for the front-end.
   *
   * @return string|bool
   *   If the component has a <component name>.html.twig file in its directory,
   *   it will be rendered and returned, otherwise false is returned.
   */
  public function getHTML() {
    // Get the path to the module implementing this component plugin.
    $modulePath   = $this->container->get('module_handler')
      ->getModule($this->pluginDefinition['provider'])->getPath();

    // This is the path to the component from Drupal's root, including the
    // implementing module.
    $componentPath  = $modulePath . '/' . $this->path;

    // This is the full file system path to the file, including the file name
    // and extension.
    $filePath =
      DRUPAL_ROOT . '/' . $componentPath . '/' .
      $this->pluginDefinition['id'] . '.html.twig';

    // Don't proceed if the file doesn't exist.
    if (!file_exists($filePath)) {
      return false;
    }

    // Build a render array containing the file contents as an inline template.
    $renderArray = [
      '#type'     => 'inline_template',
      '#template' => file_get_contents($filePath),
    ];

    // Render the inline template and return it.
    return $this->container->get('renderer')->renderPlain($renderArray);
  }
}
