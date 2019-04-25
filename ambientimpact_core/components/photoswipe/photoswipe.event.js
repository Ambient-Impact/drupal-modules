/* -----------------------------------------------------------------------------
   Ambient.Impact - Core - PhotoSwipe event component
----------------------------------------------------------------------------- */

// The following PhotoSwipe events are fired on the document as a convenience,
// invoked from gallery.listen():
// * initialZoomIn    => PhotoSwipeInitialZoomIn
// * initialZoomInEnd => PhotoSwipeInitialZoomInEnd
// * initialZoomOut   => PhotoSwipeInitialZoomOut
// * initialZoomOutEnd  => PhotoSwipeInitialZoomOutEnd
// * beforeChange   => PhotoSwipeBeforeChange
// * afterChange    => PhotoSwipeAfterChange

// See: http://photoswipe.com/documentation/api.html (Events heading)

// This provides some additional events that the main PhotoSwipe component or
// PhotoSwipe library itself do not provide. These are fired on the document:
// * PhotoSwipeFullscreenEnter
// * PhotoSwipeFullscreenExit
// * PhotoSwipeZoomIn
// * PhotoSwipeZoomOut

AmbientImpact.on(['photoswipe'], function(aiPhotoSwipe) {
AmbientImpact.addComponent(
  'photoswipe.event',
function(aiPhotoSwipeEvent, $) {
  'use strict';

  var viewerClass   = $.PhotoSwipe.baseClass,
    transposeEvents = {
      initialZoomIn:    'PhotoSwipeInitialZoomIn',
      initialZoomInEnd: 'PhotoSwipeInitialZoomInEnd',
      initialZoomOut:   'PhotoSwipeInitialZoomOut',
      // 'initialZoomOutEnd' doesn't want to fire as of 4.0.5, so we're
      // binding to 'destroy', which fires right after that is supposed
      // to, and actually does fire. ¯\_(ツ)_/¯
      destroy:      'PhotoSwipeInitialZoomOutEnd',
      beforeChange:   'PhotoSwipeBeforeChange',
      afterChange:    'PhotoSwipeAfterChange'
    };

  // We bind to the before open event to ensure we can reliably catch the
  // initialZoomIn event.
  $(document).on('PhotoSwipeBeforeOpen.aiPhotoSwipeEvent', function(
    event, gallery, $gallery, gallerySettings
  ) {
    // Fire events on the document when PhotoSwipe fires them on the
    // gallery.
    $.each(transposeEvents, function(PhotoSwipeEventName, docEventName) {
      gallery.listen(PhotoSwipeEventName, function() {
        $(document).trigger(docEventName, [
          gallery,
          $gallery,
          gallerySettings
        ]);
      });
    });
  });


  // Don't do anything until the gallery has finished transitioning to the
  // open state, so as to not affect performance.
  $(document).on('PhotoSwipeInitialZoomInEnd.aiPhotoSwipeEvent', function(
    event, gallery, $gallery, gallerySettings
  ) {
    // Check that the PhotoSwipe UI offers the method to get the browser's
    // fullscreen API. This is still a patchwork of vendor prefixes as of
    // May 2018, so this is required. See ui.getFullscreenAPI in
    // photoswipe-ui-default.js.
    if (
      !gallery.ui ||
      !gallery.ui.getFullscreenAPI ||
      typeof gallery.ui.getFullscreenAPI !== 'function'
    ) {
      return;
    }

    var api = gallery.ui.getFullscreenAPI();

    // Check in case something borked for an unforeseen reason. If the
    // API is not supported at all, api.isFullscreen() is not defined by
    // the default PhotoSwipe UI. See ui.getFullscreenAPI in
    // photoswipe-ui-default.js.
    if (
      !api.eventK ||
      !api.isFullscreen ||
      typeof api.isFullscreen !== 'function'
    ) {
      return;
    }

    // Attach events
    $(document).on(api.eventK + '.aiPhotoSwipeEvent', function(event) {
      if (api.isFullscreen()) {
        // We've entered fullscreen.
        $(document).trigger('PhotoSwipeFullscreenEnter', [
          gallery,
          $gallery,
          gallerySettings
        ]);
      } else {
        // We've exited fullscreen.
        $(document).trigger('PhotoSwipeFullscreenExit', [
          gallery,
          $gallery,
          gallerySettings
        ]);
      }
    });

    // Unbind the event when the gallery is destroyed.
    gallery.listen('destroy', function() {
      $(document).off(api.eventK + '.aiPhotoSwipeEvent');
    });
  });

  // Don't do anything for the zoom event if MutationObserver isn't supported.
  if (!window.MutationObserver) {
    return;
  }

  // Don't do anything until the gallery has finished transitioning to the
  // open state, so as to not affect performance.
  $(document).on('PhotoSwipeInitialZoomInEnd.aiPhotoSwipeEvent', function(
    event, gallery, $gallery, gallerySettings
  ) {
    var zoomedIn    = false,
      zoomedInClass = viewerClass + '--zoomed-in',
      observer,
      $viewer     = $(gallery.template);

    observer = new MutationObserver(function(mutationsList) {
      for (var i = mutationsList.length - 1; i >= 0; i--) {
        // Did we just zoom in?
        if (!zoomedIn && $viewer.hasClass(zoomedInClass)) {
          zoomedIn = true;

          $(document).trigger('PhotoSwipeZoomIn', [
            gallery,
            $gallery,
            gallerySettings
          ]);

          return;
        }

        // Did we just zoom out?
        if (zoomedIn && !$viewer.hasClass(zoomedInClass)) {
          zoomedIn = false;

          $(document).trigger('PhotoSwipeZoomOut', [
            gallery,
            $gallery,
            gallerySettings
          ]);

          return;
        }
      }
    });

    observer.observe(gallery.template, {
      attributes:     true,
      attributeFilter:  ['class']
    });

    gallery.listen('destroy', function() {
      observer.disconnect();
    });
  });
});
});
