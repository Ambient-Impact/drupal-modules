<?php

namespace Drupal\ambientimpact_core\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\image\Entity\ImageStyle;
use Drupal\Core\Image\ImageFactory;
use Drupal\video_embed_field\ProviderManagerInterface;
use Drupal\video_embed_field\Plugin\Field\FieldFormatter\Thumbnail;

/**
 * Plugin implementation of the thumbnail field formatter.
 *
 * This adds a play icon if the thumbnail is linked to the provider URL and
 * attempts to fetch and set the image width and height as Thumbnail does not.
 *
 * @FieldFormatter(
 *   id = "ambientimpact_video_embed_field_thumbnail",
 *   label = @Translation("Thumbnail"),
 *   field_types = {
 *     "video_embed_field"
 *   }
 * )
 */
class VideoEmbedFieldThumbnail extends Thumbnail {
  /**
   * Constructs a new instance of the plugin.
   *
   * This is modified from the parent Thumbnail::__construct() to add the
   * Drupal image factory service.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Third party settings.
   * @param \Drupal\video_embed_field\ProviderManagerInterface $provider_manager
   *   The video embed provider manager.
   * @param \Drupal\Core\Image\ImageFactory $image_factory
   *   The Drupal image factory service.
   */
  public function __construct(
    $plugin_id, $plugin_definition,
    FieldDefinitionInterface $field_definition,
    $settings, $label, $view_mode, $third_party_settings,
    ProviderManagerInterface $provider_manager,
    EntityStorageInterface $image_style_storage,
    ImageFactory $image_factory
  ) {
    parent::__construct(
      $plugin_id, $plugin_definition, $field_definition, $settings, $label,
      $view_mode, $third_party_settings,
      $provider_manager, $image_style_storage
    );

    $this->imageFactory = $image_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('video_embed_field.provider_manager'),
      $container->get('entity.manager')->getStorage('image_style'),
      $container->get('image.factory')
    );
  }

  /**
   * {@inheritdoc}
   *
   * This extends Thumbnail::viewElements() with the following:
   * - Adds a play icon to each item's render array if the corresponding setting
   *   is enabled in the field formatter settings.
   *
   * - Attempts to fetch and set the thumbnail image's width and height,
   *   including if it's set to use an image style (if the derivative exists and
   *   is readable), as Thumbnail::viewElements() does not and we need those for
   *   the intrinsic ratio wrapper to be able to calculate the aspect ratio.
   *
   * @see \ambientimpact_core_field_formatter_third_party_settings_form()
   *   Provides the third party setting form element to enable or disable the
   *   play icon on the field formatter form.
   *
   * @see https://www.drupal.org/node/2130757
   *   Change record; describes the third party settings usage.
   *
   * @see https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Config%21Entity%21ThirdPartySettingsInterface.php/interface/ThirdPartySettingsInterface
   *   API documentation for third party settings on entities.
   */
  public function viewElements(FieldItemListInterface $items, $langCode) {
    $elements = parent::viewElements($items, $langCode);

    // Don't alter the render array if our field formatter setting isn't present
    // or is not set to true. We still need to loop over all the items to
    // attempt to fetch the width and height regardless, so just set a variable
    // that we can use to quickly continue in the foreach loop.
    if (
      !isset($this->thirdPartySettings['ambientimpact_core']['play_icon']) ||
      $this->thirdPartySettings['ambientimpact_core']['play_icon'] !== true
    ) {
      $addPlayIcon = false;
    } else {
      $addPlayIcon = true;
    }

    foreach ($items as $delta => $item) {
      $element  = &$elements[$delta];
      $image    = &$element['#title'];
      $provider = $this->providerManager->loadProviderFromInput($item->value);

      // If the width or height aren't defined, attempt to fetch them.
      if (
        (!isset($image['#width']) || !isset($image['#height'])) &&
        isset($image['#uri'])
      ) {
        // If an image style is used, load the style and build the URI to the
        // derivative image.
        if ($image['#theme'] === 'image_style') {
          $style = ImageStyle::load($image['#style_name']);

          $uri = $style->buildUri($image['#uri']);

        // If it's not an image style, a plain image is assumed.
        } else {
          $uri = $image['#uri'];
        }

        // Create an Image instance.
        $imageInstance = $this->imageFactory->get($uri);

        $width  = $imageInstance->getWidth();
        $height = $imageInstance->getHeight();

        // If the width and height are numeric (i.e. either integers, floats, or
        // strings that contain the former two), set them on the image render
        // array so that intrinsic ratio works correctly.
        if (is_numeric($width) && is_numeric($height)) {
          $image['#width']  = $width;
          $image['#height'] = $height;
        }
      }

      // Skip items where the provider is missing, the image is not linked to
      // its provider, or we're not set to add the play icon.
      if (
        !$provider ||
        $this->getSetting('link_image_to') !== static::LINK_PROVIDER ||
        !$addPlayIcon
      ) {
        continue;
      }

      $pluginID = $provider->getPluginId();

      // Determine what icon and text to use based on provider.
      switch ($pluginID) {
        // These have their own brand icons.
        case 'youtube':
        case 'vimeo':
          $iconName   = $pluginID;
          $iconBundle = 'brands';

          $text       = $provider->getPluginDefinition()['title'];

          break;

        // If not a recognized brand, just use a plain play icon.
        default:
          $iconName   = 'play';
          $iconBundle = 'core';

          $text       = $this->t('Play');
      }

      // Restructure the link #title and include our play icon.
      $element['#title'] = [
        'thumbnail' => $element['#title'],
        'play'      => [
          '#type'     => 'container',
          '#attributes' => [
            'class'       => ['field__item-play'],
          ],
          '#attached' => [
            'library'   => [
              'ambientimpact_core/component.media.thumbnail.play',
            ],
          ],
          'icon'      => [
            '#type'     => 'ambientimpact_icon',
            '#icon'     => $iconName,
            '#bundle'   => $iconBundle,
            '#text'     => $text,
            '#containerAttributes'  => [
              'class'     => ['field__item-play-icon'],
            ],
          ],
        ],
      ];
    }

    return $elements;
  }
}
