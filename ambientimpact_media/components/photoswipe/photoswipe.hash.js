/* -----------------------------------------------------------------------------
   Ambient.Impact - Core - PhotoSwipe URL hash component
----------------------------------------------------------------------------- */

AmbientImpact.on(['photoswipe'], function(aiPhotoSwipe) {
AmbientImpact.addComponent(
  'photoswipe.hash',
function(aiPhotoSwipeHash, $) {
  'use strict';

  /**
   * Attempt to extract gallery UID and item ID from URL hash.
   *
   * @param {String} hash
   *   An option hash to use. If not passed, will use window.location.hash.
   *
   * @returns {object}
   *   Object containing 'gid' and 'pid', if found.
   */
  var parseHash = function(hash) {
    var hash,
      params  = {};

    if (typeof hash === 'undefined') {
      hash = window.location.hash.substring(1)
    }

    if(hash.length < 5) {
      return params;
    }

    var vars = hash.split('&');
    for (var i = 0; i < vars.length; i++) {
      if(!vars[i]) {
        continue;
      }
      var pair = vars[i].split('=');
      if(pair.length < 2) {
        continue;
      }
      params[pair[0]] = pair[1];
    }

    if(params.gid) {
      params.gid = parseInt(params.gid, 10);
    }

    if(!params.hasOwnProperty('pid')) {
      return params;
    }
    params.pid = parseInt(params.pid, 10);

    return params;
  };

  /**
   * Open a PhotoSwipe gallery if the URL hash matches the gallery UID.
   *
   * @param {int} galleryUID  - The gallery UID to test for.
   */
  var openOnHash = function(galleryUID) {
    var hashData = parseHash();

    var openHandler = function() {
      if (
        hashData.pid > 0 &&
        hashData.gid === galleryUID
      ) {
        aiPhotoSwipe.open(
          galleryUID,
          hashData.pid - 1
        );
      }
    }

    // if ($.PhotoSwipe.isOpen === true) {
    //  var gallery = getGallery(galleryUID);
    //  if (
    //    // If PhotoSwipe instance is available,
    //    'PhotoSwipe' in gallery &&
    //    // the parsed hash data has a galleryUID, and
    //    'gid' in hashData &&
    //    // the parsed galleryUID does not match the current one.
    //    hashData.gid !== galleryUID
    //  ) {
    //    // TO DO: this can cause PhotoSwipe to choke, so switching from
    //    // one gallery directly to another (one valid hash to another,
    //    // without a close in between) is disabled for now

    //    /*console.log(gallery, galleryUID, hashData.gid);
    //    gallery.PhotoSwipe.listen('destroy', function() {
    //      setTimeout(openHandler, 4000);
    //    });
    //    gallery.PhotoSwipe.close();*/
    //  } else {
    //    openHandler();
    //  }
    // } else {
      openHandler();
    // }
  };

  $(document).on('PhotoSwipeAttach.aiPhotoSwipeHash', function(
    event, $gallery, gallerySettings
  ) {
    // Open gallery immediately if a hash exists in the URL and matches this
    // gallery and a valid item in it.
    openOnHash(gallerySettings.uid);

    // Attach a hashchange event to open this gallery if the user navigates
    // via the browser's back and forward functions.
    $(window).on('hashchange.aiPhotoSwipeHash', function(event) {
      openOnHash(gallerySettings.uid);
    });
  });

  AmbientImpact.on(['hashScroll'], function(aiHashScroll) {
    var lastHash = location.hash;

    // This prevents scrolling if either the current hash or the previously
    // recorded hash looks like it points to a PhotoSwipe gallery.
    $(document).on('hashChangeScroll.aiPhotoSwipeHash', function(event) {
      if (
        'gid' in parseHash(lastHash) ||
        'gid' in parseHash(location.hash)
      ) {
        event.preventDefault();
      }

      lastHash = location.hash;
    });

    $(document).on('PhotoSwipeInitialZoomInEnd.aiPhotoSwipeHash', function(
      event, gallery, $gallery, gallerySettings
    ) {
      // Cancel the hash change scroll on PhotoSwipe close.
      // There are two ways to close PhotoSwipe:
      // 1) via the UI
      // 2) by hitting back, thus removing the hash
      //
      // In 1), the hash changes after PhotoSwipe has been destroyed.
      // In 2), the hash changes before the zoom out animation has been
      // triggered.
      //
      // Order of events:
      // - <possible back button, hash removed>
      // - Event: initialZoomOut
      // - Event: destroy
      // - <hash is removed by PhotoSwipe>
      //
      // tl;dr we can't depend on 'initialZoomOut' or 'destroy' for
      // binding, so we have to bind on 'initialZoomInEnd' to make sure
      // we catch both cases.

      // Only fire once. PhotoSwipe uses history.replaceState(), so no
      // hashchange events are fired until it is closing or already
      // closed, depending on how it was triggered.
      $(document).one(
        'hashChangeScroll.aiPhotoSwipeHash',
      function(event) {
        event.preventDefault();
      });
    });
  });
});
});
