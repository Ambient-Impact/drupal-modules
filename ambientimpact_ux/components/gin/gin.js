// -----------------------------------------------------------------------------
//   Ambient.Impact - UX - Gin component
// -----------------------------------------------------------------------------

// This switches between the Gin light and dark modes based on whether the
// (prefers-color-scheme: dark) media query matches.
//
// @see https://www.drupal.org/project/gin/issues/3161904#comment-13946852
//   Gin project issue to implement this functionality in the theme itself.
//
// @todo Remove this once Gin adds this in the theme itself.

AmbientImpact.onGlobals([
  'drupalSettings.gin.darkmode',
  'drupalSettings.gin.darkmode_class',
], function() {
AmbientImpact.on(['mediaQuery'], function(aiMediaQuery, $) {
AmbientImpact.addComponent('gin', function(aiGin, $) {

  'use strict';

  // Bail if the dark mode setting isn't a boolean as that likely means the
  // work-in-progress issue linked above is in use.
  if (typeof drupalSettings.gin.darkmode !== 'boolean') {
    return;
  }

  /**
   * A MediaQueryList that matches when the browser says dark mode is preferred.
   *
   * @type {MediaQueryList}
   */
  var query = aiMediaQuery.getQuery('(prefers-color-scheme: dark)');

  /**
   * Simple event handler to add or remove the Gin dark mode <body> class.
   */
  function eventHandler() {
    if (aiMediaQuery.matches(query)) {
      $('body').addClass(drupalSettings.gin.darkmode_class);
    } else {
      $('body').removeClass(drupalSettings.gin.darkmode_class);
    }
  };

  // Invoke the handler once on load.
  if (
    drupalSettings.gin.darkmode === true &&
    !aiMediaQuery.matches(query)
  ) {
    eventHandler();
  }

  // Attach the handler to the MediaQueryList to toggle the class based on
  // browser changes.
  aiMediaQuery.onMedia(query, eventHandler);

});
});
});
