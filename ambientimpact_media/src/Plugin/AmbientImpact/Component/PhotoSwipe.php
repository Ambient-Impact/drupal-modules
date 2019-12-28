<?php

namespace Drupal\ambientimpact_media\Plugin\AmbientImpact\Component;

use Drupal\ambientimpact_core\ComponentBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * PhotoSwipe component.
 *
 * @Component(
 *   id = "photoswipe",
 *   title = @Translation("PhotoSwipe"),
 *   description = @Translation("Provides a wrapper component around <a href='https://photoswipe.com/'>PhotoSwipe</a>.")
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
  public function getJSSettings(): array {
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
   *   Our third-party settings for the field.
   *
   * @see \Drupal\ambientimpact_media\EventSubscriber\Preprocess\PreprocessFieldPhotoSwipeEventSubscriber
   *   Attaches the PhotoSwipe attributes and field library to fields.
   */
  public function alterImageFormatterElements(
    array &$elements,
    FieldItemListInterface $items,
    array $settings = []
  ) {
    if (empty($elements)) {
      return;
    }

    $imageAttributeMap = $this->configuration['linkedImageAttributes'];

    // Whether to automatically group all field items into one gallery.
    $gallery = false;

    if (
      isset($settings['use_photoswipe_gallery']) &&
      $settings['use_photoswipe_gallery'] === true
    ) {
      $gallery = true;
    }

    // Pass this flag to PreprocessFieldPhotoSwipeEventSubscriber so that it
    // knows to add PhotoSwipe attributes on this and to attach the library.
    $elements[0]['#use_photoswipe'] = true;

    // Indicate to PreprocessFieldPhotoSwipeEventSubscriber whether this item is
    // to be grouped into a gallery with the other items in this field.
    $elements[0]['#use_photoswipe_gallery'] = $gallery;

    foreach ($items as $delta => $item) {
      if (!isset($elements[$delta]['#link_attributes'])) {
        $elements[$delta]['#link_attributes'] = [];
      }

      $attributes = &$elements[$delta]['#link_attributes'];

      // If the width and height have been provided by the formatter, use those.
      if (
        isset($elements[$delta]['#photoswipe_width']) &&
        isset($elements[$delta]['#photoswipe_height'])
      ) {
        foreach (['width', 'height'] as $dimension) {
          $attributes[$imageAttributeMap[$dimension]] =
            $elements[$delta]['#photoswipe_' . $dimension];
        }

      // If they haven't been provided, fall back to using the ones on the image
      // item. If the aspect ratio is the same between the displayed image on
      // the page and the image being linked to, PhotoSwipe should work fine.
      } else {
        foreach (['width', 'height'] as $dimension) {
          $attributes[$imageAttributeMap[$dimension]] = $item->$dimension;
        }
      }
    }
  }

  /**
   * Set default PhotoSwipe image formatter third party settings.
   *
   * These are defined here in one place rather than in multiple image
   * formatters to make maintaining simple.
   *
   * @param mixed $formatterInstance
   *   An image field formatter instance to apply the settings to.
   */
  public function setImageFormatterDefaults($formatterInstance) {
    // PhotoSwipe is not used by default. This may be changed later.
    $formatterInstance->setThirdPartySettingDefault(
      'ambientimpact_media', 'use_photoswipe', false
    );

    // Set default for the gallery setting to true.
    $formatterInstance->setThirdPartySettingDefault(
      'ambientimpact_media', 'use_photoswipe_gallery', true
    );
  }
}
