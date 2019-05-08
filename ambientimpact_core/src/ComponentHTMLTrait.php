<?php

namespace Drupal\ambientimpact_core;

/**
 * A trait for returning HTML from Ambient.Impact Component plugins.
 */
trait ComponentHTMLTrait {
  /**
   * Get the files system path to this Component's HTML file.
   *
   * @return string
   *   The Component's <component name>.html.twig file path.
   */
  protected function getHTMLPath() {
    // Get the path to the module implementing this component plugin.
    $modulePath   = $this->container->get('module_handler')
      ->getModule($this->pluginDefinition['provider'])->getPath();

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
    // Don't proceed if the file doesn't exist.
    if (!$this->hasHTML()) {
      return false;
    }

    // Build a render array containing the file contents as an inline template.
    $renderArray = [
      '#type'     => 'inline_template',
      '#template' => file_get_contents($this->getHTMLPath()),
    ];

    // Render the inline template and return it.
    return $this->container->get('renderer')->renderPlain($renderArray);
  }
}
