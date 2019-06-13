/* -----------------------------------------------------------------------------
   Ambient.Impact - Media - PhotoSwipe immerse events component
----------------------------------------------------------------------------- */

AmbientImpact.on(['photoswipe', 'photoswipe.event'], function(
  aiPhotoSwipe,
  aiPhotoSwipeEvent
) {
AmbientImpact.addComponent(
  'photoswipe.event.immerse',
function(aiPhotoSwipeEventImmerse, $) {
  'use strict';

  var $PhotoSwipe = $('.' + $.PhotoSwipe.baseClass);

  // Fire immerseEnter and immerseExit events on the document on PhotoSwipe
  // open and close, respectively.
  $(document).on(
    'PhotoSwipeInitialZoomIn.aiPhotoSwipeEventImmerse',
  function(
    event, gallery, $gallery, gallerySettings
  ) {
    $(document).trigger('immerseEnter', [$PhotoSwipe[0]]);

  }).on('PhotoSwipeInitialZoomOut.aiPhotoSwipeEventImmerse', function(
    event, gallery, $gallery, gallerySettings
  ) {
    $(document).trigger('immerseExit', [$PhotoSwipe[0]]);
  });
});
});
