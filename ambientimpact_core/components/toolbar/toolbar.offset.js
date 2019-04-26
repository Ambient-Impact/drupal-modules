/* -----------------------------------------------------------------------------
   Ambient.Impact - Core - Toolbar offset management component
----------------------------------------------------------------------------- */

AmbientImpact.onGlobals(['Drupal.toolbar.mql'], function() {
AmbientImpact.addComponent('toolbar.offset', function(aiToolbarOffset, $) {
  'use strict';

  var $trays              = $('.toolbar-tray'),
      // offsetsCache        = {top: 0},
      standardMediaQuery  = null;

  // These cannot be wrapped in a behaviour as that will be too late to apply
  // our adjustments on the narrow media query range.
  $(document)
    /**
     * Offset adjustment handler.
     *
     * This makes changes to the offsets in the narrow media query range.
     */
    .on('drupalViewportOffsetChange.aiToolbarOffset', function(
      event, offsets
    ) {
      if (
        standardMediaQuery === null &&
        'toolbar.standard' in Drupal.toolbar.mql &&
        'matches' in Drupal.toolbar.mql['toolbar.standard']
      ) {
        standardMediaQuery = Drupal.toolbar.mql['toolbar.standard'];
      } else {
        standardMediaQuery = {matches: true}
      }

      // If the viewport is not at least wide enough to hit the
      // 'toolbar.standard' breakpoint, any open vertical trays will be
      // positioned absolutely and will not be taking up any horizontal space.
      // For whatever reason, the offsets still read as though they do, so make
      // sure we save zero values. Do the same for the top offset, as the
      // Toolbar is not actually displacing the viewport.
      if (standardMediaQuery.matches === true) {
        return;
      }

      offsets.left  = 0;
      offsets.right = 0;

      if (offsets.top > 0) {
        // Save the existing top offset, since we need it to set the top
        // padding for trays.
        // offsetsCache.top = offsets.top;
        offsets.top = 0;
      }

      // Set top padding for trays. This is necessary as offsets.top will be
      // zero on page load at this breakpoint, resulting in items in the trays
      // being cut off.
      // $trays.css('padding-top', offsetsCache.top);
    });
});
});
