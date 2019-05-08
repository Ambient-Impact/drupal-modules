<?php

namespace Drupal\ambientimpact_core;

/**
 * A trait for returning JS settings for Ambient.Impact Component plugins.
 */
trait ComponentJSSettingsTrait {
  /**
   * {@inheritdoc}
   */
  public function getJSSettings(): array {
    return [];
  }
}
