<?php

namespace Drupal\ambientimpact_media\Plugin\Field\FieldFormatter;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter as CoreImageFormatter;
use Drupal\ambientimpact_core\Config\Entity\ThirdPartySettingsDefaultsTrait;
use Drupal\ambientimpact_core\ComponentPluginManager;

/**
 * Plugin override of the core 'image' formatter.
 *
 * This extends the core formatter to add PhotoSwipe data.
 *
 * @see ambientimpact_media_field_formatter_info_alter()
 *   Core formatter is replaced in this hook.
 */
class ImageFormatter extends CoreImageFormatter {
  use ThirdPartySettingsDefaultsTrait;

  /**
   * The Component plugin manager instance.
   *
   * @var \Drupal\ambientimpact_core\ComponentPluginManager
   */
  protected $componentManager;

  /**
   * Constructor; saves dependencies and sets default third-party settings.
   *
   * @param string $pluginID
   *   The plugin_id for the formatter.
   *
   * @param mixed $pluginDefinition
   *   The plugin implementation definition.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $fieldDefinition
   *   The definition of the field to which the formatter is associated.
   *
   * @param array $settings
   *   The formatter settings.
   *
   * @param string $label
   *   The formatter label display setting.
   *
   * @param string $viewMode
   *   The view mode.
   *
   * @param array $thirdPartySettings
   *   Any third party settings settings.
   *
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current user.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $imageStyleStorage
   *   The image style storage.
   *
   * @param \Drupal\ambientimpact_core\ComponentPluginManager $componentManager
   *   The Ambient.Impact Component manager service.
   */
  public function __construct(
    $pluginID,
    $pluginDefinition,
    FieldDefinitionInterface $fieldDefinition,
    array $settings,
    $label,
    $viewMode,
    array $thirdPartySettings,
    AccountInterface $currentUser,
    EntityStorageInterface $imageStyleStorage,
    ComponentPluginManager $componentManager
  ) {
    parent::__construct(
      $pluginID, $pluginDefinition, $fieldDefinition, $settings, $label,
      $viewMode, $thirdPartySettings, $currentUser, $imageStyleStorage
    );

    $this->componentManager = $componentManager;

    // Set our default third-party settings.
    $this->componentManager->getComponentInstance('photoswipe')
      ->setImageFormatterDefaults($this);
    $this->componentManager->getComponentInstance('animated_gif_toggle')
      ->setImageFormatterDefaults($this);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $pluginID,
    $pluginDefinition
  ) {
    return new static(
      $pluginID,
      $pluginDefinition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('current_user'),
      $container->get('entity.manager')->getStorage('image_style'),
      $container->get('plugin.manager.ambientimpact_component')
    );
  }

  /**
   * {@inheritdoc}
   *
   * This extends the parent::viewElements() to alter the element render arrays
   * for the PhotoSwipe and AnimatedGIFToggle components.
   *
   * @see \Drupal\ambientimpact_media\Plugin\AmbientImpact\Component\PhotoSwipe::alterImageFormatterElements()
   *   Elements are passed to this PhotoSwipe component method to be altered.
   *
   * @see \Drupal\ambientimpact_media\Plugin\AmbientImpact\Component\AnimatedGIFToggle::alterImageFormatterElements()
   *   Elements are passed to this AnimatedGIFToggle component method to be
   *   altered.
   */
  public function viewElements(FieldItemListInterface $items, $langCode) {
    $elements = parent::viewElements($items, $langCode);

    $settings = $this->getThirdPartySettings('ambientimpact_media');

    // Allow the Animated GIF toggle component to alter $elements if set to
    // display an image style and linked to either the file or media entity.
    if (
      !empty($elements) &&
      $this->getSetting('image_style') !== '' &&
      (
        $this->getSetting('image_link') === 'file' ||
        $this->getSetting('image_link') === 'content'
      )
    ) {
      $this->componentManager->getComponentInstance('animated_gif_toggle')
        ->alterImageFormatterElements(
          $elements, $items, $this->getEntitiesToView($items, $langCode),
          $settings
        );
    }

    // Don't do any work if the field is empty, the field is not linked to the
    // image file, or PhotoSwipe is not to be used.
    if (
      empty($elements) ||
      $this->getSetting('image_link') !== 'file' ||
      $settings['use_photoswipe'] !== true
    ) {
      return $elements;
    }

    $this->componentManager->getComponentInstance('photoswipe')
      ->alterImageFormatterElements($elements, $items, $settings);

    return $elements;
  }
}
