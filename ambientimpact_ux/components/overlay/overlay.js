// -----------------------------------------------------------------------------
//   Ambient.Impact - UX - Overlay component
// -----------------------------------------------------------------------------

AmbientImpact.onGlobals([
  'ally.maintain.disabled',
  'Modernizr.csstransitions',
], function() {
AmbientImpact.addComponent('overlay', function(aiOverlay, $) {
  'use strict';

  /**
   * Base class for the overlay element.
   *
   * @type {String}
   */
  var baseClass = 'overlay';

  /**
   * Active class applied to the overlay element when the overlay is shown.
   *
   * @type {String}
   */
  var activeClass = baseClass + '--is-active';

  /**
   * Default settings for new overlays.
   *
   * The following keys are supported:
   *
   * - elementType: the type of HTML element of the newly created overlay
   *   element.
   *
   * - modal: if true, will use ally.maintain.disabled() to disable focusable
   *   elements within the 'modalContext' key, excluding the 'modalFilter' key.
   *
   * - modalContext: the scope within which to search for focusable elements.
   *   Defaults to document.documentElement. This is passed to
   *   ally.maintain.disabled() as the 'context' option. Only applied if the
   *   'modal' option is set to true.
   *
   * - modalFilter: an element whose tree is not to be searched for focusable
   *   elements to disable, excluding it from being disabled. This is passed to
   *   ally.maintain.disabled() as the 'filter' option. Only applied if the
   *   'modal' option is set to true.
   *
   * @type {Object}
   *
   * @see https://allyjs.io/api/maintain/disabled.html
   *   ally.maintain.disabled() documentation.
   */
  this.defaults = {
    elementType:  'div',
    modal:        false,
    modalContext: document.documentElement,
    modalFilter:  null
  };

  /**
   * Get a Promise to delay ally.maintain.disabled() until resolved.
   *
   * @param {Promise|undefined} disabledPromise
   *   Either a Promise or undefined.
   *
   * @return {Promise}
   *   If disabledPromise looks like a Promise, will return that; if not, will
   *   return a new Promise that's immediately resolved.
   */
  function getDisabledPromise(disabledPromise) {
    // If disabledPromise looks like a Promise, return it as-is.
    if ('then' in disabledPromise) {
      return disabledPromise;

    // Otherwise, return a new Promise that's immediately resolved.
    } else {
      return new Promise(function(resolve, reject) {
        resolve();
      });
    }
  }

  /**
   * Show an overlay.
   *
   * @param {Promise|undefined} disabledPromise
   *   Either a Promise to delay invoking ally.maintain.disabled() or undefined
   *   to invoke ally.maintain.disabled() immediately.
   *
   * @see getDisabledPromise()
   *   disabledPromise is passed through this to ensure we always have a Promise
   *   object, either a provided Promise or a new one that's immediately
   *   resolved.
   */
  function show(disabledPromise) {
    this.$overlay.addClass(activeClass);

    if (this.settings.modal === false) {
      return;
    }

    disabledPromise = getDisabledPromise(disabledPromise);

    var data = this;

    disabledPromise.then(function() {
      data.disabledHandle = ally.maintain.disabled({
        filter:   data.settings.modalFilter,
        context:  data.settings.modalContext
      });
    });
  };

  /**
   * Hide an overlay.
   *
   * @param {Promise|undefined} disabledPromise
   *   Either a Promise to delay disengaging ally.maintain.disabled() or
   *   undefined to disengage ally.maintain.disabled() immediately.
   *
   * @see getDisabledPromise()
   *   disabledPromise is passed through this to ensure we always have a Promise
   *   object, either a provided Promise or a new one that's immediately
   *   resolved.
   */
  function hide(disabledPromise) {
    this.$overlay.removeClass(activeClass);

    if (
      this.settings.modal === false ||
      this.disabledHandle === null ||
      !('disengage' in this.disabledHandle)
    ) {
      return;
    }

    disabledPromise = getDisabledPromise(disabledPromise);

    var data = this;

    disabledPromise.then(function() {
      data.disabledHandle.disengage();

      data.disabledHandle = null;
    });
  };

  /**
   * Determine if an overlay is currently active, i.e. visible.
   *
   * @return {Boolean}
   *   True if active, false otherwise.
   */
  function isActive() {
    return this.$overlay.hasClass(activeClass);
  };

  /**
   * Destroy an overlay.
   *
   * @see this.create()
   *   Creates an overlay.
   */
  function destroy() {
    this.$overlay.remove();

    // If the ally.maintain.disabled handle is found, disengage the service.
    // This can happen if the overlay is destroyed while open and modal.
    if (
      this.disabledHandle !== null &&
      'disengage' in this.disabledHandle
    ) {
      this.disabledHandle.disengage();

      this.disabledHandle = null;
    }

    // We don't really need to remove the aiOverlay object from the overlay
    // element since we're removing it from the DOM anyways and it's not
    // intended to be used after this point, but we might as well to remove the
    // API so unexpected things don't happen.
    delete this.$overlay[0].aiOverlay;
  };

  /**
   * Create an overlay.
   *
   * @param {Object} options
   *
   * @return {jQuery}
   *   An overlay element, wrapped in a jQuery collection, with API and settings
   *   available under the 'aiOverlay' DOM property attached to the element,
   *   with the following keys:
   *
   *   - $overlay: the overlay element, wrapped in a jQuery collection; mostly
   *     for internal use.
   *
   *   - show: method to show the overlay, with optional Promise parameter to
   *     delay invokation of ally.maintain.disabled() until resolved.
   *
   *   - hide: method to hide the overlay, with  optional Promise parameter to
   *     delay disengaging of ally.maintain.disabled() until resolved.
   *
   *   - isActive: method to determine if the overlay is currently active, i.e.
   *     visible; returns true if active, false otherwise.
   *
   *   - destroy: method to destroy the overlay element and functionality; will
   *     remove this DOM property and remove the overlay element from the DOM.
   *
   *   - disabledHandle: the ally.maintain.disabled() service handle, or null if
   *     none is currently active, e.g. when the overlay is not active or is
   *     active but not set to be modal.
   *
   *   - settings: an object containing the settings for this overlay instance;
   *     is the result of merging the 'options' parameter on top of
   *     this.defaults with jQuery.extend().
   *
   * @see this.defaults
   *   Default values for the options parameter.
   *
   * @see destroy()
   *   Destroys a created overlay.
   */
  this.create = function(options) {
    if (!options) {
      options = {};
    }

    var settings = $.extend(true, {}, this.defaults, options);

    var $overlay = $(document.createElement(settings.elementType));

    $overlay.addClass(baseClass);

    $overlay[0].aiOverlay = {
      $overlay:       $overlay,
      show:           show,
      hide:           hide,
      isActive:       isActive,
      destroy:        destroy,
      disabledHandle: null,
      settings:       settings
    };

    return $overlay;
  };
});
});
