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
   * Whether to scale the scroll duration based on the distance travelled.
   *
   * @type {Boolean}
   */
  this.useAdaptiveScrollDuration = true;

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
   * The base duration of the scrolling animation, in seconds.
   *
   * This duration value is used to determine the actual scrolling duration,
   * which takes this and increases it based on the distance travelled. This
   * value can most simply be thought of as the duration for scrolling one
   * viewport width or height.
   *
   * @type {Number}
   */
  var baseScrollDuration = 0.8;

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
   * An HTML element to scroll to, if any, wrapped in a jQuery collection.
   *
   * @type {jQuery}
   */
    var $element = $();

    /**
     * An HTML element ID, if found in the hash.
     *
     * @type {String}
     */
    var elementID = location.hash.substr(1);

    /**
     * Viewport displacement offsets as returned by the displace component.
     *
     * @type {Object}
     */
    var displacementOffsets = aiDisplace.getOffsets();

    /**
     * Data for scrolling the viewport, grouped by X and Y axes.
     *
     * Purpose of each key:
     *
     * - size: the size of the viewport in this axis, minus viewport
     *   displacement values, if any.
     *
     * - scrollTo: the scroll value to scroll to.
     *
     * - distance: the distance that scrolling to scrollTo will cover, from the
     *   current scroll position.
     *
     * - offsetName: the name of the element offset to grab for this axis. Also
     *   doubles as the name of the viewport offset value to pass to GSAP for
     *   the offsetX/offsetY settings.
     *
     * - duration: the duration of the scrolling for this axis. If adaptive
     *   scroll duration is enabled, this will be calculated based on the
     *   distance travelled in the context of the to the viewport size.
     *
     * @type {Object}
     */
    var viewportData = {
      x: {
        size:
          $window.width() - displacementOffsets.left -
          displacementOffsets.right,
        scrollTo:   0,
        distance:   0,
        offsetName: 'left',
        duration:   0
      },
      y: {
        size:
          $window.height() - displacementOffsets.top -
          displacementOffsets.bottom,
        scrollTo:   0,
        distance:   0,
        offsetName: 'top',
        duration:   0
      }
    };

    /**
     * The duration of the scrolling animation, in seconds.
     *
     * This defaults to zero and is only given a non-zero value if the
     * '(prefers-reduced-motion: reduce)' media query doesn't match.
     *
     * @type {Number}
     */
    var scrollDuration = 0;

    /**
     * The options object to be passed to TweenLite.to().
     *
     * @type {Object}
     */
    var tweenOptions = {
      scrollTo: {},
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
      element   = document.getElementById(elementID);
      $element  = $(element);
    }

    // If a valid element is found by looking for the ID in the hash, have GSAP
    // scroll it into view.
    if (element !== null) {
      tweenOptions.scrollTo.y = '#' + elementID;
      tweenOptions.scrollTo.x = tweenOptions.scrollTo.y;

      // Trigger an event on the element as using :target to start an animation
      // on it would likely start it prematurely, so this event allows that to
      // start when the scrolling has actually come to a stop on element.
      tweenOptions.onComplete = function() {
        $element.trigger('scrollTarget');
      };

    // Otherwise, set the scroll coordinates to those stored in history.state,
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

    // Only scroll smoothly if the browser doesn't indicate the user prefers
    // reduced motion:
    // https://ambientimpact.com/web/snippets/the-reduced-motion-media-query
    if (!aiMediaQuery.matches('(prefers-reduced-motion: reduce)')) {
      for (var directionName in viewportData) {
        if (!viewportData.hasOwnProperty(directionName)) {
          continue;
        }

        if (element !== null) {
          viewportData[directionName].scrollTo = $element.offset()[
            viewportData[directionName].offsetName
          ];
        } else if (typeof tweenOptions.scrollTo[directionName] === 'number') {
          viewportData[directionName].scrollTo =
            tweenOptions.scrollTo[directionName];
        }

        if (aiSmoothScroll.useAdaptiveScrollDuration === true) {
          // Calculate the distance this scroll will cover.
          viewportData[directionName].distance = Math.abs(
            lastPosition[viewportData[directionName].offsetName] -
            viewportData[directionName].scrollTo
          );

          // Calculate the duration based on the distance to be scrolled.
          viewportData[directionName].duration =
            viewportData[directionName].distance /
            viewportData[directionName].size * baseScrollDuration;
        }
      }

      // If adaptive scroll duration is enabled, set the duration to whichever
      // is longer of either X or Y.
      if (aiSmoothScroll.useAdaptiveScrollDuration === true) {
        scrollDuration = Math.max(
          viewportData.x.duration, viewportData.y.duration
        );

      // If adaptive scroll duration is disabled, just use the base scroll
      // duration value.
      } else {
        scrollDuration = baseScrollDuration;
      }
    }

    for (var directionName in viewportData) {
      if (!viewportData.hasOwnProperty(directionName)) {
        continue;
      }

      // If the location to scroll to is greather than or equal to the relevant
      // displacement offset, tell GSAP to offset the scroll on this axis. This
      // is done conditionally here, because GSAP doesn't seem to adjust the
      // animation when the location to scroll to is at the top, for example,
      // and so ends up hitting the top ungracefully without fully easing out.
      if (
        viewportData[directionName].scrollTo >= displacementOffsets[
          viewportData[directionName].offsetName
        ]
      ) {
        tweenOptions.scrollTo['offset' + directionName.toUpperCase()] =
          displacementOffsets[viewportData[directionName].offsetName];
      }
    }

    TweenLite.to(
      window,
      scrollDuration,
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
