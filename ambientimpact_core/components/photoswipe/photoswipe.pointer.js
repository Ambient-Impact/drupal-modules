/* -----------------------------------------------------------------------------
   Ambient.Impact - Core - PhotoSwipe pointer component
----------------------------------------------------------------------------- */

AmbientImpact.on(
  ['photoswipe', 'photoswipeAccessibility', 'pointerFocusHide'],
function(
  aiPhotoSwipe, aiPhotoSwipeAccessibility, aiPointerFocusHide
) {
AmbientImpact.addComponent(
  'photoswipe.pointer',
function(aiPhotoSwipePointer, $) {
  'use strict';

  $(document).on('PhotoSwipeBeforeAttach.aiPhotoSwipePointer', function(
    event, $gallery, gallerySettings
  ) {
    // Force pointer focus hiding on the links, even if they open in a new
    // tab/window.
    $gallery.find('a').data('pointer-focus-hide', true);
  })
  .on('PhotoSwipeOpen.aiPhotoSwipePointer', function(
    event, gallery, $gallery, gallerySettings
  ) {
    // Lock the pointer focus state so we have the same focus state on
    // PhotoSwipe close as when we opened, i.e. so that if the pointer
    // was used to open PhotoSwipe, the outline will not be shown on the
    // link when we return focus to it on close.

    // Note that we're using setTimeout() because accurate detection of the
    // current focus source may sometimes be off if we're in a click
    // handler, so we need to jump ahead on tick. See:
    // https://github.com/medialize/ally.js/issues/150#issuecomment-244898298
    setTimeout(function() {
      aiPointerFocusHide.lock();
    });
  })
  .on('PhotoSwipeAccessibilityDestroy.aiPhotoSwipePointer', function(
    event, gallery, $gallery, gallerySettings
  ) {
    // Unlock the pointer focus state when accessibility stuff has been
    // removed. We have to wait for this as it will focus the last viewed
    // item's link, and the focus source would be detected as 'script', but
    // that's not what we want.
    aiPointerFocusHide.unlock();
  });
});
});
