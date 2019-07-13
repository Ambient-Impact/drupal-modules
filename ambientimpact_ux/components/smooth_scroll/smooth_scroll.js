// -----------------------------------------------------------------------------
//   Ambient.Impact - UX - Smooth scroll component
// -----------------------------------------------------------------------------

AmbientImpact.on([
  'mediaQuery', 'displace',
], function(aiMediaQuery, aiDisplace) {
AmbientImpact.onGlobals([
  'history.state', 'TweenLite.to', 'Power4.easeOut',
], function() {
AmbientImpact.addComponent('smoothScroll', function(aiSmoothScroll, $) {
  'use strict';

  /**
   * The last two document scroll positions.
   *
   * @type {Array}
   *
   * @see https://github.com/jonaskuske/smoothscroll-anchor-polyfill/blob/master/index.js
   *   Uses this method to keep track of the last two document scroll positions
   *   to allow reseting the browser's default behaviour of instantly scrolling
   *   to a target element. The reason for keeping track of the last two
   *   positions rather than just the one is that some browsers (IE, of course)
   *   is triggered right before the hashchange event, in which case the last
   *   scroll event position would the same as the one measured on hashchange.
   */
  var lastTwoScrollPositions = [{top: 0, left: 0}, {top: 0, left: 0}];

  /**
   * The window, wrapped in a jQuery collection.
   *
   * @type {jQuery}
   */
  var $window = $(window);

  /**
   * The document, wrapped in a jQuery collection.
   *
   * @type {jQuery}
   */
  var $document = $(document);

  /**
   * The duration of the scrolling animation.
   *
   * @type {Number}
   */
  var scrollDuration = 0.8;

  /**
   * Update lastTwoScrollPositions on document scroll.
   */
  $document.on('scroll.aiSmoothScroll', function(event) {
    lastTwoScrollPositions[0] = lastTwoScrollPositions[1];
    lastTwoScrollPositions[1] = {
      top:  $window.scrollTop(),
      left: $window.scrollLeft()
    };
  });

  /**
   * hashchange event listener for smooth scrolling.
   *
   * This scrolls smoothly (if the user hasn't indicated reduced motion) to an
   * element, to the last stored scroll position if no element is found, or if
   * the position wasn't found, to the top of the page.
   *
   * @todo Should the 'hashChangeScroll' event be renamed to better match this
   * component's name or is it fine as-is?
   */
  $window.on('hashchange.aiSmoothScroll', function(event) {
    var scrollEvent = new $.Event('hashChangeScroll');

    // Trigger the hash change scroll event, allowing a handler to cancel the
    // scroll via event.preventDefault().
    $document.trigger(scrollEvent);

    // Abort if the event default action was prevented.
    if (scrollEvent.isDefaultPrevented() === true) {
      return;
    }

    /**
     * An HTML element to scroll to, or null if one is not found in the hash.
     *
     * @type {Null|HTMLElement}
     */
    var element = null;

    /**
     * An HTML element ID, if found in the hash.
     *
     * @type {String}
     */
    var elementID = location.hash.substr(1);

    /**
     * Viewport offsets as returned by the displace component.
     *
     * @type {Object}
     */
    var offsets = aiDisplace.getOffsets();

    /**
     * The options object to be passed to TweenLite.to().
     *
     * @type {Object}
     */
    var tweenOptions = {
      scrollTo: {
        // This adds top and left offsets from viewport displacement. This
        // allows the admin toolbar width/height to be taken into account, for
        // example.
        offsetX:  offsets.left,
        offsetY:  offsets.top
      },
      ease:     Power4.easeOut
    };

    // Get the last scroll position that differs from the current position. See
    // the lastTwoScrollPositions declaration at the top for why we have to do
    // this.
    var lastPosition = lastTwoScrollPositions[
      (
        lastTwoScrollPositions[1].top   === $window.scrollTop() &&
        lastTwoScrollPositions[1].left  === $window.scrollLeft()
      ) ? 0 : 1
    ];

    // Reset the scroll position to the last one, to undo the browser's default
    // behaviour of jumping to the target element.
    // @see https://developer.mozilla.org/en-US/docs/Web/API/ScrollToOptions/behavior
    window.scroll({
      top:      lastPosition.top,
      left:     lastPosition.left,
      behavior: 'auto'
    });

    // If there's a hash, try and find an element with that ID.
    if (location.hash !== '') {
      element = document.getElementById(elementID);
    }

    // If a valid element is found by looking for the ID in the hash, scroll it
    // into view.
    if (element !== null) {
      tweenOptions.scrollTo.y = '#' + elementID;
      tweenOptions.scrollTo.x = tweenOptions.scrollTo.y;

      // Trigger an event on the element as using :target to start an animation
      // on it would likely start it prematurely, so this event allows that to
      // start when the scrolling has actually come to a stop on element.
      tweenOptions.onComplete = function() {
        $(element).trigger('scrollTarget');
      };

    // Otherwise, attempt to scroll to the coordinates stored in history.state,
    // if found. If not found, just scroll to the top. Note that history.state
    // can be an empty string and also null, but checking via typeof just
    // returns 'object', so we have to check for those values explicitly.
    } else {
      if (
        history.state !== '' &&
        history.state !== null &&
        'scroll' in history.state
      ) {
        tweenOptions.scrollTo.y = history.state.scroll.top;
        tweenOptions.scrollTo.x = history.state.scroll.left;
      } else {
        tweenOptions.scrollTo.y = 0;
        tweenOptions.scrollTo.x = 0;
      }
    }

    TweenLite.to(
      window,
      // Only scroll smoothly if the browser doesn't indicate the user prefers
      // reduced motion:
      // https://ambientimpact.com/web/snippets/the-reduced-motion-media-query
      aiMediaQuery.matches(
        '(prefers-reduced-motion: reduce)'
      ) ? 0 : scrollDuration,
      tweenOptions
    );
  });

  // Don't attach the link click handler past this if history.pushState() and
  // history.replaceState() are not supported.
  if (
    !('pushState' in history) ||
    !('replaceState' in history)
  ) {
    return;
  }

  /**
   * Link click event listener for smooth scrolling.
   *
   * This replaces the current state with one that contains the current scroll
   * coordinates, pushes the hash onto a new history state, and prevents the
   * default click action so we can scroll ourselves.
   */
  $document.on('click.aiSmoothScroll', 'a[href^="#"]', function(event) {
    var $this = $(this);

    // Don't do anything and/or defer to the default action if:
    if (
      // the default action was prevented or
      event.isDefaultPrevented() ||

      // the href attribute isn't long enough to contain an ID or
      $this.attr('href').length < 2 ||

      // a modifier key was pressed during the click (to open the link in a new
      // tab, window, etc.) - note that this is a truthy check rather than a
      // strict check for the existence of and boolean true value of the various
      // event properties:
      // * https://ambientimpact.com/web/snippets/conditional-statements-and-truthy-values-robust-client-side-javascript
      // * https://developer.mozilla.org/en-US/docs/Web/API/MouseEvent/ctrlKey
      // * https://developer.mozilla.org/en-US/docs/Web/API/MouseEvent/shiftKey
      event.ctrlKey || event.shiftKey
    ) {
      return;
    }

    // This will replace the current state with one that includes the current
    // scroll coordinates, so that we can restore to this in the hashchange
    // handler if we don't have an element to scroll to. The most common case of
    // this is using the back or forward browser functionality, so we want to
    // mimic the expected native behaviour of taking the user to the last place
    // they were, rather than the top.
    var replacedState;

    // If there's an existing state, use that as a starting point, otherwise use
    // an empty object.
    if (history.state !== null) {
      replacedState = history.state;
    } else {
      replacedState = {};
    }

    // Extend the replace state with the current scroll coordinates.
    $.extend(true, replacedState, {
      scroll: {
        top:  $window.scrollTop(),
        left: $window.scrollLeft()
      }
    });

    // Replace the current state with ours.
    history.replaceState(replacedState, '', location.href);

    // Now push the new state that points to the new hash from clicking this
    // link.
    history.pushState(
      {},
      '',
      location.pathname + location.search + $this.attr('href')
    );

    // Trigger the hashchange event, as it isn't by default if we do a
    // history.pushState() and preventDefault() on this click event.
    $window.triggerHandler('hashchange');

    // Prevent the default behaviour, so we can scroll ourselves.
    event.preventDefault();
  });
});
});
});
