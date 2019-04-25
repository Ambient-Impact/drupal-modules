/* -----------------------------------------------------------------------------
   Ambient.Impact - Core - PhotoSwipe transition component
----------------------------------------------------------------------------- */

// This hides the currently active image's original thumbnail, while PhotoSwipe
// is open and while transitions are running.

AmbientImpact.on('photoswipe', function(aiPhotoSwipe) {
AmbientImpact.addComponent(
  'photoswipe.thumbnail.transition',
function(aiPhotoSwipeThumbnailTransition, $) {
  'use strict';

  var thumbnailBaseClass    = 'photoswipe-enabled-thumbnail',
    thumbnailHiddenClass  = thumbnailBaseClass + '--hidden';

  /**
   * Hide the thumbnail of the currently open PhotoSwipe item.
   *
   * @param {object} gallery
   *   - The PhotoSwipe gallery instance.
   *
   * @param {jQuery} $gallery
   *   - The jQuery collection containing the gallery container element.
   *
   * @param {object} gallerySettings
   *   - The settings for this gallery, built by the PhotoSwipe component.
   */
  function hideCurrentThumbnail(gallery, $gallery, gallerySettings) {
    $(gallery.currItem.el).find('.' + thumbnailBaseClass)
      .addClass(thumbnailHiddenClass);
  };

  /**
   * Show all thumbnails of PhotoSwipe items in the current gallery.
   *
   * @param {object} gallery
   *   - The PhotoSwipe gallery instance.
   *
   * @param {jQuery} $gallery
   *   - The jQuery collection containing the gallery container element.
   *
   * @param {object} gallerySettings
   *   - The settings for this gallery, built by the PhotoSwipe component.
   */
  function showAllThumbnails(gallery, $gallery, gallerySettings) {
    $gallery.find('.' + thumbnailHiddenClass)
      .removeClass(thumbnailHiddenClass);
  };

  $(document).on('PhotoSwipeAttach.aiPhotoSwipeThumbnailTransition', function(
    event, $gallery, gallerySettings
  ) {
    // Add the base class on attach.
    $gallery
      .find(
        gallerySettings.itemSelector + ' ' +
        gallerySettings.linkSelector + ' img'
      )
      .addClass(thumbnailBaseClass);
  })
  .on('PhotoSwipeDestroy.aiPhotoSwipeThumbnailTransition', function(
    event, $gallery, gallerySettings
  ) {
    // Remove the base class on destroy.
    $gallery.find('.' + thumbnailBaseClass)
      .removeClass(thumbnailBaseClass);
  })
  .on('PhotoSwipeInitialZoomIn.aiPhotoSwipeThumbnailTransition', function(
    event, gallery, $gallery, gallerySettings
  ) {
    // Abort if not using the zoom transition.
    if (gallerySettings.transition !== 'zoom') {
      return;
    }

    setTimeout(function() {
      // Hide the current thumbnail when zoom in animation starts.
      hideCurrentThumbnail(gallery, $gallery, gallerySettings);
    });

  })
  .on('PhotoSwipeAfterChange.aiPhotoSwipeThumbnailTransition', function(
    event, gallery, $gallery, gallerySettings
  ) {
    // Abort if not using the zoom transition.
    if (gallerySettings.transition !== 'zoom') {
      return;
    }

    setTimeout(function() {
      // This shows all thumbnails, and hides the current one whenever the
      // current slide changes.
      showAllThumbnails(gallery, $gallery, gallerySettings);
      hideCurrentThumbnail(gallery, $gallery, gallerySettings);
    });

  })
  .on('PhotoSwipeInitialZoomOutEnd.aiPhotoSwipeThumbnailTransition', function(
    event, gallery, $gallery, gallerySettings
  ) {
    // Abort if not using the zoom transition.
    if (gallerySettings.transition !== 'zoom') {
      return;
    }

    // Show any hidden thumbnails once the zoom out animation has completed.
    showAllThumbnails(gallery, $gallery, gallerySettings);
  });
});
});
