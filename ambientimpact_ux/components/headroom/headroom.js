// -----------------------------------------------------------------------------
//   Ambient.Impact - UX - Headroom.js component
// -----------------------------------------------------------------------------

// This attaches a Headroom.js instance to a given element while providing some
// improvements:
//
// - Callbacks are replaced with events fired on the element.
//
// - Adds 'headroomFreeze' and 'headroomUnfreeze' events.
//
// - Adds the ability to pin the element if anything inside of it gains focus.
//   This can be disabled by passing 'pinOnFocus: false' to the init method.
//
// - Some default options are provided. These can be overridden.
//
// @see http://wicky.nillia.ms/headroom.js/
//   Lists Headroom.js options.

AmbientImpact.onGlobals(['Headroom'], function() {

  // Don't do anything if Headroom can't be used due to lack of browser support.
  // This primarily targets IE at the moment.
  //
  // @see https://github.com/WickyNilliams/headroom.js/releases/tag/v0.10.3
  //
  // @see https://github.com/WickyNilliams/headroom.js/issues/347
  //   This may be renamed to Headroom.isSupported when 1.0 is released.
  if (
    AmbientImpact.objectPathExists('Headroom.cutsTheMustard') &&
    Headroom.cutsTheMustard === false
  ) {
    return;
  }

AmbientImpact.addComponent('headroom', function(aiHeadroom, $) {
  'use strict';

  /**
   * Default Headroom.js settings passed to the Headroom() constructor.
   *
   * The following are provided by default:
   *
   * - tolerance: this is set to a non-zero value to give scrolling a slight
   *   margin of error to avoid pinning/unpinning due to small scrolls, e.g. by
   *   accident.
   *
   * - pinOnFocus: if true (the default), will automatically pin the Headroom.js
   *   instance if focus moves into the element.
   *
   * @type {Object}
   *
   * @see this.init()
   *
   * @see http://wicky.nillia.ms/headroom.js/
   *   Lists Headroom.js options.
   */
  var defaults = {
    tolerance:  5,
    pinOnFocus: true,
  };

  /**
   * Callback names to generate events from.
   *
   * This is used to map the Headroom.js callbacks to events that are triggered
   * on Headroom.js-enabled elements.
   *
   * @type {Array}
   *
   * @see this.init()
   */
  var callbackNames = [
    'Pin',
    'Unpin',
    'Top',
    'NotTop',
    'Bottom',
    'NotBottom',
  ];

  /**
   * Initialize a Headroom.js instance.
   *
   * This is a wrapper around creating a Headroom.js instance. It attaches the
   * instance to element.headroom, and provides some default options.
   *
   * @param {HTMLElement|jQuery} element
   *   An HTML element or jQuery collection containing an element.
   *
   * @param {object} options
   *   The options to pass to Headroom.js. These are merged over top of the
   *   defaults defined above.
   *
   * @see defaults
   *
   * @see callbackNames
   */
  this.init = function(element, options) {
    /**
     * The provided element wrapped in a jQuery collection.
     *
     * @type {jQuery}
     */
    var $element = $(element);

    /**
     * The settings that will be passed to Headroom.js.
     *
     * @type {Object}
     */
    var settings;

    /**
     * This element's original Headroom.js freeze method.
     *
     * @type {Function}
     */
    var originalFreeze;

    /**
     * This element's original Headroom.js unfreeze method.
     *
     * @type {Function}
     */
    var originalUnfreeze;

    /**
     * This element's original Headroom.js destroy method.
     *
     * @type {Function}
     */
    var originalDestroy;

    // Make sure options is an object.
    if (typeof options !== 'object') {
      options = {};
    }

    // If no offset was specified, use the element's offset as read by jQuery.
    // We're using Math.floor() to avoid possible 1 pixel gaps along the top
    // edge in some rendering engines, e.g. Chromium/Blink.
    if (!('offset' in options)) {
      options.offset = Math.floor($element.offset().top);
    }

    // Merge in options over defaults.
    settings = $.extend(true, {}, defaults, options);

    // Make sure element is actually an HTML element and not a jQuery object.
    element = $element[0];

    // If an active Headroom.js instance already exists on the element, destroy
    // it.
    if (
      AmbientImpact.objectPathExists('headroom.destroy', element) &&
      typeof element.headroom.destroy === 'function'
    ) {
      element.headroom.destroy();
    }

    // Trigger events on all the Headroom.js callbacks on the element. This
    // converts 'on<eventName>' to 'headroom<eventName>'. See callbackNames for
    // the event names.
    $.each(callbackNames, function(i, name) {
      settings['on' + name] = function() {
        $element.trigger('headroom' + name);
      };
    });

    // Initialize the new instance.
    element.headroom = new Headroom(element, settings);
    element.headroom.init();

    // Save a reference to the original Headroom.js freeze method.
    originalFreeze = element.headroom.freeze;

    // Wrap Headroom.js' freeze method with our own, allowing us to trigger an
    // event.
    element.headroom.freeze = function() {
      // Don't do anything if currently frozen so that event handlers don't have
      // to check this and avoids potential infinite recursion.
      if (element.headroom.frozen === true) {
        return;
      }

      originalFreeze.apply(element.headroom);

      $element.trigger('headroomFreeze');
    };

    // Save a reference to the original Headroom.js unfreeze method.
    originalUnfreeze = element.headroom.unfreeze;

    // Wrap Headroom.js' unfreeze method with our own, allowing us to trigger an
    // event.
    element.headroom.unfreeze = function() {
      // Don't do anything if not currently frozen so that event handlers don't have
      // to check this and avoids potential infinite recursion.
      if (element.headroom.frozen === false) {
        return;
      }

      originalUnfreeze.apply(element.headroom);

      $element.trigger('headroomUnfreeze');
    };

    // Pin and freeze the element on focus, and unfreeze it when focus leaves.
    // Note that 'focus' and 'blur' events don't bubble up the DOM tree, but
    // 'focusin' and 'focusout' do.
    if (settings.pinOnFocus === true) {
      $element
        .on('focusin.aiHeadroom', function(event) {
          this.headroom.pin();
          this.headroom.freeze();
        })
        .on('focusout.aiHeadroom', function(event) {
          this.headroom.unfreeze();
        });
    }

    // Save a reference to the original Headroom.js destroy method.
    originalDestroy = element.headroom.destroy;

    // Wrap Headroom.js' destroy method with our own, allowing us to trigger
    // events before and after destruction.
    element.headroom.destroy = function() {
      $element
        .trigger('headroomBeforeDestroy')
        .off([
          'focusin.aiHeadroom',
          'focusout.aiHeadroom',
        ].join(' '));

      originalDestroy.apply(element.headroom);

      $element.trigger('headroomDestroy');

      // Remove our object from the element.
      delete element.headroom;
    };
  };
});
});
