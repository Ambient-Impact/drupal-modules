/* -----------------------------------------------------------------------------
   Ambient.Impact - Media - PhotoSwipe component
----------------------------------------------------------------------------- */

// This component is loosely based the getting started guide of the PhotoSwipe
// documentation, which has been modified to use jQuery as much as possible, and
// providing a jQuery plugin.

// @see http://photoswipe.com/documentation/getting-started.html#dom-to-slide-objects

AmbientImpact.onGlobals(['PhotoSwipe'], function() {
AmbientImpact.on(['jquery', 'mediaQuery'], function(aiJQuery, aiMediaQuery) {
AmbientImpact.addComponent('photoswipe', function(aiPhotoSwipe, $) {
  'use strict';

  // Private counter of gallery UID, used in $.fn.PhotoSwipe to check
  // for a gallery and item in the hash on page load.
  var galleryUIDCounter = 1,
    // Private list of galleries, structure is as follows per gallery
    // object, keyed by gallery UID:

    // {
    //    PhotoSwipe  : <PhotoSwipe instance>,
    //    $gallery  : <jQuery object of root gallery element>,
    //    settings  : <settings for this gallery, use
    //            getGallerySettings() rather than access directly>
    // }
    galleries     = {};

  $.PhotoSwipe = {
    // Base class of PhotoSwipe viewer.
    baseClass:  'pswp',

    // Default settings. Passed settings are merged on top of these.
    defaults: {
      // The selector of gallery item within the the parent to consider as
      // distinct items. Note that this selector can match the gallery element
      // that PhotoSwipe is applied to, in which case the gallery itself will be
      // added as an item.
      // @todo Wouldn't it make more sense to allow a value of false or null
      // here so that a selector doesn't need to be provided in that case?
      itemSelector:     'li',

      // The selector to use when searching for links. This should only
      // match one link in each gallery item, and only links that contain
      // an image.
      linkSelector:     'a.ambientimpact-link-has-image',

      // The selector to use to find the caption element, inside of
      // itemSelector.
      captionSelector:    'figcaption',

      // If the caption element is not found, and this is set to 'title',
      // will attempt to use the title attribute of the image and then the
      // link, in that order.
      captionFallback:    'title',

      // If true, this will tell us to use the zoom and scale transitions.
      // If false, will not use the zoom and scale to avoid warped and
      // broken transitions. The default is null, which means to auto-
      // detect.
      thumbnailRatioMatches:  null,

      // The tolerance within that we should consider the thumbnail and
      // full size image as being the same ratio. This is expressed as a
      // float where 1 = 100% and 0 = 0%. The default is 0.05, which
      // equates to 5% larger or smaller.
      ratioTolerance:     0.05,

      // The transition to use. The default is 'zoom', which will zoom the
      // thumbnail into the full size. The other option is 'fade', which
      // will not zoom but fade the viewer into view. If the thumbnail
      // aspect ratio does not match the full image, or the user prefers
      // reduced motion, 'fade' will be used, even if 'zoom' is set.
      transition:       'zoom',

      // PhotoSwipe options go here:
      // http://photoswipe.com/documentation/options.html
      PhotoSwipe: {
        // This is disabled for the moment, as it's buggy. In addition,
        // using navigator.share() (if available) to replace this would
        // make a lot more sense. In that case, breaking out the
        // Download link into its own button and making the share button
        // simply invoke navigator.share() would be ideal. See:
        // https://developer.mozilla.org/en-US/docs/Web/API/Navigator/share
        shareEl:      false
      }
    },

    // Whether PhotoSwipe is open at any given time.
    isOpen: false
  };

  /**
   * Get a gallery object.
   *
   * @param {number} galleryUID
   *   The gallery UID to get the object for.
   *
   * @return {object|bool}
   *   The gallery object, or false if not found.
   *
   * @see galleries
   */
  function getGallery(galleryUID) {
    if (galleryUID in galleries) {
      return galleries[galleryUID];
    }
    return false;
  }

  /**
   * Get gallery settings, merged over defaults.
   *
   * @param {number} galleryUID
   *   The gallery UID to get the object for.
   *
   * @return {object|bool}
   *   The gallery settings object, or false if not found.
   *
   * @see getGallery()
   */
  function getGallerySettings(galleryUID) {
    var gallery = getGallery(galleryUID);

    if (gallery !== false) {
      return $.extend(true,
        {},
        $.PhotoSwipe.defaults,
        gallery.settings
      );
    }
    return false;
  };

  /**
   * Save a gallery object.
   *
   * @param {number} galleryUID
   *   The gallery UID to get the object for.
   *
   * @param {object} galleryObject
   *   The gallery object to save.
   *
   * @see galleries
   */
  function saveGallery(galleryUID, galleryObject) {
    galleries[galleryUID] = galleryObject;
  };

  /**
   * Delete a gallery object.
   *
   * @param {number} galleryUID
   *   The gallery UID to delete.
   *
   * @see galleries
   */
  function deleteGallery(galleryUID) {
    delete galleries[galleryUID];
  };

  /**
   * Parse gallery items for a given gallery.
   *
   * @param {number} galleryUID
   *   The gallery UID to get the object for.
   *
   * @return {object|bool}
   *   The gallery items, or false if an error was encountered.
   *
   * @see getGallery()
   * @see getGallerySettings()
   */
  function parseItems(galleryUID) {
    var gallery     = getGallery(galleryUID),
      gallerySettings = getGallerySettings(galleryUID),
      $items      = $(),
      items     = [];

    if (gallery === false) {
      return items;
    }

    $items = gallery.$gallery.findAndSelf(gallerySettings.itemSelector);

    $items.each(function(i) {
      var $item       = $(this),
        $link       =
          $item.find(gallerySettings.linkSelector).first(),
        $image        = $link.find('img').first(),
        item,
        src,
        linkedDimensions  = {width: 0, height: 0},
        ratios        = {linked: null, thumbnail: null};

      if ($link.length === 0) {
        console.error('Could not find a link to parse!');

        // Abort the loop as we don't want to confuse PhotoSwipe with
        // empty gallery indices.
        return false;
      }

      if ($image.length === 0) {
        console.error(
          'Could not find an image within the link to parse!'
        );

        // Abort the loop as we don't want to confuse PhotoSwipe with
        // empty gallery indices.
        return false;
      }

      // Try to load the src from the data attribute if present.
      src = $link.attr('data-photoswipe-src');

      // No data attribute? Use the link's href.
      if (src === undefined) {
        src = $link.attr('href');
      }

      // Attempt to get the destination image's width and height from data
      // attributes.
      $.each(linkedDimensions, function(type, value) {
        var dataAttributeName,
          linkedDimension;

        try {
          dataAttributeName =
            aiPhotoSwipe.settings.linkedImageAttributes[type];
        } catch (error) {
          console.error(error);
        }

        if (dataAttributeName) {
          // Note that we're using $().attr() instead of $().data(),
          // as the data attribute names are passed to us from the
          // backend with the 'data-' prefix.
          linkedDimension = $image.attr(dataAttributeName);
        }

        // Save the parsed value if it was successfully retrieved, is a
        // number (i.e. not NaN), and the value is greater than zero.
        // We're not gonna fall back to using the image's width/height
        // attributes as that doesn't seem to work correctly.
        if (
          linkedDimension !== undefined &&
          typeof parseInt(linkedDimension, 10) === 'number' &&
          parseInt(linkedDimension, 10) > 0
        ) {
          linkedDimensions[type] = parseInt(linkedDimension, 10);
        } else {
          console.error(
            'Failed to parse a valid integer! Got: "%s".',
            linkedDimension
          );
        }
      });

      // Validate item data. If one or more of these have falsey values,
      // throw an error and don't add the item to the gallery.
      if (
        !src ||
        !linkedDimensions.width ||
        !linkedDimensions.height
      ) {
        console.error(
          'Failed to parse one of the following values:' + "\n" +
          'Linked image href: %s' + "\n" +
          'Linked image width: %d (should not be 0)' + "\n" +
          'Linked image height: %d (should not be 0)',
          src,
          linkedDimensions.width,
          linkedDimensions.height
        );

        // Abort the loop as we don't want to confuse PhotoSwipe with
        // empty gallery indices.
        return false;
      }

      // Save the linked and thumbnail ratios as floats.
      ratios.linked =
        linkedDimensions.height / linkedDimensions.width;
      ratios.thumbnail =
        $image.height() / $image.width();

      // Create slide item object.
      item = {
        // This is used for finding bounds of thumbnail.
        el:   this,

        src:  src,
        w:    linkedDimensions.width,
        h:    linkedDimensions.height,

        // Whether the aspect ratio of the linked image matches that of
        // the thumbnail, within the tolerance setting. This is used by
        // aiPhotoSwipe.open() to determine if we should use the scale
        // and zoom animation, as that requires the thumbnail to match
        // the full size in ratio.
        ratioMatches:
          ratios.linked > (
            ratios.thumbnail * (1 - gallerySettings.ratioTolerance)
          ) &&
          ratios.linked < (
            ratios.thumbnail * (1 + gallerySettings.ratioTolerance)
          )
      };

      // Attempt to find the caption element, and use its contents for the
      // PhotoSwipe item caption.
      if (gallerySettings.captionSelector) {
        item.title = $item.find(gallerySettings.captionSelector).html();
      }

      // If no caption was found and the fallback is 'title', attempt to
      // use the title attribute of the image, and then the link, in that
      // order.
      if (!item.title && gallerySettings.captionFallback === 'title') {
        $.each([$image, $link], function(i, $element) {
          if (!item.title) {
            item.title = $element.attr('title');
          }
        });
      }

      // Check if the thumbnail ratio is marked as being the same as the
      // full image, and only set the thumbnail src (item.msrc) if so.
      // This tells PhotoSwipe to only scale in the thumbnail if the ratio
      // is the same. See:
      // http://photoswipe.com/documentation/faq.html#different-thumbnail-dimensions
      if (item.ratioMatches) {
                // .currentSrc gets us the current chosen image from srcset, if
        // available.
        // http://timkadlec.com/test/images/srcset/
        item.msrc = $image[0].currentSrc ?
          $image[0].currentSrc :
          $image.attr('src');
      }

      items.push(item);
    });

    return items;
  };

  /**
   * Click handler for gallery items.
   *
   * @param {event} event
   *   jQuery event object.
   *
   * @see getGallery()
   * @see getGallerySettings()
   */
  function itemClickHandler(event) {
    // Don't do anything and defer to the default action if a modifier key
    // was pressed during the click (to open the link in a new tab, window,
    // etc.) - note that this is a truthy check rather than a strict check
    // for the existence of and boolean true value of the various event
    // properties:
    // * https://ambientimpact.com/web/snippets/conditional-statements-and-truthy-values-robust-client-side-javascript
    // * https://developer.mozilla.org/en-US/docs/Web/API/MouseEvent/ctrlKey
    // * https://developer.mozilla.org/en-US/docs/Web/API/MouseEvent/shiftKey
    if (event.ctrlKey || event.shiftKey) {
      return;
    }

    var galleryUID  = event.data.galleryUID,
      gallery   = getGallery(galleryUID);

    if (gallery === false) {
      return;
    }

    var gallerySettings = getGallerySettings(galleryUID),
      $items      = gallery.$gallery
                .findAndSelf(gallerySettings.itemSelector),
      index;

    index = $items.index($(this).closest(gallerySettings.itemSelector));

    if (index > -1 && aiPhotoSwipe.open(galleryUID, index)) {
      event.preventDefault();
    }
  };

  /**
   * Open a PhotoSwipe gallery to the given item index.
   *
   * @param {number} galleryUID
   *   The gallery UID to open.
   *
   * @param {number} index
   *   The gallery item index open.
   *
   * @return {bool}
   *   True if no errors and PhotoSwipe told to open, false if an error was
   *   encountered.
   *
   * @see getGallery()
   * @see getGallerySettings()
   * @see parseItems()
   * @see saveGallery()
   */
  this.open = function(galleryUID, index) {
    var gallery     = getGallery(galleryUID),
      gallerySettings = getGallerySettings(galleryUID),
      options,
      $gallery    = gallery.$gallery,
      items;

    // Throw an error and return false if the specified gallery cannot be
    // loaded for whatever reason.
    if (gallery === false) {
      console.error('Could not load gallery with UID %d!', galleryUID);

      return false;
    }

    items = parseItems(galleryUID);

    // Throw an error and return false if the specified gallery index does
    // not exist.
    if (!items[index]) {
      console.error('Could not find index %d in gallery items!', index);

      return false;
    }

    // Define options to be passed to PhotoSwipe.
    var PhotoSwipeOptions = {
      index:    index,

      // Define gallery index (for URL).
      galleryUID: galleryUID
    };

    // If the thumbnailRatioMatches setting is set to null, attempt to auto-
    // detect whether any of the thumbnails don't match the full size ratio,
    // and set the setting to false if so.
    if (gallerySettings.thumbnailRatioMatches === null) {
      $.each(items, function(i, item) {
        if (item.ratioMatches === false) {
          gallerySettings.thumbnailRatioMatches = false;

          return false;
        }
      });

      // If we didn't set to false, assume all ratios match.
      if (gallerySettings.thumbnailRatioMatches === null) {
        gallerySettings.thumbnailRatioMatches = true;
      }
    }

    // If the thumbnail ratio doesn't match or the user indicates they
    // prefer reduced motion, override the transition to 'fade'.
    // https://ambientimpact.com/web/snippets/the-reduced-motion-media-query
    if (
      gallerySettings.thumbnailRatioMatches === false ||
      aiMediaQuery.matches('(prefers-reduced-motion)')
    ) {
      gallerySettings.transition = 'fade';
    }

    if (gallerySettings.transition === 'zoom') {
      // Get the bounds of the thumbnail for the zoom in/out effect. See
      // the 'getThumbBoundsFn' section of documentation for more info:
      // http://photoswipe.com/documentation/options.html
      PhotoSwipeOptions.getThumbBoundsFn = function(index) {
        var thumbnail = $(items[index].el).find('img')[0],
          rect    = thumbnail.getBoundingClientRect();

        return {
          x: rect.left,
          y: rect.top + $(document).scrollTop(),
          w: rect.width
        };
      };
    } else {
      // If the ratio doesn't match, use the opacity transition. This also
      // tells us not to bother hiding the original thumbnail, as that
      // would result in it being obviously missing during the transition.
      PhotoSwipeOptions.showHideOpacity = true;
    }

    // Merge in settings over defaults.
    $.extend(true, PhotoSwipeOptions, gallerySettings.PhotoSwipe);

    // Create PhotoSwipe instance.
    gallery.PhotoSwipe = new PhotoSwipe(
      $('.' + $.PhotoSwipe.baseClass)[0],
      PhotoSwipeUI_Default,
      items,
      PhotoSwipeOptions
    );


    // Trigger the before open event, allowing any bound events to modify
    // the settings before the gallery is opened.
    var beforeOpenEvent = new $.Event('PhotoSwipeBeforeOpen');
    $(document).trigger(beforeOpenEvent, [
      gallery.PhotoSwipe,
      $gallery,
      gallerySettings
    ]);


    // Save gallery.
    saveGallery(galleryUID, gallery);


    // Initialize (open) the viewer.
    gallery.PhotoSwipe.init();


    // Mark as open at this point.
    $.PhotoSwipe.isOpen = true;
    gallery.PhotoSwipe.listen('destroy', function() {
      // Mark as closed on destroy event.
      $.PhotoSwipe.isOpen = false;
    });


    // Trigger the open event.
    var openEvent = new $.Event('PhotoSwipeOpen');
    $(document).trigger(openEvent, [
      gallery.PhotoSwipe,
      $gallery,
      gallerySettings
    ]);


    // Indicate we've successfully completed this method and (hopefully)
    // PhotoSwipe is opening correctly.
    return true;
  };

  /**
   * PhotoSwipe jQuery plugin.
   *
   * This saves gallery settings, fires attach events, and attaches click
   * event to open PhotoSwipe.
   *
   * @param {object} settings
   *   Settings object to use for the gallery.
   *
   * @see $.PhotoSwipe.defaults
   * @see saveGallery()
   * @see getGallerySettings()
   */
  $.fn.PhotoSwipe = function(settings) {
    var $gallery  = this,
      galleryUID  = galleryUIDCounter,
      gallerySettings;

    // Save the gallery UID in case this is needed during events, etc.
    settings.uid = galleryUID;

    // Trigger the before attach event, allowing any bound events to modify
    // settings before the gallery is initialized.
    var beforeAttachEvent = new $.Event('PhotoSwipeBeforeAttach');
    $(document).trigger(beforeAttachEvent, [
      $gallery,
      settings
    ]);

    // Save the gallery.
    saveGallery(galleryUID, {
      $gallery: $gallery,
      settings: settings
    });

    // Grab merged settings.
    gallerySettings = getGallerySettings(galleryUID);

    // Bind event handler to the click event to open this PhotoSwipe
    // instance.
    $gallery.on(
      'click.aiPhotoSwipe',
      gallerySettings.linkSelector,
      {
        galleryUID: galleryUID
      },
      itemClickHandler
    );

    $gallery.data('PhotoSwipeGalleryUID', galleryUID);

    // Trigger the attach event.
    var attachEvent = new $.Event('PhotoSwipeAttach');
    $(document).trigger(attachEvent, [
      $gallery,
      gallerySettings
    ]);

    // Increment the gallery UID counter.
    galleryUIDCounter++;

    return $gallery;
  };


  /**
   * Destroy an element's PhotoSwipe gallery.
   *
   * This saves gallery settings, fires destroy events, detaches click event,
   * and deletes the gallery object.
   *
   * @see getGallerySettings()
   * @see deleteGallery()
   */
  $.fn.PhotoSwipeDestroy = function() {
    var $gallery    = this,
      galleryUID    = $gallery.data('PhotoSwipeGalleryUID'),
      gallerySettings = getGallerySettings(galleryUID);

    $(document).trigger('PhotoSwipeBeforeDestroy', [
      $gallery,
      gallerySettings
    ]);

    $gallery.off(
      'click.aiPhotoSwipe',
      gallerySettings.linkSelector,
      {
        galleryUID: galleryUID
      },
      itemClickHandler
    ).removeData('PhotoSwipeGalleryUID');

    deleteGallery(galleryUID);

    $(document).trigger('PhotoSwipeDestroy', [
      $gallery,
      gallerySettings
    ]);

    return $gallery;
  };
});
});
});
