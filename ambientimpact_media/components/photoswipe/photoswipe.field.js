/* -----------------------------------------------------------------------------
   Ambient.Impact - Media - PhotoSwipe Drupal field component
----------------------------------------------------------------------------- */

// This automatically attaches and configures PhotoSwipe for fields that have
// the necessary attributes added by our PhotoSwipe field formatter.

AmbientImpact.on(['photoswipe'], function(aiPhotoSwipe) {
AmbientImpact.addComponent('photoswipe.field', function(aiPhotoSwipeField, $) {
  'use strict';

  var photoswipeSettings  = AmbientImpact.getComponentSettings('photoswipe'),
      fieldAttributes     = photoswipeSettings.fieldAttributes;

  this.addBehaviour(
    'AmbientImpactPhotoSwipeFieldImages',
    'ambientimpact-photoswipe-field-images',
    '[' + fieldAttributes.enabled + ']',
    function(context, settings) {
      var $this = $(this),
          $galleries;

      // If field items are to be placed in the same gallery, set the attached
      // element as the gallery element.
      if ($this.attr(fieldAttributes.gallery) === 'true') {
        $galleries = $this;

      // If field items are to be placed in their own, separate galleries, set
      // the field items as the gallery elements.
      } else {
        $galleries = $this.find('.field__item');
      }

      // Create the PhotoSwipe galleries.
      for (var i = 0; i < $galleries.length; i++) {
        $galleries.eq(i).PhotoSwipe({itemSelector: '.field__item'});
      }

      // Save the gallery elements to the attached element so that we can detach
      // without having to know anything about which elements are the gallery
      // elements.
      this.$photoswipeFieldImagesGalleries = $galleries;
    },
    function(context, settings, trigger) {
      for (var i = 0; i < $gallery.length; i++) {
        this.$photoswipeFieldImagesGalleries.eq(i).PhotoSwipeDestroy();
      }

      delete this.$photoswipeFieldImagesGalleries;
    }
  );
});
});
