<?php

namespace Drupal\ambientimpact_media\Plugin\Field\FieldFormatter;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;
use Drupal\image_formatter_link_to_image_style\Plugin\Field\FieldFormatter\ImageFormatterLinkToImageStyleFormatter as DefaultImageFormatterLinkToImageStyleFormatter;
use Drupal\ambientimpact_core\Config\Entity\ThirdPartySettingsDefaultsTrait;
use Drupal\ambientimpact_core\ComponentPluginManagerInterface;

/**
 * Plugin override of the 'image_formatter_link_to_image_style' formatter.
 *
 * This extends the default formatter to add PhotoSwipe data.
 *
 * @see ambientimpact_media_field_formatter_info_alter()
 *   Default formatter is replaced in this hook.
 */
class ImageFormatterLinkToImageStyleFormatter
extends DefaultImageFormatterLinkToImageStyleFormatter {
  use ThirdPartySettingsDefaultsTrait;

  /**
   * The Component plug-in manager instance.
   *
   * @var \Drupal\ambientimpact_core\ComponentPluginManagerInterface
   */
  protected $componentManager;

  /**
   * Constructor; saves dependencies and sets default third-party settings.
   *
   * @param string $pluginID
   *   The plugin_id for the formatter.
   *
   * @param mixed $pluginDefinition
   *   The plug-in implementation definition.
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
   * @param \Drupal\Core\Utility\LinkGeneratorInterface $linkGenerator
   *   The link generator service.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $imageStyleStorage
   *   The image style storage.
   *
   * @param \Drupal\ambientimpact_core\ComponentPluginManagerInterface $componentManager
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
    LinkGeneratorInterface $linkGenerator,
    EntityStorageInterface $imageStyleStorage,
    ComponentPluginManagerInterface $componentManager
  ) {
    parent::__construct(
      $pluginID, $pluginDefinition, $fieldDefinition, $settings, $label,
      $viewMode, $thirdPartySettings, $currentUser, $linkGenerator,
      $imageStyleStorage
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
      $container->get('link_generator'),
      $container->get('entity_type.manager')->getStorage('image_style'),
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

    // Use the image caption formatter template. See this module's readme.md for
    // details and requirements.
    foreach ($elements as $delta => $element) {
      $elements[$delta]['#theme'] = 'image_caption_formatter';
    }

    // Allow the Animated GIF toggle component to alter $elements if set to
    // display an image style. This formatter is always linked, unlike the
    // default image formatter, so there's no check for what it links to.
    if (
      !empty($elements) &&
      $this->getSetting('image_style') !== ''
    ) {
      $this->componentManager->getComponentInstance('animated_gif_toggle')
        ->alterImageFormatterElements(
          $elements, $items, $this->getEntitiesToView($items, $langCode),
          $settings
        );
    }

    // Don't do any work if the field is empty or PhotoSwipe is not to be used.
    if (
      empty($elements) ||
      $settings['use_photoswipe'] !== true
    ) {
      return $elements;
    }

    // The image style name linked to, if set.
    $linkedImageStyleName = $this->getSetting('image_link_style');

    // Check if an image style name is available; if no style is chosen in the
    // field formatter settings, this will be an empty string.
    if (empty($linkedImageStyleName)) {
      return $elements;
    }

    $linkedImageStyle = ImageStyle::load($linkedImageStyleName);

    // Check that we've loaded a valid image style; this will be null if Drupal
    // cannot load the entity.
    if (
      $linkedImageStyle === null ||
      !\method_exists($linkedImageStyle, 'transformDimensions')
    ) {
      return $elements;
    }

    foreach ($items as $delta => $item) {
      $file = File::load($item->target_id);

      // If we couldn't load a valid Drupal file entity, skip this item.
      if (empty($file)) {
        continue;
      }

      $fileURI = $file->getFileUri();

      $dimensions = [
        'width'   => $item->width,
        'height'  => $item->height,
      ];

      $linkedImageStyle->transformDimensions($dimensions, $fileURI);

      // If the width and height are numeric (i.e. either integers, floats, or
      // strings that contain the former two), pass them to the image render
      // array.
      if (
        is_numeric($dimensions['width']) &&
        is_numeric($dimensions['height'])
      ) {
        $elements[$delta]['#photoswipe_width']  = $dimensions['width'];
        $elements[$delta]['#photoswipe_height'] = $dimensions['height'];
      }
    }

    $this->componentManager->getComponentInstance('photoswipe')
      ->alterImageFormatterElements($elements, $items, $settings);

    return $elements;
  }
}
