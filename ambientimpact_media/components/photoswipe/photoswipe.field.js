// -----------------------------------------------------------------------------
//   Ambient.Impact - Media - PhotoSwipe Drupal field component
// -----------------------------------------------------------------------------

// This automatically attaches and configures PhotoSwipe for fields that have
// the necessary attributes added by our PhotoSwipe field formatter.

AmbientImpact.on(['photoswipe'], function(aiPhotoSwipe) {
AmbientImpact.addComponent('photoswipe.field', function(aiPhotoSwipeField, $) {

  'use strict';

  /**
   * PhotoSwipe component settings.
   *
   * @type {Object}
   */
  var photoswipeSettings = AmbientImpact.getComponentSettings('photoswipe');

  /**
   * PhotoSwipe field attribute names.
   *
   * @type {Object}
   */
  var fieldAttributes = photoswipeSettings.fieldAttributes;

  // This behaviour finds and groups all field items in an entity that is itself
  // rendered in an entity reference field as PhotoSwipe galleries. This allows
  // multiple media entities to be grouped as a single PhotoSwipe gallery.
  this.addBehaviour(
    'AmbientImpactPhotoSwipeFieldEntityReference',
    'ambientimpact-photoswipe-field-entity-reference',
    '[' + fieldAttributes.entityReference + ']',
    function(context, settings) {

      /**
       * The gallery element, wrapped in a jQuery collection.
       *
       * @type {jQuery}
       */
      var $gallery = $(this);

      /**
       * The item selector.
       *
       * @type {String}
       */
      var itemSelector =
        '[' + fieldAttributes.enabled + '="true"]' +
        '[' + fieldAttributes.gallery + '="true"]';

      if ($gallery.find(itemSelector).length === 0) {
        return;
      }

      $gallery.PhotoSwipe({itemSelector: itemSelector});

      // Save the gallery jQuery collection to the attached element so that we
      // can detach without having to know anything about which elements are the
      // gallery elements.
      this.$photoswipeFieldEntityReferenceGallery = $gallery;

    },
    function(context, settings, trigger) {

      this.$photoswipeFieldEntityReferenceGallery.PhotoSwipeDestroy();

      delete this.$photoswipeFieldEntityReferenceGallery;

    }
  );

  this.addBehaviour(
    'AmbientImpactPhotoSwipeFieldImages',
    'ambientimpact-photoswipe-field-images',
    '[' + fieldAttributes.enabled + ']',
    function(context, settings) {

      /**
       * The behaviour target, wrapped in a jQuery collection.
       *
       * @type {jQuery}
       */
      var $this = $(this);

      /**
       * One or more gallery elements, wrapped in a jQuery collection.
       *
       * @type {jQuery}
       */
      var $galleries;

      /**
       * An entity reference field, if present, wrapped in a jQuery collection.
       *
       * @type {jQuery}
       */
      var $entityReference = $this.parents(
        '[' + fieldAttributes.entityReference + ']'
      );

      // Bail if this is inside of an entity reference field that's handled by
      // the respective behaviour.
      if (
        $entityReference.length > 0 &&
        typeof $entityReference
          .prop('$photoswipeFieldEntityReferenceGallery') !== 'undefined'
      ) {
        return;
      }

      // If field items are to be placed in the same gallery or the field is the
      // .field__item, set the attached element as the gallery element.
      if (
        $this.attr(fieldAttributes.gallery) === 'true' ||
        $this.is('.field__item')
      ) {
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
