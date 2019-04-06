<?php

namespace Drupal\ambientimpact_core\Plugin\Field\FieldFormatter;

use Drupal\video_embed_field\Plugin\Field\FieldFormatter\Thumbnail;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the thumbnail field formatter.
 *
 * This adds a play icon if the thumbnail is linked to the provider URL.
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
   * {@inheritdoc}
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
    // or is not truthy. (Drupal saves checkbox values as '0' '1'.)
    if (
      !isset($this->thirdPartySettings['ambientimpact_core']['play_icon']) ||
      !$this->thirdPartySettings['ambientimpact_core']['play_icon']
    ) {
      return $elements;
    }

    foreach ($items as $delta => $item) {
      $element  = &$elements[$delta];
      $provider = $this->providerManager->loadProviderFromInput($item->value);

      // Skip items where the provider is missing or the image is not linked to
      // its provider.
      if (
        !$provider ||
        $this->getSetting('link_image_to') !== static::LINK_PROVIDER
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
