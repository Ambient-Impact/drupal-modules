<?php

namespace Drupal\ambientimpact_core;

/**
 * An interface for returning JS settings for Ambient.Impact Component plugins.
 */
interface ComponentJSSettingsInterface {
  /**
   * Return JavaScript settings to pass to the front-end.
   *
   * The default implementation just returns an empty array, which is ignored.
   * Override this to return settings. Don't nest arrays under 'AmbientImpact'
   * or the component name as that's done automatically.
   *
   * @return array
   *
   * @see https://www.drupal.org/docs/8/creating-custom-modules/adding-stylesheets-css-and-javascript-js-to-a-drupal-8-module#configurable
   *
   * @see \Drupal\ambientimpact_core\ComponentPluginManager\getComponentJSSettings
   *
   * @see ambientimpact_core_page_attachments()
   */
  public function getJSSettings(): array;
}
