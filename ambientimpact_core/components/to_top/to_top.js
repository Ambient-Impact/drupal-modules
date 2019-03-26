/* -----------------------------------------------------------------------------
   Ambient.Impact - Core - Scroll to top component
----------------------------------------------------------------------------- */

// This creates a floating button that scrolls to the top of the screen when
// activated. The button (technically a link to #top) will only show if
// scrolling up, the viewport has been scrolled down enough to merit it, and no
// text field is focused (to avoid blocking one on narrow screens).

AmbientImpact.onGlobals([
  'Headroom',
  'Modernizr.csstransitions',
  'ally.get.activeElement',
], function() {
AmbientImpact.on([
  'jquery',
  'mediaQuery',
], function(aijQuery, aiMediaQuery) {
AmbientImpact.addComponent('toTop', function(aiToTop, $) {
  'use strict';

  // Container classes.
  this.baseClass    = 'to-top';
  this.hiddenClass  = this.baseClass + '--hidden';
  this.linkClass    = this.baseClass + '__link';

  var $container    = $('<div></div>'),
      $link         = $('<a></a>'),

    // The Headroom.js instance.
    headroom,

    // The threshold in pixels scrolled down to show the container. This is set
    // to the screen height at the start of every call to handleVisibility().
    scrollShowThreshold,

    // The multiplier to use to determine the area within which to not show the
    // container as it would result in showing and then immediately hiding on
    // upward scroll. See handleVisibility().
    scrollShowIgnoreFactor  = 1.5,

    // The direction Headroom.js informs us we're scrolling. If 'down',
    // handleVisibility() will not show the container, and hide it if visible;
    // if 'up', will show the container after checking other criteria. Starts
    // with 'down' so we don't show the container on init until needed.
    scrollDirection     = 'down';

  // Set link attributes and text, and append to the container.
  $link
    .attr('href', '#' + this.settings.topAnchorID)
    .attr('title', Drupal.t('Go to the top'))
    .addClass(this.linkClass)
    .text(Drupal.t('Top'))
    .appendTo($container);

  // Add class to the container and append it to the body.
  $container
    .addClass(this.baseClass)
    .appendTo('body');

  AmbientImpact.on('icon', function(aiIcon) {
    // Wrap the text in an icon, with visually hidden text.
    $link.wrapTextWithIcon('arrow-up', {
      bundle:       'core',
      textDisplay:  'visuallyHidden'
    });

    // Add Material ripple. This has to be here to not get trashed by the text
    // wrapping above.
    AmbientImpact.on('material.ripple', function() {
      this.add({
        selector: '.' + aiToTop.baseClass + ' a',
        isDark:   true
      });
    });
  });

  // Scroll to top on link click.
  $link.on('click.aiToTop', function(event) {
    // Remove the hash when scrolling up, in case a jump-to-section link was
    // used.
    // http://stackoverflow.com/a/5298684
    if (window.location.hash && 'pushState' in history) {
      history.pushState(
        '',
        document.title,
        window.location.pathname + window.location.search
      );
    }

    var scrollObject = {top: 0, left: 0};

    // Only smoothly scroll if the browser doesn't indicate the user prefers
    // reduced motion:
    // https://ambientimpact.com/web/snippets/the-reduced-motion-media-query
    if (!aiMediaQuery.matches('(prefers-reduced-motion)')) {
      scrollObject.behavior = 'smooth';
    }

    window.scroll(scrollObject);

    // The link has #top as the href, but we're going to prevent that being add
    // to the URL to not mess with history states and keep the URL clean.
    event.preventDefault();
  });

  $container.on({
    // Show even handler. This is naive to any conditions, so only call this to
    // definitely show the container.
    'show.aiToTop': function(event) {
      // Remove the hidden attribute.
      $container.removeAttr('hidden');

      // We need to delay the removal of the class until the next paint, so that
      // the browser has a chance to paint the container as unhidden, otherwise
      // transitions won't run.
      if (window.requestAnimationFrame) {
        // At this point we haven't painted a new frame yet.
        window.requestAnimationFrame(function(timestamp) {
          // Now we have, so we remove on the next frame.
          window.requestAnimationFrame(function(timestamp) {
            $container.removeClass(aiToTop.hiddenClass);
          });
        });
      } else {
        // Timeout fallback.
        setTimeout(function() {
          $container.removeClass(aiToTop.hiddenClass);
        }, 20);
      }
    },
    // Hide the container. Again, this does not perform any checks.
    'hide.aiToTop': function(event) {
      $container.addClass(aiToTop.hiddenClass);

      // Manually trigger the transitionend handler if transitions are not
      // supported so that we still properly hide the container.
      if (!Modernizr.csstransitions) {
        $container.trigger('transitionend.aiToTop');
      }
    },
    'transitionend.aiToTop': function(event) {
      // Hide the container from everything, including screen readers, at the
      // end of a transition if the hidden class is set.
      if ($container.is('.' + aiToTop.hiddenClass)) {
        $container.attr('hidden', 'hidden');
      }
    }
  });

  /**
   * Show or hide the container depending on various conditions.
   *
   * This checks the following conditions, and if all are true, will show the
   * container. If any of them are false, the container will be hidden instead:
   *
   * * Is the user scrolling upwards?
   *
   * * Is the current window scroll position outside of the ignore area? This
   *   prevents the container being shown and then immediately hidden again if
   *   an upward scroll was initiated close to the scroll threshold. Note that
   *   Headroom.js is in charge of the hiding on the actual threshold, and will
   *   fire this on that.
   *
   * * Is the currently focused element not a text input?
   */
  function handleVisibility() {
    var scrollTop   = $(window).scrollTop();

    // Should we show the container?
    if (
      // Are we scrolling up?
      scrollDirection === 'up' &&
      // Is the scroll position not within the ignore area?
      scrollTop > scrollShowThreshold * scrollShowIgnoreFactor &&
      // Is there no text input currently focused?
      !$(ally.get.activeElement()).is('input:textall, textarea')
    ) {
      $container.trigger('show.aiToTop');

    // If not, hide the container.
    } else {
      $container.trigger('hide.aiToTop');
    }
  };

  // Run once on ready to show or hide depending on conditions.
  handleVisibility();
  // Immediately trigger the transitionend event so that the 'hidden' attribute
  // is added (if handleVisibility() set the hidden class) and no transition
  // occurs.
  $container.trigger('transitionend.aiToTop');

  // Handle visibility on text input focus/blur.
  $('body').on(
    'focus.aiToTop blur.aiToTop',
    'input:textall, textarea',
    function(event) {
      // Use a timeout to delay checking the active element. This ensures we
      // don't incorrectly show the widget when the user blurs on text field by
      // focusing another one.
      setTimeout(handleVisibility);
    }
  );

  // Hide unconditionally on immerseEnter.
  $(document).on('immerseEnter.aiToTop', function(event, element) {
    $container.trigger('hide.aiToTop');

  // Show if allowed on immerseExit.
  }).on('immerseExit.aiToTop', function(event, element) {
    handleVisibility();
  });

  /**
   * Initialize our Headroom.js instance, destroying any previous instance.
   *
   * This (re-)initializes our Headroom.js instance, updating the scroll
   * threshold before it does so, to account for viewport size changes.
   */
  function initHeadroom() {
    if (headroom && headroom.destroy) {
      headroom.destroy();
    }

    // Update the scroll threshold based on window height, in case the user
    // resizes the window or rotates the screen, e.g. on mobile devices.
    scrollShowThreshold = $(window).height();

    // Initialize Headroom instance to act on scroll direction and to detect
    // when we pass the scroll threshold.
    // http://wicky.nillia.ms/headroom.js/
    headroom = new Headroom($container[0], {
      // This is the threshold from the top of the window/parent that determines
      // if we're considered at the top or not. Above this fires the onTop
      // handler.
      offset:   scrollShowThreshold,
      // This gives the scrolling a slight margin of error so that scrolling up
      // or down less than this (usually by accident) does not cause a state
      // change.
      tolerance:  5,

      // Specify our own classes so we don't inherit the headroom
      // component styles.
      classes: {
        initial:    aiToTop.baseClass,
        pinned:     aiToTop.baseClass + '--pinned',
        unpinned:   aiToTop.baseClass + '--unpinned',
        top:        aiToTop.baseClass + '--top',
        notTop:     aiToTop.baseClass + '--not-top',
        bottom:     aiToTop.baseClass + '--bottom',
        notBottom:  aiToTop.baseClass + '--not-bottom'
      },

      // When Headroom says we're pinned and thus going up, record the direction
      // and execute show if other conditions are met.
      onPin:    function() {
        scrollDirection = 'up';
        handleVisibility();
      },
      // When Headroom says we're unpinned and thus going down, record the
      // direction and hide if not already hidden.
      onUnpin:  function() {
        scrollDirection = 'down';
        handleVisibility();
      },
      // When Headroom says we're at the top, i.e. above the 'offset' setting,
      // hide if not already hidden.
      onTop:    function() {
        handleVisibility();
      },
      // When Headroom says we've hit the bottom, set the scroll direction to
      // 'up' and show the container if possible. This automatically shows the
      // container at the bottom, which makes a certain intuitive sense.
      onBottom: function() {
        scrollDirection = 'up';
        handleVisibility();
      }
    });
    headroom.init();
  };
  // Init on load.
  initHeadroom();

  // Re-init on lazy resize and orientation change events.
  $(window).on('lazyResize.aiToTop orientationchange.aiToTop', initHeadroom);
});
});
});
