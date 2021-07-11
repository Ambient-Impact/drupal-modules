// -----------------------------------------------------------------------------
//   Ambient.Impact - UX - Gin component
// -----------------------------------------------------------------------------

// This switches between the Gin light and dark modes based on whether the
// (prefers-color-scheme: dark) media query matches.
//
// @todo Rework this as a CSS-only feature as a merge request to the Gin
//   project.
//
// @see https://www.drupal.org/project/gin/issues/3161904#comment-13946852

AmbientImpact.onGlobals([
  'drupalSettings.gin.darkmode',
  'drupalSettings.gin.darkmode_class',
], function() {
AmbientImpact.on(['mediaQuery'], function(aiMediaQuery, $) {
AmbientImpact.addComponent('gin', function(aiGin, $) {

  'use strict';

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
