<?php

namespace Drupal\ambientimpact_core\Config\Entity;

/**
 * Trait for configuration entities to handle default third party settings.
 */
trait ThirdPartySettingsDefaultsTrait {
  /**
   * Sets the default value of a third-party setting.
   *
   * This will only set given the setting if hasn't been set yet. This is
   * primarily intended to be used in a constructor, but can also be used
   * elsewhere.
   *
   * @param string $module
   *   The module providing the third-party setting.
   *
   * @param string $key
   *   The setting name.
   *
   * @param mixed $value
   *   The setting value.
   *
   * @return $this
   *
   * @see \Drupal\Core\Config\Entity\ConfigEntityBase::getThirdPartySetting()
   *   Sets third party settings.
   *
   * @see \Drupal\Core\Config\Entity\ConfigEntityBase::$third_party_settings
   *   Third party settings are stored in this array.
   *
   * @see \Drupal\Core\Config\Entity\ThirdPartySettingsInterface
   *   Defines third party settings methods.
   */
  public function setThirdPartySettingDefault(
    string $module, string $key, $value
  ) {
    if (!isset($this->third_party_settings[$module][$key])) {
      $this->setThirdPartySetting($module, $key, $value);
    }

    return $this;
  }
}
