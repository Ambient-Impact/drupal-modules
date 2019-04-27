<?php

namespace Drupal\ambientimpact_core\Plugin\AmbientImpact\Component;

use Drupal\ambientimpact_core\ComponentBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * PhotoSwipe component.
 *
 * @Component(
 *   id = "photoswipe",
 *   title = @Translation("PhotoSwipe"),
 *   description = @Translation("Provides a wrapper component around PhotoSwipe.")
 * )
 */
class PhotoSwipe extends ComponentBase {
  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      // Array of icons defined for the front end. If you define any other
      // bundles for the front end to use, it will always pick the first bundle
      // it finds when looking for a specific icon, so you should unset the icon
      // from the 'photoswipe' bundle definition here to ensure it always picks
      // yours.
      'icons'   => [
        // The key is the bundle name.
        'photoswipe'  => [
          // Pairs of icon keys to the icon codes within this bundle to use. The
          // key is fixed, but the icon code can be whatever you like, hence the
          // definition structure.
          'close'             => 'close',
          'fullscreen-enter'  => 'fullscreen-enter',
          'fullscreen-exit'   => 'fullscreen-exit',
          'zoom-in'           => 'zoom-in',
          'zoom-out'          => 'zoom-out',
          'share'             => 'share',
          'arrow-left'        => 'arrow-left',
          'arrow-right'       => 'arrow-right',
        ],
      ],
      'linkedImageAttributes' => [
        'width'   => 'data-photoswipe-linked-width',
        'height'  => 'data-photoswipe-linked-height',
      ],
      'fieldAttributes' => [
        'enabled' => 'data-photoswipe-field-enabled',
        'gallery' => 'data-photoswipe-field-gallery',
      ],
    ];
  }
  /**
   * {@inheritdoc}
   */
  public function getJSSettings() {
    return [
      'icons'                 => $this->configuration['icons'],
      'linkedImageAttributes' => $this->configuration['linkedImageAttributes'],
      'fieldAttributes'       => $this->configuration['fieldAttributes'],
    ];
  }

  /**
   * Replace PhotoSwipe icons.
   *
   * This replaces existing PhotoSwipe icons with new icons. This will remove
   * any existing icons from the settings if they have the same icon keys.
   *
   * @param array $newIcons
   *   An array of bundle(s) containg the new icon(s). This must be in the same
   *   format as found in $this->defaultConfiguration(); the top level key(s)
   *   being the bundle name(s) which contain the icons to replace the existing
   *   icons.
   *
   * @todo Test this!
   */
  public function replaceIcons(array $newIcons = []) {
    $icons = &$this->configuration['icons'];

    foreach ($newIcons as $newBundleName => $newBundleIcons) {
      // Remove any existing icon keys in any existing bundles so the new ones
      // are guaranteed to be used.
      foreach ($newBundleIcons as $newIconKey => $newIconName) {
        foreach ($icons as $oldBundleName => &$oldBundleIcons) {
          if (!empty($oldBundleIcons[$newIconKey])) {
            unset($oldBundleIcons[$newIconKey]);
          }
        }
      }

      $icons[$newBundleName] = $newBundleIcons;
    }
  }

  /**
   * Alter an image formatter elements array to add PhotoSwipe data attributes.
   *
   * @param array &$elements
   *   The elements array from a field formatter's viewElements() method.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The field items from a field formatter's viewElements() method.
   *
   * @param array $settings
   *   Any settings for the field.
   */
  public function alterImageFormatterElements(
    array &$elements,
    FieldItemListInterface $items,
    array $settings = []
  ) {
    $imageAttributeMap  = $this->configuration['linkedImageAttributes'];
    $fieldAttributeMap  = $this->configuration['fieldAttributes'];

    if (!empty($elements)) {
      $attributes = &$elements[0]['#item_attributes'];

      // Pass this flag to ambientimpact_core_preprocess_field() so that it
      // knows that it should look for PhotoSwipe attributes on this, to save
      // having to load the component settings for every image field.
      $attributes['photoswipe'] = true;

      // Indicate that PhotoSwipe should attach to this item.
      $attributes[$fieldAttributeMap['enabled']] = 'true';

      // Indicate whether this item is to be grouped into a gallery with the
      // other items in this field.
      $attributes[$fieldAttributeMap['gallery']] =
        $settings['gallery'] ? 'true' : 'false';

      // Clean up just in case we could affect the foreach below.
      unset($attributes);
    }

    foreach ($items as $delta => $item) {
      $attributes = &$elements[$delta]['#item_attributes'];
      foreach (['width', 'height'] as $type) {
        $attributes[$imageAttributeMap[$type]] = $item->$type;
      }
    }
  }
}
