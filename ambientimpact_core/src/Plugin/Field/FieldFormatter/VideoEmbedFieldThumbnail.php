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
   */
  public function viewElements(FieldItemListInterface $items, $langCode) {
    $elements = parent::viewElements($items, $langCode);

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
