/* -----------------------------------------------------------------------------
   Ambient.Impact - Media - PhotoSwipe meta component
----------------------------------------------------------------------------- */

AmbientImpact.on('photoswipe', function(aiPhotoSwipe) {
AmbientImpact.addComponent(
  'photoswipe.meta',
function(aiPhotoSwipeMeta, $) {
  'use strict';

  // Theme colour meta tag - set to any CSS colour, or false to disable
  // changing colour while PhotoSwipe is open.
  $.PhotoSwipe.defaults.metaThemeColour = 'black';

  var $themeColour = $('meta[name="theme-color"]');

  // Temporarily replace the theme colour meta tag while PhotoSwipe is open,
  // and restore it on close.
  $(document).on('PhotoSwipeInitialZoomIn.aiPhotoSwipeMeta', function(
    event, gallery, $gallery, gallerySettings
  ) {
    if (gallerySettings.metaThemeColour) {
      $themeColour
        .data(
          'PhotoSwipe-previous-content',
          $themeColour.attr('content')
        )
        .attr('content', gallerySettings.metaThemeColour);
    }
  }).on('PhotoSwipeInitialZoomOut.aiPhotoSwipeMeta', function(
    event, gallery, $gallery, gallerySettings
  ) {
    if ($themeColour.data('PhotoSwipe-previous-content')) {
      $themeColour.attr(
        'content',
        $themeColour.data('PhotoSwipe-previous-content')
      )
      .removeData('PhotoSwipe-previous-content');
    }
  });
});
});
