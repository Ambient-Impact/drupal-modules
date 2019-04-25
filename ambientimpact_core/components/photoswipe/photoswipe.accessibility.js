/* -----------------------------------------------------------------------------
   Ambient.Impact - Core - PhotoSwipe accessibility component
----------------------------------------------------------------------------- */

// TO DO: this should scroll to the element receiving focus when PhotoSwipe
// closes and returns focus to the link of the last viewed item.

// TO DO: we need a way to batch the disabling/enabling of focus for document
// elements, as there can sometimes be quite a few. Prioritizing elements within
// or close to the viewport would also make sense, with the rest being toggled
// last.


AmbientImpact.onGlobals('ally.maintain.disabled', function() {
AmbientImpact.on('photoswipe', function(aiPhotoSwipe) {
AmbientImpact.addComponent(
  'photoswipeAccessibility',
function(aiPhotoSwipeAccessibility, $) {
  'use strict';

  var focusDisabledHandle,
    focusDisabledFilter = '.' + $.PhotoSwipe.baseClass + ' *',
    openedScrollTop   = 0;

  function preventScroll(event) {
    $(document).scrollTop(openedScrollTop);
  };

  // Don't do anything until the gallery has finished transitioning to the
  // open state and then wait a tick with a setTimeout(), so as to not affect
  // performance.
  $(document).on(
    'PhotoSwipeInitialZoomInEnd.aiPhotoSwipeAccessibility',
  function(
    event, gallery, $gallery, gallerySettings
  ) {
    setTimeout(function() {
    // Disable the ability to focus any element that isn't within the viewer
    // on open, and remove the restriction on viewer close. The scroll
    // position is saved on viewer open and used to prevent the document
    // from scrolling until the viewer closes, to ensure the user is where
    // they left off. This can happen with keyboard navigation focusing the
    // document or viewport, regardless of our disabling everything outside
    // of the viewer.
    focusDisabledHandle = ally.maintain.disabled({
      filter: focusDisabledFilter
    });

    openedScrollTop = $(document).scrollTop();

    $(document).on('scroll.aiPhotoSwipeAccessibility', preventScroll);

    // Fire an event on the document that we've initialized.
    $(document).trigger('PhotoSwipeAccessibilityInit', [
      gallery,
      $gallery,
      gallerySettings
    ]);
    });
  })
  .on(
    'PhotoSwipeInitialZoomOutEnd.aiPhotoSwipeAccessibility',
  function(
    event, gallery, $gallery, gallerySettings
  ) {
    setTimeout(function() {
    if (
      'disengage' in focusDisabledHandle &&
      typeof focusDisabledHandle.disengage === 'function'
    ) {
      focusDisabledHandle.disengage();
    }

    $(document).off('scroll.aiPhotoSwipeAccessibility', preventScroll);

    // Focus the link of the current gallery index on gallery destruction.
    // By default, PhotoSwipe doesn't do this, which results in focus being
    // reset to the start of the document which is terrible for
    // accessibility.
    $(gallery.currItem.el).find('a').trigger('focus');

    // Fire an event on the document that we've removed all our stuff.
    $(document).trigger('PhotoSwipeAccessibilityDestroy', [
      gallery,
      $gallery,
      gallerySettings
    ]);
    });
  });
});
});
});
