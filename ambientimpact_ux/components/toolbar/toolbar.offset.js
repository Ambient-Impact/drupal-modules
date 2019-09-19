// -----------------------------------------------------------------------------
//   Ambient.Impact - UX - Toolbar offset management component
// -----------------------------------------------------------------------------

// This component's primary purpose is to remove the Drupal toolbar's height
// from the top viewport offset when in the 'toolbar.narrow' media query width.
// This is done because Drupal's displace functionality doesn't currently take
// into account whether an element marked for displacement is actually
// displacing, so it incorrectly counts the toolbar in the 'toolbar.narrow'
// media query despite not being in fixed positioning. Altering the top offset
// allows other components to get an accurate top displacement.

AmbientImpact.onGlobals([
  'drupalSettings.toolbar.breakpoints',
  'Drupal.displace.calculateOffset',
], function() {
AmbientImpact.on(['mediaQuery', 'displace'], function(
  aiMediaQuery, aiDisplace
) {
AmbientImpact.addComponent('toolbar.offset', function(aiToolbarOffset, $) {
  'use strict';

  /**
   * The 'toolbar.standard' MediaQueryList object.
   *
   * @type {MediaQueryList}
   */
  var standardMediaQuery = aiMediaQuery.getQuery(
    drupalSettings.toolbar.breakpoints['toolbar.standard']
  );

  /**
   * The toolbar bar element, wrapped in a jQuery collection.
   *
   * @type {jQuery}
   */
  var $toolbarBar = $();

  /**
   * The 'toolbar.standard' media query change event handler.
   */
  function standardMediaQueryChangeHandler(event) {
    // If narrower than the 'toolbar.standard' media query - i.e. in
    // 'toolbar.narrow' media query - add an explicit zero top offset to the
    // toolbar bar element so that Drupal's displacement counts it as such.
    if (event.matches === false) {
      $toolbarBar.attr('data-offset-top', '0');

    // If matching the 'toolbar.standard' media query, set the top offset to an
    // empty value so that Drupal's displacement once again includes its height
    // in the top offset value.
    } else {
      $toolbarBar.attr('data-offset-top', '');
    }
  };

  /**
   * Toolbar bar viewport offset adjustment event handler.
   */
  function viewportOffsetChangeHandler(event, offsets) {
    if (
      standardMediaQuery.matches === true ||
      offsets.top === 0
    ) {
      return;
    }

    // Recalculate the top offset. This runs with a debounce and thus after
    // standardMediaQueryChangeHandler() has run and altered the toolbar bar's
    // data-offset-top attribute.
    offsets.top = Drupal.displace.calculateOffset('top');
  };

  this.addBehaviour(
    'AmbientImpactToolbarOffset',
    'ambientimpact-toolbar-offset',
    '#toolbar-administration',
    function(context, settings) {
      $toolbarBar = $(this).find('#toolbar-bar');

      aiMediaQuery.onMedia(
        standardMediaQuery,
        standardMediaQueryChangeHandler
      );

      // Run once on attach in case we're already in the 'toolbar.narrow' media
      // query range. Note that we're passing the MediaQueryList object in place
      // of the event object - this works because they both have a 'matches'
      // property and that's the only thing the event handler checks.
      standardMediaQueryChangeHandler(standardMediaQuery);
    },
    function(context, settings, trigger) {
      aiMediaQuery.offMedia(
        standardMediaQuery,
        standardMediaQueryChangeHandler
      );
    }
  );

  // Bind to the viewport offset change event globally. This cannot be wrapped
  // in a behaviour as that will be too late to apply our adjustments on the
  // narrow media query range - we need to bind as early as possible so other
  // handlers will get the updated values.
  $(document).on(
    'drupalViewportOffsetChange.aiToolbarOffset',
    viewportOffsetChangeHandler
  );
});
});
});
