<?php

namespace Drupal\ambientimpact_media\Plugin\AmbientImpact\Component;

use Drupal\ambientimpact_core\ComponentBase;

/**
 * Remote video component.
 *
 * @Component(
 *   id = "remote_video",
 *   title = @Translation("Remote video"),
 *   description = @Translation("Provides functionality for working with remote video media entities.")
 * )
 */
class RemoteVideo extends ComponentBase {
  /**
   * Set default image formatter third-party settings.
   *
   * These are defined here in one place rather than in multiple image
   * formatters to make maintaining simple.
   *
   * @param mixed $formatterInstance
   *   An image field formatter instance to apply the settings to.
   */
  public function setImageFormatterDefaults($formatterInstance) {
    // Set default for the play icon setting to true.
    $formatterInstance->setThirdPartySettingDefault(
      'ambientimpact_media', 'play_icon', true
    );
  }

  /**
   * Preprocess image formatter variables.
   *
   * This wraps the 'image' key in $variables with a media play overlay.
   *
   * @param array &$variables
   *
   * @see ambientimpact_media_preprocess_image_formatter()
   *   Called from this.
   */
  public function preprocessImageFormatter(array &$variables) {
    // Don't do anything if the icon is not enabled for this element.
    if (empty($variables['useRemoteVideoPlayIcon'])) {
      return;
    }

    // Determine what icon and text to use based on provider.
    switch ($variables['remoteVideoProviderName']) {
      // These have their own brand icons.
      case 'YouTube':
      case 'Vimeo':
        $iconName   = strtolower($variables['remoteVideoProviderName']);
        $iconBundle = 'brands';

        // Include the video title in a visually hidden element for
        // accessibility.
        $text       = $this->t(
          '<span class="visually-hidden">Watch @videoTitle on </span>@providerTitle',
          [
            '@videoTitle'     => $variables['remoteVideoMediaName'],
            '@providerTitle'  => $variables['remoteVideoProviderName'],
          ]
        );

        break;

      // If not a recognized brand, just use a plain play icon.
      default:
        $iconName   = 'twistie-right';
        $iconBundle = 'libricons';

        $text       = $this->t(
          'Play<span class="visually-hidden"> @videoTitle</span>',
          [
            '@videoTitle' => $variables['remoteVideoMediaName'],
          ]
        );
    }

    $variables['image'] = [
      '#type'       => 'media_play_overlay',
      '#text'       => $text,
      '#iconName'   => $iconName,
      '#iconBundle' => $iconBundle,
      '#preview'    => $variables['image'],
    ];
  }
}
