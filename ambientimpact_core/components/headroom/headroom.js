// -----------------------------------------------------------------------------
//   Ambient.Impact - Core - Headroom.js component
// -----------------------------------------------------------------------------

// This attaches a Headroom.js instance to a given element, with some
// modifications:
// * Callbacks are replaced with events fired on the element.
//
// * Some default options are provided. These can be overridden.
//
// See http://wicky.nillia.ms/headroom.js/ for more info.
//
// @todo Add option to stick to top or bottom, and change settings accordingly?

AmbientImpact.onGlobals(['Headroom'], function() {
AmbientImpact.on([
  'environment', 'event.lazyResize',
], function(aiEnvironment, ailazyResize) {

  // Don't do anything if position:sticky; is not supported as we use this to
  // avoid having to write complicated code/DOM interactions that behave
  // similarily. If this is a dealbreaker in the future, we could use the
  // 'stickyfill' polyfill library.
  if (!aiEnvironment.cssSupports(
    'position', 'sticky'
  )) {
    return;
  }
AmbientImpact.addComponent('headroom', function(aiHeadroom, $) {
  'use strict';

  var defaults = {
    // This gives the scrolling a slight margin of error so that
    // scrolling up or down less than this (usually by accident) does
    // not cause a state change.
    tolerance:  5,

    // Whether to use Drupal.displace to shift the top of the element.
    displace: true
  },
  // These are the callback names that we use to generate events from
  // Headroom.js' callbacks. See this.init().
  callbackNames = [
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
   * @see callbackNames
   */
  this.init = function(element, options) {
    var $element  = $(element),
        settings,
        originalDestroy;

    // Make sure options is an object.
    if (typeof options !== 'object') {
      options = {};
    }

    // If no offset was specified, use the element's offset as read by jQuery.
    // We're using Math.floor() to avoid possible 1 pixel gaps along the top
    // edge in some rendering engines, e.g. Blink.
    if (!('offset' in options)) {
      options.offset = Math.floor($element.offset().top);
    }

    // Merge in options over defaults.
    settings = $.extend(true, {}, defaults, options);

    // Make sure element is actually an HTML element and not a jQuery object.
    element = $element[0];

    // If an active Headroom already exists, destroy it.
    if (
      element.headroom &&
      element.headroom.destroy &&
      typeof element.headroom.destroy === 'function'
    ) {
      element.headroom.destroy();
    }

    // Trigger events on all the Headroom callbacks on the element. This
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

    // Wrap Headroom's destroy method with our own, allowing us to fire events.
    originalDestroy = element.headroom.destroy;
    element.headroom.destroy = function() {
      $element.trigger('headroomBeforeDestroy');

      originalDestroy.apply(element.headroom);

      $element.trigger('headroomDestroy');

      // Remove our object from the element.
      delete element.headroom;
    };

    // Create an object to store handlers in, so we can unbind just this
    // element's handlers.
    element.headroom.aiHandlers = {};

    // If we're told to take viewport displacement into account, do so.
    if (settings.displace) {
      element.headroom.aiHandlers.displace = function(event, offsets) {
        // We're using Math.floor() to avoid rounding errors in some browsers.
        // Without this, a 1 pixel gap may appear, depending on the rendering
        // engine of the browser. Blink can do this, and likely others may too.
        var topOffset = Math.floor(offsets.top);

        $element.css('top', topOffset + 'px');

        // If the browser supports custom properties, set the offset as one for
        // the CSS to use, e.g. in immerse mode.
        element.style.setProperty('--offset-top', topOffset + 'px');
      };

      // If offsets are available, fire once. This is needed if we're
      // reinitializing, so that changes to displacement from resizing are taken
      // into account.
      if (AmbientImpact.objectPathExists('Drupal.displace.offsets.top')) {
        element.headroom.aiHandlers.displace(
          {},
          Drupal.displace.offsets
        );
      }

      $(document).on(
        'drupalViewportOffsetChange.aiHeadroom',
        element.headroom.aiHandlers.displace
      );

      // Remove the handler, the 'top' CSS, and the offset custom property on
      // destroy().
      $element.one('headroomDestroy', function(event) {
        $(document).off(
          'drupalViewportOffsetChange.aiHeadroom',
          element.headroom.aiHandlers.displace
        );

        $element.css('top', '');

        element.style.removeProperty('--offset-top');
      });
    }

    // Re-init on lazy resize and orientation change events.
    element.headroom.aiHandlers.resize = function(event) {
      aiHeadroom.init(element, options);
    };

    $(window).on([
      'lazyResize.aiHeadroom',
      'orientationchange.aiHeadroom'
    ].join(' '), element.headroom.aiHandlers.resize);

    $element.one('headroomDestroy', function(event) {
      $(window).off([
        'lazyResize.aiHeadroom',
        'orientationchange.aiHeadroom'
      ].join(' '), element.headroom.aiHandlers.resize);
    });
  };
});
});
});
