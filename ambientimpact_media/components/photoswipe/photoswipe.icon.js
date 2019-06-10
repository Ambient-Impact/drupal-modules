/* -----------------------------------------------------------------------------
   Ambient.Impact - Core - PhotoSwipe icon component
----------------------------------------------------------------------------- */

AmbientImpact.on(['photoswipe', 'photoswipe.event', 'icon'], function(
  aiPhotoSwipe, aiPhotoSwipeEvent, aiIcon
) {
AmbientImpact.addComponent('photoswipe.icon', function(aiPhotoSwipeIcon, $) {
  'use strict';

    // This is a map of button CSS classes to icon keys. We search for the CSS
    // classes to determine which icon goes into each button.
  var cssButtonMap      = {
      'close':        'close',
      'fs':           'fullscreen-enter',
      'zoom':         'zoom-in',
      'share':        'share',
      'arrow--left':  'arrow-left',
      'arrow--right': 'arrow-right',
    },
    // Default icon settings, which we merge specific icon settings on top of.
    iconDefaults    = {
      textDisplay:  'visuallyHidden'
    },
    // A map of icon keys to icon settings, the latter which includes the
    // actual icon name and settings, which contain the bundle for each
    // icon, as that can be modified.
    iconSettings    = {},
    // The PhotoSwipe viewer root class.
    viewerClass     = $.PhotoSwipe.baseClass,
    // The zoom button.
    $zoomButton     = $('.' + viewerClass + '__button--zoom'),
    // The fullscreen toggle button.
    $fullscreenButton = $('.' + viewerClass + '__button--fs');

  // Build the icon names and settings from the settings we're passed.
  $.each(aiPhotoSwipe.settings.icons, function(bundleName, iconMap) {
    $.each(iconMap, function(iconKey, iconName) {
      // Only include the icon if it hasn't been defined yet. This means
      // the first found icon is used, even if multiple bundles define the
      // same icon. If you're overriding icons, you should unset the
      // default icon under the 'photoswipe' bundle to make sure yours is
      // used.
      if (!(iconKey in iconSettings)) {
        iconSettings[iconKey] = {
          name:   iconName,
          settings: $.extend(true, {
            bundle:   bundleName
          }, iconDefaults)
        };
      }
    });
  });

  // Insert the icons into the buttons.
  $.each(cssButtonMap, function(className, iconKey) {
    var icon = iconSettings[iconKey];

    $('.' + viewerClass + '__button--' + className)
      .wrapTextWithIcon(icon.name, icon.settings)
      .addClass(viewerClass + '__button--has-ambientimpact-icon');
  });

  /**
   * Replace a button's icon with a new one. Useful for toggle buttons.
   *
   * @param {jQuery} $button
   *   The button as a jQuery collection.
   *
   * @param {string} iconKey
   *   The icon key (not name) to insert.
   *
   * @see iconSettings
   */
  function updateButtonIcon($button, iconKey) {
    var icon = iconSettings[iconKey];

    $button
      .unwrapTextWithIcon()
      .wrapTextWithIcon(icon.name, icon.settings);
  };

  $(document).on('PhotoSwipeZoomIn.aiPhotoSwipeIcon', function(
    event, gallery, $gallery, gallerySettings
  ) {
    // We just zoomed in, so change the button icon out to zoom out.
    updateButtonIcon($zoomButton, 'zoom-out');

  }).on('PhotoSwipeZoomOut.aiPhotoSwipeIcon', function(
    event, gallery, $gallery, gallerySettings
  ) {
    // We just zoomed out, so change the button icon out to zoom in.
    updateButtonIcon($zoomButton, 'zoom-in');

  }).on('PhotoSwipeFullscreenEnter.aiPhotoSwipeIcon', function(
    event, gallery, $gallery, gallerySettings
  ) {
    // We just entered fullscreen, so change the button icon to fullscreen
    // exit.
    updateButtonIcon($fullscreenButton, 'fullscreen-exit');

  }).on('PhotoSwipeFullscreenExit.aiPhotoSwipeIcon', function(
    event, gallery, $gallery, gallerySettings
  ) {
    // We just exited fullscreen, so change the button icon to fullscreen
    // enter.
    updateButtonIcon($fullscreenButton, 'fullscreen-enter');

  }).on('PhotoSwipeBeforeChange.aiPhotoSwipeIcon', function(
    event, gallery, $gallery, gallerySettings
  ) {
    // We're about to switch to another slide, so change the button icon out
    // to zoom in.
    updateButtonIcon($zoomButton, 'zoom-in');

  }).on('PhotoSwipeInitialZoomOutEnd.aiPhotoSwipeIcon', function(
    event, gallery, $gallery, gallerySettings
  ) {
    // Reset zoom and fullscreen button states, in case the user closed the
    // viewer while zoomed in or in fullscreen mode. If we don't do this,
    // the buttons will erroneously retain their states.
    updateButtonIcon($zoomButton,   'zoom-in');
    updateButtonIcon($fullscreenButton, 'fullscreen-enter');
  });
});
});
