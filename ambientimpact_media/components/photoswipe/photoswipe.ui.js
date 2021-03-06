/* -----------------------------------------------------------------------------
   Ambient.Impact - Media - PhotoSwipe UI component
----------------------------------------------------------------------------- */

AmbientImpact.on(['photoswipe', 'icon'], function(aiPhotoSwipe, aiIcon) {
AmbientImpact.addComponent('photoswipe.ui', function(aiPhotoSwipeUI, $) {
  'use strict';

  // Shorten share menu text and insert icons.
  $(document).on('PhotoSwipeInitialZoomInEnd.aiPhotoSwipeUI', function(
    event, gallery, $gallery, gallerySettings
  ) {
    // Don't do anything if the share menu is not enabled for this gallery.
    if (
      'shareEl' in gallerySettings.PhotoSwipe &&
      gallerySettings.PhotoSwipe.shareEl === false
    ) {
      return;
    }

    var shareIcons = {
      facebook: {
        name:   'facebook',
        bundle: 'brands',
        text:   'Share<span class="element-invisible"> on Facebook</span>'
      },
      twitter:  {
        name:   'twitter',
        bundle: 'brands',
        text:   'Tweet',
      },
      pinterest:  {
        name:   'pinterest',
        bundle: 'brands',
        text:   'Pin<span class="element-invisible"> on Pinterest</span>'
      },
      download: {
        name:   'push-down',
        bundle: 'libricons',
        text:   'Download'
      }
    }

    // Replace text with our icons and text.
    $.each(gallery.options.shareButtons, function(i, item) {
      gallery.options.shareButtons[i].label = $(aiIcon.get(
        shareIcons[item.id].name,
        shareIcons[item.id]
      )).html();
    });
  });
});
});
