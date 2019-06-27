<?php

namespace Drupal\ambientimpact_media\Plugin\Field\FieldFormatter;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Image\ImageFactory;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Utility\LinkGeneratorInterface;
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
   * The Drupal image factory service.
   *
   * @var \Drupal\Core\Image\ImageFactory
   */
  protected $imageFactory;

  /**
   * The Component plugin manager instance.
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
   * @param \Drupal\Core\Utility\LinkGeneratorInterface $linkGenerator
   *   The link generator service.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $imageStyleStorage
   *   The image style storage.
   *
   * @param \Drupal\Core\Image\ImageFactory $imageFactory
   *   The Drupal image factory service.
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
    ImageFactory $imageFactory,
    ComponentPluginManagerInterface $componentManager
  ) {
    parent::__construct(
      $pluginID, $pluginDefinition, $fieldDefinition, $settings, $label,
      $viewMode, $thirdPartySettings, $currentUser, $linkGenerator,
      $imageStyleStorage
    );

    $this->imageFactory     = $imageFactory;
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
      $container->get('entity.manager')->getStorage('image_style'),
      $container->get('image.factory'),
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
    if (!empty($linkedImageStyleName)) {
      $linkedImageStyle = ImageStyle::load($linkedImageStyleName);

      // Check that we've loaded a valid image style; this will be null if
      // Drupal cannot load the entity.
      if (!empty($linkedImageStyle)) {
        $files = $this->getEntitiesToView($items, $langCode);

        foreach ($files as $delta => $file) {
          $imageStyleURI = $linkedImageStyle->buildUri($file->getFileUri());

          // Create an Image instance.
          $imageInstance = $this->imageFactory->get($imageStyleURI);

          $width  = $imageInstance->getWidth();
          $height = $imageInstance->getHeight();

          // If the width and height are numeric (i.e. either integers, floats,
          // or strings that contain the former two), set them on the image
          // render array so that intrinsic ratio works correctly.
          if (is_numeric($width) && is_numeric($height)) {
            $elements[$delta]['#photoswipe_width']  = $width;
            $elements[$delta]['#photoswipe_height'] = $height;
          }
        }
      }
    }

    $this->componentManager->getComponentInstance('photoswipe')
      ->alterImageFormatterElements($elements, $items, $settings);

    return $elements;
  }
}
